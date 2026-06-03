<?php
// Password reset is SMTP-only (token-based). The old direct-reset form was insecure
// (anyone could set any account's password by email) and has been disabled.
session_start();
$lang_param = isset($_SESSION['language']) ? '?lang=' . $_SESSION['language'] : '';
header('Location: /auth/forgot_password_smtp.php' . $lang_param);
exit();
