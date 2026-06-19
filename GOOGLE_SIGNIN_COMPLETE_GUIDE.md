# Google Sign-In: Complete Implementation Overview

**pbsys already has Google Sign-In built in. This doc covers what's there, what you need, and how to activate it.**

---

## Current State

### ✓ Already Implemented

```
auth/login.php                 ← Google button (renders on HTTPS)
auth/google_callback.php       ← JWT handler (POST from GIS)
includes/google_auth.php       ← Verification library (no external deps)
```

**Code handles:**
- Google Identity Services (GIS) card-tap flow
- RS256 JWT signature verification (OpenSSL)
- JWKS caching (6-hour cache + stale fallback)
- CSRF protection (double-submit token)
- Domain restriction via `hd` claim
- UiTM email domain enforcement (@uitm.edu.my)
- Allowlist-gated access (admin_allowlist table)
- Automatic account + session creation

---

## What You Need

1. **OAuth 2.0 Client ID** from Google Cloud Console (5 min)
2. **Add to secrets.php** (1 min)
3. **HTTPS on production** (already done on Hestia)

---

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                      Browser (User)                          │
└─────────────────────────────────────────────────────────────┘
                          │
                          ↓
         ┌────────────────────────────────┐
         │  https://neovtrack.uitm.edu.my │
         │      /auth/login.php           │
         │  (GIS button + script)         │
         └────────────────────────────────┘
                 │ (on click)
                 ↓
  ┌──────────────────────────────────────────┐
  │  Google Identity Services (gsi/client)   │
  │  Renders card → user selects account     │
  └──────────────────────────────────────────┘
                 │ (POST w/ JWT + CSRF token)
                 ↓
     ┌───────────────────────────────────┐
     │  /auth/google_callback.php        │
     │  1. Verify CSRF (double-submit)   │
     │  2. Fetch Google JWKS              │
     │  3. Verify JWT signature (RS256)   │
     │  4. Check email_verified flag      │
     │  5. Enforce @uitm.edu.my domain    │
     │  6. Enforce hd=uitm.edu.my         │
     │  7. Check admin_allowlist          │
     │  8. Create account + session       │
     └───────────────────────────────────┘
                 │ (redirect)
                 ↓
        ┌──────────────────────┐
        │  /admin/index_user   │
        │  (or /index.php)     │
        └──────────────────────┘
```

---

## Implementation Timeline

### Phase 1: Setup (Today - 10 min)

```
1. Create OAuth Client ID (Google Console)          → 5 min
2. Add to includes/secrets.php                      → 1 min
3. Run verify-google-signin.php                     → 2 min
4. Test locally with ?dev=1                         → 2 min
```

### Phase 2: Deploy (When Ready)

```
1. Verify HTTPS on Hestia                           → pre-done
2. rclone copy secrets.php to Hestia                → 1 min
3. Add yourself to allowlist (admin panel)          → 2 min
4. Test live sign-in                                → 2 min
```

### Phase 3: Onboard Users

```
1. Admins add staff emails to allowlist             → as needed
2. Users click "Sign in with UiTM Google"           → instant
3. Auto-routed to their dashboard                   → instant
```

---

## Google Cloud Console Walkthrough

### Step 1: Open Console
Go to https://console.cloud.google.com/

### Step 2: Create Project (if needed)
- Select organization or create new
- Name: `NEO V-TRACK` or similar
- Wait ~30s for project to be created

### Step 3: Enable APIs
- **APIs & Services** → **+ Enable APIs and Services**
- Search for: **Google+ API**
- Enable it

### Step 4: Create OAuth Credentials
- **APIs & Services** → **Credentials**
- **+ Create Credentials** → **OAuth 2.0 Client ID**
- App type: **Web application**
- Name: `NEO V-TRACK Web`

### Step 5: Configure Origins
Under **Authorized JavaScript origins**, add:
```
https://neovtrack.uitm.edu.my
```

Leave **Authorized redirect URIs** empty (GIS uses POST, not redirect).

### Step 6: Copy Client ID
- Click **Create**
- You'll see a modal with Client ID and Client Secret
- Copy the **Client ID** (looks like: `1234567890-abcd1234.apps.googleusercontent.com`)
- You can close the modal; the credentials are also in the table

---

## Local Setup (Step-by-Step)

### 1. Update secrets.php

Edit `includes/secrets.php` and add after the `otp_pepper` line:

```php
    // Google Sign-In (UiTM)
    'google_client_id' => '1234567890-abcd1234.apps.googleusercontent.com',  // from Google Console
    'google_hd'        => 'uitm.edu.my',  // restrict to UiTM domain
```

Full file should look like:

```php
<?php
return [
    'migrate_key' => 'ddcbf7574308e163e59eec829fddf096357fc1acfccbcc6a',
    'app_secret'  => 'b8f2a5c828efcc31ec87b0e8fbd46e5e05af0501c55fad2b7f9b5e9689a7f5d6',
    'otp_pepper'  => '9450c7fb01552e8cf1a22ee2b05a1769',

    // Google Sign-In (UiTM)
    'google_client_id' => '1234567890-abcd1234.apps.googleusercontent.com',
    'google_hd'        => 'uitm.edu.my',

    'smtp' => [
        'host'       => 'badang.uitm.edu.my',
        'port'       => 25,
        'secure'     => '',
        'auth'       => true,
        'username'   => '',
        'password'   => '',
        'from_email' => 'noreply@uitm.edu.my',
        'from_name'  => 'NEO V-TRACK',
        'timeout'    => 12,
    ],
];
```

### 2. Test Locally (HTTP Bypass)

```bash
cd C:\Users\User.J1-ALPHA-PENS\pbsys

# Start dev environment (if not already running)
./dev.sh

# Open browser
# http://localhost:8000/auth/login.php?dev=1
```

You'll see:
- A developer bypass form (since we're on HTTP, not HTTPS)
- Google button will be hidden with message "Google sign-in is being set up..."
- That's expected on HTTP

### 3. Verify Configuration

```bash
php verify-google-signin.php
```

Output:
```
╔══════════════════════════════════════════════════════════════╗
║         Google Sign-In Configuration Verification            ║
╚══════════════════════════════════════════════════════════════╝

[1] Client ID Configuration
    ✓ Client ID: 1234567890-abcd1...

[2] Hosted Domain (Optional Security Layer)
    ✓ Hosted Domain: uitm.edu.my

[3] HTTPS Requirement
    ⚠ HTTP detected (Google button will be hidden)

[4] Database & Tables
    ✓ otp_auth.php loaded (nv_table_exists available)

...

✓ Google Sign-In is ready!
```

---

## Production Deployment (Hestia)

### Prerequisites

- [ ] HTTPS certificate installed on Hestia (already done)
- [ ] Google Console has `https://neovtrack.uitm.edu.my` in authorized origins
- [ ] Client ID added to `includes/secrets.php`

### Deploy Steps

```bash
# 1. From your local machine:
cd C:\Users\User.J1-ALPHA-PENS\pbsys

# 2. Deploy secrets.php (one-time, manual; not in auto-sync)
rclone copy includes/secrets.php hestia:web/neovtrack.uitm.edu.my/public_html/includes/ --sftp-disable-hashcheck

# 3. Push any code changes (if you modified auth files)
git push origin main
# → GitHub Actions will deploy to Hestia via rclone sync

# 4. Verify production
# Open: https://neovtrack.uitm.edu.my/auth/login.php
# You should see the Google button

# 5. Verify deployment ran
# SSH to Hestia (or snapshot):
ls -la ~/pbsys-hestia-snapshot/auth/google_callback.php
```

---

## First Login (Admin Setup)

### 1. Get Allowlisted First

Before **anyone** can sign in, they must be in the `admin_allowlist` table. As developer, you can:

**Option A: SQL (direct)**

```bash
# SSH to Hestia or MySQL locally
mysql -u root neovtrack_db -e "
INSERT INTO admin_allowlist (email, role, is_active, is_locked) VALUES 
  ('your-email@uitm.edu.my', 'admin', 1, 0),
  ('admin2@uitm.edu.my', 'admin', 1, 0),
  ('user1@uitm.edu.my', 'user', 1, 0);
"
```

**Option B: Admin UI (once you're in)**

1. Someone with access adds you to allowlist
2. You sign in via Google
3. You go to admin panel → Admin Users → add more

### 2. Test Sign-In

1. Open `https://neovtrack.uitm.edu.my/auth/login.php`
2. Click "Sign in with UiTM Google"
3. Google card appears
4. Select your UiTM account
5. Should redirect to dashboard

### 3. Check Session

```php
// In any page after login:
echo $_SESSION['email'];        // your@uitm.edu.my
echo $_SESSION['email_Admin'];  // set if admin
```

---

## Troubleshooting Matrix

| Symptom | Cause | Fix |
|---------|-------|-----|
| "Google button doesn't show" | HTTP, not HTTPS | Check certificate on Hestia; GIS requires HTTPS |
| "Invalid audience" / "aud" error | Client ID mismatch | Verify Client ID in secrets.php matches Console exactly |
| "Not on allowlist" | Email not in table | Add to admin_allowlist (SQL or admin UI) |
| "Invalid signature" | Corrupted JWT | Rare; retry; check system clock on server |
| "JWKS fetch failed" | Network timeout | pbsys falls back to 6-hour cache; will work on retry |
| "Email not verified" | Google account issue | User's email not verified in Google account |
| "Bad domain" / `bad_domain` error | Email not @uitm.edu.my | Only UiTM emails allowed |
| "Domain" error (with hd set) | hd claim mismatch | Ensure hosted domain in Google Console matches config |

---

## Security Checklist

- [ ] **HTTPS enforced** (button won't render on HTTP)
- [ ] **JWT signature verified** (RS256 against Google's JWKS)
- [ ] **CSRF protection** (double-submit token check)
- [ ] **Email domain restricted** (@uitm.edu.my only)
- [ ] **Hosted domain optional** (google_hd = uitm.edu.my)
- [ ] **Allowlist gates access** (only pre-approved emails sign in)
- [ ] **Email must be verified** (in Google account)
- [ ] **Token expiry checked** (exp claim)
- [ ] **Issuer validated** (accounts.google.com only)
- [ ] **Audience checked** (Client ID match)

---

## Files Reference

| File | Lines | Purpose |
|------|-------|---------|
| `auth/login.php` | ~150 | Renders GIS button; enforces HTTPS + Client ID |
| `auth/google_callback.php` | ~50 | POST handler; CSRF + JWT verification |
| `includes/google_auth.php` | ~280 | JWT verification; JWKS fetch + cache |
| `includes/otp_auth.php` | ~500 | Session + allowlist (shared with OTP flow) |
| `includes/secrets_loader.php` | ~30 | Config loader (fallback to template) |
| `verify-google-signin.php` | ~200 | Diagnostic/verification script |

---

## Quick Commands

```bash
# Check if Client ID is set
php -r "require 'includes/secrets_loader.php'; echo nv_google_client_id() ?: 'NOT SET';"

# Run verification
php verify-google-signin.php

# View current secrets
php -r "require 'includes/secrets_loader.php'; print_r(nv_secrets());"

# Fetch Google JWKS (should cache to disk)
php -r "require 'includes/google_auth.php'; print_r(nv_google_jwks());"

# Test JWT verification (with dummy token)
php -r "require 'includes/google_auth.php'; var_dump(nv_google_verify_id_token('bad.jwt', \$c, \$e));"

# Allowlist check
php -r "
require 'includes/connect.php';
require 'includes/otp_auth.php';
\$role = nv_allowlist_role(\$con, 'test@uitm.edu.my');
echo 'Role: ' . (\$role ?? 'NOT ALLOWED');
"
```

---

## Next Steps

1. **Get Client ID** (Google Console, 5 min)
2. **Update secrets.php** (1 min)
3. **Run verify-google-signin.php** (2 min)
4. **Deploy to Hestia** (rclone copy, 1 min)
5. **Add yourself to allowlist** (SQL or admin UI, 2 min)
6. **Sign in via Google** (1 min)
7. **Onboard other users** (as needed)

---

## Support

- **Google Identity Services docs**: https://developers.google.com/identity/gis/overview
- **pbsys documentation**: See CLAUDE.md for architecture
- **Database**: admin_allowlist table in neovtrack_db

