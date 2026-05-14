# SSH Key Setup for GitHub Actions Deployment

## Overview

This guide shows how to set up SSH key-based authentication for deploying to Hestia via GitHub Actions.

SSH keys are more secure than passwords:
- No passwords transmitted over network
- Can be rotated independently
- Better audit trail
- Accepted industry standard

## What You Get

An SSH key pair has been generated locally:
- **Private Key**: `~/.ssh/hestia_deploy_key` (keeps on your machine)
- **Public Key**: `~/.ssh/hestia_deploy_key.pub` (goes to Hestia)

Key fingerprint (for verification):
```
4096 SHA256:9CHENQeSW40YHYInWBhXQ0dRfNN4vKo1KJAUUG5q6qI
```

## Setup Steps

### Step 1: Add SSH Private Key to GitHub Secrets

1. Go to: https://github.com/meper77/pbsys/settings/secrets/actions

2. Click "New repository secret" button

3. Create secret with these values:
   - **Name**: `HESTIA_SSH_KEY`
   - **Value**: (Paste the entire private key content)

4. To get private key content:
   ```powershell
   # Windows PowerShell
   Get-Content C:\Users\User.J1-ALPHA-PENS\.ssh\hestia_deploy_key -Raw
   ```
   OR on Mac/Linux:
   ```bash
   cat ~/.ssh/hestia_deploy_key
   ```

5. Copy the entire output (including `-----BEGIN RSA PRIVATE KEY-----` and `-----END RSA PRIVATE KEY-----`)

6. Paste into GitHub secret value field

7. Click "Add secret"

### Step 2: Add Hestia Host and User Secrets

**Secret 2: HESTIA_HOST**
1. Click "New repository secret"
2. Name: `HESTIA_HOST`
3. Value: `neovtrack.uitm.edu.my`
4. Click "Add secret"

**Secret 3: HESTIA_USER**
1. Click "New repository secret"
2. Name: `HESTIA_USER`
3. Value: Your Hestia SSH username (e.g., your domain prefix)
4. Click "Add secret"

After adding all 3, you should see:
- HESTIA_SSH_KEY (private key, masked)
- HESTIA_HOST
- HESTIA_USER

### Step 3: Add Public Key to Hestia

1. Connect to Hestia via SFTP or SSH:
   ```bash
   ssh your_username@hestia.uitm.edu.my
   ```

2. Create .ssh directory if it doesn't exist:
   ```bash
   mkdir -p ~/.ssh
   chmod 700 ~/.ssh
   ```

3. Add the public key to authorized_keys:
   ```bash
   # Option A: If you have local SSH access
   cat ~/.ssh/hestia_deploy_key.pub | ssh your_username@hestia.uitm.edu.my "cat >> ~/.ssh/authorized_keys"
   
   # Option B: Manual via SFTP
   # Upload hestia_deploy_key.pub to Hestia, then SSH and run:
   cat hestia_deploy_key.pub >> ~/.ssh/authorized_keys
   ```

4. Set correct permissions:
   ```bash
   chmod 600 ~/.ssh/authorized_keys
   chmod 700 ~/.ssh
   ```

5. Verify it works:
   ```bash
   # This should not ask for password if key is set up correctly
   ssh -i ~/.ssh/hestia_deploy_key your_username@hestia.uitm.edu.my "echo 'SSH key works!'"
   ```

### Step 4: Test GitHub Actions Deployment

1. Push code to main branch:
   ```bash
   git push origin main
   ```

2. Go to: https://github.com/meper77/pbsys/actions

3. Watch the "Deploy to Hestia" workflow run

4. Check for success (green checkmark) or failure (red X)

5. If failed, click on the run to see detailed logs

## Workflow Details

The GitHub Actions workflow now:

1. **Checks out code** from repository
2. **Configures SSH** with your private key
3. **Adds Hestia to known_hosts** (prevents "host key verification" prompts)
4. **Uploads files** via SCP (Secure Copy):
   - admin_management_api.php
   - vehicle_stats_api.php
   - sticker_management_api.php
   - superadmin.php
   - vehicle_list_drill_down.php
5. **Sets permissions** to 644 on all files
6. **Reports status** (success or failure)

Total deployment time: ~30-60 seconds

## Troubleshooting

### Issue: "Host key verification failed"
**Solution**: Workflow automatically adds host to known_hosts. If issue persists:
```bash
# Add Hestia to known hosts manually
ssh-keyscan -H neovtrack.uitm.edu.my >> ~/.ssh/known_hosts
```

### Issue: "Permission denied (publickey)"
**Causes**:
- Public key not added to ~/.ssh/authorized_keys on Hestia
- Wrong permissions on ~/.ssh or authorized_keys
- SSH service not running on Hestia

**Solutions**:
1. Verify public key content matches on Hestia:
   ```bash
   ssh your_username@hestia.uitm.edu.my "cat ~/.ssh/authorized_keys"
   # Should contain your public key
   ```

2. Check permissions:
   ```bash
   ssh your_username@hestia.uitm.edu.my "ls -la ~/.ssh/"
   # Should show:
   # drwx------ (700) for .ssh directory
   # -rw------- (600) for authorized_keys
   ```

3. Test SSH connection manually:
   ```bash
   ssh -i ~/.ssh/hestia_deploy_key -v your_username@hestia.uitm.edu.my
   # -v shows verbose output for debugging
   ```

### Issue: "No such file or directory" for files
**Solution**: Verify public_html directory exists on Hestia:
```bash
ssh your_username@hestia.utm.edu.my "ls -la ~/public_html"
```

### Issue: "Permission denied" setting file permissions
**Solution**: Hestia might require different approach. Try:
```bash
# Via workflow, we use SSH to set perms after upload
ssh your_username@hestia.uitm.edu.my "chmod 644 ~/public_html/*.php"
```

## Security Notes

### Private Key Security
- Never share your private key
- Never commit private key to repository
- If compromised, rotate immediately:
  ```bash
  # Generate new key pair
  ssh-keygen -t rsa -b 4096 -f hestia_deploy_key_new -N ""
  # Add new public key to Hestia
  # Update GitHub secret with new private key
  # Remove old public key from Hestia
  ```

### GitHub Secrets Security
- GitHub automatically masks secrets in logs
- Secrets cannot be viewed after creation (only overwritten)
- Only show in workflow environment, not in logs
- Automatically encrypted

### Best Practices
1. Use unique SSH key for each service (don't reuse for other servers)
2. Set expiration on keys if possible
3. Regularly audit authorized_keys on Hestia
4. Monitor GitHub Actions logs for failed deployments
5. Use SSH key instead of password authentication

## Key Rotation

To rotate SSH keys:

1. Generate new key pair:
   ```bash
   ssh-keygen -t rsa -b 4096 -f hestia_deploy_key_new -N ""
   ```

2. Add new public key to Hestia:
   ```bash
   cat hestia_deploy_key_new.pub | ssh your_username@hestia.uitm.edu.my "cat >> ~/.ssh/authorized_keys"
   ```

3. Update GitHub secret with new private key

4. Test deployment to verify new key works

5. Remove old public key from Hestia:
   ```bash
   # Edit ~/.ssh/authorized_keys and remove old key
   ssh your_username@hestia.uitm.edu.my "nano ~/.ssh/authorized_keys"
   ```

## Verification

After setup, verify everything works:

### Local Verification
```bash
# Test SSH connection with key
ssh -i ~/.ssh/hestia_deploy_key your_username@hestia.uitm.edu.my "whoami"

# Should output your username without asking for password
```

### GitHub Actions Verification
1. Push any change to main branch
2. Go to Actions tab
3. Click "Deploy to Hestia" workflow
4. Should complete in ~1 minute with green checkmark
5. Verify files uploaded to public_html:
   ```bash
   ssh your_username@hestia.uitm.edu.my "ls -la ~/public_html/*.php"
   ```

### Deployment Verification
1. Access superadmin dashboard: https://neovtrack.uitm.edu.my/superadmin.php
2. Test statistics display
3. Test drill-down functionality
4. Test API endpoints

## Reference

Related files:
- `.github/workflows/deploy-to-hestia.yml` - GitHub Actions workflow
- `DEPLOYMENT_CHECKLIST.md` - Deployment methods
- `DEPLOYMENT_METHOD_1_GUIDE.txt` - Previous setup guide

SSH Documentation:
- GitHub SSH Keys: https://docs.github.com/en/authentication/connecting-to-github-with-ssh
- OpenSSH manual: https://man.openbsd.org/ssh

## Summary

After following these steps:
- ✅ SSH key pair generated locally
- ✅ Private key added to GitHub Secrets
- ✅ Public key added to Hestia authorized_keys
- ✅ GitHub Actions workflow updated for SSH
- ✅ Deployment is secure and automated

Next: Push to main branch to trigger first deployment!
