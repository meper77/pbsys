# deploy-to-hestia.ps1
# Deployment automation script for NEO.V-TRACK admin features to Hestia server
# Usage: .\deploy-to-hestia.ps1 -Host "neovtrack.uitm.edu.my" -User "username" -Password "password"
#        OR set environment variables: $env:HESTIA_HOST, $env:HESTIA_USER, $env:HESTIA_PASSWORD

param(
    [string]$Host = $env:HESTIA_HOST,
    [string]$User = $env:HESTIA_USER,
    [string]$Password = $env:HESTIA_PASSWORD,
    [string]$SshKey = $env:HESTIA_SSH_KEY,
    [switch]$SkipDatabase = $false
)

$ErrorActionPreference = "Stop"

# Color output
function Write-Success { Write-Host $args -ForegroundColor Green }
function Write-Error-Custom { Write-Host $args -ForegroundColor Red }
function Write-Warning { Write-Host $args -ForegroundColor Yellow }
function Write-Info { Write-Host $args -ForegroundColor Cyan }

Write-Info "=== NEO.V-TRACK Admin Features Deployment to Hestia ==="
Write-Info "Time: $(Get-Date)"

# Validate inputs
if (-not $Host) {
    Write-Error-Custom "Error: Host not provided. Use -Host parameter or set `$env:HESTIA_HOST"
    exit 1
}

Write-Info "Target Host: $Host"

# Files to deploy
$FilesToDeploy = @(
    "admin_management_api.php",
    "vehicle_stats_api.php",
    "sticker_management_api.php",
    "superadmin.php",
    "vehicle_list_drill_down.php"
)

# Database migration file
$DbMigration = "database/migration_admin_features.sql"

# Verify files exist locally
Write-Info "`n[1/6] Verifying local files..."
$MissingFiles = @()
foreach ($file in $FilesToDeploy) {
    if (Test-Path $file) {
        Write-Success "  ✓ $file ($(Get-Item $file).Length / 1KB KB)"
    } else {
        Write-Error-Custom "  ✗ $file NOT FOUND"
        $MissingFiles += $file
    }
}

if (Test-Path $DbMigration) {
    Write-Success "  ✓ $DbMigration ($(Get-Item $DbMigration).Length / 1KB KB)"
} else {
    Write-Error-Custom "  ✗ $DbMigration NOT FOUND"
    $MissingFiles += $DbMigration
}

if ($MissingFiles.Count -gt 0) {
    Write-Error-Custom "Error: Missing files: $($MissingFiles -join ', ')"
    exit 1
}

Write-Success "`n[2/6] All local files verified!"

# SFTP deployment
if ($User -and ($Password -or $SshKey)) {
    Write-Info "`n[3/6] Deploying PHP files via SFTP..."
    
    # Create SFTP session script
    $SftpScript = @"
lcd $(Get-Location)
cd public_html
mput admin_management_api.php
mput vehicle_stats_api.php
mput sticker_management_api.php
mput superadmin.php
mput vehicle_list_drill_down.php
chmod 644 admin_management_api.php
chmod 644 vehicle_stats_api.php
chmod 644 sticker_management_api.php
chmod 644 superadmin.php
chmod 644 vehicle_list_drill_down.php
bye
"@
    
    # Save script to temp file
    $SftpScript | Out-File -Encoding ASCII -FilePath "$env:TEMP\sftp_commands.txt"
    
    try {
        if ($SshKey) {
            Write-Info "Using SSH key authentication..."
            # Note: psftp doesn't support key auth directly in batch mode
            # User should use WinSCP or FileZilla for key-based auth
            Write-Warning "Note: SSH key auth requires manual SFTP or WinSCP. Use password auth or upload manually."
        } else {
            Write-Info "Connecting to SFTP: $User@$Host..."
            
            # Use echo to pipe password to psftp (Windows built-in SSH/SFTP tools may not support this)
            # Instead, provide instructions for WinSCP
            Write-Warning "⚠️  Batch SFTP with password requires WinSCP or FileZilla."
            Write-Warning "Please upload these files manually:"
            foreach ($file in $FilesToDeploy) {
                Write-Warning "  - $file → /public_html/"
            }
        }
    } catch {
        Write-Error-Custom "SFTP Error: $_"
    }
    
    Remove-Item "$env:TEMP\sftp_commands.txt" -Force -ErrorAction SilentlyContinue
    
} else {
    Write-Warning "`n[3/6] Skipping SFTP deployment (no credentials provided)"
    Write-Warning "Please upload these files manually to public_html:"
    foreach ($file in $FilesToDeploy) {
        Write-Warning "  - $file"
    }
}

# Database migration info
if (-not $SkipDatabase) {
    Write-Info "`n[4/6] Database migration instructions..."
    Write-Info "Database migration file: $DbMigration"
    Write-Info "`nTo apply migration:"
    Write-Info "1. Login to Hestia control panel"
    Write-Info "2. Navigate to Databases → neovtrack_db → phpMyAdmin"
    Write-Info "3. Click SQL tab and paste the following commands:"
    Write-Info ""
    
    # Show migration content
    $migrationContent = Get-Content $DbMigration -Raw
    Write-Host $migrationContent
    
    Write-Info "`nAlternatively, via SSH:"
    Write-Info "  mysql -u [user] -p [database] < database/migration_admin_features.sql"
} else {
    Write-Info "`n[4/6] Skipping database migration (--SkipDatabase flag set)"
}

# Verification
Write-Info "`n[5/6] Post-deployment verification commands..."
Write-Info "After files are uploaded, test with:"
Write-Info ""
Write-Info "  # Test admin management API"
Write-Info "  curl -X GET 'http://$Host/admin_management_api.php?action=list_admins' `\"
Write-Info "    -H 'Cookie: email_Admin=admin@email.com'"
Write-Info ""
Write-Info "  # Test vehicle stats API"
Write-Info "  curl -X GET 'http://$Host/vehicle_stats_api.php?action=get_stats' `"
Write-Info "    -H 'Cookie: email_Admin=admin@email.com'"
Write-Info ""
Write-Info "  # Test sticker management API"
Write-Info "  curl -X GET 'http://$Host/sticker_management_api.php?action=get_stickers' `"
Write-Info "    -H 'Cookie: email_Admin=admin@email.com'"
Write-Info ""
Write-Info "  # Test superadmin dashboard"
Write-Info "  curl -X GET 'http://$Host/superadmin.php' -L"
Write-Info ""

# Summary
Write-Success "`n[6/6] Deployment preparation complete!"
Write-Success "`n=== DEPLOYMENT SUMMARY ==="
Write-Success "Files to deploy: $($FilesToDeploy.Count)"
Write-Success "Target directory: public_html"
Write-Success "Database migration: $DbMigration"
Write-Success "Target host: $Host"
Write-Success ""
Write-Info "Next steps:"
Write-Info "1. If using Hestia File Manager:"
Write-Info "   - Log in to Hestia control panel"
Write-Info "   - Go to File Manager"
Write-Info "   - Navigate to public_html"
Write-Info "   - Upload each PHP file"
Write-Info "   - Set permissions to 644"
Write-Info ""
Write-Info "2. If using SFTP (WinSCP/FileZilla):"
Write-Info "   - Connect to $Host with your credentials"
Write-Info "   - Navigate to public_html"
Write-Info "   - Upload all PHP files"
Write-Info "   - Set permissions to 644"
Write-Info ""
Write-Info "3. Apply database migration"
Write-Info "   - Login to phpMyAdmin"
Write-Info "   - Paste the migration SQL in the SQL tab"
Write-Info "   - Click Go"
Write-Info ""
Write-Info "4. Test the endpoints using curl commands above"
Write-Info ""
Write-Success "Deployment documentation: ADMIN_FEATURES_DEPLOYMENT.txt"

Write-Info "`nFor automated deployment, set environment variables:"
Write-Info "  `$env:HESTIA_HOST = '$Host'"
Write-Info "  `$env:HESTIA_USER = 'your_username'"
Write-Info "  `$env:HESTIA_PASSWORD = 'your_password'"
Write-Info "  .\deploy-to-hestia.ps1"
