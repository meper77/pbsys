<?php
/**
 * Shared vehicle helpers for the unified `owner` table.
 *
 * All four categories (Staf, Pelajar, Pelawat, Kontraktor) are stored in `owner`
 * with `status` = category. Active/inactive is derived from the lifecycle clock:
 *   effective date = COALESCE(reactivated_at, created_at)
 *   active when effective >= NOW() - INTERVAL 1 YEAR
 *
 * 9-column fields (staff/student): model (MODEL KENDERAAN), date_taken (TARIKH AMBIL),
 * serial_no (NO SIRI — per-category, per-year recycle increment number).
 */

require_once $_SERVER['DOCUMENT_ROOT'].'/includes/schema_guard.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/contact_links.php';  // format_contact_links() for nv_table_cell()

if (!defined('NV_SERIAL_PREFIX')) { define('NV_SERIAL_PREFIX', 'JA'); } // sticker series prefix (UiTM Johor)

/** Display label for NO SIRI: e.g. 1 -> "JA0001"; null/'' -> "—". */
function nv_serial_label($v): string
{
    if ($v === null || $v === '') { return '—'; }
    return NV_SERIAL_PREFIX . str_pad((string) (int) $v, 4, '0', STR_PAD_LEFT);
}

/** Vehicle type is KERETA or MOTOSIKAL only (foundation): MOTO* -> MOTOSIKAL, else KERETA. */
function nv_norm_vehicle_type($t): string
{
    return strncmp(strtoupper(trim((string) $t)), 'MOTO', 4) === 0 ? 'MOTOSIKAL' : 'KERETA';
}

if (!defined('NV_INACTIVE_AFTER')) {
    // SQL fragment for the effective lifecycle date.
    define('NV_EFFECTIVE_DATE_SQL', 'COALESCE(`reactivated_at`, `created_at`)');
    // A row is active when its effective date is within the last year.
    define('NV_ACTIVE_WHERE',   NV_EFFECTIVE_DATE_SQL . ' >= (NOW() - INTERVAL 1 YEAR)');
    define('NV_INACTIVE_WHERE', NV_EFFECTIVE_DATE_SQL . ' <  (NOW() - INTERVAL 1 YEAR)');
}

/**
 * Ordered xlsx columns for a category (shared by export + import for a stable
 * round-trip). Each entry is [header, ownerField, kind]; kind in
 * bil|plate|type|model|date|idnum|name|phone|serial|company|email|note.
 * Staf/Pelajar/Pelawat = 9 cols; Kontraktor = 12; Pesara (alumni) = 10.
 */
function nv_category_xlsx_cols(string $category): array
{
    if ($category === 'Kontraktor') {
        return [
            ['BIL', '', 'bil'],
            ['NO. SIRI', 'serial_no', 'serial'],
            ['NAMA', 'name', 'name'],
            ['NO. IC', 'idnumber', 'idnum'],
            ['NO KENDERAAN', 'platenum', 'plate'],
            ['KENDERAAN', 'type', 'type'],
            ['MODEL KENDERAAN', 'model', 'model'],
            ['SYARIKAT', 'company', 'company'],
            ['NO TELEFON', 'phone', 'phone'],
            ['TARIKH KELUAR PELEKAT', 'date_taken', 'date'],
            ['EMAIL', 'ownerEmail', 'email'],
            ['CATATAN', 'note', 'note'],
        ];
    }
    if ($category === 'Pesara') {
        return [
            ['BIL', '', 'bil'],
            ['NO. SIRI PELEKAT', 'serial_no', 'serial'],
            ['NO. KENDERAAN', 'platenum', 'plate'],
            ['JENIS KENDERAAN', 'type', 'type'],
            ['MODEL KENDERAAN', 'model', 'model'],
            ['TARIKH AMBIL PELEKAT', 'date_taken', 'date'],
            ['NAMA', 'name', 'name'],
            ['NO. KP', 'idnumber', 'idnum'],
            ['NO.TELEFON', 'phone', 'phone'],
            ['CATATAN', 'note', 'note'],
        ];
    }
    $idH = ($category === 'Pelajar') ? 'NO PELAJAR' : (($category === 'Staf') ? 'NO PEKERJA' : 'NO PENGENALAN');
    return [
        ['BIL', '', 'bil'],
        ['NO KENDERAAN', 'platenum', 'plate'],
        ['JENIS KENDERAAN', 'type', 'type'],
        ['MODEL KENDERAAN', 'model', 'model'],
        ['TARIKH AMBIL', 'date_taken', 'date'],
        [$idH, 'idnumber', 'idnum'],
        ['NAMA', 'name', 'name'],
        ['NO TELEFON', 'phone', 'phone'],
        ['NO SIRI', 'serial_no', 'serial'],
    ];
}

/**
 * On-screen `[kind, label]` columns for a category (localized). Mirrors the
 * per-category list pages (vehicle_table_view default 9-col for Staf/Pelajar/
 * Pelawat; Kontraktor 12; Pesara 10) so search results read the same as the
 * list. `kind` is rendered by nv_table_cell().
 */
function nv_category_columns(string $category, string $lang): array
{
    $bm = $lang === 'bm';
    if ($category === 'Kontraktor') {
        return $bm ? [
            ['serial','NO SIRI'], ['name','NAMA'], ['idnum','NO. IC'], ['plate','NO KENDERAAN'],
            ['type','KENDERAAN'], ['model','MODEL KENDERAAN'], ['company','SYARIKAT'], ['phone','NO TELEFON'],
            ['date','TARIKH KELUAR PELEKAT'], ['email','EMAIL'], ['note','CATATAN'],
        ] : [
            ['serial','SERIAL NO.'], ['name','NAME'], ['idnum','IC NO.'], ['plate','PLATE NO.'],
            ['type','VEHICLE'], ['model','VEHICLE MODEL'], ['company','COMPANY'], ['phone','PHONE'],
            ['date','STICKER ISSUE DATE'], ['email','EMAIL'], ['note','NOTE'],
        ];
    }
    if ($category === 'Pesara') {
        return $bm ? [
            ['serial','NO SIRI PELEKAT'], ['plate','NO KENDERAAN'], ['type','JENIS KENDERAAN'], ['model','MODEL KENDERAAN'],
            ['date','TARIKH AMBIL PELEKAT'], ['name','NAMA'], ['idnum','NO. KP'], ['phone','NO. TELEFON'], ['note','CATATAN'],
        ] : [
            ['serial','STICKER SERIAL NO.'], ['plate','PLATE NO.'], ['type','VEHICLE TYPE'], ['model','VEHICLE MODEL'],
            ['date','STICKER DATE'], ['name','NAME'], ['idnum','IC NO.'], ['phone','PHONE'], ['note','NOTE'],
        ];
    }
    // Staf / Pelajar / Pelawat (and unknown) — 8 data columns; only the ID label varies.
    $idH = $bm
        ? ($category === 'Pelajar' ? 'NO PELAJAR' : ($category === 'Staf' ? 'NO PEKERJA' : 'NO PENGENALAN'))
        : ($category === 'Pelajar' ? 'STUDENT NO.' : ($category === 'Staf' ? 'STAFF NO.' : 'ID NO.'));
    return $bm ? [
        ['plate','NO KENDERAAN'], ['type','JENIS KENDERAAN'], ['model','MODEL KENDERAAN'], ['date','TARIKH AMBIL'],
        ['idnum',$idH], ['name','NAMA'], ['phone','NO TELEFON'], ['serial','NO SIRI'],
    ] : [
        ['plate','PLATE NO.'], ['type','VEHICLE TYPE'], ['model','VEHICLE MODEL'], ['date','DATE TAKEN'],
        ['idnum',$idH], ['name','NAME'], ['phone','PHONE'], ['serial','SERIAL NO.'],
    ];
}

if (!function_exists('nv_table_cell')) {
    /** Render one <td> for a render-type (kind) from an owner row (cells uppercased via CSS). */
    function nv_table_cell($type, $r) {
        switch ($type) {
            case 'plate':   return '<td><span class="plate">' . htmlspecialchars($r['platenum'] ?? '') . '</span></td>';
            case 'type':    return '<td>' . htmlspecialchars($r['type'] ?? '') . '</td>';
            case 'model':   $m = (($r['model'] ?? '') !== '' && ($r['model'] ?? '') !== 'N/A') ? $r['model'] : '—'; return '<td>' . htmlspecialchars($m) . '</td>';
            case 'date':    $d = $r['date_taken'] ?? null; $dd = ($d && $d !== '0000-00-00') ? date('d M Y', strtotime($d)) : '—'; return '<td class="meta">' . htmlspecialchars($dd) . '</td>';
            case 'idnum':   return '<td class="text-mono">' . htmlspecialchars($r['idnumber'] ?? '') . '</td>';
            case 'name':    return '<td>' . htmlspecialchars($r['name'] ?? '') . '</td>';
            case 'phone':   $p = htmlspecialchars($r['phone'] ?? ''); return '<td class="lower">' . ($p !== '' ? '<span class="text-mono">' . $p . '</span> ' . format_contact_links($r['phone']) : '<span class="text-muted">—</span>') . '</td>';
            case 'serial':  return '<td class="text-mono">' . htmlspecialchars(nv_serial_label($r['serial_no'] ?? null)) . '</td>';
            case 'company': $c = ($r['company'] ?? '') !== '' ? $r['company'] : '—'; return '<td>' . htmlspecialchars($c) . '</td>';
            case 'email':   $e = htmlspecialchars($r['ownerEmail'] ?? ''); return '<td class="lower">' . ($e !== '' ? '<span class="text-mono">' . $e . '</span>' : '<span class="text-muted">—</span>') . '</td>';
            case 'note':    $n = ($r['note'] ?? '') !== '' ? $r['note'] : '—'; return '<td>' . htmlspecialchars($n) . '</td>';
        }
        return '<td></td>';
    }
}

/** Which of the 9-column fields exist on `owner` right now (resilient to a half-provisioned DB). */
function nv_owner_new_cols($con): array
{
    static $cols = null;
    if ($cols !== null) { return $cols; }
    $cols = [];
    foreach (['model', 'date_taken', 'serial_no'] as $c) {
        if (function_exists('nv_schema_col_exists') && nv_schema_col_exists($con, 'owner', $c)) {
            $cols[$c] = true;
        }
    }
    return $cols;
}

/** Calendar year for a date string (TARIKH AMBIL); falls back to the current year. */
function nv_owner_year_of($dateStr): int
{
    $d = trim((string) $dateStr);
    if ($d !== '' && ($ts = strtotime($d)) !== false) {
        return (int) date('Y', $ts);
    }
    return (int) date('Y');
}

/**
 * Next NO SIRI for a given year: the smallest free positive integer across ALL
 * categories (one shared counter per year; recycles numbers freed by deletes;
 * resets each year).
 */
function nv_next_serial($con, $year): int
{
    $year = (int) $year;
    $used = [];
    $sql  = "SELECT `serial_no` FROM `owner`
             WHERE `serial_no` IS NOT NULL
               AND YEAR(COALESCE(`date_taken`, `created_at`)) = $year";
    if ($res = mysqli_query($con, $sql)) {
        while ($r = mysqli_fetch_assoc($res)) { $used[(int) $r['serial_no']] = true; }
    }
    $n = 1;
    while (isset($used[$n])) { $n++; }
    return $n;
}

/**
 * Register a vehicle from $_POST.
 *
 * Identity rules (per upgrade spec):
 *   - staff/student keep `idnumber` (staff no / matric).
 *   - visitor/contractor have no IC; their identity is the phone number.
 *
 * Per-year, many-to-many model: each registration is its own row (the same plate
 * may recur across years and across owners), so there is no reactivation and no
 * plate-level block. An exact duplicate of every stored column is a no-op so a
 * double-submit can't create a twin.
 *
 * @return string|false 'created' (new row) | 'exists' (identical row already
 *                      present) on success, false on error.
 */
function nv_vehicle_register($con, $category, &$error)
{
    nv_schema_autoprovision_once($con); // make sure the 9-column fields exist on live

    $name   = mysqli_real_escape_string($con, strtoupper(trim($_POST['name'] ?? '')));
    $phone  = mysqli_real_escape_string($con, trim($_POST['phone'] ?? ''));
    // Vehicle type is KERETA or MOTOSIKAL only (foundation) — coerce anything else.
    $type   = mysqli_real_escape_string($con, nv_norm_vehicle_type($_POST['type'] ?? ''));
    $plate  = mysqli_real_escape_string($con, strtoupper(trim($_POST['platenum'] ?? '')));
    $idnum  = mysqli_real_escape_string($con, strtoupper(trim($_POST['idnumber'] ?? '')));
    $status = mysqli_real_escape_string($con, $category);

    $model  = strtoupper(trim($_POST['model'] ?? ''));
    if ($model === '') { $model = 'N/A'; }
    $model  = mysqli_real_escape_string($con, $model);

    // TARIKH AMBIL: accept yyyy-mm-dd; default to today.
    $dateRaw = trim($_POST['date_taken'] ?? '');
    $dateTs  = $dateRaw !== '' ? strtotime($dateRaw) : false;
    $dateVal = $dateTs !== false ? date('Y-m-d', $dateTs) : date('Y-m-d');
    $dateEsc = mysqli_real_escape_string($con, $dateVal);

    if ($plate === '' || $phone === '') {
        $error = 'required';
        return false;
    }

    $cols    = nv_owner_new_cols($con);
    $hasNew  = !empty($cols);

    // Extended optional fields (contractor SYARIKAT/EMAIL/CATATAN; alumni CATATAN),
    // persisted only when the column exists. Email is kept as entered; the rest
    // are uppercased like the other columns.
    $extra = [];
    if (function_exists('nv_schema_col_exists')) {
        if (nv_schema_col_exists($con, 'owner', 'company'))    { $extra['company']    = mysqli_real_escape_string($con, strtoupper(trim($_POST['company'] ?? ''))); }
        if (nv_schema_col_exists($con, 'owner', 'ownerEmail')) { $extra['ownerEmail'] = mysqli_real_escape_string($con, trim($_POST['email'] ?? '')); }
        if (nv_schema_col_exists($con, 'owner', 'note'))       { $extra['note']       = mysqli_real_escape_string($con, strtoupper(trim($_POST['note'] ?? ''))); }
    }

    // NO SIRI: use a provided number, else allocate the next free per (category, year).
    $serialRaw = trim($_POST['serial_no'] ?? '');
    $serial    = ($serialRaw !== '' && ctype_digit($serialRaw)) ? (int) $serialRaw : null;
    if (isset($cols['serial_no']) && $serial === null) {
        $serial = nv_next_serial($con, nv_owner_year_of($dateVal));
    }

    // Per-year, many-to-many model (foundation): never reactivate and never block
    // on the plate alone — the same vehicle in another year, or for another owner,
    // is its own row. Skip only an EXACT duplicate (every stored column) so a
    // double-submit can't create a twin.
    $dupWhere = "platenum='$plate' AND phone='$phone' AND name='$name' AND idnumber='$idnum' AND type='$type' AND status='$status'";
    if (isset($cols['model']))      { $dupWhere .= " AND model='$model'"; }
    if (isset($cols['date_taken'])) { $dupWhere .= " AND date_taken='$dateEsc'"; }
    foreach ($extra as $col => $val) { $dupWhere .= " AND `$col`='$val'"; }
    $dq = mysqli_query($con, "SELECT id FROM `owner` WHERE $dupWhere LIMIT 1");
    if ($dq && mysqli_num_rows($dq) > 0) {
        return 'exists'; // identical record already present — idempotent, no twin
    }

    // brand has a DB default ('N/A'); omit it so the default applies.
    if ($hasNew) {
        $colSql = "`name`, `phone`, `idnumber`, `type`, `status`, `platenum`";
        $valSql = "'$name','$phone','$idnum','$type','$status','$plate'";
        if (isset($cols['model']))      { $colSql .= ", `model`";      $valSql .= ", '$model'"; }
        if (isset($cols['date_taken'])) { $colSql .= ", `date_taken`"; $valSql .= ", '$dateEsc'"; }
        if (isset($cols['serial_no']))  { $colSql .= ", `serial_no`";  $valSql .= ", " . (int) $serial; }
        foreach ($extra as $col => $val) { $colSql .= ", `$col`"; $valSql .= ", '$val'"; }
        $sql = "INSERT INTO `owner` ($colSql) VALUES($valSql)";
    } else {
        $sql = "INSERT INTO `owner` (`name`, `phone`, `idnumber`, `type`, `status`, `platenum`)
                VALUES('$name','$phone','$idnum','$type','$status','$plate')";
    }
    if (mysqli_query($con, $sql)) {
        return 'created';
    }

    $error = mysqli_error($con);
    return false;
}
