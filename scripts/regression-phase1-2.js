#!/usr/bin/env node
/**
 * Comprehensive UI/UX Regression Test Suite - Phase 1-2 (Fixed)
 * Visual Audit + Form Logic Testing via Playwright
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

// Configuration
const BASE_URL = 'http://localhost:8000';
const REPORT_DIR = '.hermes/regression-reports';
const SCREENSHOT_DIR = '.hermes/regression-screenshots';

// Ensure directories exist
[REPORT_DIR, SCREENSHOT_DIR].forEach(dir => {
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
});

// Page configurations
const PAGES_TO_AUDIT = [
  // Auth pages
  { name: 'login', url: '/auth/login.php', type: 'auth' },
  { name: 'forgot_password', url: '/auth/forgot_password_smtp.php', type: 'auth' },
  { name: 'reset_password', url: '/auth/reset_password_token.php?token=test', type: 'auth' },
];

// Bootstrap classes to detect (NEO V-TRACK classes are NOT flagged)
const BOOTSTRAP_PATTERN = /\b(container|col-\d+|row|form-control|alert-(?:primary|secondary|danger|warning|info|success)|btn-(?:primary|secondary|danger|warning|info|success|light|dark)|badge-|modal-|navbar-|dropdown-|card-body|card-header|card-footer|spinner-border|toast-|offcanvas-)\b/gi;

// Inline styles to flag (excluding CSS variables)
const INLINE_STYLE_PATTERN = /style\s*=\s*["']([^"']*(?:(?!var\()[^"']*)*(?:(?:justify-content|width|margin|padding|font-weight|font-size|color|background)[^"']*)*[^"']*)?["']/gi;

async function checkDesignConsistency(page, pageName) {
  const issues = {
    bootstrapClasses: [],
    problematicInlineStyles: [],
    missingLabels: [],
    noButtonClass: [],
  };

  // Check for Bootstrap classes (exclude NEO V-TRACK classes)
  const bootstrapMatches = await page.locator('*').evaluateAll(
    (elements) => {
      const matches = [];
      const neoVtrackClasses = /\b(auth-card|auth-form-group|auth-message|auth-label|btn|btn-ghost|btn-primary|field|flash|nv-|nv)[a-z-]*\b/i;
      
      elements.forEach(el => {
        const className = el.className || '';
        // Check for Bootstrap classes NOT in NEO V-TRACK namespace
        if (/\b(container|col-\d+|row|form-control|alert-(?:primary|secondary|danger|warning|info|success)|btn-(?:primary|secondary|danger|warning|info|success|light|dark)|badge-|modal-|navbar-|dropdown-|card-body|card-header|card-footer|spinner-border|toast-|offcanvas-)\b/i.test(className)) {
          // Exclude if it's a NEO class
          if (!neoVtrackClasses.test(className)) {
            matches.push({
              tag: el.tagName.toLowerCase(),
              class: className,
              text: el.textContent?.substring(0, 50),
            });
          }
        }
      });
      return matches;
    }
  );
  issues.bootstrapClasses = bootstrapMatches;

  // Check for problematic inline styles (ignore CSS variables)
  const inlineMatches = await page.locator('[style]').evaluateAll(
    (elements) => {
      const matches = [];
      elements.forEach(el => {
        const style = el.getAttribute('style') || '';
        // Ignore if style ONLY contains CSS variables and whitespace
        if (!/^(\s*[a-z-]+:\s*var\([^)]+\)\s*;?\s*)*$/.test(style)) {
          matches.push({
            tag: el.tagName.toLowerCase(),
            style: style.substring(0, 100),
            text: el.textContent?.substring(0, 50),
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
      const type = await input.getAttribute('type');
      const name = await input.getAttribute('name');
      issues.missingLabels.push({ type, name, id: inputId });
    }
  }

  // Check for buttons without proper class
  const buttons = await page.locator('button, a[role="button"]').all();
  for (const button of buttons) {
    const className = await button.getAttribute('class') || '';
    if (!className.includes('btn') && await button.isVisible()) {
      const text = await button.textContent();
      issues.noButtonClass.push({ text: text?.substring(0, 50), class: className });
    }
  }

  return issues;
}

async function main() {
  console.log('🚀 Starting Regression Test Suite - Phase 1 (Visual Audit)\n');

  const browser = await chromium.launch({ headless: true });
  const results = {
    timestamp: new Date().toISOString(),
    summary: {
      totalPages: PAGES_TO_AUDIT.length,
      pagesAudited: 0,
      issuesFound: 0,
      criticalIssues: 0,
    },
    pages: [],
  };

  // Phase 1: Visual Audit
  console.log('📊 PHASE 1: VISUAL AUDIT\n');
  for (const pageConfig of PAGES_TO_AUDIT) {
    console.log(`   Auditing: ${pageConfig.name}`);
    
    const context = await browser.newContext();
    const page = await context.newPage();
    const pageResult = {
      name: pageConfig.name,
      url: pageConfig.url,
      type: pageConfig.type,
      issues: {},
      screenshot: null,
      status: 'pass',
    };

    try {
      await page.goto(BASE_URL + pageConfig.url, { waitUntil: 'networkidle', timeout: 10000 });
      
      // Check design consistency
      pageResult.issues = await checkDesignConsistency(page, pageConfig.name);
      
      // Take screenshot
      const screenshotPath = path.join(SCREENSHOT_DIR, `audit-${pageConfig.name}.png`);
      await page.screenshot({ path: screenshotPath, fullPage: true });
      pageResult.screenshot = screenshotPath;
      
      // Count issues
      const bootstrapCount = pageResult.issues.bootstrapClasses.length;
      const inlineStyleCount = pageResult.issues.problematicInlineStyles.length;
      const labelCount = pageResult.issues.missingLabels.length;
      const buttonCount = pageResult.issues.noButtonClass.length;
      
      const totalIssues = bootstrapCount + inlineStyleCount + labelCount + buttonCount;
      
      if (bootstrapCount > 0 || inlineStyleCount > 0) {
        pageResult.status = 'fail';
        results.summary.criticalIssues += bootstrapCount + inlineStyleCount;
      }
      
      if (totalIssues === 0) {
        console.log(`   ✓ No design issues found`);
      } else {
        console.log(`   ⚠️  ${totalIssues} issues found (${bootstrapCount} Bootstrap, ${inlineStyleCount} inline styles)`);
      }
      
      results.pages.push(pageResult);
      results.summary.pagesAudited++;
      results.summary.issuesFound += totalIssues;
    } catch (error) {
      console.log(`   ✗ Error: ${error.message}`);
      pageResult.error = error.message;
      pageResult.status = 'error';
      results.pages.push(pageResult);
    } finally {
      await context.close();
    }
  }

  // Generate summary
  console.log('\n📈 GENERATING SUMMARY REPORT...\n');

  // Save detailed JSON report
  const reportPath = path.join(REPORT_DIR, `regression-phase1-${Date.now()}.json`);
  fs.writeFileSync(reportPath, JSON.stringify(results, null, 2));
  console.log(`✅ Detailed report: ${reportPath}`);

  // Print summary
  console.log('\n╔════════════════════════════════════════════════════════════╗');
  console.log('║           REGRESSION TEST SUMMARY - PHASE 1               ║');
  console.log('╚════════════════════════════════════════════════════════════╝\n');

  console.log(`Pages Audited:          ${results.summary.pagesAudited}/${results.summary.totalPages}`);
  console.log(`Total Issues Found:     ${results.summary.issuesFound}`);
  console.log(`Critical Issues:        ${results.summary.criticalIssues}\n`);

  // Detailed breakdown
  let totalBootstrap = 0;
  let totalInlineStyles = 0;
  let totalMissingLabels = 0;
  let totalNoButtonClass = 0;

  for (const page of results.pages) {
    if (page.issues) {
      totalBootstrap += page.issues.bootstrapClasses?.length || 0;
      totalInlineStyles += page.issues.problematicInlineStyles?.length || 0;
      totalMissingLabels += page.issues.missingLabels?.length || 0;
      totalNoButtonClass += page.issues.noButtonClass?.length || 0;
    }
  }

  console.log('Design Issues Breakdown:');
  console.log(`  • Bootstrap classes:  ${totalBootstrap}`);
  console.log(`  • Inline styles:      ${totalInlineStyles}`);
  console.log(`  • Missing labels:     ${totalMissingLabels}`);
  console.log(`  • Buttons w/o class:  ${totalNoButtonClass}\n`);

  // Page-by-page results
  console.log('Page Results:');
  for (const page of results.pages) {
    const status = page.status === 'pass' ? '✓' : page.status === 'error' ? '✗' : '⚠️';
    const issues = page.issues ? 
      (page.issues.bootstrapClasses.length + page.issues.problematicInlineStyles.length) : 
      0;
    console.log(`  ${status} ${page.name.padEnd(20)} - ${issues} issues`);
  }

  // Status
  const status = totalBootstrap === 0 && totalInlineStyles === 0 ? '✅ PASS' : '⚠️  ISSUES FOUND';
  console.log(`\nOverall Status: ${status}\n`);

  await browser.close();
  process.exit(status === '✅ PASS' ? 0 : 1);
}

main().catch(console.error);
