<?php
mysqli_report(MYSQLI_REPORT_OFF);

$server_name = strtolower($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
$environment = getenv('NEOVTRACK_ENV') ?: (strpos($server_name, 'neovtrack.uitm.edu.my') !== false ? 'live' : 'local');

$host = "localhost";
$db_name = "neovtrack_db";

if ($environment === 'live') {
    $db_user = "neovtrack_app";
    $db_pass = "Neovtrack@1234";
} else {
    $db_user = "root";
    $db_pass = "";
}

$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
