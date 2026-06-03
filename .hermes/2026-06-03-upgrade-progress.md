# NEO V-TRACK minor-upgrade — execution progress (2026-06-03)

Worktree: `../pbsys-upgrade` (branch `minor-upgrade`). Dev server: `php -S localhost:8080`
(router at %TEMP%\nv_router.php, docroot = this worktree). DB: local MariaDB `neovtrack_db`.
Local admin login: `admin@mail.com` / `111111` (plaintext in DB).

## ARCHITECTURE TRUTHS (verified, not from docs)
- **All live vehicle data is in the `owner` table.** `staffcar/studentcar/visitorcar/contractorcar`
  are LEGACY and unused by current add/list/update flows.
  - `owner.status` = CATEGORY (`Staf|Pelajar|Pelawat|Kontraktor`) — NOT active/inactive. (Note: data
    has a stray `PELAWAT` uppercase — normalize.)
  - `owner.idnumber` = staffno (Staf) / matric (Pelajar) / IC (Pelawat,Kontraktor).
  - `owner.brand` is `NOT NULL` with NO default → INSERTs that omit brand fail with
    "Field 'brand' doesn't have a default value". **Root cause of the reported bug.**
  - `owner.sticker`, `owner.stickerno` exist → to be REMOVED (user decision).
- Add/update pages write to `owner`. List pages read `owner WHERE status='<Category>'`.
- index.php = REAL dashboard (KPIs from owner). admin/dashboard.php = actually the ADMINS LIST
  (filename diverges → rename later, task 7).

## DECISIONS (from user, 2026-06-03)
1. Identity: keep internal auto-inc ids; DROP IC concept; UNIQUE business key staffno/matric/phone;
   reactivation matches on plate+phone. (No true PK swap.)
2. Inactivity: 1 year from registration (`created_at`); reactivation resets clock via `reactivated_at`.
   Effective date = COALESCE(reactivated_at, created_at). Active if effective >= NOW()-1yr.
3. Dashboard: clean branded CSS + KPI cards, NO photo.
4. Also REMOVE sticker column/field everywhere.

## BUGS CONFIRMED
- bulk_delete_api.php deletes from legacy `*car` + non-existent `vehicle_status`/`vehicle_search_cache`/
  `admin_action_logs`; UI passes owner.id → deletes nothing. REWRITE against `owner`.
- admin/reports.php SELECTs `status` col not present in `vehicle_reports` (has `vehicle_status`) → empty.
- permission_check.php uses `$conn` (connect.php defines `$con`) and logs to missing `admin_action_logs`.

## PLAN / STATUS
- [x] Local stack up (web :8080, emulator Pixel_API_36 booted), worktree created.
- [x] DB migration 2026_06_03_minor_upgrade.sql applied (brand default, drop sticker/stickerno,
      add reactivated_at, normalize category case, strip IC from legacy tables).
- [x] Fix delete: bulk_delete_api.php owner-based; bulk-delete.js binds click; reports per-row
      single delete removed; delete_report.php already bulk-capable.
- [x] reports.php query fixed (was selecting non-existent `status` col -> empty).
- [x] Vehicles add (4 cats): shared nv_vehicle_register() with reactivation on same plate+phone,
      brand default applies; IC field removed from visitor+contractor add.
- [x] Vehicles update: IC field removed from visitor+contractor update.
- [x] Status: list pages show Active + Inactive tables via shared includes/vehicle_list_view.php.
- [x] Role-aware nav: hides users/admins/reports/import for non-admins.
- [x] Playwright pass 1: all 14 key pages HTTP 200, no PHP errors, no JS console errors.
      (fixed bulk_import.php parse error + 404 autocomplete.css link found in this pass.)

### STILL TODO (not yet done)
- [ ] Import xlsx: BLOCKED — PhpSpreadsheet not installed (only PHPMailer in vendor/, no root
      vendor/autoload.php). Needs either composer install or a pure-PHP ZipArchive xlsx reader/writer.
      Parse error fixed so page loads, but template download / .xlsx upload will fatal at runtime.
- [ ] SMTP-only password reset (Phase 5): verify auth/forgot_password_smtp.php + reset_password_token.php;
      remove non-SMTP reset paths.
- [ ] Full view-permission GATES: currently ALL pages require email_Admin (regular users locked out
      entirely). Spec wants users to browse vehicle/search/dashboard but not users/admin/report/import.
      Requires changing per-page auth gate + a shared guard. Behavioral change — confirm with user.
- [ ] Search "click to fill" autocomplete (basic text-filter works; click-to-fill not wired).
- [ ] Rename divergent files (admin/dashboard.php is the ADMINS LIST; index.php is the dashboard).
      Affects .htaccess rewrites + nav links + Flutter api_service. Do carefully near deploy.
- [ ] Dashboard: already card-based/branded (not white-plain) — arguably satisfied; could enhance.

### GATED (after user approves website)
- [ ] Push to Hestia (SFTP, user neovtrack, ssh keys). [ ] Rebuild Flutter app from scratch -> Hestia.
