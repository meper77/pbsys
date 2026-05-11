# Bidirectional Codespace ↔ Local Sync with GitHub

This guide sets up automatic syncing between your Codespace and local machine using GitHub Actions to build APKs.

## 📋 Prerequisites

1. GitHub account with access to this repository
2. GitHub token (Personal Access Token)
3. Git installed on your local machine
4. Flutter installed locally (optional, but recommended)

## 🔑 Step 1: Generate GitHub Token

1. Go to https://github.com/settings/tokens
2. Click "Generate new token" → "Generate new token (classic)"
3. Set name: `CODESPACE_SYNC_TOKEN`
4. Select scopes:
   - ✅ `repo` (full control of private repositories)
   - ✅ `workflow` (update GitHub Action workflows)
   - ✅ `read:packages`
5. Click "Generate token" and **copy it** (you'll only see it once!)

## ⚙️ Step 2: Setup on Your Local Machine

### Clone the Repository
```bash
git clone https://github.com/YOUR_USERNAME/pbsys.git
cd pbsys
```

### Configure Git Credentials
Store your GitHub token for auto-authentication:

**macOS/Linux:**
```bash
# Create/edit ~/.netrc
cat >> ~/.netrc << EOF
machine github.com
login YOUR_USERNAME
password YOUR_GITHUB_TOKEN
EOF
chmod 600 ~/.netrc
```

**Windows (PowerShell):**
```powershell
# Use Git Credential Manager (install via: winget install --id Microsoft.GitCredentialManager)
git credential approve
# Type: protocol=https, host=github.com, username=YOUR_USERNAME, password=YOUR_GITHUB_TOKEN
```

### Configure Git Auto-Pull
```bash
# Setup auto-fetch every 2 minutes
git config --global fetch.prune true
git config --global pull.rebase false

# Create sync script (optional)
cat > ~/bin/git-sync.sh << 'EOF'
#!/bin/bash
while true; do
  git -C ~/pbsys fetch origin
  git -C ~/pbsys pull origin main --no-edit
  sleep 120
done
EOF
chmod +x ~/bin/git-sync.sh
```

## 📲 Step 3: Daily Workflow

### From Local Machine
```bash
# 1. Make changes
echo "your changes here" >> some_file.txt

# 2. Commit
git add .
git commit -m "Your change description"

# 3. Push (auto-syncs to Codespace)
git push origin main
```

### From Codespace (in GitHub)
```bash
# 1. Make changes in editor

# 2. Commit in terminal
git add .
git commit -m "Your change description"

# 3. Push
git push origin main

# 4. Pull latest on local
git pull origin main
```

## 🚀 Step 4: Enable Automatic APK Building

### Trigger Build
Builds automatically trigger on:
- **Any push to `main` branch**
- **Any push to `develop` branch**
- **Pull requests to `main`**

### Download APK
1. Go to GitHub: https://github.com/YOUR_USERNAME/pbsys/actions
2. Click the latest workflow run
3. Scroll to "Artifacts" section
4. Download `apk-builds.zip`
5. Extract and install on your device:
   ```bash
   adb install -r app-debug.apk
   # or
   adb install -r app-release.apk
   ```

## 💾 Step 5: Setup Auto-Sync on Local Machine

### Option A: Using Git Hooks (Automatic)
```bash
# Create post-merge hook
mkdir -p .git/hooks
cat > .git/hooks/post-merge << 'EOF'
#!/bin/bash
echo "✅ Repository updated from Codespace"
echo "Latest commit: $(git log -1 --oneline)"
EOF
chmod +x .git/hooks/post-merge
```

### Option B: Using a Cron Job (Background)
```bash
# Edit crontab
crontab -e

# Add this line (syncs every 5 minutes)
*/5 * * * * cd ~/pbsys && git fetch origin && git pull origin main --no-edit > /dev/null 2>&1
```

### Option C: Using VS Code Dev Containers
If you're using VS Code:
```bash
# Install extensions
code --install-extension ms-vscode-remote.remote-containers
code --install-extension GitHub.copilot
```

Then open the repo:
```bash
code --folder-uri vscode-remote://dev-container+$(cat /dev/urandom | tr -dc 'a-z0-9' | fold -w 32 | head -n 1)/workspaces/pbsys
```

## 🔄 Workflow Diagram

```
Your Local Machine          GitHub (Main Repo)          Codespace
      ↓                            ↓                          ↓
   changes ──push──→ triggers CI/CD build ←──pull── changes
      ↑                            ↓                          ↑
   pull ←─── APK artifacts ←── GitHub Actions ──push─→
```

## 📊 Monitoring Builds

### GitHub Actions Dashboard
```bash
# Check build status on command line
gh run list --repo YOUR_USERNAME/pbsys

# Watch a specific build
gh run watch <run-id> --repo YOUR_USERNAME/pbsys

# View build logs
gh run view <run-id> --log --repo YOUR_USERNAME/pbsys
```

## 🐛 Troubleshooting

### Token Expired
```bash
# Generate new token and update
~/.netrc  # on macOS/Linux
# or Git Credential Manager on Windows
```

### Build Failing
1. Check GitHub Actions logs
2. Verify Java version (uses JDK 21)
3. Check Flutter version (3.44.0)
4. View detailed logs:
   ```bash
   gh run view <run-id> --log --repo YOUR_USERNAME/pbsys
   ```

### Merge Conflicts
```bash
# Pull with rebase to avoid conflicts
git pull --rebase origin main
git push origin main
```

### APK Not Generated
1. Check the GitHub Actions workflow ran successfully
2. Verify Flutter build succeeded:
   ```bash
   gh run view <run-id> --log | grep -i "flutter build"
   ```
3. Android SDK must be available in CI (it is in our workflow)

## 🎯 Common Tasks

### Push from Codespace to Local
```bash
# In Codespace terminal
git push origin main

# On local machine
git pull origin main
```

### Pull Latest to Codespace
```bash
# In Codespace terminal
git pull origin main
```

### Sync Everything
```bash
# On local
git fetch origin
git pull origin main
git push origin main

# Wait for GitHub Actions to complete, then in Codespace:
git pull origin main
```

### Create Release Build
```bash
# Tag a release (triggers workflow)
git tag v1.0.0
git push origin v1.0.0

# APK will be released on GitHub Releases page
```

## 🔗 Useful Commands

```bash
# Check remote status
git remote -v

# View commit history
git log --oneline --graph --all

# Create feature branch
git checkout -b feature/your-feature
git push -u origin feature/your-feature

# Merge to main
git checkout main
git pull origin main
git merge feature/your-feature
git push origin main

# Monitor CI/CD
gh run list
gh run view <run-id>
gh run download <run-id>
```

## 📚 Next Steps

1. ✅ Generate GitHub token
2. ✅ Configure local machine
3. ✅ Make a test commit
4. ✅ Watch GitHub Actions build APK
5. ✅ Download APK from artifacts
6. ✅ Set up auto-sync on local

## 🚀 That's It!

Now you can:
- Work on local or Codespace interchangeably  
- Automatic APK builds on every push
- Easy APK download for testing
- Full bidirectional sync via GitHub

Happy coding! 🎉
