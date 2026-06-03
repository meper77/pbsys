<?php
/**
 * Shared vehicle helpers for the unified `owner` table.
 *
 * All four categories (Staf, Pelajar, Pelawat, Kontraktor) are stored in `owner`
 * with `status` = category. Active/inactive is derived from the lifecycle clock:
 *   effective date = COALESCE(reactivated_at, created_at)
 *   active when effective >= NOW() - INTERVAL 1 YEAR
 */

if (!defined('NV_INACTIVE_AFTER')) {
    // SQL fragment for the effective lifecycle date.
    define('NV_EFFECTIVE_DATE_SQL', 'COALESCE(`reactivated_at`, `created_at`)');
    // A row is active when its effective date is within the last year.
    define('NV_ACTIVE_WHERE',   NV_EFFECTIVE_DATE_SQL . ' >= (NOW() - INTERVAL 1 YEAR)');
    define('NV_INACTIVE_WHERE', NV_EFFECTIVE_DATE_SQL . ' <  (NOW() - INTERVAL 1 YEAR)');
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
    $name   = mysqli_real_escape_string($con, trim($_POST['name'] ?? ''));
    $phone  = mysqli_real_escape_string($con, trim($_POST['phone'] ?? ''));
    $type   = mysqli_real_escape_string($con, trim($_POST['type'] ?? ''));
    $plate  = mysqli_real_escape_string($con, strtoupper(trim($_POST['platenum'] ?? '')));
    $idnum  = mysqli_real_escape_string($con, strtoupper(trim($_POST['idnumber'] ?? '')));
    $status = mysqli_real_escape_string($con, $category);

    if ($plate === '' || $phone === '') {
        $error = 'required';
        return false;
    }

    // Reactivation: same plate AND phone => reset the lifecycle clock.
    $r = mysqli_query($con, "SELECT id FROM `owner` WHERE platenum='$plate' AND phone='$phone' LIMIT 1");
    if ($r && mysqli_num_rows($r) > 0) {
        $row = mysqli_fetch_assoc($r);
        $id  = (int) $row['id'];
        mysqli_query($con, "UPDATE `owner`
            SET name='$name', idnumber='$idnum', type='$type', status='$status', reactivated_at=NOW()
            WHERE id=$id");
        return 'reactivated';
    }

    // Plate already used by a different phone => genuine conflict.
    $p = mysqli_query($con, "SELECT id FROM `owner` WHERE platenum='$plate' LIMIT 1");
    if ($p && mysqli_num_rows($p) > 0) {
        $error = 'plate_exists';
        return false;
    }

    // brand has a DB default ('N/A'); omit it so the default applies.
    $sql = "INSERT INTO `owner` (`name`, `phone`, `idnumber`, `type`, `status`, `platenum`)
            VALUES('$name','$phone','$idnum','$type','$status','$plate')";
    if (mysqli_query($con, $sql)) {
        return 'created';
    }

    $error = mysqli_error($con);
    return false;
}
