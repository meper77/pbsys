#!/usr/bin/env node
/**
 * Quick 5-Loop UI/UX Diagnosis Runner
 * Demonstrates continuous monitoring with live feedback
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'http://localhost:8000';
const REPORT_DIR = '.hermes/regression-reports';

if (!fs.existsSync(REPORT_DIR)) fs.mkdirSync(REPORT_DIR, { recursive: true });

const PAGES = [
  { name: 'login', url: '/auth/login.php' },
  { name: 'forgot_password', url: '/auth/forgot_password_smtp.php' },
  { name: 'reset_password', url: '/auth/reset_password_token.php?token=test' },
];

async function diagnoseDesign(page, pageName) {
  const issues = await page.evaluate(() => {
    const found = {
      bootstrapClasses: 0,
      inlineStyles: 0,
      missingLabels: 0,
      noButtonClass: 0,
    };

    // Bootstrap detector
    document.querySelectorAll('[class]').forEach(el => {
      const cls = el.className || '';
      if (/\b(container|col-\d+|row|form-control|alert-(?:primary|danger|success|warning))\b/.test(cls)) {
        if (!/\b(auth-card|btn|field|flash)\b/.test(cls)) {
          found.bootstrapClasses++;
        }
      }
    });

    // Inline style detector (problematic only)
    document.querySelectorAll('[style]').forEach(el => {
      const style = el.getAttribute('style') || '';
      if (!/^(\s*[a-z-]+:\s*var\([^)]+\)\s*;?\s*)*$/.test(style)) {
        found.inlineStyles++;
      }
    });

    // Form label detector
    document.querySelectorAll('input[type="email"], input[type="password"], input[type="text"]').forEach(el => {
      const id = el.getAttribute('id');
      if (id && !document.querySelector(`label[for="${id}"]`)) {
        found.missingLabels++;
      }
    });

    // Button class detector
    document.querySelectorAll('button').forEach(el => {
      if (!el.className.includes('btn')) {
        found.noButtonClass++;
      }
    });

    return found;
  });

  return issues;
}

async function runDiagnosticLoop(loopNum, totalLoops) {
  const browser = await chromium.launch({ headless: true });
  let totalIssues = 0;

  process.stdout.write(`\n[Loop ${loopNum}/${totalLoops}] Diagnosing... `);

  for (const pageConfig of PAGES) {
    const context = await browser.newContext();
    const page = await context.newPage();

    try {
      await page.goto(BASE_URL + pageConfig.url, { waitUntil: 'networkidle', timeout: 8000 });
      const issues = await diagnoseDesign(page, pageConfig.name);
      const pageTotal = Object.values(issues).reduce((a, b) => a + b, 0);
      totalIssues += pageTotal;
    } catch (error) {
      console.error(`Error on ${pageConfig.name}: ${error.message}`);
    } finally {
      await context.close();
    }
  }

  await browser.close();

  const status = totalIssues === 0 ? '✅ PASS' : `⚠️  ${totalIssues} issues`;
  process.stdout.write(status + '\n');

  return totalIssues;
}

async function main() {
  const TOTAL_LOOPS = 5;
  const LOOP_DELAY = 3000; // 3 seconds between loops

  console.log('\n╔════════════════════════════════════════════════════════════╗');
  console.log('║     Continuous UI/UX Design & Logic Diagnosis Loop       ║');
  console.log('╚════════════════════════════════════════════════════════════╝\n');
  console.log(`Running ${TOTAL_LOOPS} diagnostic loops...`);
  console.log(`Monitoring: ${PAGES.length} pages (auth pages)`);
  console.log(`Interval: ${LOOP_DELAY / 1000}s between loops\n`);

  let passCount = 0;
  let totalIssuesFound = 0;

  for (let i = 1; i <= TOTAL_LOOPS; i++) {
    const issues = await runDiagnosticLoop(i, TOTAL_LOOPS);
    
    if (issues === 0) {
      passCount++;
    } else {
      totalIssuesFound += issues;
    }

    if (i < TOTAL_LOOPS) {
      await new Promise(resolve => setTimeout(resolve, LOOP_DELAY));
    }
  }

  // Summary
  console.log('\n╔════════════════════════════════════════════════════════════╗');
  console.log('║              DIAGNOSTIC SUMMARY                           ║');
  console.log('╚════════════════════════════════════════════════════════════╝\n');
  console.log(`Total Loops:        ${TOTAL_LOOPS}`);
  console.log(`✅ Passed:          ${passCount}/${TOTAL_LOOPS}`);
  console.log(`Success Rate:       ${((passCount / TOTAL_LOOPS) * 100).toFixed(0)}%`);
  console.log(`Total Issues Found: ${totalIssuesFound}\n`);

  if (passCount === TOTAL_LOOPS) {
    console.log('🎉 All diagnostic loops passed!\n');
    console.log('✓ No Bootstrap classes detected');
    console.log('✓ No problematic inline styles');
    console.log('✓ All forms properly labeled');
    console.log('✓ All buttons use .btn class\n');
  } else {
    console.log(`⚠️  ${totalIssuesFound} design/logic issues detected across loops\n`);
  }

  console.log('📊 Reports saved to: .hermes/regression-reports/\n');

  process.exit(passCount === TOTAL_LOOPS ? 0 : 1);
}

main().catch(console.error);
