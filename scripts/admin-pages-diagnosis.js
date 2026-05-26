#!/usr/bin/env node
/**
 * Admin Pages Diagnosis Script
 * Diagnoses /admin/dashboard.php and /admin/users.php
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'http://localhost:8000';
const PAGES = [
  { name: 'dashboard', url: '/admin/dashboard.php' },
  { name: 'users', url: '/admin/users.php' }
];

// Ensure output directories exist
const screenshotDir = path.join('.hermes', 'admin-diagnosis-screenshots');
const reportDir = path.join('.hermes', 'admin-diagnosis-reports');
[screenshotDir, reportDir].forEach(dir => {
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
});

async function diagnosePage(page, testPage) {
  console.log(`\n   Testing: ${testPage.url}...`);
  
  try {
    await page.goto(BASE_URL + testPage.url, { waitUntil: 'networkidle' });
  } catch (err) {
    console.log(`   ❌ Navigation failed: ${err.message}`);
    return {
      name: testPage.name,
      url: testPage.url,
      status: 'error',
      error: err.message,
      issues: { navigation: [err.message] }
    };
  }

  const issues = {
    bootstrapClasses: [],
    problematicInlineStyles: [],
    missingLabels: [],
    noButtonClass: [],
    missingH1: [],
    redirects: [],
    authRequired: [],
    pageTitle: '',
    formCount: 0,
    buttonCount: 0
  };

  let status = 'pass';

  // Check if redirected (auth required)
  const currentUrl = page.url();
  if (!currentUrl.includes(testPage.url.replace('.php', ''))) {
    issues.authRequired.push(`Redirected to: ${currentUrl}`);
    status = 'auth-required';
    console.log(`   ⚠️  Auth required - redirected to login`);
  }

  // Get page title
  issues.pageTitle = await page.title();

  // Check for Bootstrap classes
  const bootstrapElements = await page.$$('[class*="btn-primary"], [class*="btn-danger"], [class*="row"], [class*="col-"]');
  if (bootstrapElements.length > 0) {
    issues.bootstrapClasses = [`Found ${bootstrapElements.length} Bootstrap elements`];
    status = 'warn';
  }

  // Check for inline styles
  const inlineStyleElements = await page.$$('[style*="display:"], [style*="width:"], [style*="height:"], [style*="margin:"], [style*="padding:"], [style*="float:"]');
  const problematicStyles = [];
  for (const el of inlineStyleElements) {
    const style = await el.getAttribute('style');
    const text = await el.textContent();
    if (style && !style.includes('var(--')) {
      problematicStyles.push(`${text?.substring(0, 30) || 'element'}: ${style}`);
    }
  }
  if (problematicStyles.length > 0) {
    issues.problematicInlineStyles = problematicStyles.slice(0, 5);
    status = 'warn';
  }

  // Count forms and check labels
  const forms = await page.$$('form');
  issues.formCount = forms.length;

  const inputs = await page.$$('input, textarea, select');
  for (const input of inputs) {
    const id = await input.getAttribute('id');
    const ariaLabel = await input.getAttribute('aria-label');
    
    if (!ariaLabel) {
      if (id) {
        const label = await page.$(`label[for="${id}"]`);
        if (!label) {
          const type = await input.getAttribute('type');
          issues.missingLabels.push(`${type || 'input'} input (id: ${id})`);
          status = 'warn';
        }
      } else {
        issues.missingLabels.push('input without id or aria-label');
        status = 'warn';
      }
    }
  }

  // Check buttons
  const buttons = await page.$$('button');
  issues.buttonCount = buttons.length;
  for (const btn of buttons) {
    const classes = await btn.getAttribute('class');
    if (classes && !classes.includes('btn')) {
      const text = await btn.textContent();
      issues.noButtonClass.push(text?.substring(0, 30) || 'button');
      status = 'warn';
    }
  }

  // Check for h1
  const h1 = await page.$('h1');
  if (!h1) {
    issues.missingH1.push('No h1 heading found');
    status = 'warn';
  }

  // Check responsive
  const responsive = {};
  const viewports = [
    { name: 'mobile', width: 375, height: 812 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'desktop', width: 1920, height: 1080 }
  ];

  for (const vp of viewports) {
    await page.setViewportSize(vp);
    const scrollWidth = await page.evaluate(() => document.documentElement.scrollWidth);
    const clientWidth = await page.evaluate(() => document.documentElement.clientWidth);
    responsive[vp.name] = {
      noHorizontalScroll: scrollWidth <= clientWidth,
      scrollWidth,
      clientWidth
    };
    if (scrollWidth > clientWidth) {
      status = 'warn';
    }
  }

  // Take screenshot
  const timestamp = Date.now();
  const screenshotPath = path.join(screenshotDir, `${testPage.name}-${timestamp}.png`);
  await page.screenshot({ path: screenshotPath });

  console.log(`   Status: ${status === 'pass' ? '✅' : status === 'warn' ? '⚠️ ' : '❌'} ${status}`);

  return {
    name: testPage.name,
    url: testPage.url,
    status,
    timestamp: new Date().toISOString(),
    issues,
    responsive,
    screenshot: screenshotPath
  };
}

async function main() {
  console.log(`
╔════════════════════════════════════════════════════════════════════════════════╗
║                                                                                ║
║              🔍 ADMIN PAGES DIAGNOSIS (Dashboard & Users)                     ║
║                                                                                ║
╚════════════════════════════════════════════════════════════════════════════════╝

   Base URL: ${BASE_URL}
   Pages: ${PAGES.length}
   Auth: May be required - will show redirects

═════════════════════════════════════════════════════════════════════════════════
`);

  const browser = await chromium.launch();
  const page = await browser.newPage();

  const results = [];

  for (let i = 0; i < PAGES.length; i++) {
    process.stdout.write(`   Testing ${i + 1}/${PAGES.length}: ${PAGES[i].name}`);
    const result = await diagnosePage(page, PAGES[i]);
    results.push(result);
  }

  await browser.close();

  // Summary
  console.log(`\n
═════════════════════════════════════════════════════════════════════════════════

📊 DIAGNOSIS RESULTS

   Pages Tested: ${results.length}
   ✅ Passed:    ${results.filter(r => r.status === 'pass').length}
   ⚠️  Warned:   ${results.filter(r => r.status === 'warn').length}
   🔒 Auth:      ${results.filter(r => r.status === 'auth-required').length}
   ❌ Errors:    ${results.filter(r => r.status === 'error').length}
`);

  // Detailed results
  for (const result of results) {
    console.log(`\n   📄 ${result.name} (${result.url})`);
    console.log(`      Status: ${result.status}`);
    
    if (result.status === 'auth-required') {
      console.log(`      ℹ️  Auth Required - Redirected to: ${result.issues.authRequired[0]}`);
      continue;
    }

    if (result.error) {
      console.log(`      ❌ Error: ${result.error}`);
      continue;
    }

    if (result.issues.bootstrapClasses.length > 0) {
      console.log(`      ⚠️  Bootstrap: ${result.issues.bootstrapClasses[0]}`);
    }
    if (result.issues.problematicInlineStyles.length > 0) {
      console.log(`      ⚠️  Inline Styles: ${result.issues.problematicInlineStyles.length} issues`);
      result.issues.problematicInlineStyles.slice(0, 2).forEach(s => {
        console.log(`         - ${s.substring(0, 60)}`);
      });
    }
    if (result.issues.missingLabels.length > 0) {
      console.log(`      ⚠️  Missing Labels: ${result.issues.missingLabels.length} inputs`);
    }
    if (result.issues.noButtonClass.length > 0) {
      console.log(`      ⚠️  No .btn Class: ${result.issues.noButtonClass.length} buttons`);
    }
    if (result.issues.missingH1.length > 0) {
      console.log(`      ⚠️  Missing H1 Heading`);
    }
    
    console.log(`      📐 Forms: ${result.issues.formCount}, Buttons: ${result.issues.buttonCount}`);
    console.log(`      📸 Screenshot: ${result.screenshot}`);
  }

  // Save full report
  const reportPath = path.join(reportDir, `admin-diagnosis-${Date.now()}.json`);
  fs.writeFileSync(reportPath, JSON.stringify({ results, timestamp: new Date().toISOString() }, null, 2));
  console.log(`\n   📋 Full Report: ${reportPath}`);

  console.log(`\n═════════════════════════════════════════════════════════════════════════════════\n`);

  // Exit with status
  const hasErrors = results.some(r => r.status === 'error');
  process.exit(hasErrors ? 1 : 0);
}

main().catch(err => {
  console.error('Fatal error:', err);
  process.exit(1);
});
