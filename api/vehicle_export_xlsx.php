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

require_once $_SERVER['DOCUMENT_ROOT'].'/includes/auth_guard.php';
nv_schema_autoprovision_once($con);

$cat_whitelist = ['Staf', 'Pelajar', 'Pelawat', 'Kontraktor', 'Pesara'];
$category = $_GET['category'] ?? '';
if (!in_array($category, $cat_whitelist, true)) {
    http_response_code(400);
    exit('Invalid category');
}
// Admins, or users granted this category, may export.
$exp_slug = ['Staf' => 'staff', 'Pelajar' => 'student', 'Pelawat' => 'visitor', 'Kontraktor' => 'contractor', 'Pesara' => 'alumni'][$category] ?? '';
if (!nv_can_access_page($con, $exp_slug)) {
    http_response_code(403);
    exit('Access denied');
}
$isTemplate = !empty($_GET['template']);
$fy = (isset($_GET['y']) && ctype_digit($_GET['y'])) ? (int) $_GET['y'] : 0;
$fm = (isset($_GET['m']) && ctype_digit($_GET['m']) && $_GET['m'] >= 1 && $_GET['m'] <= 12) ? (int) $_GET['m'] : 0;
$year = $fy > 0 ? $fy : (int) date('Y');

// The official blank workbook for this category (a copy of the foundation/assets form
// with the data removed), if one exists.
$static = $_SERVER['DOCUMENT_ROOT'] . '/assets/templates/' . basename($category) . '.xlsx';

// Template download: serve the official blank file as-is.
if ($isTemplate && is_file($static)) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . strtoupper(nv_xlsx_meta($category)['cat']) . '_TEMPLATE.xlsx"');
    header('Content-Length: ' . filesize($static));
    header('Cache-Control: max-age=0');
    readfile($static);
    exit;
}

// Filled export: pour live data into the official template so the download is identical
// in look to the template (title, month sheets, borders, dropdowns). Fall back to the
// generated layout if the template is missing or the zip reader is unavailable.
$spreadsheet = null;
$filled = false;
$rowCount = 0;
if (!$isTemplate && is_file($static)) {
    try {
        $spreadsheet = nv_xlsx_fill_template($static, $con, $category, $year, $fm, $rowCount);
        $filled = true;
    } catch (\Throwable $e) {
        $spreadsheet = null;
        $filled = false;
    }
}
if ($spreadsheet === null) {
    $spreadsheet = nv_xlsx_build($con, $category, $year, $fm, $isTemplate);
}

// Safeguard: nothing to export for this scope — send the user back with a notice
// instead of handing them a blank workbook.
if ($filled && $rowCount === 0) {
    $scope = $fm > 0 ? ($year . '-' . sprintf('%02d', $fm)) : (string) $year;
    $_SESSION['success_message'] = 'Tiada rekod untuk dieksport (' . $scope . ').';
    header('Location: /vehicles/' . $exp_slug . '/list.php' . ($fy > 0 ? '?y=' . (int) $fy : ''));
    exit;
}

$suffix = $isTemplate ? 'TEMPLATE' : ($fm > 0 ? sprintf('%04d-%02d', $year, $fm) : (string) $year);
$filename = strtoupper(nv_xlsx_meta($category)['cat']) . '_' . $suffix . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
if ($filled) {
    // PhpSpreadsheet drops the OOXML <family> font hint on save, so the title font
    // (Bernard MT Condensed) substitutes to a different weight than the template in viewers
    // that lack the font. Save to a temp file, restore <family>, then stream — so the export
    // renders identically to the template download.
    $tmp = tempnam(sys_get_temp_dir(), 'nvx');
    $writer->save($tmp);
    nv_xlsx_restore_font_family($tmp);
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
} else {
    $writer->save('php://output');
}
exit;
