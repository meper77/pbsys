# DEPLOYMENT CHECKLIST - Admin Features & Dashboard
## NEO.V-TRACK Vehicle Management System
**Date:** 2026-05-14
**Version:** 1.0
**Target:** Hestia Server (neovtrack.uitm.edu.my)

---

## 📋 QUICK START

### Option 1: Automated Deployment (via GitHub Actions)
1. Add these secrets to GitHub repository settings:
   - `HESTIA_HOST` = neovtrack.uitm.edu.my
   - `HESTIA_USER` = your_sftp_username
   - `HESTIA_PASSWORD` = your_sftp_password
2. Push changes to `main` branch
3. GitHub Actions will automatically deploy files
4. Monitor progress at: https://github.com/meper77/pbsys/actions

### Option 2: Manual Deployment (via Hestia File Manager)
1. Log into Hestia Control Panel
2. File Manager → neovtrack.uitm.edu.my
3. Navigate to public_html
4. Upload each PHP file (see Files to Deploy below)
5. Set permissions to 644
6. Apply database migration via phpMyAdmin

### Option 3: SFTP Deployment (via WinSCP/FileZilla)
1. Open SFTP client
2. Connect to `hestia.uitm.edu.my:22`
3. Navigate to public_html
4. Upload all PHP files
5. Set permissions to 644

---

## 📁 FILES TO DEPLOY

### PHP Files (web application)
**Location:** public_html/

- [ ] `admin_management_api.php` (200 lines)
  - Purpose: Add/manage admins and users
  - Endpoint: POST/GET admin_management_api.php?action=...
  - Dependencies: connect.php, session

- [ ] `vehicle_stats_api.php` (180 lines)
  - Purpose: Vehicle statistics and filtered lists
  - Endpoint: GET vehicle_stats_api.php?action=...
  - Dependencies: connect.php, session

- [ ] `sticker_management_api.php` (160 lines)
  - Purpose: Remove/restore vehicle stickers
  - Endpoint: POST sticker_management_api.php?action=...
  - Dependencies: connect.php, session

- [ ] `superadmin.php` (600 lines)
  - Purpose: Superadmin dashboard with statistics
  - Access: http://neovtrack.uitm.edu.my/superadmin.php
  - Authorization: userid <= 10 only

- [ ] `vehicle_list_drill_down.php` (370 lines)
  - Purpose: Filtered vehicle records by type
  - Access: http://neovtrack.uitm.edu.my/vehicle_list_drill_down.php?type=[staff|student|visitor|contractor]
  - Used by: superadmin.php statistics drill-down

### Database Files
**Location:** Execute in phpMyAdmin or via CLI

- [ ] `database/migration_admin_features.sql` (120 lines)
  - Schema changes:
    - Add `created_at`, `updated_at`, `sticker_status` to existing tables
    - Add unique constraints on primary identifiers
    - Create `visitorcar` table
    - Create `contractorcar` table
    - Create `admin_users` and `admin_action_logs` tables

---

## 🚀 DEPLOYMENT STEPS

### STEP 1: Verify Local Repository
```bash
# Check local files exist
ls -la admin_management_api.php
ls -la vehicle_stats_api.php
ls -la sticker_management_api.php
ls -la superadmin.php
ls -la vehicle_list_drill_down.php
ls -la database/migration_admin_features.sql

# Verify git commits
git log --oneline -5
```

### STEP 2: Push to Repository (if not already done)
```bash
git add .
git commit -m "Add admin features deployment files"
git push origin main
```

### STEP 3: Upload PHP Files to Hestia
**Using Hestia File Manager:**
1. Log in to https://hestia.uitm.edu.my:8083
2. File Manager → neovtrack.uitm.edu.my
3. Navigate to public_html
4. Upload each .php file:
   - admin_management_api.php
   - vehicle_stats_api.php
   - sticker_management_api.php
   - superadmin.php
   - vehicle_list_drill_down.php
5. For each file:
   - Right-click → Permissions
   - Set to 644
   - Click OK

**Using SFTP (WinSCP/FileZilla):**
1. Host: hestia.uitm.edu.my (port 22)
2. Username: [your_sftp_username]
3. Password: [your_sftp_password]
4. Navigate to public_html
5. Upload all 5 PHP files
6. Select all files → Right-click → Properties → Permissions → Set to 644

**Using Command Line (if SSH available):**
```bash
# From local repository
scp admin_management_api.php user@hestia.uitm.edu.my:~/public_html/
scp vehicle_stats_api.php user@hestia.uitm.edu.my:~/public_html/
scp sticker_management_api.php user@hestia.uitm.edu.my:~/public_html/
scp superadmin.php user@hestia.uitm.edu.my:~/public_html/
scp vehicle_list_drill_down.php user@hestia.uitm.edu.my:~/public_html/

# Set permissions
ssh user@hestia.uitm.edu.my 'chmod 644 ~/public_html/*.php'
```

### STEP 4: Apply Database Migration
**Via phpMyAdmin:**
1. Log into Hestia Control Panel → Databases
2. Click on neovtrack_db → phpMyAdmin
3. Click SQL tab
4. Copy entire content of `database/migration_admin_features.sql`
5. Paste into SQL editor
6. Click "Go" or "Execute"
7. Verify: Check tables for new columns and tables

**Via SSH/CLI:**
```bash
ssh user@hestia.uitm.edu.my
mysql -u [username] -p [password] [database_name] < /path/to/migration_admin_features.sql
# OR
mysql -u [username] -p [password]
USE neovtrack_db;
[paste contents of migration_admin_features.sql]
```

### STEP 5: Verify Database Changes
**In phpMyAdmin, verify:**
- [ ] `staffcar` table has columns: created_at, updated_at, sticker_status
- [ ] `studentcar` table has columns: created_at, updated_at, sticker_status
- [ ] `visitorcar` table exists with: visitorid, name, phone, ic_passport (unique), model, platenum, sticker, sticker_status, created_at, updated_at
- [ ] `contractorcar` table exists with: contractorid, name, phone, ic_passport (unique), company_name, model, platenum, sticker, sticker_status, created_at, updated_at
- [ ] `admin_users` table exists
- [ ] `admin_action_logs` table exists

---

## ✅ POST-DEPLOYMENT TESTING

### Test 1: Admin Management API
```bash
# List admins (requires admin login)
curl -X GET 'https://neovtrack.uitm.edu.my/admin_management_api.php?action=list_admins' \
  -H 'Cookie: email_Admin=admin@example.com' \
  -H 'Content-Type: application/json'

# Expected response:
# {"success":1,"data":[...],"message":"Admin list retrieved"}
```

### Test 2: Vehicle Statistics API
```bash
curl -X GET 'https://neovtrack.uitm.edu.my/vehicle_stats_api.php?action=get_stats' \
  -H 'Cookie: email_Admin=admin@example.com'

# Expected response:
# {"success":1,"staff_count":N,"student_count":N,"visitor_count":N,"contractor_count":N}
```

### Test 3: Superadmin Dashboard
```bash
# Open in browser (ensure logged in as admin first)
https://neovtrack.uitm.edu.my/superadmin.php
```

### Test 4: Statistics Drill-Down
```bash
# Click on any statistic card in superadmin dashboard
# Should navigate to vehicle_list_drill_down.php?type=[vehicle_type]
# Should display filtered list of vehicles with timestamps
```

### Test 5: Sticker Management API
```bash
curl -X POST 'https://neovtrack.uitm.edu.my/sticker_management_api.php?action=remove_sticker' \
  -H 'Cookie: email_Admin=admin@example.com' \
  -d 'vehicle_id=1&vehicle_type=staff'

# Expected response:
# {"success":1,"message":"Sticker removed successfully"}
```

---

## 🔄 ROLLBACK INSTRUCTIONS

If deployment needs to be reverted:

### Remove PHP Files
```bash
# Via SSH or Hestia File Manager, delete:
- admin_management_api.php
- vehicle_stats_api.php
- sticker_management_api.php
- superadmin.php
- vehicle_list_drill_down.php
```

### Revert Database Changes
**In phpMyAdmin, execute:**
```sql
-- Drop new tables
DROP TABLE IF EXISTS admin_action_logs;
DROP TABLE IF EXISTS admin_users;
DROP TABLE IF EXISTS visitorcar;
DROP TABLE IF EXISTS contractorcar;

-- Remove columns from updated tables
ALTER TABLE staffcar DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at, DROP COLUMN IF EXISTS sticker_status;
ALTER TABLE studentcar DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at, DROP COLUMN IF EXISTS sticker_status;
ALTER TABLE admin DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;
ALTER TABLE user DROP COLUMN IF EXISTS created_at, DROP COLUMN IF EXISTS updated_at;

-- Drop unique constraints (if needed)
ALTER TABLE staffcar DROP INDEX IF EXISTS unique_staffno;
ALTER TABLE studentcar DROP INDEX IF EXISTS unique_matric;
```

---

## 🐛 TROUBLESHOOTING

### Problem: 403 Forbidden Error
**Solution:**
- Check file permissions (should be 644)
- Verify owner/group is web user (www-data or nobody)
- Check firewall rules

### Problem: 404 Not Found
**Solution:**
- Verify file placement in correct public_html directory
- Check virtual host configuration
- Verify file names are exactly as listed (case-sensitive)

### Problem: Database Connection Error
**Solution:**
- Verify connect.php is in same directory as new PHP files
- Check database credentials in connect.php
- Test database connection: `php test_connection.php`

### Problem: API Returns 403 (Access Denied)
**Solution:**
- Ensure you're logged in as admin
- Check that userid <= 10 (superadmin requirement)
- Verify session cookie is properly set

### Problem: Statistics Don't Display
**Solution:**
- Check database tables exist and have data
- Verify vehicle counts query in vehicle_stats_api.php
- Check browser console for JavaScript errors
- Clear browser cache

### Problem: Drill-Down Links Not Working
**Solution:**
- Verify vehicle_list_drill_down.php is uploaded
- Check that type parameter is being passed correctly
- Verify vehicle type tables exist (staffcar, studentcar, visitorcar, contractorcar)
- Check browser console for navigation errors

---

## 📞 SUPPORT

For issues or questions:
1. Check ADMIN_FEATURES_DEPLOYMENT.txt for detailed documentation
2. Review deploy-to-hestia.ps1 for deployment automation help
3. Check GitHub Actions logs at: https://github.com/meper77/pbsys/actions
4. Contact system administrator

---

## 📊 DEPLOYMENT RECORD

**Deployed Date:** _______________
**Deployed By:** _______________
**Status:** [ ] Success [ ] Failed [ ] Partial
**Notes:** 
```
_______________________________________________
_______________________________________________
```

**Verified By:** _______________
**Date Verified:** _______________

---

## 🎯 SUCCESS CRITERIA

Deployment is successful when:
- [ ] All 5 PHP files are in public_html with 644 permissions
- [ ] Database migration executed without errors
- [ ] All new tables and columns exist in database
- [ ] Admin can access https://neovtrack.uitm.edu.my/superadmin.php
- [ ] Statistics display correctly on dashboard
- [ ] Clicking statistics shows filtered vehicle records
- [ ] Timestamps display on all vehicle records
- [ ] Sticker status badges are visible
- [ ] API endpoints respond with correct JSON format
- [ ] Bilingual UI works correctly (Malay/English toggle)

---

**Document Version:** 1.0
**Last Updated:** 2026-05-14
**Next Review:** 2026-06-14
