<?php
/**
 * Dedicated per-category 9-column XLSX import.
 *
 *   POST  category=Staf|Pelajar|Pelawat|Kontraktor,  file: xlsx_file
 *
 * Reads the same layout produced by api/vehicle_export_xlsx.php (the Bil column A
 * is ignored), and reuses nv_vehicle_register() per row so import behaves exactly
 * like the on-screen register (reactivation, uppercasing, shared per-year serial).
 *
 * Admin only. Redirects back to the category list with a flash summary.
 */
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_helpers.php';

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
$slug = $slug_map[$category];
$listUrl = '/vehicles/' . $slug . '/list.php';

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

/** Excel cell -> Y-m-d string (handles both real dates and plain text). */
function nv_cell_to_date($cell): string {
    $v = $cell->getValue();
    if ($v === null || $v === '') { return ''; }
    if (is_numeric($v)) {
        try { return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $v)->format('Y-m-d'); }
        catch (\Throwable $e) { return ''; }
    }
    $ts = strtotime((string) $v);
    return $ts ? date('Y-m-d', $ts) : '';
}

try {
    require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $reader->setReadDataOnly(false);
    $spreadsheet = $reader->load($_FILES['xlsx_file']['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();

    $added = 0; $updated = 0; $skipped = 0; $errors = [];
    $lastRow = $sheet->getHighestDataRow();

    // Map each spec column (by position) to a register field, mirroring the export.
    $cols = nv_category_xlsx_cols($category);
    $kindToField = ['plate' => 'platenum', 'type' => 'type', 'model' => 'model', 'date' => 'date_taken',
                    'idnum' => 'idnumber', 'name' => 'name', 'phone' => 'phone', 'serial' => 'serial_no',
                    'company' => 'company', 'email' => 'email', 'note' => 'note'];

    for ($row = 2; $row <= $lastRow; $row++) {
        $vals = [];
        foreach ($cols as $i => $c) {
            $kind = $c[2];
            if ($kind === 'bil' || !isset($kindToField[$kind])) { continue; }
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            if ($kind === 'date') {
                $vals['date_taken'] = nv_cell_to_date($sheet->getCell($letter . $row));
            } else {
                $vals[$kindToField[$kind]] = trim((string) $sheet->getCell($letter . $row)->getValue());
            }
        }

        $plate = $vals['platenum'] ?? '';
        $phone = $vals['phone'] ?? '';
        $name  = $vals['name'] ?? '';
        if ($plate === '' && $name === '' && $phone === '') { continue; } // blank row
        if ($plate === '' || $phone === '') {
            $skipped++; $errors[] = "Row $row: plate and phone are required";
            continue;
        }
        if (isset($vals['serial_no']) && !($vals['serial_no'] !== '' && ctype_digit($vals['serial_no']))) { $vals['serial_no'] = ''; }

        // Reuse the register helper for identical behaviour (reactivation, uppercasing, serial).
        $_POST = $vals;
        $err = '';
        $res = nv_vehicle_register($con, $category, $err);
        if ($res === 'created') { $added++; }
        elseif ($res === 'reactivated') { $updated++; }
        else { $skipped++; $errors[] = "Row $row: " . ($err === 'plate_exists' ? 'plate belongs to another owner' : ($err ?: 'skipped')); }
    }

    $msg = "Import complete: $added added, $updated updated, $skipped skipped.";
    if ($errors) { $msg .= ' (' . implode('; ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? '…' : '') . ')'; }
    nv_import_redirect($listUrl, $msg);

} catch (\Throwable $e) {
    nv_import_redirect($listUrl, 'Import failed: ' . $e->getMessage());
}
