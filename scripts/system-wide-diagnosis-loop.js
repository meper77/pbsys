#!/usr/bin/env node
/**
 * Comprehensive System-Wide UI/UX Diagnosis Loop
 * Full pbsys application coverage via Playwright
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'http://localhost:8000';
const REPORT_DIR = '.hermes/regression-reports';
const SCREENSHOT_DIR = '.hermes/regression-screenshots';
const LOOP_INTERVAL = 120000; // 2 minutes between full system checks
const MAX_LOOPS = 0; // 0 = infinite loop

[REPORT_DIR, SCREENSHOT_DIR].forEach(dir => {
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
});

// Comprehensive page inventory across all modules
const SYSTEM_PAGES = [
  // Auth Module
  { name: 'login', url: '/auth/login.php', module: 'auth', requiresAuth: false },
  { name: 'register', url: '/auth/register.php', module: 'auth', requiresAuth: false },
  { name: 'forgot_password', url: '/auth/forgot_password_smtp.php', module: 'auth', requiresAuth: false },
  { name: 'reset_password', url: '/auth/reset_password_token.php?token=test', module: 'auth', requiresAuth: false },
  
  // Admin Module
  { name: 'admin_dashboard', url: '/admin/dashboard.php', module: 'admin', requiresAuth: true, role: 'admin' },
  { name: 'admin_users_list', url: '/admin/users/list.php', module: 'admin', requiresAuth: true, role: 'admin' },
  { name: 'admin_users_add', url: '/admin/users/add.php', module: 'admin', requiresAuth: true, role: 'admin' },
  { name: 'admin_admins_list', url: '/admin/admins/list.php', module: 'admin', requiresAuth: true, role: 'admin' },
  { name: 'admin_admins_add', url: '/admin/admins/add.php', module: 'admin', requiresAuth: true, role: 'admin' },
  { name: 'admin_bulk_import', url: '/admin/bulk_import.php', module: 'admin', requiresAuth: true, role: 'admin' },
  { name: 'admin_reports', url: '/admin/reports.php', module: 'admin', requiresAuth: true, role: 'admin' },
  
  // Visitor Vehicles Module
  { name: 'visitor_list', url: '/vehicles/visitor/list.php', module: 'vehicles', requiresAuth: true },
  { name: 'visitor_add', url: '/vehicles/visitor/add.php', module: 'vehicles', requiresAuth: true },
  
  // Staff Vehicles Module
  { name: 'staff_list', url: '/vehicles/staff/list.php', module: 'vehicles', requiresAuth: true },
  { name: 'staff_add', url: '/vehicles/staff/add.php', module: 'vehicles', requiresAuth: true },
  
  // Student Vehicles Module
  { name: 'student_list', url: '/vehicles/student/list.php', module: 'vehicles', requiresAuth: true },
  { name: 'student_add', url: '/vehicles/student/add.php', module: 'vehicles', requiresAuth: true },
  
  // Contractor Vehicles Module
  { name: 'contractor_list', url: '/vehicles/contractor/list.php', module: 'vehicles', requiresAuth: true },
  { name: 'contractor_add', url: '/vehicles/contractor/add.php', module: 'vehicles', requiresAuth: true },
  
  // Search Module
  { name: 'search_admin', url: '/search/admin_search.php', module: 'search', requiresAuth: true, role: 'admin' },
  { name: 'search_user', url: '/search/user_search.php', module: 'search', requiresAuth: true },
];

// Design consistency checker
async function checkDesignConsistency(page, pageName) {
  const issues = {
    bootstrapClasses: [],
    problematicInlineStyles: [],
    missingLabels: [],
    noButtonClass: [],
    missingAria: [],
    colorContrast: [],
    pageTitle: null,
    formCount: 0,
    buttonCount: 0,
  };

  try {
    // Get page title
    issues.pageTitle = await page.title();

    // Check for Bootstrap classes
    const bootstrapMatches = await page.locator('*').evaluateAll(
      (elements) => {
        const matches = [];
        const neoVtrackClasses = /\b(auth-card|auth-form-group|auth-message|auth-label|btn|btn-ghost|btn-primary|field|flash|nv-|container-|form-|card-|grid-|row-|col-|header|footer|sidebar|nav-|main-content)\b/i;
        
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
          // Allow CSS variables only
          if (!/^(\s*[a-z-]+:\s*var\([^)]+\)\s*;?\s*)*$/.test(style) && style.trim().length > 0) {
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
    const inputs = await page.locator('input[type="text"], input[type="email"], input[type="password"], input[type="number"], select, textarea').all();
    issues.formCount = inputs.length;
    
    for (const input of inputs) {
      const inputId = await input.getAttribute('id');
      const ariaLabel = await input.getAttribute('aria-label');
      const hasLabel = inputId ? await page.locator(`label[for="${inputId}"]`).count() > 0 : false;
      if (!hasLabel && !ariaLabel) {
        const name = await input.getAttribute('name');
        issues.missingLabels.push({ name, id: inputId });
      }
    }

    // Check for buttons without proper class
    const buttons = await page.locator('button, a[role="button"]').all();
    issues.buttonCount = buttons.length;
    
    for (const button of buttons) {
      const className = await button.getAttribute('class') || '';
      const isVisible = await button.isVisible();
      if (!className.includes('btn') && isVisible && className.length > 0) {
        issues.noButtonClass.push({ class: className });
      }
    }

    // Check for ARIA labels
    const ariaIssues = await page.evaluate(() => {
      const issues = [];
      // Check headings
      if (document.querySelectorAll('h1').length === 0) {
        issues.push('Missing h1 heading');
      }
      // Check images
      document.querySelectorAll('img:not([alt=""])').forEach(img => {
        if (!img.hasAttribute('alt') || img.getAttribute('alt') === '') {
          issues.push(`Image missing alt: ${img.src?.substring(0, 30)}`);
        }
      });
      return issues;
    });
    issues.missingAria = ariaIssues;

  } catch (error) {
    issues.error = error.message;
  }

  return issues;
}

async function runFullSystemDiagnostics(loopNumber) {
  const browser = await chromium.launch({ headless: true });
  const timestamp = new Date().toISOString();
  const results = {
    loopNumber,
    timestamp,
    totalPages: SYSTEM_PAGES.length,
    modules: {},
    summary: {
      totalIssues: 0,
      pagesPassed: 0,
      pagesFailed: 0,
      pagesWarning: 0,
      pagesError: 0,
    },
  };

  for (const pageConfig of SYSTEM_PAGES) {
    const context = await browser.newContext();
    const page = await context.newPage();
    
    const pageResult = {
      name: pageConfig.name,
      url: pageConfig.url,
      module: pageConfig.module,
      status: 'pass',
      issues: {},
      responsive: {},
      performance: {},
    };

    try {
      // Navigate to page
      const startTime = Date.now();
      await page.goto(BASE_URL + pageConfig.url, { waitUntil: 'networkidle', timeout: 10000 });
      const loadTime = Date.now() - startTime;
      pageResult.performance.loadTime = loadTime;

      // Run design consistency checks
      pageResult.issues = await checkDesignConsistency(page, pageConfig.name);
      
      // Check responsive design (mobile viewport)
      await page.setViewportSize({ width: 375, height: 812 });
      const mobileNoScroll = await page.evaluate(() => {
        return document.documentElement.scrollWidth <= window.innerWidth + 1;
      });
      pageResult.responsive.mobile = { noHorizontalScroll: mobileNoScroll };

      // Take screenshot for this page
      const screenshotPath = path.join(SCREENSHOT_DIR, `${pageConfig.module}-${pageConfig.name}-${loopNumber}.png`);
      await page.screenshot({ path: screenshotPath });

      // Calculate issue count
      const bootstrapCount = pageResult.issues.bootstrapClasses?.length || 0;
      const inlineStyleCount = pageResult.issues.problematicInlineStyles?.length || 0;
      const labelCount = pageResult.issues.missingLabels?.length || 0;
      const buttonCount = pageResult.issues.noButtonClass?.length || 0;
      const ariaCount = pageResult.issues.missingAria?.length || 0;
      
      const totalPageIssues = bootstrapCount + inlineStyleCount + labelCount + buttonCount + ariaCount;
      results.summary.totalIssues += totalPageIssues;

      if (bootstrapCount > 0 || inlineStyleCount > 0) {
        pageResult.status = 'fail';
        results.summary.pagesFailed++;
      } else if (labelCount > 0 || buttonCount > 0 || ariaCount > 0) {
        pageResult.status = 'warning';
        results.summary.pagesWarning++;
      } else {
        pageResult.status = 'pass';
        results.summary.pagesPassed++;
      }

    } catch (error) {
      pageResult.error = error.message;
      pageResult.status = 'error';
      results.summary.pagesError++;
    } finally {
      await context.close();
    }

    // Organize results by module
    if (!results.modules[pageConfig.module]) {
      results.modules[pageConfig.module] = [];
    }
    results.modules[pageConfig.module].push(pageResult);
  }

  await browser.close();

  // Save report
  const reportPath = path.join(REPORT_DIR, `system-wide-${loopNumber}-${Date.now()}.json`);
  fs.writeFileSync(reportPath, JSON.stringify(results, null, 2));

  return {
    loopNumber,
    timestamp,
    results,
    reportPath,
  };
}

async function main() {
  console.log('\n╔══════════════════════════════════════════════════════════════════════════════╗');
  console.log('║                                                                              ║');
  console.log('║           🚀 COMPREHENSIVE SYSTEM-WIDE UI/UX DIAGNOSIS LOOP                  ║');
  console.log('║                                                                              ║');
  console.log('╚══════════════════════════════════════════════════════════════════════════════╝\n');

  console.log(`   Base URL: ${BASE_URL}`);
  console.log(`   Pages monitored: ${SYSTEM_PAGES.length}`);
  console.log(`   Modules covered: ${new Set(SYSTEM_PAGES.map(p => p.module)).size}`);
  console.log(`   Interval: ${LOOP_INTERVAL / 1000}s between full system checks`);
  console.log(`   Max loops: ${MAX_LOOPS === 0 ? 'infinite' : MAX_LOOPS}\n`);
  console.log('   Modules:');
  const modules = new Set(SYSTEM_PAGES.map(p => p.module));
  modules.forEach(m => {
    const count = SYSTEM_PAGES.filter(p => p.module === m).length;
    console.log(`      • ${m.toUpperCase()}: ${count} pages`);
  });
  console.log('\n═══════════════════════════════════════════════════════════════════════════════════\n');

  let loopNumber = 0;
  let totalLoops = 0;
  let passedLoops = 0;
  let failedLoops = 0;

  while (MAX_LOOPS === 0 || loopNumber < MAX_LOOPS) {
    loopNumber++;
    totalLoops++;
    const loopStartTime = Date.now();

    try {
      const result = await runFullSystemDiagnostics(loopNumber);
      const loopDuration = ((Date.now() - loopStartTime) / 1000).toFixed(1);
      
      const summary = result.results.summary;
      let statusEmoji = '✅';
      
      if (summary.pagesFailed > 0) {
        statusEmoji = '❌';
        failedLoops++;
      } else if (summary.pagesWarning > 0 || summary.pagesError > 0) {
        statusEmoji = '⚠️';
      } else {
        passedLoops++;
      }

      const timestamp = new Date().toLocaleString();
      console.log(`[${timestamp}] Loop #${loopNumber} ${statusEmoji} (${loopDuration}s)\n`);
      
      console.log(`   Summary:`);
      console.log(`      Pages Tested:    ${summary.totalPages || SYSTEM_PAGES.length}`);
      console.log(`      ✅ Passed:       ${summary.pagesPassed}`);
      console.log(`      ⚠️  Warning:     ${summary.pagesWarning}`);
      console.log(`      ❌ Failed:       ${summary.pagesFailed}`);
      console.log(`      🔥 Error:        ${summary.pagesError}`);
      console.log(`      Total Issues:    ${summary.totalIssues}\n`);

      // Module breakdown
      console.log(`   By Module:`);
      for (const [moduleName, pages] of Object.entries(result.results.modules)) {
        const passed = pages.filter(p => p.status === 'pass').length;
        const warned = pages.filter(p => p.status === 'warning').length;
        const failed = pages.filter(p => p.status === 'fail').length;
        const errored = pages.filter(p => p.status === 'error').length;
        
        const moduleStatus = failed > 0 ? '❌' : warned > 0 ? '⚠️' : '✅';
        console.log(`      ${moduleStatus} ${moduleName.toUpperCase().padEnd(12)} ${passed}/${pages.length} ✓`);
      }

      // Failed/warning pages
      if (summary.pagesFailed > 0 || summary.pagesWarning > 0) {
        console.log(`\n   Issues Detected:`);
        for (const [moduleName, pages] of Object.entries(result.results.modules)) {
          const problematic = pages.filter(p => p.status === 'fail' || p.status === 'warning');
          for (const page of problematic) {
            const status = page.status === 'fail' ? '❌' : '⚠️';
            const issues = (page.issues.bootstrapClasses?.length || 0) + 
                          (page.issues.problematicInlineStyles?.length || 0) +
                          (page.issues.missingLabels?.length || 0);
            console.log(`      ${status} ${page.name.padEnd(25)} - ${issues} design issues`);
          }
        }
      }

      console.log('\n');

    } catch (error) {
      console.log(`[${new Date().toLocaleString()}] Loop #${loopNumber} ✗ ERROR`);
      console.log(`   ${error.message}\n`);
      failedLoops++;
    }

    // Wait before next loop
    if (MAX_LOOPS === 0 || loopNumber < MAX_LOOPS) {
      const waitSeconds = LOOP_INTERVAL / 1000;
      process.stdout.write(`   ⏳ Next check in ${waitSeconds}s...\n\n`);
      await new Promise(resolve => setTimeout(resolve, LOOP_INTERVAL));
    }
  }

  // Summary
  console.log('═══════════════════════════════════════════════════════════════════════════════════\n');
  console.log('📊 SYSTEM-WIDE DIAGNOSTIC SUMMARY\n');
  console.log(`   Total Loops:        ${totalLoops}`);
  console.log(`   ✅ Passed:          ${passedLoops}/${totalLoops}`);
  console.log(`   ❌ Failed:          ${failedLoops}/${totalLoops}`);
  console.log(`   Success Rate:       ${(((totalLoops - failedLoops) / totalLoops) * 100).toFixed(1)}%\n`);
  console.log(`   Pages Covered:      ${SYSTEM_PAGES.length}`);
  console.log(`   Modules Audited:    ${new Set(SYSTEM_PAGES.map(p => p.module)).size}`);
  console.log(`   Reports:            .hermes/regression-reports/`);
  console.log(`   Screenshots:        .hermes/regression-screenshots/\n`);

  process.exit(failedLoops > 0 ? 1 : 0);
}

main().catch(console.error);
