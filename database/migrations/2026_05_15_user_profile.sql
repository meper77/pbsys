-- 2026-05-15: Add phone, profile_image, updated_at to user + admin tables
-- so the web Profile page has somewhere to persist photo + contact info.

ALTER TABLE `user`
    ADD COLUMN IF NOT EXISTS `phone` VARCHAR(30) NULL,
    ADD COLUMN IF NOT EXISTS `profile_image` VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `admin`
    ADD COLUMN IF NOT EXISTS `phone` VARCHAR(30) NULL,
    ADD COLUMN IF NOT EXISTS `profile_image` VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
