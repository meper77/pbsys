-- 2026-05-15: Defensive re-apply of timestamp columns on staffcar/studentcar.
-- The bulk of this work was done in admin_features.sql (2026-05-14); this
-- migration uses ADD COLUMN IF NOT EXISTS so it is safe to re-run on
-- environments where admin_features.sql was already applied.
--
-- Requires MariaDB 10.0.2+ or MySQL 8.0.29+ (for IF NOT EXISTS on ADD COLUMN).
-- If the target server is older, drop the IF NOT EXISTS and skip statements
-- whose columns already exist.

ALTER TABLE `staffcar`
    ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `studentcar`
    ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
