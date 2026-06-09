<?php
// Passwordless: there is no password to reset. The OTP login is the recovery path.
session_start();
$lang_param = isset($_SESSION['language']) ? '?lang=' . $_SESSION['language'] : '';
header('Location: /auth/login.php' . $lang_param);
exit;
