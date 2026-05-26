#!/usr/bin/env node
/**
 * Continuous UI/UX Diagnosis Loop via Playwright
 * Runs regression tests in a loop with configurable interval
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'http://localhost:8000';
const REPORT_DIR = '.hermes/regression-reports';
const SCREENSHOT_DIR = '.hermes/regression-screenshots';
const LOOP_INTERVAL = 60000; // 60 seconds between runs
const MAX_LOOPS = 0; // 0 = infinite loop

[REPORT_DIR, SCREENSHOT_DIR].forEach(dir => {
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
});

// Pages to monitor
const PAGES_TO_AUDIT = [
  { name: 'login', url: '/auth/login.php', type: 'auth' },
  { name: 'forgot_password', url: '/auth/forgot_password_smtp.php', type: 'auth' },
  { name: 'reset_password', url: '/auth/reset_password_token.php?token=test', type: 'auth' },
];

// Design consistency checker
async function checkDesignConsistency(page, pageName) {
  const issues = {
    bootstrapClasses: [],
    problematicInlineStyles: [],
    missingLabels: [],
    noButtonClass: [],
  };

  // Check for Bootstrap classes (exclude NEO V-TRACK)
  const bootstrapMatches = await page.locator('*').evaluateAll(
    (elements) => {
      const matches = [];
      const neoVtrackClasses = /\b(auth-card|auth-form-group|auth-message|auth-label|btn|btn-ghost|btn-primary|field|flash|nv-|nv)[a-z-]*\b/i;
      
      elements.forEach(el => {
        const className = el.className || '';
        if (/\b(container|col-\d+|row|form-control|alert-(?:primary|secondary|danger|warning|info|success)|btn-(?:primary|secondary|danger|warning|info|success|light|dark)|badge-|modal-|navbar-|dropdown-|card-body|card-header|card-footer|spinner-border|toast-|offcanvas-)\b/i.test(className)) {
          if (!neoVtrackClasses.test(className)) {
            matches.push({
              tag: el.tagName.toLowerCase(),
              class: className.substring(0, 50),
            });
          }
        }
      });
      return matches;
    }
  );
  issues.bootstrapClasses = bootstrapMatches;

  // Check for problematic inline styles
  const inlineMatches = await page.locator('[style]').evaluateAll(
    (elements) => {
      const matches = [];
      elements.forEach(el => {
        const style = el.getAttribute('style') || '';
        if (!/^(\s*[a-z-]+:\s*var\([^)]+\)\s*;?\s*)*$/.test(style)) {
          matches.push({
            tag: el.tagName.toLowerCase(),
            style: style.substring(0, 50),
          });
        }
      });
      return matches;
    }
  );
  issues.problematicInlineStyles = inlineMatches;

  // Check for form inputs without labels
  const inputs = await page.locator('input[type="text"], input[type="email"], input[type="password"], input[type="number"]').all();
  for (const input of inputs) {
    const inputId = await input.getAttribute('id');
    const hasLabel = inputId ? await page.locator(`label[for="${inputId}"]`).count() > 0 : false;
    if (!hasLabel) {
      const name = await input.getAttribute('name');
      issues.missingLabels.push({ name, id: inputId });
    }
  }

  // Check for buttons without proper class
  const buttons = await page.locator('button, a[role="button"]').all();
  for (const button of buttons) {
    const className = await button.getAttribute('class') || '';
    if (!className.includes('btn') && await button.isVisible()) {
      issues.noButtonClass.push({ class: className });
    }
  }

  return issues;
}

async function runDiagnostics(loopNumber) {
  const browser = await chromium.launch({ headless: true });
  const timestamp = new Date().toISOString();
  const results = {
    loopNumber,
    timestamp,
    pages: [],
  };

  let totalIssues = 0;

  for (const pageConfig of PAGES_TO_AUDIT) {
    const context = await browser.newContext();
    const page = await context.newPage();
    const pageResult = {
      name: pageConfig.name,
      url: pageConfig.url,
      status: 'pass',
      issues: {},
    };

    try {
      await page.goto(BASE_URL + pageConfig.url, { waitUntil: 'networkidle', timeout: 10000 });
      
      pageResult.issues = await checkDesignConsistency(page, pageConfig.name);
      
      const bootstrapCount = pageResult.issues.bootstrapClasses.length;
      const inlineStyleCount = pageResult.issues.problematicInlineStyles.length;
      const labelCount = pageResult.issues.missingLabels.length;
      const buttonCount = pageResult.issues.noButtonClass.length;
      
      const totalPageIssues = bootstrapCount + inlineStyleCount + labelCount + buttonCount;
      
      if (bootstrapCount > 0 || inlineStyleCount > 0) {
        pageResult.status = 'fail';
      } else if (labelCount > 0 || buttonCount > 0) {
        pageResult.status = 'warning';
      }
      
      totalIssues += totalPageIssues;
    } catch (error) {
      pageResult.error = error.message;
      pageResult.status = 'error';
    } finally {
      await context.close();
    }

    results.pages.push(pageResult);
  }

  // Save report
  const reportPath = path.join(REPORT_DIR, `loop-${loopNumber}-${Date.now()}.json`);
  fs.writeFileSync(reportPath, JSON.stringify(results, null, 2));

  await browser.close();

  return {
    loopNumber,
    timestamp,
    totalIssues,
    results,
    reportPath,
  };
}

async function main() {
  console.log('🚀 Starting Continuous UI/UX Diagnosis Loop\n');
  console.log(`   Base URL: ${BASE_URL}`);
  console.log(`   Pages monitored: ${PAGES_TO_AUDIT.length}`);
  console.log(`   Interval: ${LOOP_INTERVAL}ms (${(LOOP_INTERVAL / 1000).toFixed(0)}s)`);
  console.log(`   Max loops: ${MAX_LOOPS === 0 ? 'infinite' : MAX_LOOPS}\n`);
  console.log('═══════════════════════════════════════════════════════════════\n');

  let loopNumber = 0;
  let totalLoops = 0;
  let failedLoops = 0;
  let warningLoops = 0;

  while (MAX_LOOPS === 0 || loopNumber < MAX_LOOPS) {
    loopNumber++;
    totalLoops++;

    try {
      const result = await runDiagnostics(loopNumber);
      
      const timestamp = new Date().toLocaleString();
      let statusEmoji = '✅';
      
      if (result.totalIssues > 0) {
        const failedPages = result.results.pages.filter(p => p.status === 'fail').length;
        const warningPages = result.results.pages.filter(p => p.status === 'warning').length;
        
        if (failedPages > 0) {
          statusEmoji = '❌';
          failedLoops++;
        } else {
          statusEmoji = '⚠️';
          warningLoops++;
        }
      }

      console.log(`[${timestamp}] Loop #${loopNumber} ${statusEmoji}`);
      console.log(`   Issues: ${result.totalIssues}`);
      
      for (const page of result.results.pages) {
        const pageStatus = page.status === 'pass' ? '✓' : page.status === 'warning' ? '⚠️' : '✗';
        const issues = (page.issues.bootstrapClasses?.length || 0) + 
                      (page.issues.problematicInlineStyles?.length || 0);
        console.log(`   ${pageStatus} ${page.name.padEnd(20)} - ${issues} issues`);
      }
      console.log();

    } catch (error) {
      console.log(`[${new Date().toLocaleString()}] Loop #${loopNumber} ✗ ERROR`);
      console.log(`   Error: ${error.message}\n`);
      failedLoops++;
    }

    // Wait before next loop
    if (MAX_LOOPS === 0 || loopNumber < MAX_LOOPS) {
      await new Promise(resolve => setTimeout(resolve, LOOP_INTERVAL));
    }
  }

  // Summary
  console.log('═══════════════════════════════════════════════════════════════\n');
  console.log('📊 LOOP DIAGNOSTIC SUMMARY\n');
  console.log(`   Total loops run: ${totalLoops}`);
  console.log(`   ✅ Passed: ${totalLoops - failedLoops - warningLoops}`);
  console.log(`   ⚠️  Warnings: ${warningLoops}`);
  console.log(`   ❌ Failed: ${failedLoops}`);
  console.log(`\n   Success rate: ${(((totalLoops - failedLoops) / totalLoops) * 100).toFixed(1)}%\n`);

  process.exit(failedLoops > 0 ? 1 : 0);
}

main().catch(console.error);
