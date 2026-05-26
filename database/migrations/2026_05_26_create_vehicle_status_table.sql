-- Migration: 2026_05_26_create_vehicle_status_table.sql
-- Creates status table to track vehicle active/inactive status across types

CREATE TABLE IF NOT EXISTS `vehicle_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` int(11) NOT NULL,
  `vehicle_type` enum('visitor','staff','student','contractor') NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `status_changed_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `status_changed_by` int(11),
  `auto_inactive_date` date,
  `reactivated_at` timestamp NULL,
  
  -- Prevent duplicates
  UNIQUE KEY `unique_vehicle_status` (`vehicle_id`, `vehicle_type`),
  
  -- Indexes
  KEY `idx_vehicle_type` (`vehicle_type`, `status`),
  KEY `idx_auto_inactive_date` (`auto_inactive_date`),
  
  -- Foreign keys
  CONSTRAINT `fk_vehicle_status_admin` FOREIGN KEY (`status_changed_by`) 
    REFERENCES `admin`(`userid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
