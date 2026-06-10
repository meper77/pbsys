-- Migration: 2026_06_10_vehicle_9col.sql
-- NEO V-TRACK 9-column vehicle table (staff + student). Operates on the live `owner` table.
-- Idempotent (IF NOT EXISTS) so it can be re-run safely. MariaDB syntax.
--
-- Also auto-applied by includes/schema_guard.php on the first staff/student admin view
-- (nv_schema_autoprovision_once), so manual application is optional.

-- MODEL KENDERAAN (car model). Non-destructive: `brand` is kept untouched.
ALTER TABLE `owner` ADD COLUMN IF NOT EXISTS `model` varchar(100) NOT NULL DEFAULT 'N/A';

-- TARIKH AMBIL (business date the sticker/vehicle was taken). Drives month/year sort + charts.
ALTER TABLE `owner` ADD COLUMN IF NOT EXISTS `date_taken` date NULL DEFAULT NULL;

-- NO SIRI (recycle increment number). Per-category, per-year; resets each year; freed numbers reused.
ALTER TABLE `owner` ADD COLUMN IF NOT EXISTS `serial_no` int(11) NULL DEFAULT NULL;
