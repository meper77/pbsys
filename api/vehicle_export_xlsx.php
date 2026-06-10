<?php
/**
 * Dedicated per-category 9-column XLSX export + blank template.
 *
 *   GET ?category=Staf|Pelajar|Pelawat|Kontraktor [&y=YYYY] [&m=1-12] [&template=1]
 *
 * Columns mirror the on-screen table so an exported file can be edited and
 * re-imported (api/vehicle_import_xlsx.php) as a round-trip:
 *   BIL | NO KENDERAAN | JENIS KENDERAAN | MODEL KENDERAAN | TARIKH AMBIL |
 *   NO PEKERJA/PELAJAR | NAMA | NO TELEFON | NO SIRI
 *
 * Admin only.
 */
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_helpers.php';

if (!isset($_SESSION['email_Admin'])) {
    http_response_code(403);
    exit('Admin access required');
}
nv_schema_autoprovision_once($con);

$cat_whitelist = ['Staf', 'Pelajar', 'Pelawat', 'Kontraktor'];
$category = $_GET['category'] ?? '';
if (!in_array($category, $cat_whitelist, true)) {
    http_response_code(400);
    exit('Invalid category');
}
$isTemplate = !empty($_GET['template']);
$fy = (isset($_GET['y']) && ctype_digit($_GET['y'])) ? (int) $_GET['y'] : 0;
$fm = (isset($_GET['m']) && ctype_digit($_GET['m']) && $_GET['m'] >= 1 && $_GET['m'] <= 12) ? (int) $_GET['m'] : 0;

// ID column header by category.
$id_header = ($category === 'Pelajar') ? 'NO PELAJAR'
           : (($category === 'Staf') ? 'NO PEKERJA' : 'NO PENGENALAN');
$headers = ['BIL', 'NO KENDERAAN', 'JENIS KENDERAAN', 'MODEL KENDERAAN', 'TARIKH AMBIL',
            $id_header, 'NAMA', 'NO TELEFON', 'NO SIRI'];

// Rows.
$dataRows = [];
if (!$isTemplate) {
    $cat = mysqli_real_escape_string($con, $category);
    $eff = "COALESCE(`date_taken`, `created_at`)";
    $where = "status='$cat'";
    if ($fy > 0) { $where .= " AND YEAR($eff) = $fy"; }
    if ($fm > 0) { $where .= " AND MONTH($eff) = $fm"; }
    $res = mysqli_query($con, "SELECT * FROM `owner` WHERE $where ORDER BY $eff DESC, id DESC");
    $bil = 1;
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $dateR = $r['date_taken'] ?? '';
            $dateD = ($dateR && $dateR !== '0000-00-00') ? date('Y-m-d', strtotime($dateR)) : '';
            $dataRows[] = [
                $bil++,
                strtoupper((string)($r['platenum'] ?? '')),
                strtoupper((string)($r['type'] ?? '')),
                strtoupper((string)(($r['model'] ?? '') !== '' ? $r['model'] : '')),
                $dateD,
                strtoupper((string)($r['idnumber'] ?? '')),
                strtoupper((string)($r['name'] ?? '')),
                (string)($r['phone'] ?? ''),
                (isset($r['serial_no']) && $r['serial_no'] !== null && $r['serial_no'] !== '') ? (int) $r['serial_no'] : '',
            ];
        }
    }
} else {
    // Two example rows to show the expected shape.
    $dataRows[] = [1, 'JSX1234', 'KERETA', 'PERODUA MYVI', date('Y-m-d'), ($category === 'Pelajar' ? '2023123456' : '200456'), 'AHMAD BIN ALI', '0123456789', 1];
    $dataRows[] = [2, 'JMB6789', 'MOTOSIKAL', 'YAMAHA Y15', date('Y-m-d'), ($category === 'Pelajar' ? '2023987654' : '200789'), 'SITI BINTI YUSOF', '0139876543', 2];
}

require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle(substr($category, 0, 31));

$sheet->fromArray([$headers], null, 'A1');
$sheet->getStyle('A1:I1')->applyFromArray([
    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
]);

// Write TARIKH AMBIL + NO SIRI as text/number explicitly to keep the round-trip stable.
$rowNo = 2;
foreach ($dataRows as $d) {
    $sheet->fromArray([$d], null, 'A' . $rowNo);
    $sheet->getCell('E' . $rowNo)->setValueExplicit((string)$d[4], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $rowNo++;
}

// JENIS KENDERAAN dropdown (KERETA | MOTOSIKAL) for staff/student; broader for others.
$typeOpts = in_array($category, ['Staf', 'Pelajar'], true)
    ? ['KERETA', 'MOTOSIKAL']
    : ['KERETA', 'MOTOSIKAL', 'LORI', '4WD', 'VAN', 'MPV', 'LAIN-LAIN'];
for ($r = 2; $r <= 500; $r++) {
    $dv = $sheet->getCell('C' . $r)->getDataValidation();
    $dv->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
    $dv->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
    $dv->setAllowBlank(true);
    $dv->setShowDropDown(true);
    $dv->setShowErrorMessage(true);
    $dv->setErrorTitle('Invalid type');
    $dv->setError('Choose a vehicle type from the list.');
    $dv->setFormula1('"' . implode(',', $typeOpts) . '"');
}

foreach (['A'=>6,'B'=>16,'C'=>16,'D'=>20,'E'=>14,'F'=>16,'G'=>28,'H'=>16,'I'=>10] as $col => $w) {
    $sheet->getColumnDimension($col)->setWidth($w);
}

$suffix = $isTemplate ? 'template' : ($fy > 0 ? ($fm > 0 ? sprintf('%04d-%02d', $fy, $fm) : (string)$fy) : 'all');
$filename = $category . '_' . $suffix . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('php://output');
exit;
