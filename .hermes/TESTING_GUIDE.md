# Minor Upgrade Testing Guide

## Pre-Deployment Testing Checklist

### Phase 1: Database Schema ✓
- [x] vehicle_status table migration created
- [x] vehicle_search_cache table migration created
- [x] Schema file updated
- **Testing:** Run migrations against staging DB (before prod deployment)

### Phase 2: Frontend Foundation ✓
- [x] Responsive CSS created with media queries
- [x] Bulk delete UI components created (JS + PHP)

**Testing:**
```bash
# Check file exists
ls -la assets/css/responsive.css
ls -la assets/js/bulk-delete.js
ls -la includes/bulk_delete_component.php

# Test on mobile browser (dev tools)
# - Check breakpoints at 768px, 1024px, 1200px
# - Test fluid typography scaling
# - Test checkbox state changes
```

### Phase 3: Backend APIs ✓
- [x] vehicle_search_api.php created
- [x] bulk_delete_api.php created
- [x] bulk_import_xlsx_api.php created

**Testing:**
```bash
# Test vehicle search API
curl -X GET "http://localhost:8000/api/vehicle_search_api.php?action=search&query=ABC"

# Test bulk delete API (requires admin session)
curl -X POST "http://localhost:8000/api/bulk_delete_api.php" \
  -H "Content-Type: application/json" \
  -d '{
    "action": "delete",
    "vehicle_type": "staffcar",
    "ids": [1, 2, 3]
  }'

# Test XLSX import API
# - Test file upload with XLSX file
# - Verify MIME type validation
# - Check vehicle creation in database
```

### Phase 4: Integration ✓
- [x] Staff vehicle list updated with bulk delete
- [x] Responsive CSS linked globally in header

**Testing:**
```bash
# Visit staff vehicle list page
http://localhost:8000/vehicles/staff/list.php

# Test:
1. Checkboxes appear in each row
2. Select-all checkbox in header works
3. Bulk delete button appears above table
4. Responsive design works on mobile (dev tools)
5. Click delete → API call succeeds → page updates
```

### Phase 5: Permissions & Security ✓
- [x] SMTP password reset page created
- [x] Permission checks added to admin pages

**Testing:**
```bash
# Test permission checks
1. Log in as regular user
2. Try accessing /admin/users.php
3. Should redirect to dashboard
4. Try accessing /admin/reports.php
5. Should redirect to dashboard

# Test SMTP password reset
1. Navigate to /auth/forgot_password_smtp.php
2. Enter email address
3. Should send email (check mail logs)
4. Click reset link in email
5. Should allow password reset

# Permission-based access
- Admin: Can view all pages (users, admin, reports, import)
- User: Can only view vehicle pages, dashboard (no admin pages)
```

## Local Testing Environment

### Start Development Stack
```bash
cd /c/Users/User.J1-ALPHA-PENS/pbsys
bash dev.sh
# Starts: MySQL, PHP server (8000), Android emulator, Flutter app
```

### Access URLs
- Dashboard: http://localhost:8000/
- Staff vehicles: http://localhost:8000/vehicles/staff/list.php
- Vehicle registration: http://localhost:8000/vehicles/daftar_kenderaan.php
- Admin users: http://localhost:8000/admin/users.php
- Bulk import: http://localhost:8000/admin/bulk_import.php
- Password reset: http://localhost:8000/auth/forgot_password_smtp.php

### Database Access
```bash
# XAMPP MySQL (dev)
# Host: localhost
# User: root
# Password: (blank)
# Database: neovtrack_db

# Check tables
SELECT * FROM vehicle_status;
SELECT * FROM vehicle_search_cache;
SELECT * FROM password_reset_tokens;
```

## Regression Testing Checklist

### Core Vehicle Operations
- [ ] Add new staff vehicle
- [ ] Edit existing vehicle
- [ ] View vehicle details
- [ ] Search vehicle by plate number
- [ ] Search vehicle by owner name
- [ ] Delete single vehicle
- [ ] Delete multiple vehicles (bulk)

### M:M Relationships
- [ ] Register vehicle with existing user (autocomplete)
- [ ] Register vehicle with new user
- [ ] View vehicle with multiple users assigned
- [ ] Add user to existing vehicle
- [ ] Remove user from vehicle

### Import/Export
- [ ] Download XLSX template
- [ ] Upload valid XLSX file
- [ ] Verify unique plate number enforcement
- [ ] Verify status records created
- [ ] Test error handling (invalid format, duplicates)

### Responsive Design
- [ ] Mobile view (< 768px)
  - [ ] Navigation collapses
  - [ ] Tables stack vertically
  - [ ] Buttons stack vertically
  - [ ] Touch targets > 44px
- [ ] Tablet view (768px - 1024px)
  - [ ] 2-column layouts work
  - [ ] Tables scroll horizontally
- [ ] Desktop view (> 1024px)
  - [ ] 3-column layouts work
  - [ ] Full tables display

### Permission Tests
- [ ] Admin can access all pages
- [ ] Admin cannot delete own admin account
- [ ] User cannot access admin pages
- [ ] User cannot view admin/user lists
- [ ] User cannot view reports
- [ ] User cannot import vehicles
- [ ] SMTP password reset works for both admin and user
- [ ] Permission denied returns 403 status

## Deployment Steps

### 1. Pre-Deployment
```bash
cd /c/Users/User.J1-ALPHA-PENS/pbsys
git status  # Should be clean
git log --oneline | head -15  # Verify all 13 commits
```

### 2. Run Tests Locally
- Execute all regression tests above
- Test all APIs with sample data
- Verify responsive design on multiple screen sizes

### 3. Deploy to Hestia
```bash
git push origin main
gh workflow run deploy-to-hestia.yml --repo meper77/pbsys --ref main
```

### 4. Post-Deployment (Hestia 10.0.26.208)
```bash
# SSH to Hestia (SFTP only - may need jumphost)
# Execute migrations
mysql -u neovtrack_app -p neovtrack_db < database/migrations/2026_05_26_create_vehicle_status_table.sql
mysql -u neovtrack_app -p neovtrack_db < database/migrations/2026_05_26_create_vehicle_search_cache.sql

# Rebuild search cache
curl -X POST "http://neovtrack.uitm.edu.my/api/vehicle_search_api.php" \
  -H "Content-Type: application/json" \
  -d '{"action": "update_cache"}'

# Test in browser
# http://neovtrack.uitm.edu.my/vehicles/staff/list.php
```

### 5. Monitoring
- Check error logs: /logs/php-error.log
- Monitor admin_action_logs for bulk delete operations
- Monitor password_reset_tokens for expiry
- Test autocomplete search performance

## Known Issues & Workarounds

### Issue: SMTP password reset not sending emails
- Check SMTP configuration in auth/forgot_password_smtp.php
- Verify mail.uitm.edu.my is accessible
- Check PHPMailer logs
- Ensure SMTP_PASSWORD environment variable is set

### Issue: Bulk delete not working
- Check admin_action_logs table exists
- Verify user has admin session
- Check browser console for JavaScript errors
- Verify bulk_delete_api.php endpoint is accessible

### Issue: Vehicle search cache not updating
- Run: `curl http://localhost:8000/api/vehicle_search_api.php?action=update_cache`
- Check vehicle_search_cache table for records
- Verify full-text index is created

## Rollback Plan

If issues occur in production:
```bash
# Restore previous version
git revert HEAD~13  # Revert all 13 commits
git push origin main

# Or rollback specific features
git revert ed82f33  # Revert permissions
git revert 34b695c  # Revert responsive CSS
git push origin main
```

## Next Steps (Future Enhancements)

1. **Extend bulk delete to all vehicle types** (visitor, student, contractor)
2. **Autocomplete search across all pages** (implement on visitor/student/contractor)
3. **Dashboard refresh** with new assets and responsive grid
4. **Remove IC NUMBER as primary key** (use staff#/matric#/phone instead)
5. **Status table integration** (show active/inactive toggle on list pages)
6. **CSV export** (export vehicle list to CSV)

---

**Test Environment:** C:\Users\User.J1-ALPHA-PENS\pbsys
**Production:** http://neovtrack.uitm.edu.my (Hestia 10.0.26.208)
**Commits:** 13 total, starting from 988f077
