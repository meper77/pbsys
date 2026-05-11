<?php

// ===================== ENVIRONMENT =====================
// Change this to 'live' when uploading to Hestia
$environment = 'local';  

// ===================== LOCAL (XAMPP) =====================
if ($environment === 'local') {

    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'neovtrack_db';

} 

// ===================== LIVE (HESTIA) =====================
else {

    $db_host = 'localhost';
    $db_user = 'neovtrack_app';
    $db_pass = 'Neovtrack@1234';
    $db_name = 'neovtrack_db';
}

// ===================== CONNECT =====================
$con = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($con->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => 0,
        'message' => 'Database connection failed'
    ]);
    exit;
}
?>