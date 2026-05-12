<?php
// login_api.php - Generic login endpoint
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
    
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check password
        if ($password === $user['password']) {
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
