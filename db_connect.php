<?php
$host = "localhost";
$db_user = "root"; // change if your XAMPP MySQL user is different
$db_pass = "";     // leave blank if no password
$db_name = "neovtrack_db";

$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
