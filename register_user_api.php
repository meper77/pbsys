<?php
header('Content-Type: application/json');
include 'connect.php';

$response = ["success" => 0, "message" => ""];

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
    $response["message"] = "Please fill in all fields";
    echo json_encode($response);
    exit;
}

if (strlen($password) < 6) {
    $response["message"] = "Password must be at least 6 characters";
    echo json_encode($response);
    exit;
}

if ($password !== $confirm_password) {
    $response["message"] = "Passwords do not match";
    echo json_encode($response);
    exit;
}

$check = mysqli_query($con, "SELECT * FROM user WHERE email='$email'");
if (mysqli_num_rows($check) > 0) {
    $response["message"] = "Email already registered";
    echo json_encode($response);
    exit;
}

$sql = "INSERT INTO user (name, email, password) VALUES ('$name', '$email', '$password')";
if (mysqli_query($con, $sql)) {
    $response["success"] = 1;
    $response["message"] = "Registration successful";
} else {
    $response["message"] = "Registration failed";
}

echo json_encode($response);
