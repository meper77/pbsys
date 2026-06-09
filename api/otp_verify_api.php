<?php
/**
 * App/JSON endpoint: verify a one-time code and return the user identity.
 * POST {email, code}  ->  {success:1, user:{id,name,email,role}} or {success:0,message}
 *
 * The app keeps identity locally (the data APIs are open), so no server session is set here.
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
$code  = preg_replace('/\D/', '', (string)($in['code'] ?? ''));

if (!nv_valid_uitm_email($email)) {
    echo json_encode(['success' => 0, 'message' => 'Invalid email.']);
    exit;
}

$reason = null;
if (!nv_verify_otp($con, $email, $code, $reason)) {
    $map = [
        'too_many' => 'Too many attempts. Request a new code.',
        'expired'  => 'Code expired. Request a new one.',
        'bad'      => 'Invalid code. Please try again.',
    ];
    echo json_encode(['success' => 0, 'message' => $map[$reason] ?? 'Invalid code.']);
    exit;
}

$role = nv_role_for_email($con, $email);
$id   = nv_ensure_account($con, $email, $role);

$name = strstr($email, '@', true) ?: $email;
$table = $role === 'admin' ? 'admin' : 'user';
$esc = $con->real_escape_string($email);
if ($r = @$con->query("SELECT name FROM `$table` WHERE email = '$esc' LIMIT 1")) {
    $row = $r->fetch_assoc();
    if ($row && !empty($row['name'])) {
        $name = $row['name'];
    }
}

echo json_encode([
    'success' => 1,
    'user' => ['id' => $id, 'name' => $name, 'email' => $email, 'role' => $role],
]);
