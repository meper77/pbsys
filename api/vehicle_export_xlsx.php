<?php
/**
 * Per-category XLSX export + blank template (foundation: staff/student 9-col,
 * contractor 12-col, alumni/pesara 10-col). Columns come from the shared spec
 * nv_category_xlsx_cols() so an exported file round-trips through the importer.
 *
 *   GET ?category=Staf|Pelajar|Pelawat|Kontraktor|Pesara [&y=YYYY] [&m=1-12] [&template=1]
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

$cat_whitelist = ['Staf', 'Pelajar', 'Pelawat', 'Kontraktor', 'Pesara'];
$category = $_GET['category'] ?? '';
if (!in_array($category, $cat_whitelist, true)) {
    http_response_code(400);
    exit('Invalid category');
}
$isTemplate = !empty($_GET['template']);
$fy = (isset($_GET['y']) && ctype_digit($_GET['y'])) ? (int) $_GET['y'] : 0;
$fm = (isset($_GET['m']) && ctype_digit($_GET['m']) && $_GET['m'] >= 1 && $_GET['m'] <= 12) ? (int) $_GET['m'] : 0;

$cols    = nv_category_xlsx_cols($category);
$headers = array_map(function ($c) { return $c[0]; }, $cols);

/** Render one export cell by kind from an owner row. */
$renderCell = function (string $kind, array $r, int $bil) {
    switch ($kind) {
        case 'bil':    return $bil;
        case 'plate':  return strtoupper((string)($r['platenum'] ?? ''));
        case 'type':   return strtoupper((string)($r['type'] ?? ''));
        case 'model':  $m = (string)($r['model'] ?? ''); return ($m !== '' && $m !== 'N/A') ? strtoupper($m) : '';
        case 'date':   $d = $r['date_taken'] ?? ''; return ($d && $d !== '0000-00-00') ? date('Y-m-d', strtotime($d)) : '';
        case 'idnum':  return strtoupper((string)($r['idnumber'] ?? ''));
        case 'name':   return strtoupper((string)($r['name'] ?? ''));
        case 'phone':  return (string)($r['phone'] ?? '');
        case 'serial': return (isset($r['serial_no']) && $r['serial_no'] !== null && $r['serial_no'] !== '') ? (int)$r['serial_no'] : '';
        case 'company':return strtoupper((string)($r['company'] ?? ''));
        case 'email':  return (string)($r['ownerEmail'] ?? '');
        case 'note':   return strtoupper((string)($r['note'] ?? ''));
    }
    return '';
};

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
            $row = [];
            foreach ($cols as $c) { $row[] = $renderCell($c[2], $r, $bil); }
            $dataRows[] = $row;
            $bil++;
        }
    }
} else {
    // Two example rows to show the expected shape (per kind).
    $ex = [
        ['name' => 'AHMAD BIN ALI', 'plate' => 'JSX1234', 'type' => 'KERETA', 'model' => 'PERODUA MYVI',
         'company' => 'ABC SDN BHD', 'phone' => '0123456789', 'email' => 'ahmad@abc.com', 'note' => 'VIP'],
        ['name' => 'SITI BINTI YUSOF', 'plate' => 'JMB6789', 'type' => 'MOTOSIKAL', 'model' => 'YAMAHA Y15',
         'company' => 'XYZ ENTERPRISE', 'phone' => '0139876543', 'email' => 'siti@xyz.com', 'note' => ''],
    ];
    $idEx = ($category === 'Pelajar') ? ['2023123456', '2023987654']
          : (in_array($category, ['Kontraktor', 'Pesara'], true) ? ['990101-01-1234', '880202-02-5678'] : ['200456', '200789']);
    foreach ([0, 1] as $i) {
        $row = [];
        foreach ($cols as $c) {
            switch ($c[2]) {
                case 'bil':    $row[] = $i + 1; break;
                case 'serial': $row[] = $i + 1; break;
                case 'idnum':  $row[] = $idEx[$i]; break;
                case 'date':   $row[] = date('Y-m-d'); break;
                default:       $row[] = $ex[$i][$c[2]] ?? ''; break;
            }
        }
        $dataRows[] = $row;
    }
}

require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle(substr($category, 0, 31));

$nCols   = count($cols);
$lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nCols);

$sheet->fromArray([$headers], null, 'A1');
$sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
]);

// Find the date + type columns by kind for explicit-text + dropdown handling.
$dateColIdx = null; $typeColIdx = null;
foreach ($cols as $i => $c) {
    if ($c[2] === 'date') { $dateColIdx = $i + 1; }
    if ($c[2] === 'type') { $typeColIdx = $i + 1; }
}

$rowNo = 2;
foreach ($dataRows as $d) {
    $sheet->fromArray([$d], null, 'A' . $rowNo);
    if ($dateColIdx) {
        $dl = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dateColIdx);
        $sheet->getCell($dl . $rowNo)->setValueExplicit((string)($d[$dateColIdx - 1] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    }
    $rowNo++;
}

// JENIS/KENDERAAN dropdown (KERETA | MOTOSIKAL) on the type column.
if ($typeColIdx) {
    $tl = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($typeColIdx);
    for ($r = 2; $r <= 500; $r++) {
        $dv = $sheet->getCell($tl . $r)->getDataValidation();
        $dv->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $dv->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
        $dv->setAllowBlank(true);
        $dv->setShowDropDown(true);
        $dv->setShowErrorMessage(true);
        $dv->setErrorTitle('Invalid type');
        $dv->setError('Choose KERETA or MOTOSIKAL.');
        $dv->setFormula1('"KERETA,MOTOSIKAL"');
    }
}

for ($i = 1; $i <= $nCols; $i++) {
    $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
    $kind = $cols[$i - 1][2];
    $w = ['bil' => 6, 'serial' => 10, 'plate' => 16, 'type' => 14, 'model' => 20, 'date' => 16,
          'idnum' => 18, 'name' => 28, 'phone' => 16, 'company' => 24, 'email' => 26, 'note' => 24][$kind] ?? 16;
    $sheet->getColumnDimension($letter)->setWidth($w);
}

$suffix = $isTemplate ? 'template' : ($fy > 0 ? ($fm > 0 ? sprintf('%04d-%02d', $fy, $fm) : (string)$fy) : 'all');
$filename = $category . '_' . $suffix . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$writer->save('php://output');
exit;
