<?php
// POST + admin only (was also GET-deletable via ?id=).
require $_SERVER['DOCUMENT_ROOT'].'/includes/require_post_admin.php';

$ids = nv_post_ids();
$deleted = 0;
$error   = '';

if (!empty($ids)) {
    $ids = array_values(array_unique($ids));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "DELETE FROM vehicle_reports WHERE id IN ($placeholders)";
    $stmt = mysqli_prepare($con, $sql);
    if ($stmt) {
        $types = str_repeat('i', count($ids));
        mysqli_stmt_bind_param($stmt, $types, ...$ids);
        if (mysqli_stmt_execute($stmt)) {
            $deleted = mysqli_stmt_affected_rows($stmt);
        } else {
            $error = mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = mysqli_error($con);
    }
}

if ($error !== '') {
    $_SESSION['reports_flash'] = ['type' => 'bad', 'message' => 'Delete failed: ' . $error];
} elseif ($deleted > 0) {
    $_SESSION['reports_flash'] = ['type' => 'ok', 'message' => $deleted . ' laporan padam.'];
} elseif (!empty($ids)) {
    $_SESSION['reports_flash'] = ['type' => 'warn', 'message' => 'No matching report records.'];
}

header('Location: /admin/reports.php');
exit;
