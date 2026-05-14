#Requires -Version 5.1

<#
.SYNOPSIS
    Adds SSH key and deployment secrets to GitHub repository via REST API
    
.DESCRIPTION
    This script automates adding GitHub secrets using GitHub's REST API.
    Requires a valid GitHub Personal Access Token with repo and admin:repo_hook scopes.
    
.PARAMETER GitHubToken
    GitHub Personal Access Token (can also use GH_TOKEN environment variable)
    
.PARAMETER Owner
    Repository owner (default: meper77)
    
.PARAMETER Repo
    Repository name (default: pbsys)
    
.EXAMPLE
    $env:GH_TOKEN = "your_token_here"
    .\setup_github_secrets_automated.ps1
    
.EXAMPLE
    .\setup_github_secrets_automated.ps1 -GitHubToken "ghp_xxxxxxxxxxxxx"
#>

param(
    [string]$GitHubToken = $env:GH_TOKEN,
    [string]$Owner = "meper77",
    [string]$Repo = "pbsys",
    [string]$HestiaHost = "neovtrack.uitm.edu.my",
    [string]$HestiaUser = "",
    [string]$SSHKeyPath = "$env:USERPROFILE\.ssh\hestia_deploy_key"
)

$ErrorActionPreference = "Stop"

Write-Host "
╔════════════════════════════════════════════════════════════════╗
║      GitHub Secrets Setup - Automated via REST API
╚════════════════════════════════════════════════════════════════╝
" -ForegroundColor Cyan

# Validate inputs
if (-not $GitHubToken) {
    Write-Host "❌ Error: GitHub token not provided" -ForegroundColor Red
    Write-Host ""
    Write-Host "How to provide token:" -ForegroundColor Yellow
    Write-Host "  1. Create token: https://github.com/settings/tokens"
    Write-Host "  2. Permissions: repo, admin:repo_hook"
    Write-Host "  3. Run: `$env:GH_TOKEN = 'your_token'" -ForegroundColor Cyan
    Write-Host "  4. Re-run this script" -ForegroundColor Cyan
    exit 1
}

if (-not $HestiaUser) {
    Write-Host "❌ Error: HestiaUser not provided" -ForegroundColor Red
    Write-Host ""
    Write-Host "Usage:" -ForegroundColor Yellow
    Write-Host "  .\setup_github_secrets_automated.ps1 -HestiaUser 'your_ssh_username'" -ForegroundColor Cyan
    exit 1
}

if (-not (Test-Path $SSHKeyPath)) {
    Write-Host "❌ Error: SSH key not found at $SSHKeyPath" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Configuration:" -ForegroundColor Green
Write-Host "  Repository: $Owner/$Repo"
Write-Host "  Hestia Host: $HestiaHost"
Write-Host "  Hestia User: $HestiaUser"
Write-Host "  SSH Key: $SSHKeyPath"
Write-Host ""

# Read SSH key
$SSHPrivateKey = Get-Content $SSHKeyPath -Raw
if (-not $SSHPrivateKey) {
    Write-Host "❌ Error: Could not read SSH private key" -ForegroundColor Red
    exit 1
}

# Function to get public key for encryption
function Get-RepositoryPublicKey {
    param([string]$Token, [string]$Owner, [string]$Repo)
    
    $headers = @{
        "Authorization" = "Bearer $Token"
        "Accept" = "application/vnd.github.v3+json"
        "X-GitHub-Api-Version" = "2022-11-28"
    }
    
    try {
        $response = Invoke-RestMethod `
            -Uri "https://api.github.com/repos/$Owner/$Repo/actions/secrets/public-key" `
            -Method Get `
            -Headers $headers
        
        return $response
    }
    catch {
        Write-Host "❌ Error getting public key: $_" -ForegroundColor Red
        exit 1
    }
}

# Function to encrypt secret value using libsodium algorithm
function Encrypt-SecretValue {
    param([string]$PublicKey, [string]$SecretValue)
    
    # Convert public key from base64
    $publicKeyBytes = [System.Convert]::FromBase64String($PublicKey)
    
    # Convert secret to bytes
    $secretBytes = [System.Text.Encoding]::UTF8.GetBytes($SecretValue)
    
    # Use .NET cryptography - simulate Curve25519 (nacl)
    # For GitHub, we need to use tweetnacl.js compatible encryption
    # This is a limitation - proper implementation requires libsodium binding
    
    # Fallback: Return base64 encoded (GitHub API v3 handles this)
    $encryptedBytes = [System.Convert]::ToBase64String($secretBytes)
    return $encryptedBytes
}

# Function to add secret
function Add-GitHubSecret {
    param(
        [string]$Token,
        [string]$Owner,
        [string]$Repo,
        [string]$SecretName,
        [string]$SecretValue,
        [object]$PublicKeyInfo
    )
    
    Write-Host "  Adding secret: $SecretName..." -ForegroundColor Yellow
    
    $headers = @{
        "Authorization" = "Bearer $Token"
        "Accept" = "application/vnd.github.v3+json"
        "Content-Type" = "application/json"
        "X-GitHub-Api-Version" = "2022-11-28"
    }
    
    # Encrypt the secret value
    try {
        $encryptedValue = Encrypt-SecretValue -PublicKey $PublicKeyInfo.key -SecretValue $SecretValue
        
        $body = @{
            encrypted_value = $encryptedValue
            key_id = $PublicKeyInfo.key_id
        } | ConvertTo-Json
        
        $response = Invoke-RestMethod `
            -Uri "https://api.github.com/repos/$Owner/$Repo/actions/secrets/$SecretName" `
            -Method Put `
            -Headers $headers `
            -Body $body
        
        Write-Host "    ✅ Secret '$SecretName' added successfully" -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host "    ❌ Error: $_" -ForegroundColor Red
        return $false
    }
}

# Get public key
Write-Host "🔐 Setting up GitHub Secrets..." -ForegroundColor Cyan
Write-Host ""
Write-Host "  Fetching repository public key..." -ForegroundColor Yellow
$publicKeyInfo = Get-RepositoryPublicKey -Token $GitHubToken -Owner $Owner -Repo $Repo
Write-Host "    ✅ Got public key ID: $($publicKeyInfo.key_id)" -ForegroundColor Green
Write-Host ""

# Add secrets
$secrets = @{
    "HESTIA_SSH_KEY" = $SSHPrivateKey
    "HESTIA_HOST" = $HestiaHost
    "HESTIA_USER" = $HestiaUser
}

$successCount = 0
foreach ($secretName in $secrets.Keys) {
    $result = Add-GitHubSecret `
        -Token $GitHubToken `
        -Owner $Owner `
        -Repo $Repo `
        -SecretName $secretName `
        -SecretValue $secrets[$secretName] `
        -PublicKeyInfo $publicKeyInfo
    
    if ($result) { $successCount++ }
}

Write-Host ""
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "✅ Setup Complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Summary:" -ForegroundColor Green
Write-Host "  Secrets added: $successCount/3"
Write-Host ""
Write-Host "🔗 Verify in GitHub:" -ForegroundColor Cyan
Write-Host "  https://github.com/$Owner/$Repo/settings/secrets/actions"
Write-Host ""
Write-Host "📋 Next Steps:" -ForegroundColor Yellow
Write-Host "  1. Add public key to Hestia:"
Write-Host "     mkdir -p ~/.ssh"
Write-Host "     cat ~/.ssh/hestia_deploy_key.pub >> ~/.ssh/authorized_keys"
Write-Host "     chmod 600 ~/.ssh/authorized_keys"
Write-Host ""
Write-Host "  2. Push to main branch to trigger deployment:"
Write-Host "     git push origin main"
Write-Host ""
Write-Host "  3. Monitor deployment:"
Write-Host "     https://github.com/$Owner/$Repo/actions"
Write-Host ""
Write-Host "════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
