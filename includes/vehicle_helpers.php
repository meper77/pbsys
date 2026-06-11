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
 * Register (or reactivate) a vehicle from $_POST.
 *
 * Identity rules (per upgrade spec):
 *   - staff/student keep `idnumber` (staff no / matric).
 *   - visitor/contractor have no IC; their identity is the phone number.
 *   - Re-uploading the SAME plate + phone reactivates the existing record
 *     (resets the 1-year clock) instead of erroring.
 *
 * @return string|false 'created' | 'reactivated' on success, false on error.
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

    // Reactivation: same plate AND phone => reset the lifecycle clock.
    $r = mysqli_query($con, "SELECT id, serial_no FROM `owner` WHERE platenum='$plate' AND phone='$phone' LIMIT 1");
    if ($r && mysqli_num_rows($r) > 0) {
        $row = mysqli_fetch_assoc($r);
        $id  = (int) $row['id'];
        $set = "name='$name', idnumber='$idnum', type='$type', status='$status', reactivated_at=NOW()";
        if (isset($cols['model']))      { $set .= ", model='$model'"; }
        if (isset($cols['date_taken'])) { $set .= ", date_taken='$dateEsc'"; }
        if (isset($cols['serial_no'])) {
            // Keep an existing serial; only assign when missing.
            if ($row['serial_no'] === null || $row['serial_no'] === '') {
                $set .= ", serial_no=" . (int) $serial;
            } elseif ($serialRaw !== '' && ctype_digit($serialRaw)) {
                $set .= ", serial_no=" . (int) $serial;
            }
        }
        foreach ($extra as $col => $val) { $set .= ", `$col`='$val'"; }
        mysqli_query($con, "UPDATE `owner` SET $set WHERE id=$id");
        return 'reactivated';
    }

    // Plate already used by a different phone => genuine conflict.
    $p = mysqli_query($con, "SELECT id FROM `owner` WHERE platenum='$plate' LIMIT 1");
    if ($p && mysqli_num_rows($p) > 0) {
        $error = 'plate_exists';
        return false;
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
