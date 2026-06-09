<?php
/**
 * App/JSON endpoint: request a one-time sign-in code.
 * POST {email}  (JSON or form)  ->  {success:1} or {success:0,message}
 */
header('Content-Type: application/json');
include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/otp_auth.php';

function nv_json_input(): array {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        $j = json_decode(file_get_contents('php://input'), true);
        return is_array($j) ? $j : [];
    }
    return $_POST;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => 0, 'message' => 'POST required']);
    exit;
}

$in = nv_json_input();
$email = nv_norm_email($in['email'] ?? '');

if (!nv_valid_uitm_email($email)) {
    echo json_encode(['success' => 0, 'message' => 'Use your UiTM email (@uitm.edu.my or @student.uitm.edu.my).']);
    exit;
}

$err = null;
if (nv_create_and_send_otp($con, $email, $err)) {
    echo json_encode(['success' => 1, 'message' => 'A sign-in code has been emailed to you.']);
    exit;
}

$map = [
    'rate' => 'Please wait a moment before requesting another code.',
    'send' => 'We could not send the email right now. Please try again shortly.',
    'db'   => 'Sign-in is temporarily unavailable. Please try again later.',
];
echo json_encode(['success' => 0, 'message' => $map[$err] ?? 'Could not send code.']);
