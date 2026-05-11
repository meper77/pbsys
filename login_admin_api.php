<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include 'connect.php';

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

$emailSafe    = mysqli_real_escape_string($con, $email);
$passwordSafe = mysqli_real_escape_string($con, $password);

$sql = "
    SELECT id, name, email
    FROM admin
    WHERE email = '$emailSafe'
      AND password = '$passwordSafe'
    LIMIT 1
";

$result = mysqli_query($con, $sql);

if ($result && mysqli_num_rows($result) === 1) {
    $admin = mysqli_fetch_assoc($result);

    echo json_encode([
        'success' => 1,
        'admin' => [
            'id' => (int)$admin['id'],
            'name' => $admin['name'],
            'email' => $admin['email']
        ]
    ]);
} else {
    echo json_encode([
        'success' => 0,
        'message' => 'Invalid email or password'
    ]);
}

mysqli_close($con);
exit;
