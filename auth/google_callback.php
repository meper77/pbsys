<?php
/**
 * Google Identity Services callback (login_uri target).
 *
 * GIS POSTs: credential (the ID token JWT) + g_csrf_token, and also sets a
 * g_csrf_token cookie. We verify the double-submit token, then verify the ID
 * token server-side and establish the session.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/i18n.php';   // session + ?lang
include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/auth_guard.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/google_auth.php';

function nv_login_fail(string $reason): void
{
    $_SESSION['login_error'] = $reason;
    header('Location: /auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auth/login.php');
    exit;
}

// CSRF: the posted token must match the cookie GIS set (double-submit).
$bodyTok   = $_POST['g_csrf_token'] ?? '';
$cookieTok = $_COOKIE['g_csrf_token'] ?? '';
if ($bodyTok === '' || $cookieTok === '' || !hash_equals($cookieTok, $bodyTok)) {
    nv_login_fail('google:csrf');
}

$credential = $_POST['credential'] ?? '';
if ($credential === '') {
    nv_login_fail('google:missing');
}

$err   = null;
$email = nv_google_login($con, $credential, $err);
if ($email === false || $email === null) {
    nv_login_fail('google:' . ($err ?: 'failed'));
}

// Session established — route to the right home.
$role = nv_is_admin() ? 'admin' : 'user';
header('Location: ' . ($role === 'admin' ? '/index.php' : '/admin/index_user.php'));
exit;
