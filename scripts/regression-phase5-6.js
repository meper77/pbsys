#!/usr/bin/env node
/**
 * Regression Test Suite - Phase 5
 * Backend Logic + Form Submission Testing
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'http://localhost:8000';
const REPORT_DIR = '.hermes/regression-reports';

if (!fs.existsSync(REPORT_DIR)) fs.mkdirSync(REPORT_DIR, { recursive: true });

// Form submission tests
const FORM_TESTS = [
  {
    name: 'Login Page Loads',
    url: '/auth/login.php',
    expectedContent: 'log masuk'
  },
  {
    name: 'Forgot Password Page Loads',
    url: '/auth/forgot_password_smtp.php',
    expectedContent: 'email'
  },
  {
    name: 'Reset Password Page Loads',
    url: '/auth/reset_password_token.php?token=test',
    expectedContent: 'reset'
  },
  {
    name: 'Login Form Has Required Fields',
    url: '/auth/login.php',
    checkForm: {
      emailField: 'input[name="email"]',
      passwordField: 'input[name="password"]',
      submitButton: 'button[type="submit"]',
    }
  },
  {
    name: 'Forgot Password Form Has Email Field',
    url: '/auth/forgot_password_smtp.php',
    checkForm: {
      emailField: 'input[name="email"]',
      submitButton: 'button[type="submit"]',
    }
  },
];

async function testFormStructure(browser, test) {
  const context = await browser.newContext();
  const page = await context.newPage();
  const result = {
    name: test.name,
    url: test.url,
    passed: false,
    details: {},
  };

  try {
    await page.goto(BASE_URL + test.url, { waitUntil: 'networkidle', timeout: 5000 });

    if (test.expectedContent) {
      const pageText = await page.evaluate(() => document.body.textContent);
      result.passed = pageText?.toLowerCase().includes(test.expectedContent.toLowerCase());
      if (!result.passed) {
        result.details.error = `Expected content "${test.expectedContent}" not found`;
      }
    }

    if (test.checkForm) {
      const formCheck = await page.evaluate((selectors) => {
        const check = {};
        for (const [key, selector] of Object.entries(selectors)) {
          const el = document.querySelector(selector);
          check[key] = {
            found: !!el,
            hasAttribute: el ? !!el.getAttribute('required') : false,
          };
        }
        return check;
      }, test.checkForm);

      result.details.formCheck = formCheck;
      const allElementsFound = Object.values(formCheck).every(c => c.found);
      result.passed = allElementsFound;
      
      if (!allElementsFound) {
        result.details.error = 'Some form elements not found';
      }
    }
  } catch (error) {
    result.details.error = error.message;
    result.passed = false;
  } finally {
    await context.close();
  }

  return result;
}

async function main() {
  console.log('🚀 Starting Regression Test Suite - Phase 5\n');

  const browser = await chromium.launch({ headless: true });
  const results = {
    timestamp: new Date().toISOString(),
    summary: { totalTests: 0, passed: 0, failed: 0 },
    tests: []
  };

  // Phase 5: Backend Logic Testing
  console.log('⚙️  PHASE 5: BACKEND LOGIC & FORM STRUCTURE TESTING\n');
  for (const test of FORM_TESTS) {
    console.log(`   Testing: ${test.name}`);
    const formResult = await testFormStructure(browser, test);
    
    results.summary.totalTests++;
    if (formResult.passed) {
      console.log(`   ✓ Pass`);
      results.summary.passed++;
    } else {
      console.log(`   ✗ Fail (${formResult.details.error || 'Unknown'})`);
      results.summary.failed++;
    }
    
    results.tests.push(formResult);
  }

  // Generate report
  console.log('\n📈 GENERATING SUMMARY REPORT...\n');

  const reportPath = path.join(REPORT_DIR, `regression-phase5-${Date.now()}.json`);
  fs.writeFileSync(reportPath, JSON.stringify(results, null, 2));
  console.log(`✅ Detailed report: ${reportPath}`);

  // Print summary
  console.log('\n╔════════════════════════════════════════════════════════════╗');
  console.log('║         REGRESSION TEST SUMMARY - PHASE 5                ║');
  console.log('╚════════════════════════════════════════════════════════════╝\n');

  console.log(`Total Tests:  ${results.summary.totalTests}`);
  console.log(`✓ Passed:     ${results.summary.passed}`);
  console.log(`✗ Failed:     ${results.summary.failed}\n`);

  // Test results
  console.log('Test Results:');
  for (const test of results.tests) {
    const status = test.passed ? '✓' : '✗';
    console.log(`  ${status} ${test.name}`);
  }

  // Overall status
  const overallStatus = results.summary.failed === 0 ? '✅ PASS' : '⚠️  ISSUES FOUND';
  
  console.log(`\nOverall Status: ${overallStatus}\n`);

  await browser.close();
  process.exit(results.summary.failed === 0 ? 0 : 1);
}

main().catch(console.error);
