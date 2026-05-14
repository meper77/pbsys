# NEO V-TRACK

Vehicle-sticker management system for UiTM. Web portal (PHP/MySQL) + Flutter mobile app, hosted internally at [neovtrack.uitm.edu.my](http://neovtrack.uitm.edu.my).

## What it does

Tracks vehicles authorised to enter UiTM premises across four categories: **staff**, **students**, **visitors**, and **contractors**. Each category has its own registration, search, update, and report flow. Admins manage stickers, run bulk imports, view statistics, and audit activity. Users (security gate operators) search vehicles by plate.

## Stack

- **Backend:** PHP 8, MySQL, vanilla Apache (Hestia panel)
- **Frontend:** Server-rendered PHP + Bootstrap + jQuery; some AJAX via `/api/*` endpoints
- **Mobile app:** Flutter (Android primary; iOS/web/desktop scaffolding present)
- **Mail:** PHPMailer
- **CI/CD:** GitHub Actions → self-hosted runner on UiTM LAN → rclone SFTP to Hestia

## Repository layout

```
auth/            Login, register, forgot-password, role selection
admin/           Admin pages — dashboard, user/admin CRUD, bulk import, reports, vehicle drilldown
vehicles/
  visitor/       list, add, update, delete, view (× same set per category)
  staff/
  student/
  contractor/
search/          Admin and user vehicle search
api/             JSON endpoints consumed by the Flutter app and AJAX
includes/        Shared PHP partials — connect.php, header.php, footer.php, menus.php, search_backend.php
assets/          css/, js/, images/
uploads/         User-uploaded files
vendor/PHPMailer/  Mail library
database/        SQL schema; migrations under database/migrations/
pbsystem_app/    Flutter app (see pbsystem_app/README.md)
.github/workflows/deploy-to-hestia.yml   Auto-deploy on push to main
.htaccess        Legacy URL rewrites + access controls
```

## Local development

**Prerequisites:** XAMPP (or any PHP 8 + MySQL stack), Git.

1. Clone:
   ```bash
   git clone https://github.com/meper77/pbsys.git
   cd pbsys
   ```
2. Import the schema:
   ```bash
   mysql -u root neovtrack_db < database/neovtrack_db.sql
   ```
   then apply any files in `database/migrations/`.
3. Drop the repo into your XAMPP `htdocs/` (or symlink it).
4. Visit `http://localhost/pbsys/` — `includes/connect.php` auto-detects local-vs-production by hostname.

Default local DB credentials (in `includes/connect.php`): `root` / blank password / db `neovtrack_db`.

## Deploying

Push to `main`. The GitHub Actions workflow at `.github/workflows/deploy-to-hestia.yml` runs on a self-hosted runner inside the UiTM network and `rclone sync`s the repo to Hestia. Live in ~30 seconds.

Manual trigger:
```bash
gh workflow run deploy-to-hestia.yml --repo meper77/pbsys --ref main
```

GitHub-hosted (cloud) runners **cannot** deploy this app — `10.0.26.208` is intranet-only.

## Mobile app

```bash
cd pbsystem_app
flutter pub get
flutter run            # for local dev
flutter build apk      # release build
```

The Android APK calls `/api/*` endpoints on `http://neovtrack.uitm.edu.my` by default. Override with `--dart-define=API_BASE_URL=http://your-host`.

## Contributing

- Follow the path conventions in [`CLAUDE.md`](CLAUDE.md) — PHP includes via `$_SERVER['DOCUMENT_ROOT']`, web URLs absolute (`/auth/login.php`)
- Don't add bare `index.php` / `connect.php` references — they break across subdirs
- LF line endings only (enforced by `.gitattributes`)
- New API endpoints go in `api/`; if the Flutter app needs to call them, update `pbsystem_app/lib/services/api_service.dart`

## Backup

Git tag `pre-reorg-2026-05-14` captures the codebase state immediately before the May 2026 folder restructure. Roll back with `git reset --hard pre-reorg-2026-05-14` if needed.

## License

Internal UiTM project — not licensed for external use.
