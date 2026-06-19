# Google Sign-In Setup — Documentation Index

## 📚 Documentation Files (5 files created)

### 1. **GOOGLE_SIGNIN_QUICK_START.md** (4 KB)
**Best for:** Getting started immediately
- 5-step setup process
- Google Console walkthrough
- Local testing
- Production deployment
- Troubleshooting quick-ref table

👉 **Start here if you want to be live in 15 minutes.**

---

### 2. **GOOGLE_SIGNIN_SETUP.md** (9 KB)
**Best for:** Understanding the complete picture
- Full architecture overview
- JWT verification flow
- Security layers explained
- Android app integration hints
- Complete checklist

👉 **Read this if you want to understand how it works.**

---

### 3. **GOOGLE_SIGNIN_COMPLETE_GUIDE.md** (13 KB)
**Best for:** Reference & troubleshooting
- Current implementation status
- Detailed step-by-step walkthrough
- Architecture diagram
- Google Console GUI walkthrough
- Full troubleshooting matrix
- Security checklist
- Quick commands reference

👉 **Use this as your reference when things don't work.**

---

## 🛠️ Automation Scripts (2 files)

### 4. **verify-google-signin.php** (6 KB, executable)
**Diagnostic tool**

Checks:
- ✓ Client ID configured
- ✓ Hosted domain set (optional)
- ✓ HTTPS available
- ✓ Database tables present
- ✓ Network connectivity
- ✓ All required files present
- ✓ JWKS fetchable from Google

Run:
```bash
php verify-google-signin.php
```

Output shows green ✓, yellow ⚠, or red ✗ indicators with actionable next steps.

---

### 5. **google-signin-setup.sh** (2 KB, executable)
**Automated config updater**

Automatically:
1. Backs up `includes/secrets.php`
2. Adds `google_client_id` and `google_hd`
3. Verifies configuration

Run:
```bash
./google-signin-setup.sh "1234567890-abcd.apps.googleusercontent.com" "uitm.edu.my"
```

---

## 🚀 Quick Start (5 Minutes)

1. **Get Client ID** (Google Console)
   - https://console.cloud.google.com/
   - Credentials → Create OAuth 2.0 Client ID → Web application
   - Copy Client ID

2. **Add to Config**
   ```bash
   # Either manual: edit includes/secrets.php
   # Or automated:
   ./google-signin-setup.sh "YOUR_CLIENT_ID"
   ```

3. **Verify Setup**
   ```bash
   php verify-google-signin.php
   ```

4. **Test Locally**
   ```bash
   ./dev.sh
   # Visit: http://localhost:8000/auth/login.php?dev=1
   ```

5. **Deploy to Hestia**
   ```bash
   rclone copy includes/secrets.php hestia:web/neovtrack.uitm.edu.my/public_html/includes/ --sftp-disable-hashcheck
   ```

---

## 📋 What's Already in the Code

✓ Google Identity Services button (auth/login.php)
✓ JWT POST handler (auth/google_callback.php)
✓ Full JWT verification with JWKS (includes/google_auth.php)
✓ CSRF protection (double-submit token)
✓ Domain restriction (@uitm.edu.my)
✓ Hosted domain enforcement (optional)
✓ Allowlist access control (admin_allowlist table)
✓ Session establishment
✓ Automatic account creation
✓ HTTPS enforcement (button hidden on HTTP)

**All that's missing:** Google OAuth Client ID in `includes/secrets.php`.

---

## 🔒 Security Features

- **JWT Signature Verification** (RS256 against Google's JWKS)
- **CSRF Protection** (double-submit token check)
- **Email Domain Restriction** (@uitm.edu.my only)
- **Hosted Domain** (optional: uitm.edu.my)
- **Email Verification** (email_verified flag required)
- **Allowlist Gating** (only pre-approved emails)
- **HTTPS Requirement** (button won't render on HTTP)
- **Token Expiry Validation** (exp claim checked)
- **Issuer Validation** (accounts.google.com only)
- **Audience Validation** (Client ID match)

---

## 🗂️ File Locations

```
pbsys/
├── GOOGLE_SIGNIN_SETUP.md              ← Architecture & detailed flow
├── GOOGLE_SIGNIN_QUICK_START.md        ← Fast setup guide
├── GOOGLE_SIGNIN_COMPLETE_GUIDE.md     ← Reference & troubleshooting
├── google-signin-setup.sh              ← Automated config (run this)
├── verify-google-signin.php            ← Diagnostic check (run this)
│
├── auth/
│   ├── login.php                       ← Google button (already working)
│   └── google_callback.php             ← JWT handler (already working)
│
└── includes/
    ├── google_auth.php                 ← JWT verification (already working)
    ├── secrets.php                     ← UPDATE: Add Client ID here
    ├── secrets_loader.php              ← Config loader (already working)
    └── otp_auth.php                    ← Session helpers (already working)
```

---

## 🎯 Next Actions

Choose your path:

### Fast Path (15 min)
```
1. Read: GOOGLE_SIGNIN_QUICK_START.md
2. Run: ./google-signin-setup.sh "YOUR_CLIENT_ID"
3. Run: php verify-google-signin.php
4. Deploy: rclone copy ...
5. Test: https://neovtrack.uitm.edu.my/auth/login.php
```

### Complete Path (30 min)
```
1. Read: GOOGLE_SIGNIN_COMPLETE_GUIDE.md (reference)
2. Follow Google Console walkthrough (section 3)
3. Update secrets.php manually
4. Run verification script
5. Follow deployment steps
6. Add yourself to allowlist
7. Test sign-in
```

### Deep Dive (1 hour)
```
1. Read: GOOGLE_SIGNIN_SETUP.md (architecture)
2. Read: GOOGLE_SIGNIN_COMPLETE_GUIDE.md (complete)
3. Review code: auth/google_callback.php + includes/google_auth.php
4. Review database: admin_allowlist schema
5. Run all scripts
6. Test all scenarios (local, dev bypass, production)
```

---

## 📞 Troubleshooting Reference

| Issue | Read | Check |
|-------|------|-------|
| Button doesn't show | QUICK_START | HTTPS enabled? Client ID in secrets.php? |
| "Invalid audience" | COMPLETE_GUIDE (Troubleshooting Matrix) | Client ID matches Console? |
| "Not on allowlist" | COMPLETE_GUIDE (First Login section) | Email in admin_allowlist? |
| JWKS fetch timeout | SETUP.md (Security layers) | Network OK? Fallback to cache? |
| Email not verified | COMPLETE_GUIDE (Google account issue) | Verify email in Google account |

---

## 💾 Summary

You have:
- ✓ **3 comprehensive guides** (quick, detailed, reference)
- ✓ **1 automated setup script** (one-liner config)
- ✓ **1 diagnostic script** (verify everything works)
- ✓ **Ready-to-go code** (all backend already implemented)

**Status: 95% done. Just add Google Client ID and deploy.**

---

## Files Created Today

| File | Size | Type | Purpose |
|------|------|------|---------|
| GOOGLE_SIGNIN_SETUP.md | 9 KB | Guide | Architecture + security layers |
| GOOGLE_SIGNIN_QUICK_START.md | 4 KB | Guide | Fast setup (15 min) |
| GOOGLE_SIGNIN_COMPLETE_GUIDE.md | 13 KB | Guide | Complete reference |
| google-signin-setup.sh | 2 KB | Script | Automated config update |
| verify-google-signin.php | 6 KB | Script | Diagnostic checker |

Total: 34 KB of guidance + automation.

---

**You're ready to go. Pick a guide and follow the steps.**

