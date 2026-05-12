#!/bin/bash

# GitHub Bidirectional Sync Setup Script
# Automates setup of local machine for Codespace sync

set -e

echo "🚀 GitHub Bidirectional Sync Setup"
echo "===================================="
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Check prerequisites
echo -e "${BLUE}📋 Checking prerequisites...${NC}"

if ! command -v git &> /dev/null; then
    echo -e "${RED}✗ Git not found. Please install Git first.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Git installed${NC}"

if ! command -v gh &> /dev/null; then
    echo -e "${YELLOW}⚠ GitHub CLI not found. Some features won't work.${NC}"
    echo "  Install from: https://cli.github.com"
else
    echo -e "${GREEN}✓ GitHub CLI installed${NC}"
fi

# Get GitHub username and token
echo ""
echo -e "${BLUE}🔑 GitHub Authentication${NC}"
read -p "Enter your GitHub username: " GH_USERNAME
echo ""
echo "Go to https://github.com/settings/tokens to create a new token"
echo "Required scopes: repo, workflow"
read -sp "Paste your GitHub token: " GH_TOKEN
echo ""
echo ""

# Test token
echo -e "${BLUE}🧪 Testing GitHub token...${NC}"
if echo "$GH_TOKEN" | gh auth login --with-token &>/dev/null; then
    echo -e "${GREEN}✓ GitHub authentication successful${NC}"
else
    echo -e "${RED}✗ GitHub authentication failed. Check your token.${NC}"
    exit 1
fi

# Setup .netrc for git credential storage
echo ""
echo -e "${BLUE}📝 Setting up .netrc for auto-authentication...${NC}"

if [[ "$OSTYPE" == "msys" || "$OSTYPE" == "win32" ]]; then
    echo -e "${YELLOW}⚠ Windows detected. Using Git Credential Manager instead.${NC}"
    echo "Run in PowerShell: git credential approve"
    echo "Then paste: protocol=https, host=github.com, username=$GH_USERNAME, password=$GH_TOKEN"
else
    # macOS/Linux
    NETRC_FILE="$HOME/.netrc"

    if [ -f "$NETRC_FILE" ]; then
        echo -e "${YELLOW}⚠ .netrc already exists. Backing up to .netrc.bak${NC}"
        cp "$NETRC_FILE" "$NETRC_FILE.bak"
    fi

    cat > "$NETRC_FILE" << EOF
machine github.com
login $GH_USERNAME
password $GH_TOKEN
EOF

    chmod 600 "$NETRC_FILE"
    echo -e "${GREEN}✓ .netrc configured${NC}"
fi

# Get repository info
echo ""
echo -e "${BLUE}📦 Repository Setup${NC}"
read -p "Enter repository name (default: pbsys): " REPO_NAME
REPO_NAME=${REPO_NAME:-pbsys}

read -p "Enter local directory path (default: ~/pbsys): " LOCAL_PATH
LOCAL_PATH=${LOCAL_PATH:-~/pbsys}
LOCAL_PATH="${LOCAL_PATH/#\~/$HOME}"

# Clone repository
if [ ! -d "$LOCAL_PATH" ]; then
    echo -e "${BLUE}Cloning repository...${NC}"
    git clone "https://github.com/$GH_USERNAME/$REPO_NAME.git" "$LOCAL_PATH"
    echo -e "${GREEN}✓ Repository cloned${NC}"
else
    echo -e "${YELLOW}⚠ Directory already exists. Skipping clone.${NC}"
fi

cd "$LOCAL_PATH"

# Configure git
echo ""
echo -e "${BLUE}⚙️ Configuring Git...${NC}"

git config user.name "$GH_USERNAME" 2>/dev/null || true
git config fetch.prune true
git config pull.rebase false
git remote set-url origin "https://github.com/$GH_USERNAME/$REPO_NAME.git"

echo -e "${GREEN}✓ Git configured${NC}"

# Setup auto-sync (optional)
echo ""
echo -e "${BLUE}🔄 Auto-Sync Setup${NC}"
read -p "Setup auto-sync with cron? (y/n): " SETUP_CRON

if [[ $SETUP_CRON == "y" || $SETUP_CRON == "Y" ]]; then
    if [[ "$OSTYPE" == "msys" || "$OSTYPE" == "win32" ]]; then
        echo -e "${YELLOW}⚠ Cron not available on Windows. Please use Task Scheduler.${NC}"
    else
        # Create sync script
        SYNC_SCRIPT="$HOME/.local/bin/git-sync.sh"
        mkdir -p "$(dirname "$SYNC_SCRIPT")"

        cat > "$SYNC_SCRIPT" << EOF
#!/bin/bash
while true; do
  cd "$LOCAL_PATH"
  git fetch origin
  git pull origin main --no-edit 2>/dev/null || true
  git push origin main 2>/dev/null || true
  sleep 300
done
EOF

        chmod +x "$SYNC_SCRIPT"

        # Add to crontab
        CRON_JOB="*/5 * * * * cd $LOCAL_PATH && git fetch origin && git pull origin main --no-edit > /dev/null 2>&1"

        if crontab -l 2>/dev/null | grep -q "git-sync"; then
            echo -e "${YELLOW}⚠ Cron job already exists${NC}"
        else
            (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
            echo -e "${GREEN}✓ Auto-sync cron job added (every 5 minutes)${NC}"
        fi
    fi
fi

# Setup post-merge hook
echo ""
echo -e "${BLUE}🔗 Setting up Git hooks...${NC}"

HOOKS_DIR="$LOCAL_PATH/.git/hooks"
POST_MERGE="$HOOKS_DIR/post-merge"

cat > "$POST_MERGE" << 'EOF'
#!/bin/bash
echo "✅ Repository updated"
echo "Latest commit: $(git log -1 --oneline)"
EOF

chmod +x "$POST_MERGE"
echo -e "${GREEN}✓ Git hooks configured${NC}"

# Summary
echo ""
echo -e "${GREEN}════════════════════════════════════════${NC}"
echo -e "${GREEN}✨ Setup Complete!${NC}"
echo -e "${GREEN}════════════════════════════════════════${NC}"
echo ""
echo "📍 Repository Location: $LOCAL_PATH"
echo "👤 GitHub User: $GH_USERNAME"
echo ""
echo "🚀 Next Steps:"
echo "1. cd $LOCAL_PATH"
echo "2. Make changes and commit"
echo "3. git push origin main"
echo "4. Check GitHub Actions for APK build"
echo "5. Download APK from artifacts"
echo ""
echo "📊 Monitor builds:"
echo "   gh run list --repo $GH_USERNAME/$REPO_NAME"
echo ""
echo "🎉 Happy coding!"
