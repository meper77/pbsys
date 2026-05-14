# CLAUDE.md

Instructions for AI coding assistants working in this repo.

## What this is

NEO V-TRACK — a PHP/MySQL vehicle-sticker management system for UiTM, serving the intranet host `neovtrack.uitm.edu.my` (Hestia panel on `10.0.26.208`). A Flutter mobile app in `pbsystem_app/` consumes the `/api/*` endpoints.

## Architecture

PHP 8 + MySQL. No framework — handwritten request handlers, one PHP file per page. Session-based auth (`$_SESSION['email']` for users, `$_SESSION['email_Admin']` for admins). All DB access via `mysqli` and procedural SQL (note: many queries are not parameterized — see [Known issues](#known-issues)).

```
auth/        Login, register, forgot-password, role selection
admin/       Admin dashboard, user CRUD, admin CRUD, bulk import, reports
vehicles/    {visitor,staff,student,contractor}/{list,add,update,delete,view}.php
search/      Vehicle search (admin + user variants)
api/         JSON endpoints (Android app + AJAX consumers)
includes/    Shared partials — header, footer, container, connect, menus, search_backend
assets/      css/, js/, images/
uploads/     User-uploaded files
vendor/      Third-party (PHPMailer only)
database/    SQL schema + migrations/
pbsystem_app/  Flutter app (see its own README)
```

## Path conventions — important

- **PHP includes** always use `$_SERVER['DOCUMENT_ROOT']` (depth-independent):
  ```php
  include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
  ```
- **Web URLs** (href, action, Location headers, fetch) always start with `/`:
  ```html
  <a href="/auth/login.php">
  <form action="/api/login_user_api.php">
  header('Location: /admin/dashboard.php');
  ```
- Never use bare `index.php`/`connect.php` — they resolve relative to the calling page's folder and 404.

## Legacy URL compatibility

`.htaccess` rewrites every old root-level URL to its new location (e.g., `loginAdmin.php → auth/login_admin.php`, `addStaffcar.php → vehicles/staff/add.php`, all `*_api.php → api/*_api.php`). Old bookmarks and shipped Android APKs keep working. **Don't break or remove these rules** unless you also update the Flutter app's `api_service.dart`.

## Deploy pipeline

`git push origin main` → GitHub workflow runs on self-hosted runner `J1-OMEGA-30` (UiTM LAN) → `rclone sync` over SFTP to Hestia.

- Workflow: `.github/workflows/deploy-to-hestia.yml`
- Runner is required because Hestia is intranet-only (`10.0.26.208`, no public DNS for the subdomain). GitHub cloud runners cannot reach it.
- Deploy excludes `*.md`, `*.txt`, `*.zip`, `*.ps1`, `*.sh`, `*.cmd`, `pbsystem_app/`, `runner-setup/`, `test_*.php`, `debug.log`.

Manual deploy: `gh workflow run deploy-to-hestia.yml --repo meper77/pbsys --ref main`

## Database

Local dev: XAMPP MySQL with `root` / no password / db `neovtrack_db`. Production: `neovtrack_app` user / db `neovtrack_db`. `includes/connect.php` auto-detects env from `$_SERVER['HTTP_HOST']`. Schema in `database/neovtrack_db.sql`, migrations in `database/migrations/`.

Tables in active use: `user`, `admin`, `visitorcar`, `staffcar`, `studentcar`, `contractorcar`, `owner`, `user_sessions`, `last_activity`, `vehicle_reports`, `admin_action_logs`.

## Line endings

`.gitattributes` enforces LF everywhere. Don't change `core.autocrlf` on a clone — the Windows self-hosted runner would mass-rewrite to CRLF and the deploy would upload broken files. If `git status` shows phantom changes after clone, run `git config core.autocrlf false && git reset --hard`.

## Common tasks

- **Add a new page** in `auth/`, `admin/`, etc.: copy a sibling for the include/header pattern, add a row to `.htaccess` only if you need a legacy URL alias.
- **Add an API endpoint**: drop into `api/`, return JSON, document expected params at the top. If the Flutter app needs to call it, add the path constant to `pbsystem_app/lib/services/api_service.dart`.
- **DB schema change**: write a SQL file in `database/migrations/`, name it with a date prefix (e.g. `2026_05_14_add_X.sql`). Apply manually to production (no automated migration runner yet).

## Known issues / non-obvious gotchas

- **SQL injection risk**: most pages build queries by string-concatenating `$_POST`/`$_GET`. New code MUST use `mysqli_prepare` with bound params, and existing pages should be hardened opportunistically when touched.
- **The four `vehicles/{type}/` directories are ~70% duplicated**. A future refactor should extract per-category config (table name, field names, i18n labels) into `includes/vehicle_config.php` and collapse the 20 files into a parametrized handler. Out of scope right now because it requires per-category integration tests.
- **Flutter app** uses `String.fromEnvironment('API_BASE_URL', defaultValue: 'http://neovtrack.uitm.edu.my')` — the base URL is overridable at build/runtime.
- **PHPMailer in `vendor/PHPMailer/`** is a third-party library; do not modify. All `*.md` and `*.txt` files (including its `README.md` and `LICENSE`) are excluded from deploy by the workflow.

## When in doubt

- Backup tag: `pre-reorg-2026-05-14` (state before the May 2026 folder restructure)
- To inspect production state without re-pulling: snapshot at `~/pbsys-hestia-snapshot/` is refreshed by `sftp -r get`
- Hestia host: `10.0.26.208`, user `neovtrack` (SFTP-only — no shell access)
