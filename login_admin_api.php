<?php
// login_admin_api.php
// Improve error logging on server: do not display errors to clients, log to php_error.log in same folder
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php_error.log');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection
$host = "localhost";
$db   = "neovtrack_db";
$user = "root";
$pass = ""; // change if your MySQL has a password

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    echo json_encode([
        'success' => 0,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => 0,
        'message' => 'Invalid request method'
    ]);
    exit;
}

$email    = '';
$password = '';

// Try JSON first
$input = json_decode(file_get_contents('php://input'), true);
if ($input && isset($input['email']) && isset($input['password'])) {
    $email = trim($input['email']);
    $password = trim($input['password']);
} else {
    // Fall back to form-encoded POST data
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
}

if ($email === '' || $password === '') {
    echo json_encode([
        'success' => 0,
        'message' => 'Email and password are required'
    ]);
    exit;
}

try {
    // Prepare SQL statement - using safer column names
    $stmt = $conn->prepare("SELECT id, name, email, password FROM admin WHERE email = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    // Use bind_result/fetch for compatibility
    $stmt->bind_result($admin_id, $admin_name, $admin_email, $admin_password);
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => 0,
            'message' => 'Invalid email or password'
        ]);
        $stmt->close();
        exit;
    }

    // Plain text password comparison
    if ($password !== $admin_password) {
        echo json_encode([
            'success' => 0,
            'message' => 'Invalid email or password'
        ]);
        $stmt->close();
        exit;
    }

    // Success
    echo json_encode([
        'success' => 1,
        'message' => 'Login successful',
        'admin' => [
            'id'    => (int)$admin_id,
            'name'  => $admin_name,
            'email' => $admin_email
        ]
    ]);

    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => 0,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

$conn->close();
exit;
