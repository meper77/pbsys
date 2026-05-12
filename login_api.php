<?php
// login_api.php - Generic login endpoint
// Improve error logging on server: do not display errors to clients, log to php_error.log in same folder
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php_error.log');
header('Content-Type: application/json; charset=UTF-8');

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

$email = '';
$password = '';

// Try JSON first
$input = json_decode(file_get_contents('php://input'), true);
if ($input && isset($input['email']) && isset($input['password'])) {
    $email = trim($input['email']);
    $password = trim($input['password']);
} else {
    // Fall back to form-encoded POST data
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
}

if (empty($email) || empty($password)) {
    echo json_encode([
        'success' => 0,
        'message' => 'Email and password are required'
    ]);
    exit();
}

try {
    // Try user table first
    $stmt = $conn->prepare("SELECT userid as id, name, email, password FROM user WHERE email = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    // Use bind_result/fetch for compatibility
    $stmt->bind_result($userid, $name_db, $email_db, $password_db);
    if ($stmt->fetch()) {
        // Check password
        if ($password === $password_db) {
            $user = [
                'id' => $userid,
                'name' => $name_db,
                'email' => $email_db,
                'password' => $password_db
            ];
            echo json_encode([
                'success' => 1,
                'user' => $user
            ]);
            $stmt->close();
            $conn->close();
            exit();
        }
    }
    
    $stmt->close();
    
    // No user found with valid credentials
    echo json_encode([
        'success' => 0,
        'message' => 'Invalid credentials'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => 0,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

$conn->close();
exit;
