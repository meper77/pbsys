#!/usr/bin/env php
<?php
/**
 * verify-google-signin.php
 * Check if Google Sign-In is properly configured in pbsys
 */

require_once $_SERVER['DOCUMENT_ROOT'] ?? __DIR__ . '/includes/secrets_loader.php';
require_once $_SERVER['DOCUMENT_ROOT'] ?? __DIR__ . '/includes/google_auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] ?? __DIR__ . '/includes/otp_auth.php';

$errors = [];
$warnings = [];
$ok = [];

echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║         Google Sign-In Configuration Verification            ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// 1. Client ID
echo "[1] Client ID Configuration\n";
$cid = nv_google_client_id();
if ($cid) {
    echo "    ✓ Client ID: " . substr($cid, 0, 20) . "...\n";
    $ok[] = "Client ID";
} else {
    echo "    ✗ Client ID NOT configured\n";
    $errors[] = "Set 'google_client_id' in includes/secrets.php";
}

// 2. HD Domain (optional)
echo "\n[2] Hosted Domain (Optional Security Layer)\n";
$hd = (string) nv_secret('google_hd', '');
if ($hd) {
    echo "    ✓ Hosted Domain: $hd\n";
    $ok[] = "Hosted Domain";
} else {
    echo "    ⚠ Hosted Domain not set (allowing any Google account)\n";
    $warnings[] = "Recommend setting google_hd to 'uitm.edu.my' for extra isolation";
}

// 3. HTTPS Check
echo "\n[3] HTTPS Requirement\n";
$isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
if ($isHttps) {
    echo "    ✓ HTTPS enabled (Google button will show)\n";
    $ok[] = "HTTPS";
} else {
    echo "    ⚠ HTTP detected (Google button will be hidden)\n";
    $warnings[] = "Google Sign-In requires HTTPS. Use ?dev=1 to test locally.";
}

// 4. Database Connection
echo "\n[4] Database & Tables\n";
if (function_exists('nv_table_exists')) {
    echo "    ✓ otp_auth.php loaded (nv_table_exists available)\n";
    $ok[] = "otp_auth functions";
} else {
    echo "    ✗ otp_auth.php not loaded\n";
    $errors[] = "Load includes/otp_auth.php";
}

// 5. Session Support
echo "\n[5] Session Support\n";
if (function_exists('nv_norm_email')) {
    echo "    ✓ Session functions available\n";
    $ok[] = "Session helpers";
} else {
    echo "    ✗ Session helpers missing\n";
    $errors[] = "Ensure includes/otp_auth.php is loaded";
}

// 6. Network (JWKS fetch)
echo "\n[6] Network & Google JWKS\n";
if (function_exists('curl_init') || ini_get('allow_url_fopen')) {
    $have_curl = function_exists('curl_init');
    $have_fopen = ini_get('allow_url_fopen');
    echo "    ✓ Network available (" . ($have_curl ? "curl" : "") . ($have_curl && $have_fopen ? " + " : "") . ($have_fopen ? "stream" : "") . ")\n";
    
    // Try fetching JWKS (if network is available)
    if ($cid && ($have_curl || $have_fopen)) {
        $jwks = nv_google_jwks();
        if ($jwks && !empty($jwks['keys'])) {
            echo "    ✓ Google JWKS fetched (" . count($jwks['keys']) . " keys)\n";
            $ok[] = "JWKS fetch";
        } else {
            echo "    ⚠ JWKS fetch failed (may retry from cache)\n";
            $warnings[] = "JWKS cache will be used if available";
        }
    }
    $ok[] = "Network available";
} else {
    echo "    ✗ Network unavailable (curl disabled, allow_url_fopen off)\n";
    $errors[] = "Enable curl extension or allow_url_fopen";
}

// 7. Files Check
echo "\n[7] Required Files\n";
$files = [
    'auth/login.php'              => 'Login page (GIS button)',
    'auth/google_callback.php'    => 'JWT callback handler',
    'includes/google_auth.php'    => 'Verification library',
    'includes/otp_auth.php'       => 'Session + allowlist helpers',
    'includes/secrets_loader.php' => 'Config loader',
];
foreach ($files as $path => $desc) {
    $fullPath = $_SERVER['DOCUMENT_ROOT'] ?? __DIR__;
    $fullPath = rtrim($fullPath, '/') . '/' . $path;
    if (file_exists($fullPath)) {
        echo "    ✓ $path\n";
        $ok[] = $path;
    } else {
        echo "    ✗ $path missing\n";
        $errors[] = "Required file missing: $path";
    }
}

// Summary
echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║                         SUMMARY                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

if (!empty($ok)) {
    echo "✓ Working (" . count($ok) . "):\n";
    foreach ($ok as $item) {
        echo "  • $item\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠ Warnings (" . count($warnings) . "):\n";
    foreach ($warnings as $item) {
        echo "  • $item\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "✗ ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $item) {
        echo "  • $item\n";
    }
    echo "\n";
    exit(1);
} else {
    echo "────────────────────────────────────────────────────────────\n";
    echo "✓ Google Sign-In is ready!\n";
    echo "\nNext steps:\n";
    if ($cid && !$isHttps) {
        echo "  1. Test with ?dev=1 on HTTP (developer bypass)\n";
    }
    if ($cid && $isHttps) {
        echo "  1. Verify the Google button appears on login page\n";
    }
    if (!$cid) {
        echo "  1. Get OAuth Client ID from Google Cloud Console\n";
    }
    echo "  2. Add your email to admin_allowlist (admin panel)\n";
    echo "  3. Click the Google button to sign in\n";
    echo "\n";
    exit(0);
}
