<?php
// Delete user(s). POST + admin only (was: no auth + GET + SQL injection).
require $_SERVER['DOCUMENT_ROOT'].'/includes/require_post_admin.php';

$ids = nv_post_ids();
if (!empty($ids)) {
    mysqli_query($con, "DELETE FROM `user` WHERE userid IN (" . implode(',', $ids) . ")");
}
header('Location: /admin/users.php');
exit;
