#!/bin/bash
# google-signin-setup.sh
# Automate Google Sign-In config for pbsys

set -e
cd "$(dirname "$0")"

CLIENT_ID="${1:-}"
HD_DOMAIN="${2:-uitm.edu.my}"

if [ -z "$CLIENT_ID" ]; then
    echo "Usage: $0 <CLIENT_ID> [HD_DOMAIN]"
    echo ""
    echo "Example:"
    echo "  $0 1234567890-abcd.apps.googleusercontent.com uitm.edu.my"
    echo ""
    echo "Get CLIENT_ID from: https://console.cloud.google.com/apis/credentials"
    exit 1
fi

echo "Updating pbsys Google Sign-In configuration..."
echo ""
echo "  Client ID: $CLIENT_ID"
echo "  HD Domain: $HD_DOMAIN"
echo ""

# Backup
cp includes/secrets.php includes/secrets.php.bak.$(date +%s)
echo "[✓] Backed up includes/secrets.php"

# Update via PHP
php << PHPEOF
<?php
\$file = 'includes/secrets.php';
\$content = file_get_contents(\$file);

// Remove old google config if exists
\$content = preg_replace(
    "/\\s*'google_client_id'\\s*=>\\s*'[^']*',?\\n/",
    '',
    \$content
);
\$content = preg_replace(
    "/\\s*'google_hd'\\s*=>\\s*'[^']*',?\\n/",
    '',
    \$content
);

// Add new config before closing ];
\$new_config = "
    // Google Sign-In (UiTM)
    'google_client_id' => '$CLIENT_ID',
    'google_hd'        => '$HD_DOMAIN',
";

\$content = str_replace(
    "    ],\\n];",
    "    ],
\$new_config
];",
    \$content
);

if (file_put_contents(\$file, \$content)) {
    echo "[✓] Updated includes/secrets.php\\n";
} else {
    echo "[✗] Failed to update secrets.php\\n";
    exit(1);
}
?>
PHPEOF

echo ""
echo "[✓] Configuration complete!"
echo ""
echo "Next steps:"
echo "  1. Test locally:  http://localhost:8000/auth/login.php?dev=1"
echo "  2. Verify HTTPS on production"
echo "  3. Deploy to Hestia:  rclone copy includes/secrets.php hestia:..."
echo "  4. Add users to allowlist via admin panel"
echo ""
echo "To verify configuration:"
php << 'PHPEOF'
require 'includes/secrets_loader.php';
require 'includes/google_auth.php';

$cid = nv_google_client_id();
echo "Client ID configured: " . ($cid ? "YES ($cid)" : "NO") . "\n";
PHPEOF
