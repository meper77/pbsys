<?php
// Adding an admin is now done by allow-listing a UiTM email on the Admins page
// (they become admin on first OTP sign-in). This legacy page just redirects there.
session_start();
header('Location: /admin/admins.php');
exit;
