<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "neovtrack_db");

if ($conn->connect_error) {
    die(json_encode(["success"=>0, "message"=>"DB connection failed"]));
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(["success"=>0, "message"=>"Email or password missing"]);
    exit;
}

$sql = "SELECT userid, name, email FROM user WHERE email=? AND password=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    echo json_encode([
        "success" => 1,
        "user" => $user
    ]);
} else {
    echo json_encode(["success"=>0, "message"=>"Invalid credentials"]);
}
