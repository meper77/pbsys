<?php
// Delete student vehicle(s) from the unified owner table. POST + admin only.
require $_SERVER['DOCUMENT_ROOT'].'/includes/require_post_admin.php';
$ids = nv_post_ids();
if (!empty($ids)) {
    mysqli_query($con, "DELETE FROM `owner` WHERE id IN (" . implode(',', $ids) . ")");
}
header('Location: /vehicles/student/list.php');
exit;
