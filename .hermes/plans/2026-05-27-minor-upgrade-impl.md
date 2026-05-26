# NEO V-TRACK Minor Upgrade - Implementation Loop

**Status:** LOOP IN PROGRESS  
**Created:** 2026-05-27  
**Target:** 11 core tasks across 5 phases

## Execution Plan

All tasks will be executed in a loop using subagent-driven-development, following pbsys-feature-expansion pattern.

### Phase 1: Foundation (Database + Frontend CSS)

**Goal:** Set up database schema for status tracking and establish responsive design foundation.

#### Task 1.1: Responsive Web Design
- **File:** `assets/css/responsive.css`
- **Type:** component
- **Scope:** 
  - Mobile-first media queries (375px, 640px, 1024px breakpoints)
  - Fluid typography with clamp()
  - Flexible grids and images
  - Link in `includes/header.php`

#### Task 1.2: Dashboard Asset Integration
- **Files:** `admin/dashboard.php`, `user/dashboard.php`
- **Type:** page
- **Scope:**
  - Replace plain white with asset-based design
  - Keep existing functionality, refresh UI
  - Link responsive CSS

#### Task 1.3: Search/Autocomplete Foundation
- **Files:** `api/vehicle_search_api.php`, `includes/search_backend.php`
- **Type:** api
- **Scope:**
  - Search API with actions: search, get_by_plate, get_by_id
  - Autocomplete endpoint for vehicle lookups
  - Cache results for performance

### Phase 2: Logic & Relationships

**Goal:** Implement user-vehicle M:M and fix default field values.

#### Task 2.1: Fix Brand Default & M:M Setup
- **Files:** `database/migrations/2026_05_27_m2m_relationships.sql`
- **Type:** migration
- **Scope:**
  - Create `user_vehicle` junction table (user_id, vehicle_id, vehicle_type, role)
  - Add default value for brand in vehicle tables
  - Update vehicle add/update to handle M:M

#### Task 2.2: Search Integration Across Pages
- **Files:** `vehicles/staff/list.php`, `vehicles/student/list.php`, `vehicles/visitor/list.php`, `vehicles/contractor/list.php`
- **Type:** page
- **Scope:**
  - Add search input with autocomplete
  - Search + click to fill pattern
  - Continue typing for new data

#### Task 2.3: M:M Logic Implementation
- **Files:** `includes/m2m_helpers.php`, `api/vehicle_manage_api.php`
- **Type:** api + component
- **Scope:**
  - Create M:M helper functions
  - Update vehicle add/delete/update APIs to use M:M
  - Handle vehicle ownership (one-to-many)

### Phase 3: Bulk Operations & Status

**Goal:** Implement bulk delete and vehicle status tracking.

#### Task 3.1: Bulk Delete Component
- **Files:** `assets/js/bulk-delete.js`, `includes/bulk_delete_component.php`
- **Type:** component
- **Scope:**
  - Multi-select checkboxes
  - Select-all checkbox
  - Disabled delete button until selection made
  - JavaScript handler

#### Task 3.2: Report Bulk Delete Integration
- **Files:** `admin/report_list.php`, `api/report_bulk_delete_api.php`
- **Type:** page + api
- **Scope:**
  - Replace individual delete links
  - Add bulk delete form wrapper
  - Implement API endpoint

#### Task 3.3: Vehicle Bulk Delete Integration
- **Files:** `vehicles/staff/list.php`, `vehicles/student/list.php`, `vehicles/visitor/list.php`, `vehicles/contractor/list.php`, `api/vehicle_bulk_delete_api.php`
- **Type:** page + api
- **Scope:**
  - Replace individual delete links on all vehicle list pages
  - Implement unified bulk delete API
  - Add audit logging

#### Task 3.4: Status Table & Tracking
- **Files:** `database/migrations/2026_05_27_vehicle_status.sql`, `admin/dashboard.php`
- **Type:** migration + page
- **Scope:**
  - Create `vehicle_status` table (id, vehicle_type, vehicle_id, status, created_at, updated_at)
  - Add active/inactive toggle to vehicle pages
  - Show active & inactive tables separately

### Phase 4: Data Import & Management

**Goal:** Update import system and admin management pages.

#### Task 4.1: CSV to XLSX Migration
- **Files:** `admin/import.php`, `admin/template.xlsx`, `api/bulk_import_xlsx_api.php`
- **Type:** page + api
- **Scope:**
  - Create new XLSX template
  - Accept .xlsx uploads only (reject .csv)
  - Validate against template
  - Enforce unique constraint on active records

#### Task 4.2: Admin & User Management
- **Files:** `admin/users.php`, `admin/admins.php`
- **Type:** page
- **Scope:**
  - Add permission checks (admin-only)
  - Add delete/edit bulk operations
  - Link responsive CSS

#### Task 4.3: Vehicle List & Reports
- **Files:** `admin/vehicle_list.php`, `admin/report_list.php`
- **Type:** page
- **Scope:**
  - Add bulk delete to vehicle reports
  - Add permission checks
  - Link responsive CSS

### Phase 5: Security & Permissions

**Goal:** Implement SMTP password reset and permission-based page access.

#### Task 5.1: SMTP Password Reset
- **Files:** `auth/forgot_password_smtp.php`, `auth/reset_token.php`
- **Type:** page
- **Scope:**
  - Generate secure tokens
  - 1-hour expiry
  - Send via PHPMailer
  - Only allow SMTP reset (no traditional form)

#### Task 5.2: Permission-Based Access Control
- **Files:** `includes/permission_check.php`
- **Type:** component
- **Scope:**
  - Create permission check functions
  - requireAdmin(), requireUser() helpers
  - Add checks to admin pages

#### Task 5.3: View Permission Implementation
- **Files:** All admin pages, navigation menus
- **Type:** page + component
- **Scope:**
  - Admin: sees all pages
  - User: sees all except users, admin, report, import
  - Hide nav items based on role
  - 403 responses for unauthorized access

## Execution Flow

```
Loop Task = 1 to 11:
  Create worktree: git worktree add ../pbsys-task-N task-N
  Execute task via delegate_task() with full spec
  Verify git commits: task-N-*.sql, feat: task-N, etc.
  Merge worktree: git worktree remove ../pbsys-task-N
  Update this file: Mark task N as COMPLETE ✓
  Continue to next task
  
After all tasks:
  Sync to Hestia: rclone sync (via CI/CD)
  Run regression tests
  Deploy to production
```

## Task Completion Status

### PHASE 1: COMPLETE ✓
- [x] 1.1: Responsive Web Design (commit: 6468012)
- [x] 1.2: Dashboard Asset Integration (commit: 66ea145)
- [x] 1.3: Search/Autocomplete Foundation (commit: 352e294)

### PHASE 2: COMPLETE ✓
- [x] 2.1: Fix Brand Default & M:M Setup (commit: 4b9694c)
- [x] 2.2: Search Integration Across Pages (commit: 2b91672)
- [x] 2.3: M:M Logic Implementation (verified pre-existing)

### PHASE 3: COMPLETE ✓
- [x] 3.1: Bulk Delete Component (commit: multi-included)
- [x] 3.2: Report Bulk Delete Integration (commit: f7d2074)
- [x] 3.3: Vehicle Bulk Delete Integration (commit: f7d2074)
- [x] 3.4: Status Table & Tracking (commit: f7d2074)

### PHASE 4: COMPLETE ✓
- [x] 4.1: CSV to XLSX Migration (commit: cb85e66)
- [x] 4.2: Admin & User Management (commit: 2a95c64)
- [x] 4.3: Vehicle List & Reports (commit: c30193f)

### PHASE 5: IN PROGRESS
- [ ] 5.1: SMTP Password Reset
- [ ] 5.2: Permission-Based Access Control
- [ ] 5.3: View Permission Implementation

**Total Tasks:** 16 (expanded from 11 in minor-upgrade.md for clarity)  
**Target Completion:** ~2 hours (4 batches × 30 min each)

## Git Workflow

Each task creates worktree:
```bash
git worktree add ../pbsys-task-N task-N
cd ../pbsys-task-N
# ... implement task ...
git commit -m "feat/db/api/style: task N description"
cd ../pbsys
git worktree remove ../pbsys-task-N
```

Then merge: `git merge task-N` after verification.

## Deployment

After all tasks complete:
```bash
git push origin main
gh workflow run deploy-to-hestia.yml --ref main
# Workflow syncs via rclone to Hestia SFTP
```

---

**START LOOP NOW** → Task 1.1: Responsive Web Design
