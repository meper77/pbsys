<?php

@include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

session_start();
session_unset();
session_destroy();

header('location:/auth/login_admin.php');
?>