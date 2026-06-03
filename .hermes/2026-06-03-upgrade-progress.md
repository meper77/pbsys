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
- [x] Local stack up (web :8080, emulator Pixel_API_36 booting), worktree created.
- [ ] DB migration 2026_06_03_minor_upgrade.sql (brand default, drop sticker/stickerno,
      add reactivated_at, normalize category case) + apply.
- [ ] Fix delete: rewrite bulk_delete_api.php (owner-based); remove single delete links
      (reports per-row + any vehicle per-row). delete_report.php bulk.
- [ ] Vehicles add/update (4 cats): provide brand default, set reactivated_at on re-upload of
      same plate+phone, drop IC field for visitor/contractor (identity=phone).
- [ ] Status: list pages show Active + Inactive tables (split by effective date).
- [ ] Dashboard subtle redesign (no photo). Responsive CSS audit. Search click-to-fill.
- [ ] Import: xlsx-only, template, unique-when-active.
- [ ] Auth: SMTP-only password reset (user+admin). Permissions: user hides users/admin/reports/import.
- [ ] Rename divergent files (admin/dashboard.php → admin/admins-list, etc.) + fix .htaccess/nav/app.
- [ ] Playwright diagnose loop. THEN STOP for user approval (gate before Hestia push + app rebuild).
