#!/usr/bin/env node
/**
 * System-Wide UI/UX Diagnosis - Fast Demo (Public Pages Only)
 * Quick validation before full system test
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'http://localhost:8000';
const REPORT_DIR = '.hermes/regression-reports';
const SCREENSHOT_DIR = '.hermes/regression-screenshots';

[REPORT_DIR, SCREENSHOT_DIR].forEach(dir => {
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
});

// Fast demo: public pages only (no auth required)
const DEMO_PAGES = [
  { name: 'login', url: '/auth/login.php', module: 'auth' },
  { name: 'register', url: '/auth/register.php', module: 'auth' },
  { name: 'forgot_password', url: '/auth/forgot_password_smtp.php', module: 'auth' },
  { name: 'reset_password', url: '/auth/reset_password_token.php?token=test', module: 'auth' },
];

async function diagnoseDesign(page, pageName) {
  const issues = await page.evaluate(() => {
    const found = {
      bootstrapClasses: 0,
      inlineStyles: 0,
      missingLabels: 0,
      noButtonClass: 0,
      missingHeadings: 0,
    };

    // Bootstrap detector
    document.querySelectorAll('[class]').forEach(el => {
      const cls = el.className || '';
      if (/\b(container|col-\d+|row|form-control|alert-(?:primary|danger|success|warning))\b/.test(cls)) {
        if (!/\b(auth-card|btn|field|flash|nv-)\b/.test(cls)) {
          found.bootstrapClasses++;
        }
      }
    });

    // Inline style detector
    document.querySelectorAll('[style]').forEach(el => {
      const style = el.getAttribute('style') || '';
      if (!/^(\s*[a-z-]+:\s*var\([^)]+\)\s*;?\s*)*$/.test(style) && style.trim().length > 0) {
        found.inlineStyles++;
      }
    });

    // Form label detector
    document.querySelectorAll('input[type="email"], input[type="password"], input[type="text"], textarea, select').forEach(el => {
      const id = el.getAttribute('id');
      const ariaLabel = el.getAttribute('aria-label');
      if (id && !document.querySelector(`label[for="${id}"]`) && !ariaLabel) {
        found.missingLabels++;
      }
    });

    // Button class detector
    document.querySelectorAll('button').forEach(el => {
      if (!el.className.includes('btn')) {
        found.noButtonClass++;
      }
    });

    // Check for headings
    if (document.querySelectorAll('h1').length === 0) {
      found.missingHeadings = 1;
    }

    return found;
  });

  return issues;
}

async function runDemoLoop() {
  const browser = await chromium.launch({ headless: true });
  const results = [];
  let totalIssues = 0;

  console.log('\n╔════════════════════════════════════════════════════════════════════════════════╗');
  console.log('║                                                                                ║');
  console.log('║              🚀 SYSTEM-WIDE DIAGNOSIS - FAST DEMO (Public Pages)             ║');
  console.log('║                                                                                ║');
  console.log('╚════════════════════════════════════════════════════════════════════════════════╝\n');

  console.log(`   Base URL: ${BASE_URL}`);
  console.log(`   Pages: ${DEMO_PAGES.length} (auth module only)`);
  console.log(`   Expected: ~15-20 seconds\n`);
  console.log('═════════════════════════════════════════════════════════════════════════════════\n');

  for (let i = 0; i < DEMO_PAGES.length; i++) {
    const pageConfig = DEMO_PAGES[i];
    process.stdout.write(`   Diagnosing ${i + 1}/${DEMO_PAGES.length}: ${pageConfig.name.padEnd(20)} `);

    const context = await browser.newContext();
    const page = await context.newPage();

    try {
      const startTime = Date.now();
      await page.goto(BASE_URL + pageConfig.url, { waitUntil: 'networkidle', timeout: 10000 });
      const loadTime = Date.now() - startTime;

      const issues = await diagnoseDesign(page, pageConfig.name);
      const pageTotal = Object.values(issues).reduce((a, b) => a + b, 0);
      totalIssues += pageTotal;

      // Take screenshot
      const screenshotPath = path.join(SCREENSHOT_DIR, `demo-${pageConfig.module}-${pageConfig.name}.png`);
      await page.screenshot({ path: screenshotPath });

      const status = pageTotal === 0 ? '✅' : `⚠️ (${pageTotal})`;
      console.log(`${status} [${loadTime}ms]`);

      results.push({
        name: pageConfig.name,
        url: pageConfig.url,
        module: pageConfig.module,
        loadTime,
        issues,
        totalIssues: pageTotal,
        status: pageTotal === 0 ? 'pass' : 'warning',
      });

    } catch (error) {
      console.log(`❌ ERROR: ${error.message.substring(0, 50)}`);
      results.push({
        name: pageConfig.name,
        url: pageConfig.url,
        module: pageConfig.module,
        error: error.message,
        status: 'error',
      });
    } finally {
      await context.close();
    }
  }

  await browser.close();

  // Summary
  console.log('\n═════════════════════════════════════════════════════════════════════════════════\n');
  console.log('📊 DEMO RESULTS\n');

  const passed = results.filter(r => r.status === 'pass').length;
  const warned = results.filter(r => r.status === 'warning').length;
  const errored = results.filter(r => r.status === 'error').length;

  console.log(`   Pages Tested: ${results.length}`);
  console.log(`   ✅ Passed:    ${passed}`);
  console.log(`   ⚠️  Warned:   ${warned}`);
  console.log(`   ❌ Errors:    ${errored}`);
  console.log(`   Total Issues: ${totalIssues}\n`);

  if (totalIssues === 0 && errored === 0) {
    console.log('   🎉 All demo pages pass design & logic checks!\n');
    console.log('   ✓ No Bootstrap classes');
    console.log('   ✓ No problematic inline styles');
    console.log('   ✓ All forms properly labeled');
    console.log('   ✓ All buttons use .btn class');
    console.log('   ✓ All pages have headings\n');
  } else if (totalIssues > 0) {
    console.log(`   ⚠️  ${totalIssues} design/logic issues detected:\n`);
    results.forEach(r => {
      if (r.totalIssues > 0) {
        console.log(`   ${r.name}:`);
        if (r.issues.bootstrapClasses > 0) console.log(`      • Bootstrap classes: ${r.issues.bootstrapClasses}`);
        if (r.issues.inlineStyles > 0) console.log(`      • Inline styles: ${r.issues.inlineStyles}`);
        if (r.issues.missingLabels > 0) console.log(`      • Missing labels: ${r.issues.missingLabels}`);
        if (r.issues.noButtonClass > 0) console.log(`      • Buttons without .btn: ${r.issues.noButtonClass}`);
        if (r.issues.missingHeadings > 0) console.log(`      • Missing h1 heading: yes`);
      }
    });
    console.log();
  }

  console.log(`   Screenshots: .hermes/regression-screenshots/demo-*.png`);
  console.log(`   Report: .hermes/regression-reports/\n`);

  // Save report
  const reportPath = path.join(REPORT_DIR, `demo-${Date.now()}.json`);
  fs.writeFileSync(reportPath, JSON.stringify({
    type: 'demo',
    timestamp: new Date().toISOString(),
    pagesCount: results.length,
    passed,
    warned,
    errored,
    totalIssues,
    results,
  }, null, 2));

  process.exit(totalIssues === 0 && errored === 0 ? 0 : 1);
}

runDemoLoop().catch(console.error);
