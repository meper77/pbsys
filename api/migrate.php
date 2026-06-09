<?php
/**
 * Idempotent schema runner + SMTP self-test, invoked over HTTP on the live host
 * (which has localhost DB access). Guarded by the migrate_key secret.
 *
 *   /api/migrate.php?key=SECRET                      -> apply pending DDL + seed
 *   /api/migrate.php?key=SECRET&selftest=mail&to=X   -> probe SMTP port/encryption combos
 *
 * Self-provisioning is the project's existing pattern (see report_vehicle_api.php).
 * All DDL is guarded by information_schema checks so re-runs are safe on MySQL/MariaDB.
 */
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/secrets_loader.php';

$key = $_GET['key'] ?? '';
$expected = (string) nv_secret('migrate_key', '');
if ($expected === '' || !hash_equals($expected, (string) $key)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';

/* ---------- SMTP self-test ---------- */
if (($_GET['selftest'] ?? '') === 'mail') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/mailer.php';
    $to = trim($_GET['to'] ?? '');
    if ($to === '') {
        echo json_encode(['ok' => false, 'error' => 'pass &to=email']);
        exit;
    }
    $combos = [
        ['port' => 25,  'secure' => ''],
        ['port' => 25,  'secure' => 'tls'],
        ['port' => 587, 'secure' => 'tls'],
        ['port' => 465, 'secure' => 'ssl'],
        ['port' => 25,  'secure' => 'ssl'],
    ];
    $out = [];
    foreach ($combos as $c) {
        $err = null;
        $label = $c['port'] . '/' . ($c['secure'] ?: 'none');
        $ok = nv_send_mail(
            $to,
            'NEO V-TRACK SMTP test (' . $label . ')',
            '<p>NEO V-TRACK SMTP self-test succeeded via <strong>' . $label . '</strong>.</p>',
            $err,
            $c
        );
        $out[] = ['transport' => $label, 'ok' => $ok, 'error' => $err];
        if ($ok) {
            break;
        }
    }
    echo json_encode(['ok' => true, 'mail' => $out], JSON_PRETTY_PRINT);
    exit;
}

/* ---------- schema helpers ---------- */
$results = [];
function mig_run($con, &$results, $label, $sql)
{
    if ($con->query($sql)) {
        $results[] = "OK   $label";
        return true;
    }
    $results[] = "ERR  $label :: " . $con->error;
    return false;
}
function mig_table_exists($con, $table)
{
    $t = $con->real_escape_string($table);
    $r = $con->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$t' LIMIT 1");
    return $r && $r->num_rows > 0;
}
function mig_col_exists($con, $table, $col)
{
    $t = $con->real_escape_string($table);
    $c = $con->real_escape_string($col);
    $r = $con->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$t' AND COLUMN_NAME='$c' LIMIT 1");
    return $r && $r->num_rows > 0;
}
function mig_add_col($con, &$results, $table, $col, $ddl)
{
    if (!mig_table_exists($con, $table)) { $results[] = "SKIP $table.$col (no such table)"; return; }
    if (mig_col_exists($con, $table, $col)) { $results[] = "SKIP $table.$col (exists)"; return; }
    mig_run($con, $results, "add $table.$col", "ALTER TABLE `$table` ADD COLUMN $ddl");
}

/* ---------- account tables: profile + lifecycle columns ---------- */
foreach (['user', 'admin'] as $tbl) {
    mig_add_col($con, $results, $tbl, 'phone',                 "`phone` VARCHAR(30) DEFAULT NULL");
    mig_add_col($con, $results, $tbl, 'profile_image',         "`profile_image` VARCHAR(255) DEFAULT NULL");
    mig_add_col($con, $results, $tbl, 'created_at',            "`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    mig_add_col($con, $results, $tbl, 'updated_at',            "`updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
    mig_add_col($con, $results, $tbl, 'last_login',            "`last_login` DATETIME DEFAULT NULL");
    mig_add_col($con, $results, $tbl, 'deletion_requested',    "`deletion_requested` TINYINT(1) NOT NULL DEFAULT 0");
    mig_add_col($con, $results, $tbl, 'deletion_requested_at', "`deletion_requested_at` DATETIME DEFAULT NULL");
    // Passwordless: password is no longer required. Make it nullable so OTP signups work.
    if (mig_col_exists($con, $tbl, 'password')) {
        mig_run($con, $results, "relax $tbl.password", "ALTER TABLE `$tbl` MODIFY `password` VARCHAR(200) NULL DEFAULT NULL");
    }
}

/* ---------- owner: lifecycle clock + brand default ---------- */
mig_add_col($con, $results, 'owner', 'created_at',     "`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
mig_add_col($con, $results, 'owner', 'reactivated_at', "`reactivated_at` DATETIME NULL DEFAULT NULL");
mig_add_col($con, $results, 'owner', 'ownerEmail',     "`ownerEmail` VARCHAR(255) DEFAULT NULL");
if (mig_col_exists($con, 'owner', 'brand')) {
    mig_run($con, $results, "owner.brand default", "ALTER TABLE `owner` MODIFY `brand` VARCHAR(100) NOT NULL DEFAULT 'N/A'");
}

/* ---------- new tables ---------- */
mig_run($con, $results, 'admin_allowlist', "CREATE TABLE IF NOT EXISTS `admin_allowlist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(200) NOT NULL,
    `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
    `added_by` VARCHAR(200) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

mig_run($con, $results, 'login_otp', "CREATE TABLE IF NOT EXISTS `login_otp` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(200) NOT NULL,
    `code_hash` VARCHAR(255) NOT NULL,
    `purpose` VARCHAR(20) NOT NULL DEFAULT 'login',
    `attempts` INT NOT NULL DEFAULT 0,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `consumed_at` DATETIME DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    KEY `idx_email` (`email`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

mig_run($con, $results, 'trusted_devices', "CREATE TABLE IF NOT EXISTS `trusted_devices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(200) NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_used_at` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    KEY `idx_email` (`email`),
    KEY `idx_token` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

/* ---------- seed admin allowlist ---------- */
if (mig_table_exists($con, 'admin_allowlist')) {
    $seed = [
        ['2023818464@student.uitm.edu.my', 1], // Developer (locked)
        ['mimihasliah@uitm.edu.my', 0],
    ];
    foreach ($seed as [$em, $lock]) {
        $stmt = $con->prepare("INSERT INTO `admin_allowlist` (`email`,`is_locked`,`added_by`) VALUES (?,?, 'seed')
                               ON DUPLICATE KEY UPDATE `is_locked` = GREATEST(`is_locked`, VALUES(`is_locked`))");
        if ($stmt) {
            $stmt->bind_param('si', $em, $lock);
            $stmt->execute();
            $results[] = "SEED allowlist $em (locked=$lock)";
            $stmt->close();
        }
    }
}

echo json_encode(['ok' => true, 'results' => $results], JSON_PRETTY_PRINT);
