<?php
/**
 * Per-category XLSX import in the official Polis Bantuan format (title row1,
 * headers row2, data row3+; per-month sheets for staff/student/visitor). Reads
 * the same shape produced by api/vehicle_export_xlsx.php and reuses
 * nv_vehicle_register() per row (reactivation, uppercasing, shared serial).
 *
 *   POST  category=Staf|Pelajar|Pelawat|Kontraktor|Pesara,  file: xlsx_file
 *
 * Admin only. Redirects back to the category list with a flash summary.
 */
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_xlsx.php';

$slug_map = ['Staf' => 'staff', 'Pelajar' => 'student', 'Pelawat' => 'visitor', 'Kontraktor' => 'contractor', 'Pesara' => 'alumni'];

if (!isset($_SESSION['email_Admin'])) {
    http_response_code(403);
    exit('Admin access required');
}

$category = $_POST['category'] ?? '';
if (!isset($slug_map[$category])) {
    http_response_code(400);
    exit('Invalid category');
}
$listUrl = '/vehicles/' . $slug_map[$category] . '/list.php';

function nv_import_redirect($listUrl, $msg) {
    $_SESSION['success_message'] = $msg;
    header('Location: ' . $listUrl);
    exit;
}

if (!isset($_FILES['xlsx_file']) || $_FILES['xlsx_file']['error'] !== UPLOAD_ERR_OK) {
    nv_import_redirect($listUrl, 'Import failed: no file uploaded.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['xlsx_file']['tmp_name']);
finfo_close($finfo);
if (!in_array($mime, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'application/zip'], true)) {
    nv_import_redirect($listUrl, 'Import failed: file must be .xlsx.');
}

nv_schema_autoprovision_once($con);

try {
    require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($_FILES['xlsx_file']['tmp_name']);

    $stats = [];
    nv_xlsx_import($spreadsheet, $category, $con, $stats);

    $msg = "Import complete: {$stats['added']} added, {$stats['skipped']} skipped.";
    if (!empty($stats['errors'])) {
        $msg .= ' (' . implode('; ', array_slice($stats['errors'], 0, 5)) . (count($stats['errors']) > 5 ? '…' : '') . ')';
    }
    nv_import_redirect($listUrl, $msg);

} catch (\Throwable $e) {
    nv_import_redirect($listUrl, 'Import failed: ' . $e->getMessage());
}
