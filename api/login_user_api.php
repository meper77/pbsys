<?php
// login_user_api.php
// Improve error logging on server: do not display errors to clients, log to php_error.log in same folder
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php_error.log');
header('Content-Type: application/json');

include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
$conn = $con;

if (!$conn) {
    echo json_encode([
        'success' => 0,
        'message' => 'Database connection failed'
    ]);
    exit();
}

// Get POST data (JSON or form-encoded)
$email = '';
$password = '';

// Try JSON first
$input = json_decode(file_get_contents('php://input'), true);
if ($input && isset($input['email']) && isset($input['password'])) {
    $email = trim($input['email']);
    $password = trim($input['password']);
} else {
    // Fall back to form-encoded POST data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
}

if ($email === '' || $password === '') {
    echo json_encode([
        'success' => 0,
        'message' => 'Email and password are required'
    ]);
    exit();
}

try {
    // Prepare SQL statement - using safer column names
    $stmt = $conn->prepare("SELECT userid, name, email, password FROM user WHERE email = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    // Use bind_result/fetch for compatibility (avoid get_result())
    $stmt->bind_result($userid, $name_db, $email_db, $password_db);
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => 0,
            'message' => 'Invalid email or password'
        ]);
        $stmt->close();
        exit();
    }

    // Plain text password comparison
    if ($password !== $password_db) {
        echo json_encode([
            'success' => 0,
            'message' => 'Invalid email or password'
        ]);
        $stmt->close();
        exit();
    }

    // Success
    echo json_encode([
        'success' => 1,
        'message' => 'Login successful',
        'user' => [
            'id'    => (int)$userid,
            'name'  => $name_db,
            'email' => $email_db
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
?>

