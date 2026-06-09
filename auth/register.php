<?php
// Sign-up is now the same passwordless flow as sign-in (UiTM email + one-time code).
session_start();
$lang_param = isset($_SESSION['language']) ? '?lang=' . $_SESSION['language'] : '';
header('Location: /auth/login.php' . $lang_param);
exit;
