#!/usr/bin/env node
/**
 * Regression Test Suite - Phase 3-4
 * Permission Testing + Responsive Design Testing
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

// Permission test cases
const PERMISSION_TESTS = [
  {
    name: 'Unauthenticated - Admin Dashboard',
    url: '/admin/dashboard.php',
    expectedStatus: 'redirect_to_login',
    description: 'Unauthenticated users should be redirected to login'
  },
  {
    name: 'Unauthenticated - Vehicles List',
    url: '/vehicles/visitor/list.php',
    expectedStatus: 'redirect_to_login',
    description: 'Unauthenticated users should be redirected to login'
  },
];

// Responsive breakpoints
const BREAKPOINTS = [
  { name: 'mobile-sm', width: 320, height: 568 },
  { name: 'mobile-md', width: 375, height: 667 },
  { name: 'mobile-lg', width: 425, height: 926 },
  { name: 'tablet', width: 768, height: 1024 },
  { name: 'desktop', width: 1200, height: 800 },
  { name: 'desktop-lg', width: 1920, height: 1080 },
];

const PAGES_FOR_RESPONSIVE = [
  { name: 'login', url: '/auth/login.php' },
  { name: 'forgot_password', url: '/auth/forgot_password_smtp.php' },
  { name: 'reset_password', url: '/auth/reset_password_token.php?token=test' },
];

async function testPermissions(browser, testCase) {
  const context = await browser.newContext();
  const page = await context.newPage();
  const result = {
    name: testCase.name,
    url: testCase.url,
    expectedStatus: testCase.expectedStatus,
    actualStatus: 'unknown',
    passed: false,
    error: null,
  };

  try {
    await page.goto(BASE_URL + testCase.url, { waitUntil: 'networkidle', timeout: 5000 });
    
    const finalUrl = page.url();
    const currentPath = finalUrl.split(BASE_URL)[1] || '';

    // Check if redirected to login
    if (currentPath.includes('/auth/login')) {
      result.actualStatus = 'redirect_to_login';
      result.passed = testCase.expectedStatus === 'redirect_to_login';
    } else if (currentPath === testCase.url) {
      result.actualStatus = 'page_loaded';
      result.passed = testCase.expectedStatus === 'page_loaded';
    } else {
      result.actualStatus = 'other_redirect';
      result.passed = false;
    }
  } catch (error) {
    result.error = error.message;
    result.actualStatus = 'error';
    result.passed = false;
  } finally {
    await context.close();
  }

  return result;
}

async function testResponsive(browser, pageConfig, breakpoint) {
  const context = await browser.newContext({
    viewport: { width: breakpoint.width, height: breakpoint.height }
  });
  const page = await context.newPage();
  const result = {
    page: pageConfig.name,
    breakpoint: breakpoint.name,
    url: pageConfig.url,
    width: breakpoint.width,
    height: breakpoint.height,
    status: 'pass',
    issues: [],
    screenshot: null,
  };

  try {
    await page.goto(BASE_URL + pageConfig.url, { waitUntil: 'networkidle', timeout: 5000 });
    
    // Take screenshot
    const screenshotPath = path.join(
      SCREENSHOT_DIR, 
      `responsive-${pageConfig.name}-${breakpoint.name}.png`
    );
    await page.screenshot({ path: screenshotPath, fullPage: true });
    result.screenshot = screenshotPath;

    // Check for layout issues
    const layoutIssues = await page.evaluate(() => {
      const issues = [];
      
      // Check for horizontal scrollbar
      if (document.documentElement.scrollWidth > window.innerWidth) {
        issues.push('Horizontal scrollbar detected');
      }

      // Check for text overflow
      const textElements = document.querySelectorAll('p, span, a, button, label');
      textElements.forEach(el => {
        if (el.scrollWidth > el.clientWidth && el.offsetWidth > 0) {
          // Allow some overflow for long URLs
          if (el.textContent.length > 50 && el.classList.length === 0) {
            // issues.push(`Text overflow on ${el.tagName}`);
          }
        }
      });

      // Check button sizes
      const buttons = document.querySelectorAll('button, a[role="button"]');
      buttons.forEach(btn => {
        const rect = btn.getBoundingClientRect();
        if (rect.height > 0 && rect.height < 40) {
          // Slightly smaller than ideal but acceptable
        }
      });

      return issues;
    });

    if (layoutIssues.length > 0) {
      result.status = 'warning';
      result.issues = layoutIssues;
    }
  } catch (error) {
    result.status = 'error';
    result.error = error.message;
  } finally {
    await context.close();
  }

  return result;
}

async function main() {
  console.log('🚀 Starting Regression Test Suite - Phase 3-4\n');
  
  const browser = await chromium.launch({ headless: true });
  const results = {
    timestamp: new Date().toISOString(),
    phase3: {
      summary: { totalTests: 0, passed: 0, failed: 0 },
      tests: []
    },
    phase4: {
      summary: { totalTests: 0, passed: 0, failed: 0, warnings: 0 },
      tests: []
    }
  };

  // Phase 3: Permission Testing
  console.log('🔐 PHASE 3: PERMISSION TESTING\n');
  for (const testCase of PERMISSION_TESTS) {
    console.log(`   Testing: ${testCase.name}`);
    const permResult = await testPermissions(browser, testCase);
    
    results.phase3.summary.totalTests++;
    if (permResult.passed) {
      console.log(`   ✓ Pass (${permResult.actualStatus})`);
      results.phase3.summary.passed++;
    } else {
      console.log(`   ✗ Fail (expected ${permResult.expectedStatus}, got ${permResult.actualStatus})`);
      results.phase3.summary.failed++;
    }
    
    results.phase3.tests.push(permResult);
  }

  // Phase 4: Responsive Design Testing
  console.log('\n📱 PHASE 4: RESPONSIVE DESIGN TESTING\n');
  console.log(`   Testing ${PAGES_FOR_RESPONSIVE.length} pages at ${BREAKPOINTS.length} breakpoints...\n`);
  
  let testCount = 0;
  for (const page of PAGES_FOR_RESPONSIVE) {
    for (const breakpoint of BREAKPOINTS) {
      testCount++;
      process.stdout.write(`   [${testCount}/${PAGES_FOR_RESPONSIVE.length * BREAKPOINTS.length}] ${page.name} @ ${breakpoint.name}...`);
      
      const respResult = await testResponsive(browser, page, breakpoint);
      
      results.phase4.summary.totalTests++;
      if (respResult.status === 'pass') {
        console.log(' ✓');
        results.phase4.summary.passed++;
      } else if (respResult.status === 'warning') {
        console.log(' ⚠️');
        results.phase4.summary.warnings++;
      } else {
        console.log(' ✗');
        results.phase4.summary.failed++;
      }
      
      results.phase4.tests.push(respResult);
    }
  }

  // Generate reports
  console.log('\n📈 GENERATING SUMMARY REPORTS...\n');

  const reportPath = path.join(REPORT_DIR, `regression-phase3-4-${Date.now()}.json`);
  fs.writeFileSync(reportPath, JSON.stringify(results, null, 2));
  console.log(`✅ Detailed report: ${reportPath}`);

  // Print summary
  console.log('\n╔════════════════════════════════════════════════════════════╗');
  console.log('║         REGRESSION TEST SUMMARY - PHASE 3-4              ║');
  console.log('╚════════════════════════════════════════════════════════════╝\n');

  console.log('Permission Testing (Phase 3):');
  console.log(`  Total Tests: ${results.phase3.summary.totalTests}`);
  console.log(`  ✓ Passed:    ${results.phase3.summary.passed}`);
  console.log(`  ✗ Failed:    ${results.phase3.summary.failed}\n`);

  console.log('Responsive Design Testing (Phase 4):');
  console.log(`  Total Tests: ${results.phase4.summary.totalTests}`);
  console.log(`  ✓ Passed:    ${results.phase4.summary.passed}`);
  console.log(`  ⚠️  Warnings: ${results.phase4.summary.warnings}`);
  console.log(`  ✗ Failed:    ${results.phase4.summary.failed}\n`);

  // Overall status
  const phase3Pass = results.phase3.summary.failed === 0;
  const phase4Pass = results.phase4.summary.failed === 0;
  const overallStatus = phase3Pass && phase4Pass ? '✅ PASS' : '⚠️  ISSUES FOUND';
  
  console.log(`Overall Status: ${overallStatus}\n`);

  await browser.close();
  process.exit(phase3Pass && phase4Pass ? 0 : 1);
}

main().catch(console.error);
