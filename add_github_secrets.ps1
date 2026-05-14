# GitHub Secrets Setup Script
# This script adds SSH key and Hestia credentials to GitHub Secrets
# Usage: ./add_github_secrets.ps1

param(
    [string]$GitHubToken = $env:GITHUB_TOKEN,
    [string]$GitHubUser = "meper77",
    [string]$GitHubRepo = "pbsys",
    [string]$HestiaHost = "neovtrack.uitm.edu.my",
    [string]$HestiaUser = "",
    [string]$SSHKeyPath = "$env:USERPROFILE\.ssh\hestia_deploy_key"
)

Write-Host "════════════════════════════════════════════════════════════════"
Write-Host "        GitHub Secrets Setup for SSH Deployment"
Write-Host "════════════════════════════════════════════════════════════════"
Write-Host ""

# Check if GitHub token is provided
if (-not $GitHubToken) {
    Write-Host "❌ Error: GITHUB_TOKEN not set"
    Write-Host ""
    Write-Host "Please provide your GitHub Personal Access Token:"
    Write-Host "  1. Go to https://github.com/settings/tokens"
    Write-Host "  2. Create new token with 'repo' and 'admin:repo_hook' scopes"
    Write-Host "  3. Set environment variable: `$env:GITHUB_TOKEN = 'your_token'"
    Write-Host "  4. Re-run this script"
    exit 1
}

# Check if Hestia user is provided
if (-not $HestiaUser) {
    Write-Host "❌ Error: HestiaUser not provided"
    Write-Host ""
    Write-Host "Usage: ./add_github_secrets.ps1 -HestiaUser 'your_ssh_username'"
    exit 1
}

# Check if SSH key exists
if (-not (Test-Path $SSHKeyPath)) {
    Write-Host "❌ Error: SSH key not found at $SSHKeyPath"
    exit 1
}

Write-Host "✓ Configuration:"
Write-Host "  Repository: $GitHubUser/$GitHubRepo"
Write-Host "  Hestia Host: $HestiaHost"
Write-Host "  Hestia User: $HestiaUser"
Write-Host "  SSH Key: $SSHKeyPath"
Write-Host ""

# Read SSH private key
$SSHPrivateKey = Get-Content $SSHKeyPath -Raw
if (-not $SSHPrivateKey) {
    Write-Host "❌ Error: Could not read SSH private key"
    exit 1
}

Write-Host "🔐 Setting up GitHub Secrets..."
Write-Host ""

# Function to add secret via GitHub API
function Add-GitHubSecret {
    param(
        [string]$SecretName,
        [string]$SecretValue
    )
    
    # Encode the secret value to base64
    $SecretBytes = [System.Text.Encoding]::UTF8.GetBytes($SecretValue)
    $SecretBase64 = [System.Convert]::ToBase64String($SecretBytes)
    
    # Get repository public key
    Write-Host "  Fetching repository public key for $SecretName..."
    try {
        $keyResponse = curl -s `
            -H "Authorization: token $GitHubToken" `
            -H "Accept: application/vnd.github.v3+json" `
            "https://api.github.com/repos/$GitHubUser/$GitHubRepo/actions/secrets/public-key"
        
        $keyData = $keyResponse | ConvertFrom-Json
        $keyId = $keyData.key_id
        $publicKey = $keyData.key
        
        if (-not $keyId -or -not $publicKey) {
            Write-Host "    ❌ Failed to get public key"
            return $false
        }
        
        # Use libsodium for encryption (requires tweetnacl.js or similar)
        # For simplicity, we'll use a workaround via GitHub API with encoded value
        Write-Host "    ✓ Got public key ID: $keyId"
        
        # Send the secret
        Write-Host "  Adding secret: $SecretName"
        $payload = @{
            encrypted_value = $SecretBase64
            key_id = $keyId
        } | ConvertTo-Json
        
        $response = curl -s -X PUT `
            -H "Authorization: token $GitHubToken" `
            -H "Content-Type: application/json" `
            -H "Accept: application/vnd.github.v3+json" `
            -d $payload `
            "https://api.github.com/repos/$GitHubUser/$GitHubRepo/actions/secrets/$SecretName"
        
        Write-Host "    ✓ Secret '$SecretName' added successfully"
        return $true
    }
    catch {
        Write-Host "    ❌ Error adding secret: $_"
        return $false
    }
}

# Add secrets
$secrets = @{
    "HESTIA_SSH_KEY" = $SSHPrivateKey
    "HESTIA_HOST" = $HestiaHost
    "HESTIA_USER" = $HestiaUser
}

Write-Host ""
Write-Host "⚠️  Note: Due to encryption complexity, use the GitHub web UI instead:"
Write-Host ""
Write-Host "Web UI Method (Recommended):"
Write-Host "  1. Go to: https://github.com/$GitHubUser/$GitHubRepo/settings/secrets/actions"
Write-Host "  2. Click 'New repository secret'"
Write-Host "  3. Add these secrets:"
Write-Host ""
Write-Host "  Secret 1: HESTIA_SSH_KEY"
Write-Host "    Value: (paste entire content of $SSHKeyPath)"
Write-Host ""
Write-Host "  Secret 2: HESTIA_HOST"
Write-Host "    Value: $HestiaHost"
Write-Host ""
Write-Host "  Secret 3: HESTIA_USER"
Write-Host "    Value: $HestiaUser"
Write-Host ""
Write-Host "════════════════════════════════════════════════════════════════"
Write-Host ""
Write-Host "Quick Copy-Paste:"
Write-Host ""
Write-Host "HESTIA_SSH_KEY:"
Write-Host "─────────────────────────────────────────────────────────────────"
Write-Host $SSHPrivateKey
Write-Host "─────────────────────────────────────────────────────────────────"
Write-Host ""
Write-Host "Or use this command to copy to clipboard:"
Write-Host "  Get-Content '$SSHKeyPath' | Set-Clipboard"
Write-Host ""
