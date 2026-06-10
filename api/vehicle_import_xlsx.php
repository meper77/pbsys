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

$slug_map = ['Staf' => 'staff', 'Pelajar' => 'student', 'Pelawat' => 'visitor', 'Kontraktor' => 'contractor'];

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

    for ($row = 2; $row <= $lastRow; $row++) {
        // B..I (col A = Bil is ignored).
        $plate  = trim((string) $sheet->getCell('B' . $row)->getValue());
        $type   = trim((string) $sheet->getCell('C' . $row)->getValue());
        $model  = trim((string) $sheet->getCell('D' . $row)->getValue());
        $date   = nv_cell_to_date($sheet->getCell('E' . $row));
        $idnum  = trim((string) $sheet->getCell('F' . $row)->getValue());
        $name   = trim((string) $sheet->getCell('G' . $row)->getValue());
        $phone  = trim((string) $sheet->getCell('H' . $row)->getValue());
        $serial = trim((string) $sheet->getCell('I' . $row)->getValue());

        if ($plate === '' && $name === '' && $phone === '') { continue; } // blank row

        if ($plate === '' || $phone === '') {
            $skipped++; $errors[] = "Row $row: plate and phone are required";
            continue;
        }

        // Reuse the register helper for identical behaviour.
        $_POST = [
            'platenum' => $plate, 'type' => $type, 'model' => $model,
            'date_taken' => $date, 'idnumber' => $idnum, 'name' => $name,
            'phone' => $phone, 'serial_no' => ($serial !== '' && ctype_digit($serial)) ? $serial : '',
        ];
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
