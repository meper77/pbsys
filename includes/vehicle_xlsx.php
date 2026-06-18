<?php
/**
 * Official Polis Bantuan xlsx format (matches foundation/assets/*.xlsx).
 *
 *   - Row 1 = a merged title ("MAKLUMAN PELEKAT KENDERAAN STAF BULAN MAC TAHUN 2026").
 *   - Row 2 = the category headers (nv_category_xlsx_cols).
 *   - Row 3+ = data. NO SIRI is text "JA0001"; TARIKH is DD/MM/YYYY.
 *   - Staff / Student / Visitor split one sheet per month (JANUARI..DISEMBER);
 *     Contractor + Pesara are a single sheet.
 *
 * Shared by the template (blank), export (filled) and import (read) so they
 * round-trip identically. Requires PhpSpreadsheet (vendored).
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/vehicle_helpers.php';

if (!defined('NV_SERIAL_PREFIX')) { define('NV_SERIAL_PREFIX', 'JA'); }

function nv_xlsx_months(): array {
    return ['JANUARI','FEBRUARI','MAC','APRIL','MEI','JUN','JULAI','OGOS','SEPTEMBER','OKTOBER','NOVEMBER','DISEMBER'];
}

/** Per-category sheet mode + title. */
function nv_xlsx_meta(string $category): array {
    switch ($category) {
        case 'Staf':       return ['mode'=>'month', 'cat'=>'STAF'];
        case 'Pelajar':    return ['mode'=>'month', 'cat'=>'PELAJAR'];
        case 'Pelawat':    return ['mode'=>'month', 'cat'=>'PELAWAT'];
        case 'Kontraktor': return ['mode'=>'single', 'sheet'=>'KONTRAK', 'cat'=>'KONTRAK'];
        case 'Pesara':     return ['mode'=>'single', 'sheet'=>'%YEAR%', 'cat'=>'PESARA'];
        default:           return ['mode'=>'single', 'sheet'=>'DATA', 'cat'=>strtoupper($category)];
    }
}

/** Title text for a sheet. $monthName '' for single-sheet (no BULAN). */
function nv_xlsx_title(string $category, string $monthName, int $year): string {
    if ($category === 'Pesara') {
        return 'MAKLUMAT MANUAL PELEKAT PESARA TAHUN ' . $year;
    }
    $cat = nv_xlsx_meta($category)['cat'];
    $bulan = $monthName !== '' ? 'BULAN ' . $monthName . ' ' : '';
    return 'MAKLUMAN PELEKAT KENDERAAN ' . $cat . ' ' . $bulan . 'TAHUN ' . $year;
}

/* ----------------------------------------------------------------- formats */

function nv_xlsx_serial_out($v): string {
    if ($v === null || $v === '') { return ''; }
    return NV_SERIAL_PREFIX . str_pad((string) (int) $v, 4, '0', STR_PAD_LEFT);
}
/** "JA0001" / "0001" / "1" -> "1"; non-numeric -> ''. */
function nv_xlsx_serial_in($s): string {
    $s = trim((string) $s);
    if ($s === '') { return ''; }
    if (preg_match('/(\d+)\s*$/', $s, $m)) { return (string) (int) $m[1]; }
    return '';
}
function nv_xlsx_date_out($ymd): string {
    if (!$ymd || $ymd === '0000-00-00') { return ''; }
    $ts = strtotime($ymd);
    return $ts ? date('d/m/Y', $ts) : '';
}
/** DD/MM/YYYY (or other) -> Y-m-d. */
function nv_xlsx_date_in($s): string {
    $s = trim((string) $s);
    if ($s === '') { return ''; }
    if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $s, $m)) {
        return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
    }
    $ts = strtotime($s);
    return $ts ? date('Y-m-d', $ts) : '';
}

/** One owner row -> ordered cell values for $cols (export). */
function nv_xlsx_row_values(array $cols, array $r, int $bil): array {
    $out = [];
    foreach ($cols as $c) {
        switch ($c[2]) {
            case 'bil':    $out[] = $bil; break;
            case 'plate':  $out[] = strtoupper((string)($r['platenum'] ?? '')); break;
            case 'type':   $out[] = strtoupper((string)($r['type'] ?? '')); break;
            case 'model':  $m = (string)($r['model'] ?? ''); $out[] = ($m !== '' && $m !== 'N/A') ? strtoupper($m) : ''; break;
            case 'date':   $out[] = nv_xlsx_date_out($r['date_taken'] ?? ''); break;
            case 'idnum':  $out[] = strtoupper((string)($r['idnumber'] ?? '')); break;
            case 'name':   $out[] = strtoupper((string)($r['name'] ?? '')); break;
            case 'phone':  $out[] = (string)($r['phone'] ?? ''); break;
            case 'serial': $out[] = nv_xlsx_serial_out($r['serial_no'] ?? ''); break;
            case 'company':$out[] = strtoupper((string)($r['company'] ?? '')); break;
            case 'email':  $out[] = (string)($r['ownerEmail'] ?? ''); break;
            case 'note':   $out[] = strtoupper((string)($r['note'] ?? '')); break;
            default:       $out[] = ''; break;
        }
    }
    return $out;
}

/** Placeholder example row for the blank template. */
function nv_xlsx_example_row(string $category, array $cols, int $bil): array {
    $combinedId = in_array($category, ['Staf','Pelajar'], true);
    $idEx = $combinedId
        ? ($category === 'Pelajar' ? '2024227876 / 020406050154' : '120605 / 700808016433')
        : '700808016433';
    $out = [];
    foreach ($cols as $c) {
        switch ($c[2]) {
            case 'bil':    $out[] = $bil; break;
            case 'plate':  $out[] = 'ABC1234'; break;
            case 'type':   $out[] = 'KERETA'; break;
            case 'model':  $out[] = 'PERODUA MYVI / PUTIH'; break;
            case 'date':   $out[] = '01/' . str_pad((string) max(1, (int) date('n')), 2, '0', STR_PAD_LEFT) . '/' . date('Y'); break;
            case 'idnum':  $out[] = $idEx; break;
            case 'name':   $out[] = 'NAMA PENUH BIN/BINTI ...'; break;
            case 'phone':  $out[] = '0123456789'; break;
            case 'serial': $out[] = nv_xlsx_serial_out($bil); break;
            case 'company':$out[] = 'NAMA SYARIKAT'; break;
            case 'email':  $out[] = 'emel@contoh.com'; break;
            case 'note':   $out[] = ''; break;
            default:       $out[] = ''; break;
        }
    }
    return $out;
}

/* ----------------------------------------------------------------- writer */

/** Write one sheet: title (row1, merged) + headers (row2) + data (row3+). */
function nv_xlsx_write_sheet($sheet, string $title, array $cols, array $dataRows): void {
    $nCols   = count($cols);
    $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nCols);

    $sheet->setCellValue('A1', $title);
    $sheet->mergeCells('A1:' . $lastCol . '1');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 12],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(24);

    $headers = array_map(function ($c) { return $c[0]; }, $cols);
    $sheet->fromArray([$headers], null, 'A2');
    $sheet->getStyle('A2:' . $lastCol . '2')->applyFromArray([
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
    ]);

    // Find date + type columns to keep dates as text and add the type dropdown.
    $dateIdx = null; $typeIdx = null;
    foreach ($cols as $i => $c) {
        if ($c[2] === 'date') { $dateIdx = $i + 1; }
        if ($c[2] === 'type') { $typeIdx = $i + 1; }
    }

    $rowNo = 3;
    foreach ($dataRows as $d) {
        $sheet->fromArray([$d], null, 'A' . $rowNo);
        if ($dateIdx) {
            $dl = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dateIdx);
            $sheet->getCell($dl . $rowNo)->setValueExplicit((string)($d[$dateIdx - 1] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
        $rowNo++;
    }

    // Data grid: full black grid (outer + vertical + horizontal) on A..last.
    $lastDataRow = $rowNo - 1;
    if ($lastDataRow >= 3) {
        $sheet->getStyle('A3:' . $lastCol . $lastDataRow)->getBorders()
              ->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FF000000');
    }

    if ($typeIdx) {
        $tl = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($typeIdx);
        for ($r = 3; $r <= 400; $r++) {
            $dv = $sheet->getCell($tl . $r)->getDataValidation();
            $dv->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $dv->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
            $dv->setAllowBlank(true);
            $dv->setShowDropDown(true);
            $dv->setShowErrorMessage(true);
            $dv->setErrorTitle('Invalid');
            $dv->setError('KERETA / MOTOSIKAL.');
            $dv->setFormula1('"KERETA,MOTOSIKAL"');
        }
    }

    foreach ($cols as $i => $c) {
        $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
        $w = ['bil'=>6,'serial'=>12,'plate'=>16,'type'=>14,'model'=>22,'date'=>16,'idnum'=>26,
              'name'=>30,'phone'=>16,'company'=>24,'email'=>26,'note'=>22][$c[2]] ?? 16;
        $sheet->getColumnDimension($letter)->setWidth($w);
    }
}

/**
 * Build the workbook for a category. $template => blank (one example row).
 * Returns a Spreadsheet ready to stream.
 */
function nv_xlsx_build($con, string $category, int $year, int $month, bool $template) {
    require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
    $meta  = nv_xlsx_meta($category);
    $cols  = nv_category_xlsx_cols($category);
    $months = nv_xlsx_months();
    if ($year <= 0) { $year = (int) date('Y'); }

    // Data grouped by month (1-12); empty when building a template.
    $byMonth = array_fill(1, 12, []);
    $all = [];
    if (!$template) {
        $cat = mysqli_real_escape_string($con, $category);
        $eff = "COALESCE(`date_taken`, `created_at`)";
        $where = "status='$cat' AND YEAR($eff) = " . (int) $year;
        if ($month >= 1 && $month <= 12) { $where .= " AND MONTH($eff) = " . (int) $month; }
        if ($res = mysqli_query($con, "SELECT * FROM `owner` WHERE $where ORDER BY $eff ASC, id ASC")) {
            while ($r = mysqli_fetch_assoc($res)) {
                $m = (int) date('n', strtotime($r['date_taken'] ?: $r['created_at'] ?: 'now'));
                if ($m < 1 || $m > 12) { $m = 1; }
                $byMonth[$m][] = $r;
                $all[] = $r;
            }
        }
    }

    $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $ss->removeSheetByIndex(0);

    if ($meta['mode'] === 'month') {
        for ($m = 1; $m <= 12; $m++) {
            if ($month >= 1 && $month <= 12 && $m !== $month) { continue; }
            $sheet = $ss->createSheet();
            $sheet->setTitle(substr($months[$m - 1], 0, 31));
            $rows = [];
            $bil = 1;
            $src = $template ? [nv_xlsx_example_row($category, $cols, 1)] : $byMonth[$m];
            if (!$template) { foreach ($src as $r) { $rows[] = nv_xlsx_row_values($cols, $r, $bil++); } }
            else { $rows = $src; }
            nv_xlsx_write_sheet($sheet, nv_xlsx_title($category, $months[$m - 1], $year), $cols, $rows);
        }
        if ($ss->getSheetCount() === 0) { // month filter outside 1-12 safety
            $sheet = $ss->createSheet(); $sheet->setTitle(substr($months[0], 0, 31));
            nv_xlsx_write_sheet($sheet, nv_xlsx_title($category, $months[0], $year), $cols, []);
        }
    } else {
        $name = $meta['sheet'] === '%YEAR%' ? (string) $year : $meta['sheet'];
        $sheet = $ss->createSheet();
        $sheet->setTitle(substr($name, 0, 31));
        $monthName = ($month >= 1 && $month <= 12) ? $months[$month - 1] : '';
        $rows = [];
        if ($template) {
            $rows = [nv_xlsx_example_row($category, $cols, 1)];
        } else {
            $bil = 1;
            foreach ($all as $r) { $rows[] = nv_xlsx_row_values($cols, $r, $bil++); }
        }
        nv_xlsx_write_sheet($sheet, nv_xlsx_title($category, $monthName, $year), $cols, $rows);
    }

    $ss->setActiveSheetIndex(0);
    return $ss;
}

/** Write data rows into a loaded template sheet at row 3+, preserving its styling. */
function nv_xlsx_fill_rows($sheet, array $cols, array $rows, ?int $dateIdx): void {
    $rowNo = 3;
    $bil = 1;
    foreach ($rows as $r) {
        $vals = nv_xlsx_row_values($cols, $r, $bil++);
        $sheet->fromArray([$vals], null, 'A' . $rowNo);
        if ($dateIdx) {                                  // keep DD/MM/YYYY as text, not a date serial
            $dl = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dateIdx);
            $sheet->getCell($dl . $rowNo)->setValueExplicit((string)($vals[$dateIdx - 1] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
        $rowNo++;
    }
}

/**
 * PhpSpreadsheet does not model the OOXML <family> font hint, so it strips it on save.
 * For the title font (Bernard MT Condensed) that hint drives font substitution in viewers
 * without the font (Google Sheets, LibreOffice, mobile) — without it the title renders a
 * different weight than the template. Re-insert <family val="1"/> into the saved file's
 * styles.xml so a filled export matches the template's title exactly. Requires ext-zip.
 */
function nv_xlsx_restore_font_family(string $path): void {
    if (!class_exists('ZipArchive')) { return; }
    $zip = new \ZipArchive();
    if ($zip->open($path) !== true) { return; }
    $styles = $zip->getFromName('xl/styles.xml');
    if ($styles !== false) {
        // Make the title font (Bernard MT Condensed) byte-identical to the template's so
        // viewers substitute it the same way (same weight): drop the no-op
        // <b val="0"/>/<i val="0"/>/<strike val="0"/>/<u val="none"/> that PhpSpreadsheet adds
        // (an explicit b=0 can render a substituted font thinner), and re-add the <family>
        // hint PhpSpreadsheet strips.
        $patched = preg_replace_callback(
            '#<font\b[^>]*>.*?</font>#s',
            function ($m) {
                $f = $m[0];
                if (strpos($f, 'Bernard MT Condensed') === false) { return $f; }
                $f = preg_replace('#<b val="0"/>|<i val="0"/>|<strike val="0"/>|<u val="none"/>#', '', $f);
                if (strpos($f, '<family') === false) {
                    $f = preg_replace('#(<name val="Bernard MT Condensed"/>)#', '$1<family val="1"/>', $f, 1);
                }
                return $f;
            },
            $styles
        );
        if ($patched !== null && $patched !== $styles) {
            $zip->addFromString('xl/styles.xml', $patched);
        }
    }
    $zip->close();
}

/**
 * Pour live data into the official static template (assets/templates/{cat}.xlsx) so an
 * export looks identical to the template download — same title, month sheets, borders,
 * column widths and KERETA/MOTOSIKAL dropdown. Data lands in row 3+ of the matching
 * month sheet (month mode) or the single sheet. Requires the zip reader extension;
 * callers fall back to nv_xlsx_build() if this throws. Returns a Spreadsheet.
 */
function nv_xlsx_fill_template(string $path, $con, string $category, int $year, int $month, ?int &$rowCount = null) {
    require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
    $cols   = nv_category_xlsx_cols($category);
    $meta   = nv_xlsx_meta($category);
    $months = nv_xlsx_months();
    if ($year <= 0) { $year = (int) date('Y'); }

    // Same source query + month grouping as nv_xlsx_build().
    $byMonth = array_fill(1, 12, []);
    $all = [];
    $cat = mysqli_real_escape_string($con, $category);
    $eff = "COALESCE(`date_taken`, `created_at`)";
    $where = "status='$cat' AND YEAR($eff) = " . (int) $year;
    if ($month >= 1 && $month <= 12) { $where .= " AND MONTH($eff) = " . (int) $month; }
    if ($res = mysqli_query($con, "SELECT * FROM `owner` WHERE $where ORDER BY $eff ASC, id ASC")) {
        while ($r = mysqli_fetch_assoc($res)) {
            $m = (int) date('n', strtotime($r['date_taken'] ?: $r['created_at'] ?: 'now'));
            if ($m < 1 || $m > 12) { $m = 1; }
            $byMonth[$m][] = $r;
            $all[] = $r;
        }
    }

    $rowCount = count($all);

    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();   // full read — keep styles + validations
    $ss = $reader->load($path);

    $dateIdx = null;
    foreach ($cols as $i => $c) { if ($c[2] === 'date') { $dateIdx = $i + 1; } }

    // Fill each sheet, remembering the first sheet that actually receives data so the
    // workbook opens there — otherwise a month-split export opens on an empty JANUARI
    // while the records sit in later month sheets, and looks empty.
    $firstData = null;
    if ($meta['mode'] === 'month') {
        foreach ($ss->getAllSheets() as $idx => $sheet) {
            $mi = array_search($sheet->getTitle(), $months, true);   // 0-based month index
            if ($mi === false) { continue; }
            $mrows = $byMonth[$mi + 1];
            if ($firstData === null && !empty($mrows)) { $firstData = $idx; }
            nv_xlsx_fill_rows($sheet, $cols, $mrows, $dateIdx);
        }
    } else {
        nv_xlsx_fill_rows($ss->getSheet(0), $cols, $all, $dateIdx);
        if (!empty($all)) { $firstData = 0; }
    }

    $ss->setActiveSheetIndex($firstData ?? 0);
    return $ss;
}

/* ----------------------------------------------------------------- reader */

/**
 * Import every sheet of an uploaded workbook (title row1, headers row2, data
 * row3+) for a category, reusing nv_vehicle_register() per row. Fills $stats
 * with added/updated/skipped/errors.
 */
function nv_xlsx_import($spreadsheet, string $category, $con, array &$stats): void {
    $cols = nv_category_xlsx_cols($category);
    $kindToField = ['plate'=>'platenum','type'=>'type','model'=>'model','date'=>'date_taken',
                    'idnum'=>'idnumber','name'=>'name','phone'=>'phone','serial'=>'serial_no',
                    'company'=>'company','email'=>'email','note'=>'note'];
    $stats = ['added'=>0,'updated'=>0,'skipped'=>0,'errors'=>[]];

    foreach ($spreadsheet->getAllSheets() as $sheet) {
        $sheetName = $sheet->getTitle();
        $lastRow = $sheet->getHighestDataRow();
        for ($row = 3; $row <= $lastRow; $row++) {           // skip title (1) + header (2)
            $vals = [];
            foreach ($cols as $i => $c) {
                $kind = $c[2];
                if ($kind === 'bil' || !isset($kindToField[$kind])) { continue; }
                $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                $raw = trim((string) $sheet->getCell($letter . $row)->getValue());
                if ($kind === 'date')        { $vals['date_taken'] = nv_xlsx_date_in($raw); }
                elseif ($kind === 'serial')  { $vals['serial_no']  = nv_xlsx_serial_in($raw); }
                else                         { $vals[$kindToField[$kind]] = $raw; }
            }
            $plate = $vals['platenum'] ?? '';
            $phone = $vals['phone'] ?? '';
            $name  = $vals['name'] ?? '';
            if ($plate === '' && $name === '' && $phone === '') { continue; }       // blank row
            if ($plate === '' || $phone === '') { $stats['skipped']++; $stats['errors'][] = "$sheetName r$row: plate+phone required"; continue; }

            $_POST = $vals;
            $err = '';
            $res = nv_vehicle_register($con, $category, $err);
            if ($res === 'created') { $stats['added']++; }
            elseif ($res === 'exists') { $stats['skipped']++; }   // identical row already present — no change
            else { $stats['skipped']++; $stats['errors'][] = "$sheetName r$row: " . ($err ?: 'skipped'); }
        }
    }
}
