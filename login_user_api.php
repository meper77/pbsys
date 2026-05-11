<?php
// login_user_api.php
header('Content-Type: application/json');

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

// Get POST data
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if ($email === '' || $password === '') {
    echo json_encode([
        'success' => 0,
        'message' => 'Email and password are required'
    ]);
    exit();
}

// Prepare SQL statement
$stmt = $conn->prepare("SELECT userid, name, email, password FROM user WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => 0,
        'message' => 'Invalid email or password'
    ]);
    exit();
}

$user = $result->fetch_assoc();

// Plain text password comparison ✅
if ($password !== $user['password']) {
    echo json_encode([
        'success' => 0,
        'message' => 'Invalid email or password'
    ]);
    exit();
}

// Success
echo json_encode([
    'success' => 1,
    'message' => 'Login successful',
    'user' => [
        'id'    => (int)$user['userid'],
        'name'  => $user['name'],
        'email' => $user['email']
    ]
]);

$stmt->close();
$conn->close();
?>


