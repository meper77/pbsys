<?php
// diagnostic.php - Server diagnostic for debugging API issues

header('Content-Type: application/json');

$diagnostics = [];

// 1. Check PHP version
$diagnostics['php_version'] = phpversion();

// 2. Check if mysqli is available
$diagnostics['mysqli_available'] = extension_loaded('mysqli') ? 'yes' : 'no';

// 3. Test MySQL connection
$host = "localhost";
$db   = "neovtrack_db";
$user = "root";
$pass = "";

$diagnostics['mysql_test'] = [];
$conn = @new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    $diagnostics['mysql_test']['status'] = 'error';
    $diagnostics['mysql_test']['error'] = $conn->connect_error;
} else {
    $diagnostics['mysql_test']['status'] = 'connected';
    
    // Try to get user count
    $result = $conn->query("SELECT COUNT(*) as user_count FROM user");
    if ($result) {
        $row = $result->fetch_assoc();
        $diagnostics['mysql_test']['user_count'] = $row['user_count'];
    } else {
        $diagnostics['mysql_test']['user_count_error'] = $conn->error;
    }
    
    // Try to get admin count
    $result = $conn->query("SELECT COUNT(*) as admin_count FROM admin");
    if ($result) {
        $row = $result->fetch_assoc();
        $diagnostics['mysql_test']['admin_count'] = $row['admin_count'];
    } else {
        $diagnostics['mysql_test']['admin_count_error'] = $conn->error;
    }
    
    $conn->close();
}

// 4. Check if login files exist
$files = ['login_user_api.php', 'login_admin_api.php', 'login_api.php'];
$diagnostics['files'] = [];
foreach ($files as $file) {
    $diagnostics['files'][$file] = file_exists(__DIR__ . '/' . $file) ? 'exists' : 'missing';
}

// 5. Check GET/POST/JSON input
$diagnostics['request'] = [];
$diagnostics['request']['method'] = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$diagnostics['request']['content_type'] = $_SERVER['CONTENT_TYPE'] ?? 'not set';

// Try to read php://input
$input = @file_get_contents('php://input');
$diagnostics['request']['input_length'] = strlen($input);
$diagnostics['request']['input_sample'] = substr($input, 0, 200);

echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>