# Google Sign-In Quick Setup

Current status: **Code is ready**, only need OAuth credentials + config.

---

## Step 1: Get OAuth Client ID (5 min)

1. Go to https://console.cloud.google.com/
2. Create or select a project
3. **APIs & Services** → **Credentials** → **+ Create Credentials** → **OAuth 2.0 Client ID**
4. Choose **Web application**
5. Add **Authorized JavaScript origins:**
   ```
   https://neovtrack.uitm.edu.my
   ```
6. Click **Create**
7. Copy the Client ID (format: `123456789-abcd.apps.googleusercontent.com`)

---

## Step 2: Add Client ID to Local Config (1 min)

Run this to patch your secrets.php:

```bash
cd C:\Users\User.J1-ALPHA-PENS\pbsys

# Backup
cp includes/secrets.php includes/secrets.php.bak

# Add Google config (replace with your actual Client ID)
cat >> includes/secrets.php.temporary << 'EOF'
// Temporary marker
EOF

# Edit manually:
#   1. Open includes/secrets.php in an editor
#   2. Find the closing ];
#   3. Before it, add:
#      'google_client_id' => '1234567890-abcd.apps.googleusercontent.com',
#      'google_hd'        => 'uitm.edu.my',
```

**Or use PHP directly:**

```php
php -r "
\$file = 'includes/secrets.php';
\$content = file_get_contents(\$file);
\$content = str_replace(
    \"    ],\\n];\",
    \"        'google_client_id' => '1234567890-abcd.apps.googleusercontent.com',
        'google_hd'        => 'uitm.edu.my',
    ],
];\",
    \$content
);
file_put_contents(\$file, \$content);
echo 'Updated includes/secrets.php';
"
```

---

## Step 3: Test Locally (Dev Bypass)

Start your dev environment:

```bash
cd C:\Users\User.J1-ALPHA-PENS\pbsys
./dev.sh
```

Then open:
```
http://localhost:8000/auth/login.php?dev=1
```

You'll see a "Developer access" form (since we're on HTTP, the Google button is hidden). The bypass token is pre-computed. You can verify the flow works.

---

## Step 4: Test HTTPS on Production

Once Hestia has HTTPS configured:

```bash
# Copy secrets to production (Hestia)
rclone copy includes/secrets.php hestia:web/neovtrack.uitm.edu.my/public_html/includes/ --sftp-disable-hashcheck
```

Then visit:
```
https://neovtrack.uitm.edu.my/auth/login.php
```

You should see the "Sign in with UiTM Google" button.

---

## Step 5: Add Your Account to Allowlist

Before you can log in, you must be on the allowlist. As admin, go to:
```
/admin/...  (admin panel)
```

Find **Admin Users** or **Allowlist Management** and add:
- Email: your-email@uitm.edu.my
- Role: admin (or user)

The developer email is pre-seeded: `2023818464@student.uitm.edu.my`

---

## Troubleshooting

### "Google button doesn't show"
- Check HTTP vs HTTPS (button only renders on HTTPS)
- Verify Client ID is in `includes/secrets.php`
- Check browser console for errors

### "Invalid audience" error after clicking button
- Client ID in secrets.php doesn't match Google Console
- Copy-paste the full ID exactly

### "Not on allowlist" after successful Google auth
- Add your email to `admin_allowlist` table
- Check `is_active = 1` (not suspended)

### "Network timeout" or "JWKS fetch failed"
- Rare; happens when Google's cert server is slow
- pbsys falls back to stale cache (good for resilience)

---

## Files Involved

| File | Purpose |
|------|---------|
| `/auth/login.php` | Renders Google button (checks HTTPS + Client ID) |
| `/auth/google_callback.php` | POST handler for Google's JWT |
| `/includes/google_auth.php` | JWT verification + JWKS caching |
| `/includes/secrets.php` | Runtime config (Client ID + secrets) |
| `/includes/otp_auth.php` | Session + allowlist (shared with OTP flow) |

---

## What's Already Done

✓ Frontend button (GIS integration)  
✓ Backend JWT verification (RS256, no external lib)  
✓ JWKS caching (6h disk cache + stale fallback)  
✓ CSRF protection (double-submit token)  
✓ Domain restriction (hd claim check)  
✓ Email domain enforcement (@uitm.edu.my)  
✓ Allowlist gating (admin_allowlist table)  
✓ Session establishment (reuses OTP helpers)  
✓ HTTPS requirement (button hidden on HTTP)  

**Just add the Client ID and you're done.**

