#!/usr/bin/env node
/**
 * Playwright UI/UX Diagnostic Audit
 * Audits design consistency, accessibility, form logic, and responsive behavior
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'http://localhost:8000';

const pages = [
  { name: 'Login', url: '/auth/login.php' },
  { name: 'Forgot Password', url: '/auth/forgot_password_smtp.php' },
  { name: 'Reset Password (Invalid Token)', url: '/auth/reset_password_token.php?token=invalid' },
  { name: 'Dashboard', url: '/admin/dashboard.php', requireAuth: true },
  { name: 'Users List', url: '/admin/users.php', requireAuth: true },
  { name: 'Add User', url: '/admin/add_user.php', requireAuth: true },
  { name: 'Bulk Import', url: '/admin/bulk_import.php', requireAuth: true },
];

async function auditPage(page, spec) {
  const results = {
    page: spec.name,
    url: spec.url,
    issues: [],
    design: {},
    forms: [],
    accessibility: {},
  };

  try {
    await page.goto(`${BASE_URL}${spec.url}`, { waitUntil: 'networkidle', timeout: 10000 });
    
    // Wait for page load
    await page.waitForTimeout(500);

    // ===== DESIGN CONSISTENCY AUDIT =====
    results.design = await page.evaluate(() => {
      const issues = [];
      const stats = {
        bootstrapClasses: 0,
        neoClasses: 0,
        inlineStyles: 0,
        buttons: {},
        forms: {},
        cards: {},
      };

      // Check for Bootstrap classes
      const bootstrapPatterns = [
        'container', 'row', 'col-', 'col-md', 'col-lg', 'col-sm',
        'form-control', 'form-group', 'form-label',
        'btn btn-', 'btn-primary', 'btn-danger', 'btn-success',
        'alert alert-', 'card-body', 'shadow-sm', 'w-100', 'mb-', 'mt-', 'mx-auto',
      ];

      document.querySelectorAll('*').forEach(el => {
        const classes = el.className;
        bootstrapPatterns.forEach(pattern => {
          if (classes && classes.includes(pattern)) {
            stats.bootstrapClasses++;
            if (!issues.find(i => i.element === el.tagName && i.pattern === pattern)) {
              issues.push({
                element: el.tagName,
                pattern,
                class: classes.substring(0, 50),
              });
            }
          }
        });
      });

      // Check for NEO V-TRACK classes
      const neoPatterns = [
        'nv-shell', 'nv-stack', 'nv-grid', 'nv-row',
        'card', 'field', 'field-label', 'input', 'flash',
        'btn', 'btn-primary', 'btn-ghost', 'btn-signal',
        'auth-card', 'auth-brand', 'auth-hero', 'auth-head',
        'eyebrow', 'page', 'page-head',
      ];

      document.querySelectorAll('*').forEach(el => {
        const classes = el.className;
        neoPatterns.forEach(pattern => {
          if (classes && classes.includes(pattern)) {
            stats.neoClasses++;
          }
        });
      });

      // Check for inline styles (anomaly)
      document.querySelectorAll('[style*=":"]').forEach(el => {
        stats.inlineStyles++;
      });

      // Audit button styles
      document.querySelectorAll('button, input[type="button"], input[type="submit"], a.btn, a[role="button"]').forEach(btn => {
        const hasBootstrap = ['btn-primary', 'btn-danger', 'btn-success', 'btn-info'].some(cls => btn.className.includes(cls));
        const hasNeo = ['btn-primary', 'btn-ghost', 'btn-signal', 'btn-quiet'].some(cls => btn.className.includes(cls));
        const hasInline = btn.getAttribute('style');

        stats.buttons[btn.textContent.substring(0, 20)] = {
          classes: btn.className.substring(0, 50),
          hasBootstrap,
          hasNeo,
          hasInline: !!hasInline,
        };
      });

      // Audit form inputs
      document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], textarea, select').forEach(inp => {
        const parent = inp.closest('.field') || inp.closest('.form-group') || inp.parentElement;
        stats.forms[inp.name || inp.id || 'unnamed'] = {
          type: inp.type,
          hasFieldWrapper: !!inp.closest('.field'),
          hasBootstrapWrapper: !!inp.closest('.form-group'),
          parentClass: parent.className.substring(0, 50),
          hasInlineStyle: !!inp.getAttribute('style'),
        };
      });

      // Audit cards
      document.querySelectorAll('.card, [class*="card"], .auth-card').forEach((card, i) => {
        if (i < 5) { // limit to first 5
          stats.cards[`card-${i}`] = {
            classes: card.className.substring(0, 60),
            hasNestedContent: card.children.length,
          };
        }
      });

      return { issues, stats };
    });

    // ===== FORM LOGIC & INTERACTION AUDIT =====
    const formElements = await page.$$('form');
    for (let i = 0; i < formElements.length; i++) {
      const formHandle = await page.$(`form:nth-of-type(${i + 1})`);
      if (!formHandle) continue;

      const formData = await page.evaluate(form => {
        const method = form.getAttribute('method') || 'GET';
        const action = form.getAttribute('action') || form.getAttribute('onsubmit') || 'inline';
        const fields = [];
        
        form.querySelectorAll('input, textarea, select').forEach(field => {
          fields.push({
            name: field.name || field.id,
            type: field.type,
            required: field.required,
            placeholder: field.placeholder,
            value: field.value,
          });
        });

        return {
          id: form.id,
          method,
          action: action.substring(0, 50),
          fieldCount: fields.length,
          fields,
          hasSubmitBtn: !!form.querySelector('button[type="submit"], input[type="submit"]'),
        };
      }, formHandle);

      results.forms.push(formData);
    }

    // ===== ACCESSIBILITY AUDIT =====
    results.accessibility = await page.evaluate(() => {
      const issues = [];

      // Check for missing labels
      document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], textarea, select').forEach(inp => {
        const hasLabel = !!document.querySelector(`label[for="${inp.id}"]`);
        const hasAriaLabel = !!inp.getAttribute('aria-label');
        if (!hasLabel && !hasAriaLabel && !inp.closest('label')) {
          issues.push({
            type: 'missing-label',
            element: inp.name || inp.id,
          });
        }
      });

      // Check for missing alt text on images
      document.querySelectorAll('img').forEach(img => {
        if (!img.getAttribute('alt')) {
          issues.push({
            type: 'missing-alt',
            src: img.src.substring(0, 50),
          });
        }
      });

      // Check heading hierarchy
      const headings = [];
      document.querySelectorAll('h1, h2, h3, h4, h5, h6').forEach(h => {
        headings.push({ tag: h.tagName, text: h.textContent.substring(0, 30) });
      });

      return {
        missingLabels: issues.filter(i => i.type === 'missing-label').length,
        missingAlt: issues.filter(i => i.type === 'missing-alt').length,
        headingCount: headings.length,
        headings: headings.slice(0, 5),
        issues,
      };
    });

    // ===== RESPONSIVE BEHAVIOR =====
    const viewports = [
      { name: 'Mobile', width: 375, height: 667 },
      { name: 'Tablet', width: 768, height: 1024 },
      { name: 'Desktop', width: 1280, height: 720 },
    ];

    results.responsive = {};
    for (const vp of viewports) {
      await page.setViewportSize({ width: vp.width, height: vp.height });
      const visible = await page.evaluate(() => window.innerWidth <= 640 ? 'mobile' : window.innerWidth <= 1024 ? 'tablet' : 'desktop');
      results.responsive[vp.name] = {
        viewport: `${vp.width}x${vp.height}`,
        layoutMode: visible,
      };
    }

  } catch (error) {
    results.issues.push({
      type: 'error',
      message: error.message.substring(0, 100),
    });
  }

  return results;
}

async function main() {
  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();

  const auditResults = [];

  console.log('🔍 Starting UI/UX Diagnostic Audit...\n');

  for (const spec of pages) {
    console.log(`📄 Auditing: ${spec.name} (${spec.url})`);
    const result = await auditPage(page, spec);
    auditResults.push(result);
    console.log(`   ✓ Complete\n`);
  }

  await browser.close();

  // ===== GENERATE REPORT =====
  const report = {
    timestamp: new Date().toISOString(),
    baseUrl: BASE_URL,
    summary: {
      totalPages: auditResults.length,
      pagesWithBootstrap: auditResults.filter(r => r.design.stats?.bootstrapClasses > 0).length,
      pagesWithInlineStyles: auditResults.filter(r => r.design.stats?.inlineStyles > 0).length,
      averageNeoClasses: Math.round(
        auditResults.reduce((sum, r) => sum + (r.design.stats?.neoClasses || 0), 0) / auditResults.length
      ),
    },
    details: auditResults,
  };

  // Save report
  const reportPath = path.join(__dirname, '..', '.hermes', 'ui-ux-audit-report.json');
  fs.mkdirSync(path.dirname(reportPath), { recursive: true });
  fs.writeFileSync(reportPath, JSON.stringify(report, null, 2));

  console.log('\n📊 AUDIT SUMMARY:');
  console.log(`   Pages Audited: ${report.summary.totalPages}`);
  console.log(`   Pages with Bootstrap: ${report.summary.pagesWithBootstrap}`);
  console.log(`   Pages with Inline Styles: ${report.summary.pagesWithInlineStyles}`);
  console.log(`   Avg NEO Classes per Page: ${report.summary.averageNeoClasses}`);
  console.log(`\n📄 Full report saved to: ${reportPath}`);

  // Print detailed issues
  console.log('\n⚠️  ANOMALIES DETECTED:\n');
  auditResults.forEach(result => {
    if (result.design.stats?.bootstrapClasses > 0 || result.design.stats?.inlineStyles > 0) {
      console.log(`❌ ${result.page}:`);
      if (result.design.stats?.bootstrapClasses > 0) {
        console.log(`   - Bootstrap Classes: ${result.design.stats.bootstrapClasses}`);
        result.design.issues.slice(0, 3).forEach(issue => {
          console.log(`     • ${issue.element}.${issue.pattern}`);
        });
      }
      if (result.design.stats?.inlineStyles > 0) {
        console.log(`   - Inline Styles: ${result.design.stats.inlineStyles}`);
      }
      console.log('');
    }
  });

  process.exit(0);
}

main().catch(err => {
  console.error('Audit failed:', err);
  process.exit(1);
});
