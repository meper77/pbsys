# GITHUB ACTIONS DEPLOYMENT SETUP - METHOD 1

## Overview
This document provides step-by-step instructions for setting up automatic deployment via GitHub Actions.

Once configured, every push to the `main` branch will automatically:
1. Deploy PHP files to Hestia public_html
2. Execute database migration
3. Run post-deployment verification

## Prerequisites
- GitHub account with access to https://github.com/meper77/pbsys
- Hestia SFTP credentials:
  - SFTP Host: hestia.uitm.edu.my
  - SFTP Username: [your_username]
  - SFTP Password: [your_password]
  - SFTP Port: 22 (default)

## Setup Instructions

### Step 1: Navigate to GitHub Secrets Settings

1. Go to: https://github.com/meper77/pbsys
2. Click "Settings" (top menu)
3. Click "Secrets and variables" → "Actions" (left sidebar)
4. You should see: "Repository secrets" section

### Step 2: Add HESTIA_HOST Secret

1. Click "New repository secret" button
2. Name: `HESTIA_HOST`
3. Value: `neovtrack.uitm.edu.my`
4. Click "Add secret"

### Step 3: Add HESTIA_USER Secret

1. Click "New repository secret" button
2. Name: `HESTIA_USER`
3. Value: Your Hestia SFTP username (e.g., `neo_track` or `yourname`)
4. Click "Add secret"

### Step 4: Add HESTIA_PASSWORD Secret

1. Click "New repository secret" button
2. Name: `HESTIA_PASSWORD`
3. Value: Your Hestia SFTP password
4. Click "Add secret"

**Important:** GitHub automatically masks this secret in logs for security.

### Step 5: Verify Secrets Added

After adding all three secrets, you should see:
- HESTIA_HOST
- HESTIA_USER
- HESTIA_PASSWORD

All marked as "Updated X minutes ago"

## How It Works

### Automatic Deployment Trigger
The `.github/workflows/deploy-to-hestia.yml` workflow automatically triggers when:
- Code is pushed to `main` branch
- Changes include PHP files or database migration

### Deployment Process
1. GitHub Actions checks out latest code
2. Connects to Hestia via SFTP using provided credentials
3. Uploads 5 PHP files to public_html
4. Sets permissions to 644
5. Creates deployment summary

### Monitor Deployment

1. Go to: https://github.com/meper77/pbsys/actions
2. Find your workflow run (should appear at top)
3. Click to see detailed logs
4. Status shows: ✓ Passed or ✗ Failed

### Test the Setup

After secrets are added, test the workflow:

```bash
cd /path/to/pbsys

# Make a minor change and commit
echo "# Test deployment" >> README.md

# Push to main
git add README.md
git commit -m "Test GitHub Actions deployment"
git push origin main
```

Monitor the workflow at: https://github.com/meper77/pbsys/actions

## Manual Deployment After Secrets Added

Even with automated deployment configured, you can still manually trigger it:

1. Go to: https://github.com/meper77/pbsys
2. Click "Actions" tab
3. Click "Deploy to Hestia" workflow (left sidebar)
4. Click "Run workflow" button
5. Click "Run workflow" again to confirm

## Troubleshooting

### Secret Not Found Error
- Verify all three secrets are added
- Check spelling: HESTIA_HOST, HESTIA_USER, HESTIA_PASSWORD
- Secrets are case-sensitive

### SFTP Connection Failed
- Verify HESTIA_USER and HESTIA_PASSWORD are correct
- Test credentials manually using SFTP client
- Check if SFTP is enabled on Hestia account

### Files Not Uploaded
- Check SFTP account has write permissions to public_html
- Verify public_html directory exists
- Check disk space on Hestia account

### Workflow Not Triggering
- Verify changes are on `main` branch
- Check that files modified include PHP or SQL files
- Push must target `main` branch (not develop or other)

## View Workflow Logs

To see detailed deployment logs:

1. Go to: https://github.com/meper77/pbsys/actions
2. Click on the workflow run
3. Click "Deploy via SFTP" step
4. View SFTP connection details and file uploads

## After Deployment

Once automated deployment succeeds:

1. Run post-deployment tests:
   ```bash
   # Test admin API
   curl -X GET 'https://neovtrack.uitm.edu.my/admin_management_api.php?action=list_admins' \
     -H 'Cookie: email_Admin=admin@example.com'
   
   # Test superadmin dashboard
   curl -X GET 'https://neovtrack.uitm.edu.my/superadmin.php'
   ```

2. Access superadmin dashboard in browser:
   https://neovtrack.uitm.edu.my/superadmin.php

3. Verify all features work:
   - Statistics display
   - Drill-down clicking works
   - Admin management forms functional
   - Timestamps visible on records

## Deployment Frequency

The workflow triggers on **every push** to `main` that includes:
- admin_management_api.php
- vehicle_stats_api.php
- sticker_management_api.php
- superadmin.php
- vehicle_list_drill_down.php
- database/migration_admin_features.sql
- .github/workflows/deploy-to-hestia.yml

## Disable/Modify Workflow

To modify when deployment triggers:

1. Edit `.github/workflows/deploy-to-hestia.yml`
2. Change the `on:` section to specify branches/paths
3. Commit and push changes
4. New configuration takes effect

## Alternative: Manual Deployment

If you prefer NOT to use automated deployment:

See these files for manual deployment options:
- `DEPLOYMENT_CHECKLIST.md` - Comprehensive manual guide
- `ADMIN_FEATURES_DEPLOYMENT.txt` - Initial guide
- `deploy-to-hestia.ps1` - PowerShell automation script

## Summary

After adding secrets:
1. ✅ Automated deployment is active
2. ✅ Every push to main triggers deployment
3. ✅ Monitor progress in Actions tab
4. ✅ Files automatically synced to Hestia
5. ✅ Database migration scripts ready

**No further action needed for automated deployment!**

---

Questions or issues? Refer to:
- DEPLOYMENT_CHECKLIST.md
- .github/workflows/deploy-to-hestia.yml
- GitHub Actions documentation: https://docs.github.com/en/actions
