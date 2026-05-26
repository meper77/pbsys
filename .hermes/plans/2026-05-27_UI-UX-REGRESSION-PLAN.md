# Comprehensive UI/UX Regression Testing Plan via Playwright

## Goal
Execute full regression testing suite across all pbsys pages using Playwright to verify UI/UX consistency, form logic, accessibility, and responsive behavior before deploying to Hestia staging (10.0.26.208).

**Timeline:** Next session after current dev stack running  
**Scope:** All 11 Minor Upgrade deliverables (Phase 1-5) + existing functionality  
**Success Criteria:** Zero regressions in UI/UX, logic, and accessibility

---

## Current Context

### Completed Work (This Session)
- ✅ Fixed PHPMailer paths and database connection variables
- ✅ Applied password_reset_tokens migration
- ✅ Standardized auth pages to NEO V-TRACK design system
- ✅ Removed all Bootstrap classes from forgot_password_smtp.php, reset_password_token.php
- ✅ Eliminated 15+ inline styles
- ✅ Generated 5 page screenshots + audit reports

### Infrastructure Ready
- Web server: http://localhost:8000 (PHP 8.0)
- Database: MySQL 5.7+ with all migrations applied
- Android emulator: Running with Flutter app deployed
- Playwright automation: Installed and configured
- Screenshots: `.hermes/screenshot-*.png` (5 pages)
- Audit scripts: `scripts/playwright-ui-audit.js`, `scripts/playwright-ui-visual.js`

### Known Constraints
- No automated test runner yet (no pytest, jest, or PHPUnit integration)
- TESTING_GUIDE.md exists but needs Playwright-based execution
- No CI/CD regression suite in repo (manual testing via Playwright scripts)
- Password reset SMTP delivery requires mock/spy setup

---

## Proposed Approach

### Phase 1: Expand Visual Audit Coverage
**Goal:** Verify all 30+ pages have correct design system usage

**Pages to Audit:**
- Auth (5 pages): login, register, role_select, forgot_password_smtp, reset_password_token
- Admin (8 pages): dashboard, users, admins, vehicles_list, reports, audit_logs, bulk_import, add_user
- Vehicles (12 pages): visitor/{list, add, edit, delete, view}, staff/{list, add, edit, delete, view}, student/{list, add, edit, delete, view}, contractor/{list, add, edit, delete, view}
- Search (2 pages): admin_search, user_search
- API test page (if exists): api_test.php or similar

**What to Check:**
- ✓ Zero Bootstrap classes (container, col-*, card, form-control, alert, btn-*)
- ✓ Zero inline styles (all style="..." removed)
- ✓ All form inputs have `<label>` elements
- ✓ All buttons have proper `.btn` classes with variants
- ✓ All form submissions have POST method + CSRF tokens (if implemented)
- ✓ Color contrast meets WCAG AA (luminance ratio 4.5:1+)
- ✓ Touch-friendly button/link sizes (48px minimum)
- ✓ Responsive breakpoints work (mobile 320px, tablet 768px, desktop 1200px)

**Output:**
- Enhanced `scripts/playwright-ui-audit.js` to check all 30+ pages
- Report: `.hermes/ui-ux-regression-full-report.json`
- Screenshots: `.hermes/screenshot-{page}.png` for all pages

---

### Phase 2: Form Logic & Submission Testing
**Goal:** Verify all forms submit correctly with proper validation and backend handling

**Test Cases:**

#### Auth Forms
1. **Login Form**
   - ✓ Empty email → "Email required" error
   - ✓ Invalid email → "Invalid email" error
   - ✓ Empty password → "Password required" error
   - ✓ Wrong credentials → "Invalid email or password" error
   - ✓ Valid login → redirect to dashboard/role_select
   - ✓ Session persists across page refreshes

2. **Forgot Password Form**
   - ✓ Empty email → validation error
   - ✓ Non-existent email → "Email not found" or silent (security)
   - ✓ Valid email → "Check your email for reset link" message
   - ✓ Token sent to email (mock SMTP or check logs)
   - ✓ Token link generated with 1-hour expiry
   - ✓ Form submits via POST with proper headers

3. **Reset Password Form**
   - ✓ Invalid/expired token → "Token expired" error
   - ✓ Empty password → validation error
   - ✓ Weak password → validation error (if implemented)
   - ✓ Password mismatch → "Passwords don't match" error
   - ✓ Valid reset → redirect to login with "Password reset successfully" message
   - ✓ bcrypt hash stored in database (verify hash format)

#### Admin Forms
4. **Add User Form**
   - ✓ All required fields validated (email, name, role)
   - ✓ Duplicate email → "Email already exists" error
   - ✓ Invalid email → validation error
   - ✓ Valid submission → user created in database
   - ✓ Audit log entry created (action="user_created")

5. **Bulk Import Form**
   - ✓ File upload validation (XLSX only)
   - ✓ Row validation (email, plate, type, etc.)
   - ✓ Duplicate handling (skip/update/error)
   - ✓ Success message shows imported count
   - ✓ Error report shows failed rows

#### Vehicle Forms
6. **Add Vehicle Form** (all 4 types: visitor, staff, student, contractor)
   - ✓ All required fields validated
   - ✓ Plate number uniqueness check
   - ✓ Owner selection (user_vehicle M:M)
   - ✓ Valid submission → vehicle created in database
   - ✓ Status table auto-set to "active" with today's date

**Testing Method:**
- Playwright: Fill form, submit, capture response
- Database verification: Check data inserted with correct values
- Error message verification: Compare to TESTING_GUIDE.md expectations

**Output:**
- Test script: `scripts/playwright-form-logic-tests.js`
- Report: `.hermes/form-logic-test-report.json`
- Screenshots: `.hermes/screenshot-form-error-{page}.png` for each error state

---

### Phase 3: Permission & Authorization Testing
**Goal:** Verify role-based access control works correctly across all pages

**Test Cases:**

1. **Unauthenticated Access**
   - ✓ Accessing /admin/* → redirect to login
   - ✓ Accessing /vehicles/* → redirect to login
   - ✓ Accessing /api/* → 401 Unauthorized (if implemented)

2. **User Role (Non-Admin)**
   - ✓ Can access /vehicles/list (their vehicles only)
   - ✓ Cannot access /admin/dashboard → redirect or 403 error
   - ✓ Cannot access /admin/users → redirect or 403 error
   - ✓ Can add/edit own vehicles only

3. **Admin Role**
   - ✓ Can access all /admin/* pages
   - ✓ Can view all users, vehicles, reports
   - ✓ Can perform bulk operations (import, delete)
   - ✓ Can view audit logs

4. **Session Expiry**
   - ✓ Session timeout after N minutes (verify timeout value)
   - ✓ Expired session → redirect to login
   - ✓ Session data cleared on logout

**Testing Method:**
- Playwright: Test each role's access patterns
- Database verification: Check `user_sessions` table updates
- Audit log verification: Check `admin_action_logs` for each action

**Output:**
- Test script: `scripts/playwright-permission-tests.js`
- Report: `.hermes/permission-test-report.json`

---

### Phase 4: Responsive Design Testing
**Goal:** Verify all pages render correctly on mobile, tablet, and desktop

**Breakpoints to Test:**
- Mobile: 320px, 375px, 425px
- Tablet: 768px, 1024px
- Desktop: 1200px, 1400px, 1920px

**What to Check:**
- ✓ Text is readable (no overflow, proper line-wrapping)
- ✓ Buttons/links are touch-friendly (48px+ tall)
- ✓ Forms stack vertically on mobile
- ✓ Tables scroll horizontally on mobile
- ✓ Navigation collapses to hamburger menu on mobile (if applicable)
- ✓ Images scale properly (no stretching/squishing)
- ✓ No horizontal scrollbar on any viewport

**Testing Method:**
- Playwright: Set viewport size, take screenshots at each breakpoint
- Visual comparison: Compare across breakpoints for consistency

**Output:**
- Test script: `scripts/playwright-responsive-tests.js`
- Report: `.hermes/responsive-test-report.json`
- Screenshots: `.hermes/screenshot-{page}-{breakpoint}.png`

---

### Phase 5: Database & Backend Logic Testing
**Goal:** Verify backend data integrity and business logic

**Test Cases:**

1. **M:M User-Vehicle Relationship**
   - ✓ Adding vehicle → entry in `user_vehicle` table
   - ✓ Assigning user to vehicle → M:M entry created
   - ✓ Bulk import → M:M relationships created
   - ✓ Vehicle appears in user's list

2. **Status Table Behavior**
   - ✓ New vehicle → status="active", date_added=today
   - ✓ Status auto-inactive after 1 year (if implemented)
   - ✓ Toggling status updates database

3. **Audit Trail**
   - ✓ Each admin action logged (create, update, delete, export)
   - ✓ Log includes: user_email, action, entity_type, entity_id, ip_address, timestamp
   - ✓ Unauthorized access logged (logUnauthorizedAccess)
   - ✓ Logs viewable in /admin/audit_logs.php

4. **Search & Filtering**
   - ✓ Plate search works across all vehicle tables
   - ✓ Owner filtering works correctly
   - ✓ Date range filtering accurate
   - ✓ No SQL injection vulnerabilities (test with malicious input)

**Testing Method:**
- Playwright: Perform action, query database directly
- MySQL queries: Verify data inserted with correct relationships
- Log verification: Check audit trail entries

**Output:**
- Test script: `scripts/playwright-backend-tests.js`
- Report: `.hermes/backend-test-report.json`

---

### Phase 6: Accessibility Testing
**Goal:** Verify pages meet WCAG AA accessibility standards

**Test Cases:**

1. **Semantic HTML**
   - ✓ Forms use `<form>`, `<input>`, `<label>`, `<button>`
   - ✓ Tables use `<table>`, `<thead>`, `<tbody>`, `<th>`
   - ✓ Navigation uses `<nav>` or semantic menu structure
   - ✓ Headings use `<h1>`, `<h2>`, etc. (no skipped levels)

2. **Color Contrast**
   - ✓ Text contrast ratio 4.5:1+ (WCAG AA)
   - ✓ Focus indicators visible (3px+ outline glow)
   - ✓ Error messages use color + icon (not color alone)

3. **Keyboard Navigation**
   - ✓ All form inputs focusable via Tab
   - ✓ All buttons/links reachable via Tab
   - ✓ Tab order is logical (top-to-bottom, left-to-right)
   - ✓ Modal/dropdown menus keyboard navigable (if applicable)

4. **Screen Reader Support**
   - ✓ Images have `alt` text
   - ✓ Icons have `aria-label` or semantic meaning
   - ✓ Form errors announced via `aria-live` region (if applicable)
   - ✓ Page landmarks (main, nav, footer) properly marked

**Testing Method:**
- Playwright: Check DOM structure, color values, focus states
- axe-core library: Run automated accessibility checks
- Manual testing: Tab through pages, check keyboard navigation

**Output:**
- Test script: `scripts/playwright-accessibility-tests.js`
- Report: `.hermes/accessibility-test-report.json`
- Accessibility audit: axe-core scan results

---

## Step-by-Step Implementation Plan

### Session 1: Setup & Phase 1-2 (Today/Next Session)
```
1. Ensure dev stack running:
   - MySQL on localhost:3306
   - PHP on http://localhost:8000
   - Android emulator online (if needed for Flutter app testing)

2. Create enhanced Playwright audit scripts:
   - Expand ui-audit.js to cover all 30+ pages
   - Add form logic test script (Phase 2)
   - Generate full visual report

3. Run audit and form tests:
   - Capture any remaining design anomalies
   - Test all form submissions and error states
   - Generate report + screenshots
```

### Session 2: Phase 3-4 (Next Session)
```
1. Create permission test script
   - Test user vs admin access
   - Verify redirects and 403 errors
   - Test session expiry

2. Create responsive test script
   - Test 6+ breakpoints
   - Capture screenshots at each breakpoint
   - Verify no overflow/layout issues

3. Generate comprehensive report
```

### Session 3: Phase 5-6 (After Phase 3-4)
```
1. Create backend logic test script
   - Verify database relationships
   - Check audit trail entries
   - Test search functionality

2. Create accessibility test script
   - Run axe-core automated checks
   - Verify keyboard navigation
   - Check color contrast

3. Final regression report
```

### Session 4: Review & Deploy
```
1. Review all regression reports
2. Fix any regressions found
3. Deploy to Hestia staging (10.0.26.208)
4. Re-test on staging environment
5. Deploy to production if all tests pass
```

---

## Files Likely to Change

### New Files (Scripts)
- `scripts/playwright-ui-audit.js` (enhanced)
- `scripts/playwright-form-logic-tests.js` (new)
- `scripts/playwright-permission-tests.js` (new)
- `scripts/playwright-responsive-tests.js` (new)
- `scripts/playwright-backend-tests.js` (new)
- `scripts/playwright-accessibility-tests.js` (new)
- `scripts/playwright-test-utils.js` (shared utilities)

### New Reports
- `.hermes/ui-ux-regression-full-report.json`
- `.hermes/form-logic-test-report.json`
- `.hermes/permission-test-report.json`
- `.hermes/responsive-test-report.json`
- `.hermes/backend-test-report.json`
- `.hermes/accessibility-test-report.json`
- `.hermes/REGRESSION-TESTING-SUMMARY.md`

### PHP Files (If Regressions Found)
- `auth/login.php` (if design issues)
- `auth/register.php` (if design issues)
- `admin/dashboard.php` (if design issues)
- `vehicles/*/add.php`, `edit.php`, `list.php` (if design issues)
- `includes/permission_check.php` (if permission logic issues)

### Database Files (If Schema Issues Found)
- `database/migrations/YYYY-MM-DD_fix-issue.sql` (if new migrations needed)

---

## Tests & Validation

### Regression Test Suite
1. ✓ Visual audit: All pages use correct design system
2. ✓ Form logic: All submissions work correctly
3. ✓ Permissions: Access control enforced
4. ✓ Responsive: Layout works on mobile/tablet/desktop
5. ✓ Backend: Data integrity verified
6. ✓ Accessibility: WCAG AA standards met

### Validation Steps
```bash
# Before regression testing
cd pbsys
npm run playwright:audit          # Run all audit scripts
npm run playwright:forms          # Run form logic tests
npm run playwright:permissions    # Run permission tests
npm run playwright:responsive     # Run responsive tests
npm run playwright:backend        # Run backend tests
npm run playwright:accessibility  # Run accessibility tests

# Generate summary report
npm run playwright:summary

# Check for regressions
if [ -f ".hermes/REGRESSION-TESTING-SUMMARY.md" ]; then
  grep "✅ PASS" .hermes/REGRESSION-TESTING-SUMMARY.md
else
  echo "Regressions found - review reports"
fi
```

---

## Risks, Tradeoffs & Open Questions

### Risks
1. **Flaky Tests:** Playwright tests may be timing-dependent (wait for elements)
   - Mitigation: Use explicit waits, retry failed tests
2. **SMTP Testing:** Cannot easily test email delivery in dev environment
   - Mitigation: Mock SMTP or check logs
3. **Database Isolation:** Tests may leave data in database
   - Mitigation: Use test database or cleanup scripts
4. **Performance:** Running 30+ page audits may take 10-15 minutes
   - Mitigation: Parallelize tests if possible

### Tradeoffs
1. **Manual vs Automated:** Playwright is automated but requires setup
   - Benefit: Repeatable, fast, comprehensive
   - Cost: Requires script development
2. **Full Regression vs Smoke Test:** Full regression is thorough but slow
   - Benefit: Catches all regressions
   - Cost: Takes time, may have false positives
3. **Database Verification:** Checking backend adds complexity
   - Benefit: Ensures data integrity
   - Cost: Requires database access, cleanup logic

### Open Questions
1. **SMTP Testing:** How to verify email delivery in dev?
   - Option A: Mock SMTP server (disposable email)
   - Option B: Check logs in PHPMailer
   - Option C: Skip email verification (test form only)

2. **Test Database:** Use production DB or separate test DB?
   - Option A: Use same DB, cleanup after tests
   - Option B: Use separate test DB (requires duplicate schema)
   - Option C: Skip database verification

3. **CI/CD Integration:** Should these tests run on every commit?
   - Option A: Add to GitHub workflow
   - Option B: Run manually before staging deployment
   - Option C: Run on staging after deployment

4. **Performance Baseline:** What's acceptable performance?
   - Option A: All pages load < 1 second
   - Option B: All pages load < 2 seconds
   - Option C: No performance targets (functional only)

---

## Success Criteria

### Green Status (Ship to Production)
- ✅ All 30+ pages use NEO V-TRACK design system
- ✅ Zero Bootstrap classes found
- ✅ Zero inline styles found
- ✅ All forms submit successfully
- ✅ All form validations work correctly
- ✅ Permission checks enforced (user/admin)
- ✅ Responsive design works on all breakpoints
- ✅ Database M:M relationships correct
- ✅ Audit trail logs all actions
- ✅ WCAG AA accessibility standards met
- ✅ No regressions from Phase 1-5 work

### Yellow Status (Investigate)
- ⚠️ 1-2 design anomalies found (can fix in next iteration)
- ⚠️ 1-2 form submission issues (minor, easily fixable)
- ⚠️ Performance slower than expected (< 2 seconds acceptable)

### Red Status (Block Deployment)
- ❌ 3+ design anomalies found
- ❌ Permission bypass vulnerability discovered
- ❌ Data corruption in database
- ❌ WCAG AA accessibility violations
- ❌ Significant performance regression

---

## Deliverables

1. **Regression Test Suite**
   - 6 comprehensive Playwright test scripts
   - 6 detailed JSON reports
   - 30+ screenshots at multiple breakpoints
   - Central summary markdown

2. **Documentation**
   - `.hermes/REGRESSION-TESTING-SUMMARY.md` (executive summary)
   - Comments in test scripts (how to run, what to check)
   - README for manual testing steps

3. **Deployment Readiness**
   - ✅ All regression tests passing
   - ✅ No blockers for staging deployment
   - ✅ Ready for production rollout

---

## Next Steps

1. **This Session:** Complete UI/UX audit standardization (DONE ✓)
2. **Next Session:** Execute Phase 1-2 (visual audit + form logic)
3. **After:** Complete Phase 3-6 (permissions, responsive, backend, accessibility)
4. **Final:** Deploy to Hestia staging with regression test reports

**Estimated Total Time:** 3-4 sessions (4-6 hours)

---

**Plan Created:** 2026-05-27  
**Status:** Ready for execution  
**Next Action:** Expand Playwright audit scripts to cover all 30+ pages
