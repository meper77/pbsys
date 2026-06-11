<?php
// Security response headers, set in PHP so they apply regardless of the front
// server (live is nginx-fronted and ignores .htaccess <IfModule mod_headers>).
// Guarded by headers_sent() so an early-output page never fatals.
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 0');
    header('Permissions-Policy: geolocation=(self), camera=(), microphone=()');
    // Upgrade to HSTS once the host serves trusted HTTPS.
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443)) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

mysqli_report(MYSQLI_REPORT_OFF);

// Auto-detect production on Hestia, otherwise use local XAMPP credentials.
$server_name = strtolower($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
$environment = getenv('NEOVTRACK_ENV') ?: (strpos($server_name, 'neovtrack.uitm.edu.my') !== false ? 'live' : 'local');

if ($environment === 'live') {
    $db_host = 'localhost';
    $db_user = 'neovtrack_app';
    $db_pass = 'Neovtrack@1234';
    $db_name = 'neovtrack_db';
} else {
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'neovtrack_db';
}

$con = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($con->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => 0,
        'message' => 'Database connection failed'
    ]);
    exit;
}
