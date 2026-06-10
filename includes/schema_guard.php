<?php
/**
 * Idempotent schema provisioning for NEO V-TRACK's foundation features.
 *
 * Self-provisioning is the project's established pattern (report_vehicle_api.php
 * CREATEs its table on demand). This centralizes the DDL so it can be run:
 *   - automatically on an admin's first visit (nv_schema_ready() gate), and
 *   - explicitly via api/migrate.php.
 *
 * All DDL is guarded by information_schema checks, so re-runs are safe.
 */

if (!function_exists('nv_schema_run')) {
    function nv_schema_run($con, &$results, $label, $sql) {
        if ($con->query($sql)) { $results[] = "OK   $label"; return true; }
        $results[] = "ERR  $label :: " . $con->error;
        return false;
    }
    function nv_schema_table_exists($con, $table) {
        $t = $con->real_escape_string($table);
        $r = @$con->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$t' LIMIT 1");
        return $r && $r->num_rows > 0;
    }
    function nv_schema_col_exists($con, $table, $col) {
        $t = $con->real_escape_string($table);
        $c = $con->real_escape_string($col);
        $r = @$con->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$t' AND COLUMN_NAME='$c' LIMIT 1");
        return $r && $r->num_rows > 0;
    }
    function nv_schema_add_col($con, &$results, $table, $col, $ddl) {
        if (!nv_schema_table_exists($con, $table)) { $results[] = "SKIP $table.$col (no such table)"; return; }
        if (nv_schema_col_exists($con, $table, $col)) { $results[] = "SKIP $table.$col (exists)"; return; }
        nv_schema_run($con, $results, "add $table.$col", "ALTER TABLE `$table` ADD COLUMN $ddl");
    }

    /** True once the foundation tables + columns exist (cheap gate to avoid re-running DDL). */
    function nv_schema_ready($con) {
        return nv_schema_table_exists($con, 'admin_allowlist')
            && nv_schema_col_exists($con, 'admin_allowlist', 'role')
            && nv_schema_col_exists($con, 'admin_allowlist', 'is_active')
            && nv_schema_table_exists($con, 'login_otp')
            && nv_schema_table_exists($con, 'trusted_devices')
            && nv_schema_col_exists($con, 'owner', 'serial_no');
    }

    /** Apply all foundation DDL + seed. Returns a log of actions. */
    function nv_ensure_schema($con): array {
        $results = [];

        // Account profile + lifecycle columns.
        foreach (['user', 'admin'] as $tbl) {
            nv_schema_add_col($con, $results, $tbl, 'position',              "`position` VARCHAR(120) DEFAULT NULL");
            nv_schema_add_col($con, $results, $tbl, 'phone',                 "`phone` VARCHAR(30) DEFAULT NULL");
            nv_schema_add_col($con, $results, $tbl, 'profile_image',         "`profile_image` VARCHAR(255) DEFAULT NULL");
            nv_schema_add_col($con, $results, $tbl, 'created_at',            "`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
            nv_schema_add_col($con, $results, $tbl, 'updated_at',            "`updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
            nv_schema_add_col($con, $results, $tbl, 'last_login',            "`last_login` DATETIME DEFAULT NULL");
            nv_schema_add_col($con, $results, $tbl, 'deletion_requested',    "`deletion_requested` TINYINT(1) NOT NULL DEFAULT 0");
            nv_schema_add_col($con, $results, $tbl, 'deletion_requested_at', "`deletion_requested_at` DATETIME DEFAULT NULL");
            if (nv_schema_col_exists($con, $tbl, 'password')) {
                nv_schema_run($con, $results, "relax $tbl.password", "ALTER TABLE `$tbl` MODIFY `password` VARCHAR(200) NULL DEFAULT NULL");
            }
        }

        // owner lifecycle clock + brand default.
        nv_schema_add_col($con, $results, 'owner', 'created_at',     "`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
        nv_schema_add_col($con, $results, 'owner', 'reactivated_at', "`reactivated_at` DATETIME NULL DEFAULT NULL");
        nv_schema_add_col($con, $results, 'owner', 'ownerEmail',     "`ownerEmail` VARCHAR(255) DEFAULT NULL");
        if (nv_schema_col_exists($con, 'owner', 'brand')) {
            nv_schema_run($con, $results, "owner.brand default", "ALTER TABLE `owner` MODIFY `brand` VARCHAR(100) NOT NULL DEFAULT 'N/A'");
        }

        // 9-column vehicle table (staff/student): MODEL KENDERAAN, TARIKH AMBIL, NO SIRI.
        //   model      -> MODEL KENDERAAN (car model)
        //   date_taken -> TARIKH AMBIL    (business date the sticker/vehicle was taken)
        //   serial_no  -> NO SIRI         (per-category, per-year recycle increment number)
        nv_schema_add_col($con, $results, 'owner', 'model',      "`model` VARCHAR(100) NOT NULL DEFAULT 'N/A'");
        nv_schema_add_col($con, $results, 'owner', 'date_taken', "`date_taken` DATE NULL DEFAULT NULL");
        nv_schema_add_col($con, $results, 'owner', 'serial_no',  "`serial_no` INT NULL DEFAULT NULL");

        // New tables.
        // admin_allowlist gates ALL sign-in now (foundation/login): only listed staff
        // may sign in, as admin OR user (per-row `role`). `permissions` holds the
        // per-user access checkboxes for role='user' (admins are implicitly full).
        nv_schema_run($con, $results, 'admin_allowlist', "CREATE TABLE IF NOT EXISTS `admin_allowlist` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `email` VARCHAR(200) NOT NULL,
            `role` ENUM('admin','user') NOT NULL DEFAULT 'admin',
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `permissions` TEXT DEFAULT NULL,
            `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
            `added_by` VARCHAR(200) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        // Backfill columns on a pre-existing allowlist (legacy rows = admins).
        nv_schema_add_col($con, $results, 'admin_allowlist', 'role',
            "`role` ENUM('admin','user') NOT NULL DEFAULT 'admin'");
        nv_schema_add_col($con, $results, 'admin_allowlist', 'is_active',
            "`is_active` TINYINT(1) NOT NULL DEFAULT 1");
        nv_schema_add_col($con, $results, 'admin_allowlist', 'permissions',
            "`permissions` TEXT DEFAULT NULL");

        nv_schema_run($con, $results, 'login_otp', "CREATE TABLE IF NOT EXISTS `login_otp` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `email` VARCHAR(200) NOT NULL,
            `code_hash` VARCHAR(255) NOT NULL,
            `purpose` VARCHAR(20) NOT NULL DEFAULT 'login',
            `attempts` INT NOT NULL DEFAULT 0,
            `expires_at` DATETIME NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `consumed_at` DATETIME DEFAULT NULL,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            KEY `idx_email` (`email`), KEY `idx_expires` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        nv_schema_run($con, $results, 'trusted_devices', "CREATE TABLE IF NOT EXISTS `trusted_devices` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `email` VARCHAR(200) NOT NULL,
            `token_hash` VARCHAR(255) NOT NULL,
            `user_agent` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `last_used_at` DATETIME DEFAULT NULL,
            `expires_at` DATETIME NOT NULL,
            KEY `idx_email` (`email`), KEY `idx_token` (`token_hash`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        // Seed admin allowlist (developer locked + the one @uitm.edu.my admin today).
        if (nv_schema_table_exists($con, 'admin_allowlist')) {
            $seed = [
                ['2023818464@student.uitm.edu.my', 1],
                ['mimihasliah@uitm.edu.my', 0],
            ];
            foreach ($seed as [$em, $lock]) {
                if ($stmt = $con->prepare("INSERT INTO `admin_allowlist` (`email`,`is_locked`,`added_by`) VALUES (?,?, 'seed')
                                           ON DUPLICATE KEY UPDATE `is_locked` = GREATEST(`is_locked`, VALUES(`is_locked`))")) {
                    $stmt->bind_param('si', $em, $lock);
                    $stmt->execute();
                    $stmt->close();
                    $results[] = "SEED allowlist $em";
                }
            }
        }

        return $results;
    }

    /** Run the DDL once if the foundation tables are not present yet. */
    function nv_schema_autoprovision($con): void {
        if (!nv_schema_ready($con)) {
            nv_ensure_schema($con);
        }
    }

    /** Cheap per-request guard so callers can ensure schema freely (gate runs at most once). */
    function nv_schema_autoprovision_once($con): void {
        static $done = false;
        if ($done) { return; }
        $done = true;
        if ($con) { nv_schema_autoprovision($con); }
    }
}
