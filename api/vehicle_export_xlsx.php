<?php
/**
 * Per-category XLSX export + blank template in the official Polis Bantuan format
 * (includes/vehicle_xlsx.php): title row, official headers, per-month sheets for
 * staff/student/visitor, single sheet for contractor/pesara; JA#### serials,
 * DD/MM/YYYY dates. Round-trips through api/vehicle_import_xlsx.php.
 *
 *   GET ?category=Staf|Pelajar|Pelawat|Kontraktor|Pesara [&y=YYYY] [&m=1-12] [&template=1]
 *
 * Admin only.
 */
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_xlsx.php';

if (!isset($_SESSION['email_Admin'])) {
    http_response_code(403);
    exit('Admin access required');
}
nv_schema_autoprovision_once($con);

$cat_whitelist = ['Staf', 'Pelajar', 'Pelawat', 'Kontraktor', 'Pesara'];
$category = $_GET['category'] ?? '';
if (!in_array($category, $cat_whitelist, true)) {
    http_response_code(400);
    exit('Invalid category');
}
$isTemplate = !empty($_GET['template']);
$fy = (isset($_GET['y']) && ctype_digit($_GET['y'])) ? (int) $_GET['y'] : 0;
$fm = (isset($_GET['m']) && ctype_digit($_GET['m']) && $_GET['m'] >= 1 && $_GET['m'] <= 12) ? (int) $_GET['m'] : 0;
$year = $fy > 0 ? $fy : (int) date('Y');

$spreadsheet = nv_xlsx_build($con, $category, $year, $fm, $isTemplate);

$suffix = $isTemplate ? 'TEMPLATE' : ($fm > 0 ? sprintf('%04d-%02d', $year, $fm) : (string) $year);
$filename = strtoupper(nv_xlsx_meta($category)['cat']) . '_' . $suffix . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('php://output');
exit;
