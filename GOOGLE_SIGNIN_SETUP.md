# Google Sign-In Setup for NEO V-TRACK

**Status:** Already implemented in codebase (auth/google_callback.php + includes/google_auth.php).  
**What you need:** Google OAuth 2.0 Client ID + HTTPS (production only).

---

## Architecture

pbsys uses **Google Identity Services (GIS)** — the modern card-tap flow (no redirect):

1. **Frontend** (`auth/login.php`): Renders Google's button via `gsi/client` script
2. **Backend** (`auth/google_callback.php`): Receives JWT, verifies signature against Google's JWKS, establishes session
3. **Access control**: Allowlist-gated (only emails in `admin_allowlist` table may sign in, as their role)
4. **Session reuse**: Shares helpers with the OTP flow (`nv_establish_session`, `nv_ensure_account` from otp_auth.php)

---

## 1. Create OAuth 2.0 Client ID in Google Cloud Console

### Steps

1. Go to https://console.cloud.google.com/
2. Create a new project or select an existing one
3. Navigate to **APIs & Services** → **Credentials**
4. Click **+ Create Credentials** → **OAuth 2.0 Client ID**
5. Choose **Web application**
6. Add authorized JavaScript origins:
   ```
   https://neovtrack.uitm.edu.my
   ```
7. Leave "Authorized redirect URIs" blank (GIS uses POST to `/auth/google_callback.php`, not a redirect)
8. Click **Create**
9. Copy the **Client ID** (looks like `1234567890-abcd1234.apps.googleusercontent.com`)

---

## 2. Update Local Config (Development)

Edit `includes/secrets.php` and add the Client ID:

```php
<?php
return [
    'migrate_key' => 'ddcbf7574308e163e59eec829fddf096357fc1acfccbcc6a',
    'app_secret'  => 'b8f2a5c828efcc31ec87b0e8fbd46e5e05af0501c55fad2b7f9b5e9689a7f5d6',
    'otp_pepper'  => '9450c7fb01552e8cf1a22ee2b05a1769',

    // Google Sign-In (UiTM)
    'google_client_id' => '1234567890-abcd1234.apps.googleusercontent.com',  // YOUR CLIENT ID
    'google_hd'        => 'uitm.edu.my',  // Hard-restrict to UiTM domain (recommended)

    'smtp' => [...],
];
```

### Why `google_hd`?

The `hd` (hosted domain) claim in Google's ID token restricts logins to accounts from a specific Google Workspace or Google Edu domain. Setting it to `'uitm.edu.my'` means:
- Only UiTM Google Workspace accounts (with `@uitm.edu.my` primary email) can sign in
- Personal Google accounts (gmail.com, etc.) are rejected even if they added an `@uitm.edu.my` email alias
- Extra layer of isolation: optional, but **recommended for UiTM**

Leave blank (`''`) to allow any Google account with a UiTM email address.

---

## 3. Test Locally (with HTTP bypass)

pbsys will **NOT show the Google button on HTTP** — it requires HTTPS. But there's a developer bypass:

1. Start the dev environment:
   ```bash
   cd C:\Users\User.J1-ALPHA-PENS\pbsys
   ./dev.sh
   ```
   (Or manually start XAMPP MySQL + PHP server)

2. Open `http://localhost:8000/auth/login.php?dev=1`

3. You'll see a "Developer access" section. The bypass token is:
   ```
   hash_hmac('sha256', 'nv-dev-bypass|2023818464@student.uitm.edu.my', app_secret)
   ```
   Pre-computed for you — just look at the form or use the `/auth/login.php?dev=1` flow in the test script below.

---

## 4. Testing Flow

### Unit Test: ID Token Verification

```bash
php -r "
require 'includes/secrets_loader.php';
require 'includes/otp_auth.php';
require 'includes/google_auth.php';

// Fetch Google's JWKS (should cache to disk)
\$jwks = nv_google_jwks();
echo 'JWKS fetched: ' . (bool)\$jwks ? 'OK' : 'FAILED' . \"\\n\";

// Try verifying an invalid token (should fail gracefully)
\$error = null;
\$ok = nv_google_verify_id_token('invalid.jwt.here', \$claims, \$error);
echo 'Invalid token rejected: ' . (\$ok ? 'FAILED (should reject)' : \"OK (\$error)\") . \"\\n\";

// Check if Client ID is configured
\$cid = nv_google_client_id();
echo 'Client ID set: ' . (\$cid ? 'YES' : 'NO - configure includes/secrets.php') . \"\\n\";
"
```

### Integration Test: Browser Flow (once HTTPS is live)

1. Visit `https://neovtrack.uitm.edu.my/auth/login.php`
2. Click the "Sign in with UiTM Google" button
3. Select a UiTM Google account
4. Should POST to `/auth/google_callback.php`, verify JWT, then redirect to the dashboard

---

## 5. Production Deployment

### On Hestia (10.0.26.208)

1. Copy the real secrets file (contains your Google Client ID):
   ```bash
   # From your local machine
   rclone copy includes/secrets.php hestia:web/neovtrack.uitm.edu.my/public_html/includes/ --sftp-disable-hashcheck
   ```

2. Ensure the site is served over HTTPS (Hestia panel / SSL certificate)

3. Verify Google Console allows the origin: `https://neovtrack.uitm.edu.my`

4. Test: Visit `https://neovtrack.uitm.edu.my/auth/login.php` → should show the button

### Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| Google button doesn't appear | HTTP, not HTTPS | Check cert is installed; GIS requires secure context |
| "Not on allowlist" error | Email not in `admin_allowlist` | Add via admin panel |
| "Invalid audience" error | Client ID mismatch | Double-check Client ID in secrets.php matches Console |
| "JWKS fetch failed" | Network timeout | Check curl is installed; stale cache falls back |
| "Not verified" error | Email not verified in Google account | User must use a UiTM account with verified email |

---

## 6. How It Works (In Detail)

### Frontend (auth/login.php)

```html
<script src="https://accounts.google.com/gsi/client" async></script>

<div id="g_id_onload"
     data-client_id="YOUR_CLIENT_ID"
     data-login_uri="https://neovtrack.uitm.edu.my/auth/google_callback.php"
     data-auto_prompt="false"></div>

<div class="g_id_signin" data-type="standard" ...></div>
```

When user taps the button → Google renders a card → user selects account → **GIS POSTs** to `login_uri` with:
- `credential`: JWT (ID token)
- `g_csrf_token`: CSRF token (also set as cookie)

### Backend (auth/google_callback.php)

```php
// 1. Verify CSRF (double-submit check)
$cookieToken = $_COOKIE['g_csrf_token'];
$bodyToken   = $_POST['g_csrf_token'];
if (!hash_equals($cookieToken, $bodyToken)) exit;

// 2. Verify JWT signature against Google's JWKS
$email = nv_google_login($con, $_POST['credential'], $error);
// Inside: nv_google_verify_id_token() checks:
//   - RS256 signature
//   - issuer = accounts.google.com
//   - audience = CLIENT_ID
//   - exp > now
//   - email_verified = true

// 3. Enforce domain restriction (hd claim)
if (config['google_hd'] !== '')
    if (id_token.hd !== config['google_hd']) reject

// 4. Enforce UiTM email (@uitm.edu.my or @student.uitm.edu.my)
if (!nv_valid_uitm_email($email)) exit;

// 5. Allowlist gates access
$role = nv_allowlist_role($con, $email);
if ($role === null) exit;  // not allowed

// 6. Create account + establish session
nv_ensure_account($con, $email, $role);
nv_establish_session($con, $email, $role);
header('Location: /admin/index_user.php');
```

### Key Security Layers

1. **CSRF** (double-submit token)
2. **JWT signature** (RS256 with Google's public keys)
3. **Domain restriction** (hosted-domain claim)
4. **Email domain** (only @uitm.edu.my / @student.uitm.edu.my)
5. **Allowlist** (only pre-approved emails get in)

---

## 7. Adding Users to the Allowlist

Once Google Sign-In is live, an admin can go to **Admin Panel** → **Admin Users** (or wherever the allowlist UI is) and add emails with their assigned role. The developer email (`2023818464@student.uitm.edu.my`) is pre-seeded as a locked entry.

---

## 8. Android App (pbsystem_app)

If you want Google Sign-In in the Flutter app too, you'll need:

1. **Create an OAuth 2.0 Client ID for Android** (SHA-1 fingerprint of your signing key)
2. Update `pbsystem_app/lib/services/api_service.dart` to call `/api/login_user_api.php` with the Google ID token
3. Implement native Google Sign-In library (google_sign_in plugin)

For now, the **web-only flow** is live: web → Google → session cookie → API endpoints work.

---

## Checklist

- [ ] Created OAuth 2.0 Client ID in Google Cloud Console
- [ ] Added HTTPS origin to authorized origins
- [ ] Updated `includes/secrets.php` with `google_client_id`
- [ ] Optionally set `google_hd` to restrict to UiTM domain
- [ ] Tested locally with `?dev=1` bypass
- [ ] Ensured `/includes/google_auth.php` is loaded (already in codebase)
- [ ] Verified `admin_allowlist` table exists and has role column
- [ ] Deployed to Hestia with secrets.php
- [ ] Verified HTTPS is enabled on production
- [ ] Tested live sign-in with UiTM account
- [ ] Added first admin user via allowlist

---

## References

- **Google Identity Services docs**: https://developers.google.com/identity/gis/overview
- **ID token verification**: https://developers.google.com/identity/gis/web-native-flow
- **JWKS endpoint**: https://www.googleapis.com/oauth2/v3/certs
- **pbsys auth**: `/auth/login.php`, `/auth/google_callback.php`, `/includes/google_auth.php`
- **Allowlist management**: `admin_allowlist` table (managed via admin UI)

