<?php
// Single sign in/up entry now (role is derived from the admin allowlist).
session_start();
$lang_param = isset($_SESSION['language']) ? '?lang=' . $_SESSION['language'] : '';
header('Location: /auth/login.php' . $lang_param);
exit;
