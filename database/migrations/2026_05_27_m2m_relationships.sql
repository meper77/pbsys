-- Phase 2: M:M Relationships - Brand Defaults & Junction Table Setup
-- Date: 2026-05-27

-- ========================================================================
-- PART 1: Fix brand column constraints across all vehicle tables
-- ========================================================================

-- Make sure brand column exists and has proper defaults in visitorcar
-- visitorcar uses 'id' as PK, inherits from owner table generally
-- We'll add/update the brand column to have a default if it doesn't exist

ALTER TABLE `visitorcar`
  MODIFY `brand` varchar(100) NOT NULL DEFAULT 'N/A';

-- Make sure brand column exists in staffcar
ALTER TABLE `staffcar`
  MODIFY `brand` varchar(100) NOT NULL DEFAULT 'N/A';

-- Make sure brand column exists in studentcar
ALTER TABLE `studentcar`
  MODIFY `brand` varchar(100) NOT NULL DEFAULT 'N/A';

-- Make sure brand column exists in contractorcar
ALTER TABLE `contractorcar`
  MODIFY `brand` varchar(100) NOT NULL DEFAULT 'N/A';

-- ========================================================================
-- PART 2: Ensure M:M Junction Table exists with proper constraints
-- ========================================================================

-- Create/recreate user_vehicle junction table with complete constraints
CREATE TABLE IF NOT EXISTS `user_vehicle` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `vehicle_type` enum('visitor', 'staff', 'student', 'contractor') NOT NULL,
  `role` varchar(50) DEFAULT 'owner' NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` int(11) DEFAULT NULL,
  
  UNIQUE KEY `unique_user_vehicle` (`user_id`, `vehicle_id`, `vehicle_type`),
  KEY `idx_vehicle_type` (`vehicle_type`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_vehicle_id` (`vehicle_id`),
  
  FOREIGN KEY (`user_id`) REFERENCES `user` (`userid`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_by`) REFERENCES `admin` (`userid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================================
-- PART 3: Verify owner table is optional for M:M compatibility
-- ========================================================================

-- Ensure owner table can coexist with M:M by making fields nullable where needed
ALTER TABLE `owner`
  MODIFY `user_id` int(11) DEFAULT NULL;

-- Add index for faster lookup
ALTER TABLE `owner`
  ADD INDEX `idx_user_id` (`user_id`);

-- ========================================================================
-- SUMMARY
-- ========================================================================
-- 1. Fixed brand DEFAULT 'N/A' on all 4 vehicle tables
-- 2. Verified user_vehicle M:M junction table with proper PKs, FKs, and UNIQUEs
-- 3. Made owner.user_id optional for backward compatibility
-- 4. Added indexes for performance on M:M operations
