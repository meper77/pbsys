-- 2026-05-15: Add created_at / updated_at to `owner` so visitor & contractor
-- rows (which still live in `owner`) carry per-record audit timestamps.
-- updated_at auto-bumps on any UPDATE via ON UPDATE CURRENT_TIMESTAMP.

ALTER TABLE `owner`
    ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
