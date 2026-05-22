<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

if (!isset($_SESSION['email_Admin'])) {
    header('Location: /auth/login_admin.php');
    exit;
}

$ids = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ids']) && is_array($_POST['ids'])) {
    foreach ($_POST['ids'] as $v) {
        $n = (int)$v;
        if ($n > 0) { $ids[] = $n; }
    }
} elseif (isset($_GET['id'])) {
    $n = (int)$_GET['id'];
    if ($n > 0) { $ids[] = $n; }
}

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
