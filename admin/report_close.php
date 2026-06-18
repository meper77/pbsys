<?php
/**
 * Close / reopen a vehicle report. Admin only, POST only.
 *   POST id=<report id>, action=close|reopen [, back=<same-site path>]
 * Closing stamps closed_at = NOW() and closed_by = the admin's email; reopening
 * clears them. Redirects back to the reports list (or the provided same-site path).
 */
require $_SERVER['DOCUMENT_ROOT'].'/includes/require_post_admin.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/schema_guard.php';
nv_ensure_report_events($con);

$id     = (int) ($_POST['id'] ?? 0);
$action = (($_POST['action'] ?? 'close') === 'reopen') ? 'reopen' : 'close';
$back   = (string) ($_POST['back'] ?? '/admin/reports.php');
if ($back === '' || $back[0] !== '/') { $back = '/admin/reports.php'; }   // same-site only

if ($id > 0) {
    $me = (string) ($_SESSION['email_Admin'] ?? '');
    if ($action === 'reopen') {
        if ($stmt = mysqli_prepare($con, "UPDATE vehicle_reports SET closed_at = NULL, closed_by = NULL WHERE id = ?")) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
        }
    } else {
        if ($stmt = mysqli_prepare($con, "UPDATE vehicle_reports SET closed_at = NOW(), closed_by = ? WHERE id = ?")) {
            mysqli_stmt_bind_param($stmt, 'si', $me, $id);
            mysqli_stmt_execute($stmt);
        }
    }
    // Record the event so the report timeline shows the full close/reopen history.
    if ($stmt = mysqli_prepare($con, "INSERT INTO `report_events` (`report_id`, `action`, `actor`) VALUES (?, ?, ?)")) {
        mysqli_stmt_bind_param($stmt, 'iss', $id, $action, $me);
        mysqli_stmt_execute($stmt);
    }
}

header('Location: ' . $back);
exit;
