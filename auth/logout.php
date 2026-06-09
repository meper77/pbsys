<?php
/**
 * Single logout entry. Forgets the trusted device (so "log out" truly logs out,
 * even on a remembered device) and destroys the session.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/otp_auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
nv_forget_device($con);

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: /auth/login.php');
exit;
