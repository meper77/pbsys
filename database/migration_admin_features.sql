-- =====================================================================
-- Migration: Add Admin Features & Enhanced Schema
-- Date: 2026-05-14
-- Description: Add timestamps, primary key constraints, sticker status,
--              company name for contractors, and admin management table
-- =====================================================================

-- =====================================================================
-- 1. Update staffcar table - add timestamps and sticker status
-- =====================================================================
ALTER TABLE `staffcar`
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `sticker`,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
ADD COLUMN `sticker_status` ENUM('active', 'removed') DEFAULT 'active' AFTER `updated_at`;

-- Add unique constraint on staffno (primary identifier)
ALTER TABLE `staffcar` ADD UNIQUE KEY `unique_staffno` (`staffno`);

-- =====================================================================
-- 2. Update studentcar table - add timestamps and sticker status
-- =====================================================================
ALTER TABLE `studentcar`
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `sticker`,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
ADD COLUMN `sticker_status` ENUM('active', 'removed') DEFAULT 'active' AFTER `updated_at`;

-- Add unique constraint on matric (primary identifier)
ALTER TABLE `studentcar` ADD UNIQUE KEY `unique_matric` (`matric`);

-- =====================================================================
-- 3. Create visitorcar table if it doesn't exist
-- =====================================================================
CREATE TABLE IF NOT EXISTS `visitorcar` (
  `visitorid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `ic_passport` varchar(50) NOT NULL COMMENT 'IC number or passport number',
  `model` varchar(120) NOT NULL,
  `platenum` varchar(30) NOT NULL UNIQUE,
  `sticker` varchar(12) NOT NULL,
  `sticker_status` ENUM('active', 'removed') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`visitorid`),
  UNIQUE KEY `unique_ic_passport` (`ic_passport`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 4. Create contractorcar table if it doesn't exist
-- =====================================================================
CREATE TABLE IF NOT EXISTS `contractorcar` (
  `contractorid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `ic_passport` varchar(50) NOT NULL COMMENT 'IC number or passport number',
  `company_name` varchar(255) NOT NULL COMMENT 'Contractor company name',
  `model` varchar(120) NOT NULL,
  `platenum` varchar(30) NOT NULL UNIQUE,
  `sticker` varchar(12) NOT NULL,
  `sticker_status` ENUM('active', 'removed') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`contractorid`),
  UNIQUE KEY `unique_ic_passport` (`ic_passport`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 5. Create admin_users table for superadmin management
-- =====================================================================
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `email` varchar(200) NOT NULL UNIQUE,
  `name` varchar(200) NOT NULL,
  `role` ENUM('admin', 'superadmin') DEFAULT 'admin',
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_by` int(11) COMMENT 'Superadmin who created this admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`admin_id`),
  UNIQUE KEY `unique_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 6. Update admin table to add role and timestamps
-- =====================================================================
ALTER TABLE `admin`
ADD COLUMN `role` ENUM('admin', 'superadmin') DEFAULT 'admin' AFTER `last_login`,
ADD COLUMN `status` ENUM('active', 'inactive') DEFAULT 'active' AFTER `role`,
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `status`,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- =====================================================================
-- 7. Update user table to add timestamps
-- =====================================================================
ALTER TABLE `user`
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `last_login`,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- =====================================================================
-- 8. Create audit log table for admin actions
-- =====================================================================
CREATE TABLE IF NOT EXISTS `admin_action_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL COMMENT 'Action type: add_admin, add_user, remove_sticker, etc',
  `table_name` varchar(100),
  `record_id` int(11),
  `description` text,
  `ip_address` varchar(45),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`admin_id`) REFERENCES `admin` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- END MIGRATION
-- =====================================================================
