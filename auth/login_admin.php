<?php
/**
 * Legacy admin login retired. The unified /auth/login.php now handles both roles
 * (role is derived from the admin allowlist) via Google Sign-In or email+password.
 * Redirecting here removes the old plaintext-password, SQL-injectable form.
 */
session_start();
$lang_param = isset($_SESSION['language']) ? '?lang=' . $_SESSION['language'] : '';
header('Location: /auth/login.php' . $lang_param);
exit;
