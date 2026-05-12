<?php
// login_admin_api.php
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

$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

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
    
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => 0,
            'message' => 'Invalid email or password'
        ]);
        exit;
    }

    $admin = $result->fetch_assoc();

    // Plain text password comparison
    if ($password !== $admin['password']) {
        echo json_encode([
            'success' => 0,
            'message' => 'Invalid email or password'
        ]);
        exit;
    }

    // Success
    echo json_encode([
        'success' => 1,
        'message' => 'Login successful',
        'admin' => [
            'id'    => (int)$admin['id'],
            'name'  => $admin['name'],
            'email' => $admin['email']
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
