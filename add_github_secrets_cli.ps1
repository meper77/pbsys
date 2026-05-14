#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Add GitHub secrets using GitHub CLI (gh command)
    
.DESCRIPTION
    This script adds SSH key and deployment secrets to GitHub using the GitHub CLI.
    Requires: gh CLI to be installed and authenticated (gh auth login)
    
.PARAMETER HestiaUser
    Your Hestia SSH username
    
.PARAMETER Owner
    Repository owner (default: meper77)
    
.PARAMETER Repo
    Repository name (default: pbsys)
    
.EXAMPLE
    .\add_github_secrets_cli.ps1 -HestiaUser 'sysadmin'
#>

param(
    [string]$HestiaUser = "",
    [string]$Owner = "meper77",
    [string]$Repo = "pbsys",
    [string]$HestiaHost = "neovtrack.uitm.edu.my",
    [string]$SSHKeyPath = "$env:USERPROFILE\.ssh\hestia_deploy_key"
)

$ErrorActionPreference = "Stop"

Write-Host "
╔════════════════════════════════════════════════════════════════╗
║       GitHub Secrets Setup via CLI (gh command)
╚════════════════════════════════════════════════════════════════╝
" -ForegroundColor Cyan

# Check if HestiaUser is provided
if (-not $HestiaUser) {
    Write-Host "❌ Error: HestiaUser parameter required" -ForegroundColor Red
    Write-Host ""
    Write-Host "Usage:" -ForegroundColor Yellow
    Write-Host "  .\add_github_secrets_cli.ps1 -HestiaUser 'your_username'" -ForegroundColor Cyan
    exit 1
}

# Check if gh is installed and authenticated
Write-Host "🔍 Checking GitHub CLI..." -ForegroundColor Yellow
try {
    $ghVersion = gh --version 2>&1
    Write-Host "  ✅ $ghVersion" -ForegroundColor Green
}
catch {
    Write-Host "  ❌ GitHub CLI not found" -ForegroundColor Red
    exit 1
}

# Check if authenticated
Write-Host ""
Write-Host "🔐 Checking authentication..." -ForegroundColor Yellow
try {
    $auth = gh auth status 2>&1
    if ($auth -match "Logged in to github.com") {
        Write-Host "  ✅ Authenticated" -ForegroundColor Green
    }
    else {
        Write-Host "  ⚠️  Not authenticated. Running gh auth login..." -ForegroundColor Yellow
        gh auth login --web
    }
}
catch {
    Write-Host "  ⚠️  Error checking auth. Attempting login..." -ForegroundColor Yellow
    gh auth login --web
}

# Verify SSH key exists
if (-not (Test-Path $SSHKeyPath)) {
    Write-Host ""
    Write-Host "❌ Error: SSH key not found at $SSHKeyPath" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "📋 Configuration:" -ForegroundColor Cyan
Write-Host "  Repository: $Owner/$Repo"
Write-Host "  Hestia Host: $HestiaHost"
Write-Host "  Hestia User: $HestiaUser"
Write-Host ""

# Add secrets
Write-Host "🔑 Adding secrets..." -ForegroundColor Yellow
Write-Host ""

# Secret 1: HESTIA_SSH_KEY
Write-Host "  1️⃣  Adding HESTIA_SSH_KEY..." -ForegroundColor Cyan
try {
    Get-Content $SSHKeyPath | gh secret set HESTIA_SSH_KEY --repo "$Owner/$Repo"
    Write-Host "      ✅ HESTIA_SSH_KEY added" -ForegroundColor Green
}
catch {
    Write-Host "      ❌ Error adding HESTIA_SSH_KEY: $_" -ForegroundColor Red
}

# Secret 2: HESTIA_HOST
Write-Host "  2️⃣  Adding HESTIA_HOST..." -ForegroundColor Cyan
try {
    $HestiaHost | gh secret set HESTIA_HOST --repo "$Owner/$Repo"
    Write-Host "      ✅ HESTIA_HOST added" -ForegroundColor Green
}
catch {
    Write-Host "      ❌ Error adding HESTIA_HOST: $_" -ForegroundColor Red
}

# Secret 3: HESTIA_USER
Write-Host "  3️⃣  Adding HESTIA_USER..." -ForegroundColor Cyan
try {
    $HestiaUser | gh secret set HESTIA_USER --repo "$Owner/$Repo"
    Write-Host "      ✅ HESTIA_USER added" -ForegroundColor Green
}
catch {
    Write-Host "      ❌ Error adding HESTIA_USER: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "✅ Secrets Setup Complete!" -ForegroundColor Green
Write-Host ""

# List secrets to verify
Write-Host "🔍 Verifying secrets..." -ForegroundColor Yellow
try {
    $secrets = gh secret list --repo "$Owner/$Repo" 2>&1
    Write-Host ""
    Write-Host $secrets
    Write-Host ""
}
catch {
    Write-Host "  Could not list secrets" -ForegroundColor Yellow
}

Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""
Write-Host "📍 Next Steps:" -ForegroundColor Green
Write-Host "  1. Verify at: https://github.com/$Owner/$Repo/settings/secrets/actions"
Write-Host "  2. Add public key to Hestia:"
Write-Host "     mkdir -p ~/.ssh"
Write-Host "     cat ~/.ssh/hestia_deploy_key.pub >> ~/.ssh/authorized_keys"
Write-Host "     chmod 600 ~/.ssh/authorized_keys"
Write-Host "  3. Push to main branch:"
Write-Host "     git push origin main"
Write-Host "  4. Monitor deployment:"
Write-Host "     https://github.com/$Owner/$Repo/actions"
Write-Host ""
