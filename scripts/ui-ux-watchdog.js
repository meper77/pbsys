#!/usr/bin/env node
/**
 * UI/UX Diagnosis Watchdog - Background monitoring with alerts
 * Sends alerts only when issues are detected (watchdog pattern)
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'http://localhost:8000';
const REPORT_DIR = '.hermes/regression-reports';
const ALERT_LOG = '.hermes/ui-ux-alert.log';
const CHECK_INTERVAL = 300000; // 5 minutes

const PAGES_TO_MONITOR = [
  { name: 'login', url: '/auth/login.php' },
  { name: 'forgot_password', url: '/auth/forgot_password_smtp.php' },
  { name: 'reset_password', url: '/auth/reset_password_token.php?token=test' },
];

if (!fs.existsSync(REPORT_DIR)) fs.mkdirSync(REPORT_DIR, { recursive: true });

function log(message) {
  const timestamp = new Date().toISOString();
  const logLine = `[${timestamp}] ${message}`;
  console.log(logLine);
  fs.appendFileSync(ALERT_LOG, logLine + '\n');
}

async function checkPage(page, pageName) {
  try {
    await page.goto(BASE_URL + `/auth/${pageName}.php`, { waitUntil: 'networkidle', timeout: 10000 });
    
    const issues = await page.evaluate(() => {
      const found = [];
      
      // Check Bootstrap classes
      document.querySelectorAll('[class*="container"], [class*="col-"], [class*="row"], [class*="form-control"]').forEach(el => {
        const neoClasses = /\b(auth-card|auth-form-group|btn|field|flash)\b/;
        if (!neoClasses.test(el.className)) {
          found.push(`Bootstrap class: ${el.className.split(' ')[0]}`);
        }
      });

      // Check problematic inline styles
      document.querySelectorAll('[style]').forEach(el => {
        const style = el.getAttribute('style') || '';
        if (!/^(\s*[a-z-]+:\s*var\([^)]+\)\s*;?\s*)*$/.test(style)) {
          found.push(`Inline style: ${style.substring(0, 30)}`);
        }
      });

      return found;
    });

    return issues;
  } catch (error) {
    return [`Error: ${error.message}`];
  }
}

async function runCheck() {
  const browser = await chromium.launch({ headless: true });
  let alertNeeded = false;
  const alerts = [];

  for (const page of PAGES_TO_MONITOR) {
    const context = await browser.newContext();
    const pageHandle = await context.newPage();
    const issues = await checkPage(pageHandle, page.name);
    
    if (issues.length > 0) {
      alertNeeded = true;
      alerts.push(`${page.name}: ${issues.join('; ')}`);
    }
    
    await context.close();
  }

  await browser.close();

  // Only output if issues found (watchdog pattern)
  if (alertNeeded) {
    log(`🚨 UI/UX ISSUES DETECTED:`);
    alerts.forEach(alert => log(`   ${alert}`));
    console.log(alerts.join('\n'));
  }

  return alertNeeded;
}

async function main() {
  log('🔍 UI/UX Watchdog started');
  
  while (true) {
    try {
      await runCheck();
    } catch (error) {
      log(`Watchdog error: ${error.message}`);
    }
    
    await new Promise(resolve => setTimeout(resolve, CHECK_INTERVAL));
  }
}

main().catch(error => {
  log(`Fatal error: ${error.message}`);
  process.exit(1);
});
