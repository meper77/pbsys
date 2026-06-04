-- Migration: 2026_06_03_minor_upgrade.sql
-- NEO V-TRACK minor upgrade. Operates on the live `owner` table.
-- Idempotent (IF EXISTS / IF NOT EXISTS) so it can be re-run safely.

-- 1) Fix root cause of "Field 'brand' doesn't have a default value":
ALTER TABLE `owner` MODIFY `brand` varchar(100) NOT NULL DEFAULT 'N/A';

-- 2) Remove sticker concept (user decision).
ALTER TABLE `owner` DROP COLUMN IF EXISTS `sticker`;
ALTER TABLE `owner` DROP COLUMN IF EXISTS `stickerno`;

-- 3) Lifecycle clock for active/inactive split.
--    Effective date = COALESCE(reactivated_at, created_at); inactive when < NOW() - 1 year.
--    NOTE: older deployments (e.g. production) lack the timestamp columns entirely, so add
--    created_at when missing (existing rows default to now). No AFTER clause for portability.
ALTER TABLE `owner` ADD COLUMN IF NOT EXISTS `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `owner` ADD COLUMN IF NOT EXISTS `reactivated_at` datetime NULL DEFAULT NULL;

-- 4) Normalize stray category casing.
UPDATE `owner` SET `status` = 'Pelawat'   WHERE `status` = 'PELAWAT';
UPDATE `owner` SET `status` = 'Staf'       WHERE `status` = 'STAF';
UPDATE `owner` SET `status` = 'Pelajar'    WHERE `status` = 'PELAJAR';
UPDATE `owner` SET `status` = 'Kontraktor' WHERE `status` = 'KONTRAKTOR';

-- 5) Remove IC/sticker from legacy vehicle tables too (remove across database).
ALTER TABLE `visitorcar`    DROP COLUMN IF EXISTS `ic_passport`;
ALTER TABLE `contractorcar` DROP COLUMN IF EXISTS `ic_passport`;
ALTER TABLE `staffcar`      DROP COLUMN IF EXISTS `sticker`;
ALTER TABLE `studentcar`    DROP COLUMN IF EXISTS `sticker`;
ALTER TABLE `visitorcar`    DROP COLUMN IF EXISTS `sticker`;
ALTER TABLE `contractorcar` DROP COLUMN IF EXISTS `sticker`;
