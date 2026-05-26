-- Migration: 2026_05_26_create_vehicle_search_cache.sql
-- Cache table for vehicle search across all types

CREATE TABLE IF NOT EXISTS `vehicle_search_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` int(11) NOT NULL,
  `vehicle_type` enum('visitor','staff','student','contractor') NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `brand` varchar(100),
  `color` varchar(50),
  `phone` varchar(20),
  `staff_number` varchar(20),
  `matric_number` varchar(20),
  `owner_name` varchar(255),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `last_updated` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Full-text search index
  FULLTEXT INDEX `ft_search` (`plate_number`, `brand`, `owner_name`),
  
  -- Regular indexes
  UNIQUE KEY `unique_cache` (`vehicle_id`, `vehicle_type`),
  KEY `idx_status` (`status`),
  KEY `idx_vehicle_type` (`vehicle_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
