-- Remove sticker and stickerno columns from vehicle tables and owner table
-- Date: 2026-05-22

ALTER TABLE `visitorcar` DROP COLUMN IF EXISTS `sticker`;
ALTER TABLE `visitorcar` DROP COLUMN IF EXISTS `stickerno`;

ALTER TABLE `staffcar` DROP COLUMN IF EXISTS `sticker`;
ALTER TABLE `staffcar` DROP COLUMN IF EXISTS `stickerno`;

ALTER TABLE `studentcar` DROP COLUMN IF EXISTS `sticker`;
ALTER TABLE `studentcar` DROP COLUMN IF EXISTS `stickerno`;

ALTER TABLE `contractorcar` DROP COLUMN IF EXISTS `sticker`;
ALTER TABLE `contractorcar` DROP COLUMN IF EXISTS `stickerno`;

ALTER TABLE `owner` DROP COLUMN IF EXISTS `sticker`;
ALTER TABLE `owner` DROP COLUMN IF EXISTS `stickerno`;
