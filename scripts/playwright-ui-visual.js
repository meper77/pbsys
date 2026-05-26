#!/usr/bin/env node
/**
 * Visual UI/UX Diagnosis Report
 * Takes screenshots of each page and generates accessibility report
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'http://localhost:8000';

const pages = [
  { name: 'login', url: '/auth/login.php', label: 'Login Page' },
  { name: 'forgot_password', url: '/auth/forgot_password_smtp.php', label: 'Forgot Password' },
  { name: 'reset_password', url: '/auth/reset_password_token.php?token=invalid', label: 'Reset Password' },
  { name: 'dashboard', url: '/admin/dashboard.php', label: 'Dashboard' },
  { name: 'users', url: '/admin/users.php', label: 'Users List' },
];

async function inspectPage(page, spec) {
  const result = {
    page: spec.name,
    label: spec.label,
    url: spec.url,
    violations: [],
    elementScan: {},
  };

  try {
    await page.goto(`${BASE_URL}${spec.url}`, { waitUntil: 'networkidle', timeout: 10000 });
    await page.waitForTimeout(500);

    // Take screenshot
    const screenshotPath = path.join(__dirname, '..', '.hermes', `screenshot-${spec.name}.png`);
    fs.mkdirSync(path.dirname(screenshotPath), { recursive: true });
    await page.screenshot({ path: screenshotPath, fullPage: true });
    result.screenshot = screenshotPath;

    // Scan for specific issues
    result.elementScan = await page.evaluate(() => {
      const scan = {
        allDivs: document.querySelectorAll('div').length,
        buttonsWithInlineStyle: [],
        inputsWithoutLabels: [],
        imagesWithoutAlt: [],
        bootstrapClasses: [],
        colorScheme: [],
        fontSizes: [],
      };

      // Find buttons with inline styles
      document.querySelectorAll('button, [role="button"]').forEach(btn => {
        if (btn.getAttribute('style')) {
          scan.buttonsWithInlineStyle.push({
            text: btn.textContent.substring(0, 30),
            style: btn.getAttribute('style').substring(0, 80),
          });
        }
      });

      // Find inputs without labels
      document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], textarea').forEach(inp => {
        const label = document.querySelector(`label[for="${inp.id}"]`);
        if (!label && !inp.closest('label')) {
          scan.inputsWithoutLabels.push({
            name: inp.name || inp.id || 'unnamed',
            type: inp.type,
          });
        }
      });

      // Find images without alt
      document.querySelectorAll('img').forEach(img => {
        if (!img.getAttribute('alt')) {
          scan.imagesWithoutAlt.push(img.src.substring(img.src.lastIndexOf('/') + 1));
        }
      });

      // Find Bootstrap usage (only check meaningful indicators)
      const bootstrapIndicators = ['container', 'form-control', 'btn-primary', 'alert-danger'];
      document.querySelectorAll('[class]').forEach(el => {
        const classes = el.className;
        bootstrapIndicators.forEach(indicator => {
          if (classes && classes.includes(indicator)) {
            scan.bootstrapClasses.push({
              element: el.tagName,
              class: classes.substring(0, 50),
            });
          }
        });
      });

      // Sample color scheme
      const elements = document.querySelectorAll('h1, h2, button, .btn, .card, input, textarea');
      for (let i = 0; i < Math.min(3, elements.length); i++) {
        const el = elements[i];
        const style = window.getComputedStyle(el);
        scan.colorScheme.push({
          element: el.tagName,
          class: el.className.substring(0, 40),
          bg: style.backgroundColor,
          fg: style.color,
          fontSize: style.fontSize,
        });
      }

      return scan;
    });

  } catch (error) {
    result.violations.push({
      type: 'error',
      message: error.message.substring(0, 150),
    });
  }

  return result;
}

async function main() {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();

  const report = {
    timestamp: new Date().toISOString(),
    baseUrl: BASE_URL,
    pages: [],
  };

  console.log('📸 Generating UI/UX Visual Inspection Report...\n');

  for (const spec of pages) {
    console.log(`📄 Inspecting: ${spec.label}`);
    const result = await inspectPage(page, spec);
    report.pages.push(result);
    console.log(`   ✓ Screenshot: ${path.basename(result.screenshot)}`);
    console.log(`   ✓ Bootstrap classes found: ${result.elementScan.bootstrapClasses?.length || 0}`);
    console.log(`   ✓ Inputs without labels: ${result.elementScan.inputsWithoutLabels?.length || 0}`);
    console.log(`   ✓ Buttons with inline styles: ${result.elementScan.buttonsWithInlineStyle?.length || 0}\n`);
  }

  // Generate HTML report
  const htmlReport = generateHTMLReport(report);
  const reportPath = path.join(__dirname, '..', '.hermes', 'ui-ux-visual-report.html');
  fs.writeFileSync(reportPath, htmlReport);

  // Save JSON report
  const jsonReportPath = path.join(__dirname, '..', '.hermes', 'ui-ux-visual-report.json');
  fs.writeFileSync(jsonReportPath, JSON.stringify(report, null, 2));

  console.log('✅ INSPECTION COMPLETE\n');
  console.log(`📊 Reports generated:`);
  console.log(`   - HTML: ${reportPath}`);
  console.log(`   - JSON: ${jsonReportPath}`);
  console.log(`   - Screenshots: .hermes/screenshot-*.png`);

  await browser.close();
  process.exit(0);
}

function generateHTMLReport(report) {
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NEO V-TRACK UI/UX Inspection Report</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; line-height: 1.6; }
    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    h1 { color: #5A2EA6; margin-bottom: 10px; font-size: 2em; }
    .timestamp { color: #999; font-size: 0.9em; margin-bottom: 30px; }
    .page-section { margin-bottom: 40px; border-top: 2px solid #eee; padding-top: 20px; }
    .page-section:first-of-type { border-top: none; padding-top: 0; }
    .page-title { font-size: 1.3em; color: #333; margin-bottom: 15px; font-weight: 600; }
    .page-url { color: #666; font-size: 0.9em; margin-bottom: 15px; font-family: monospace; }
    .screenshot { width: 100%; max-width: 800px; border: 1px solid #ddd; border-radius: 6px; margin: 15px 0; }
    .issues { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; border-radius: 4px; }
    .issues.none { background: #d4edda; border-left-color: #28a745; }
    .issue-item { margin: 8px 0; font-size: 0.95em; }
    .issue-item.warning { color: #856404; }
    .issue-item.ok { color: #155724; }
    .element-scan { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 15px 0; }
    .scan-box { background: #f8f9fa; padding: 12px; border-radius: 6px; border: 1px solid #dee2e6; font-size: 0.85em; }
    .scan-box strong { display: block; margin-bottom: 5px; color: #333; }
    .scan-box code { background: #fff; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
  </style>
</head>
<body>
  <div class="container">
    <h1>🔍 NEO V-TRACK UI/UX Inspection Report</h1>
    <div class="timestamp">Generated: ${new Date(report.timestamp).toLocaleString()}</div>

    ${report.pages.map(page => `
      <div class="page-section">
        <div class="page-title">${page.label}</div>
        <div class="page-url">${page.url}</div>

        <div class="issues ${page.elementScan.bootstrapClasses?.length === 0 && page.elementScan.buttonsWithInlineStyle?.length === 0 ? 'none' : ''}">
          <strong>🔎 Design Anomalies:</strong>
          ${page.elementScan.bootstrapClasses?.length > 0 ? 
            `<div class="issue-item warning">⚠️ Bootstrap classes found: ${page.elementScan.bootstrapClasses.length}</div>` : 
            '<div class="issue-item ok">✓ No Bootstrap classes</div>'}
          ${page.elementScan.buttonsWithInlineStyle?.length > 0 ? 
            `<div class="issue-item warning">⚠️ Buttons with inline styles: ${page.elementScan.buttonsWithInlineStyle.length}</div>` : 
            '<div class="issue-item ok">✓ No inline button styles</div>'}
          ${page.elementScan.inputsWithoutLabels?.length > 0 ? 
            `<div class="issue-item warning">⚠️ Inputs without labels: ${page.elementScan.inputsWithoutLabels.length}</div>` : 
            '<div class="issue-item ok">✓ All inputs have labels</div>'}
          ${page.elementScan.imagesWithoutAlt?.length > 0 ? 
            `<div class="issue-item warning">⚠️ Images without alt text: ${page.elementScan.imagesWithoutAlt.length}</div>` : 
            '<div class="issue-item ok">✓ All images have alt text</div>'}
        </div>

        <img src="${page.screenshot}" alt="${page.label}" class="screenshot">

        ${page.elementScan.bootstrapClasses?.length > 0 ? `
          <div class="scan-box">
            <strong>Bootstrap Classes:</strong>
            ${page.elementScan.bootstrapClasses.slice(0, 3).map(b => 
              `<div><code>${b.element}.${b.class}</code></div>`
            ).join('')}
          </div>
        ` : ''}

        ${page.elementScan.buttonsWithInlineStyle?.length > 0 ? `
          <div class="scan-box">
            <strong>Buttons with Inline Styles:</strong>
            ${page.elementScan.buttonsWithInlineStyle.slice(0, 3).map(b => 
              `<div>"${b.text}" → <code>${b.style}</code></div>`
            ).join('')}
          </div>
        ` : ''}
      </div>
    `).join('')}

  </div>
</body>
</html>
`;
}

main().catch(err => {
  console.error('Inspection failed:', err);
  process.exit(1);
});
