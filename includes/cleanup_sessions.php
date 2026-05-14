<?php
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

// Clean up old sessions (older than 24 hours)
$query = "DELETE FROM user_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
mysqli_query($con, $query);

// Also clean sessions older than 30 days from admin and user last_login
$query = "UPDATE user SET last_login = NULL WHERE last_login < DATE_SUB(NOW(), INTERVAL 30 DAY)";
mysqli_query($con, $query);

$query = "UPDATE admin SET last_login = NULL WHERE last_login < DATE_SUB(NOW(), INTERVAL 30 DAY)";
mysqli_query($con, $query);

echo "Session cleanup completed at " . date('Y-m-d H:i:s');
mysqli_close($con);
?>