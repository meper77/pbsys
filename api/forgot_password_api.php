<?php
header('Content-Type: application/json');
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

$response = ["success" => 0, "message" => ""];

$step = $_POST['step'] ?? '';

if ($step === "1") {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        $response["message"] = "Email is required";
        echo json_encode($response);
        exit;
    }

    $check = mysqli_query($con, "SELECT * FROM user WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $response["success"] = 1;
        $response["message"] = "Email exists";
    } else {
        $response["message"] = "Email not found";
    }

    echo json_encode($response);
    exit;
}

if ($step === "2") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($password) || empty($confirm_password)) {
        $response["message"] = "All fields required";
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

    $update = mysqli_query($con, "UPDATE user SET password='$password' WHERE email='$email'");
    if ($update) {
        $response["success"] = 1;
        $response["message"] = "Password updated successfully";
    } else {
        $response["message"] = "Failed to update password";
    }

    echo json_encode($response);
    exit;
}

$response["message"] = "Invalid request";
echo json_encode($response);
