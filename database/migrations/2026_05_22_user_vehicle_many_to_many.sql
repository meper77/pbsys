-- Create many-to-many relationship between users and vehicles
-- Date: 2026-05-22

-- Junction table for user-vehicle association
CREATE TABLE IF NOT EXISTS `user_vehicle` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `vehicle_type` enum('visitor', 'staff', 'student', 'contractor') NOT NULL,
  `role` varchar(50) DEFAULT 'owner',
  `assigned_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` int(11) DEFAULT NULL,
  UNIQUE KEY `unique_user_vehicle` (`user_id`, `vehicle_id`, `vehicle_type`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`userid`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `admin` (`userid`) ON DELETE SET NULL,
  INDEX `idx_vehicle` (`vehicle_id`, `vehicle_type`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add optional user_id to vehicle tables for backward compatibility (optional)
ALTER TABLE `visitorcar` ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `id`;
ALTER TABLE `staffcar` ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `id`;
ALTER TABLE `studentcar` ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `id`;
ALTER TABLE `contractorcar` ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `id`;

-- Add optional user_id to owner table
ALTER TABLE `owner` ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `id`;
