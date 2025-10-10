-- Add shifts and shift_assignments tables for employee shift management
-- This allows admins to create shifts and assign employees to specific shifts

-- Create shifts table to store shift information
CREATE TABLE IF NOT EXISTS `shifts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shift_name` varchar(100) NOT NULL,
  `shift_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `description` text,
  `location` varchar(255) DEFAULT NULL,
  `max_employees` int DEFAULT 10,
  `status` enum('scheduled','in-progress','completed','cancelled') DEFAULT 'scheduled',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `shift_date` (`shift_date`),
  KEY `status` (`status`),
  CONSTRAINT `shifts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create shift_assignments table to assign employees to shifts
CREATE TABLE IF NOT EXISTS `shift_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shift_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` enum('supervisor','regular') DEFAULT 'regular',
  `status` enum('assigned','confirmed','declined','completed','no-show') DEFAULT 'assigned',
  `notes` text,
  `assigned_by` int NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_shift_user` (`shift_id`, `user_id`),
  KEY `shift_id` (`shift_id`),
  KEY `user_id` (`user_id`),
  KEY `assigned_by` (`assigned_by`),
  CONSTRAINT `shift_assignments_ibfk_1` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insert sample shifts for testing
INSERT INTO `shifts` (`shift_name`, `shift_date`, `start_time`, `end_time`, `description`, `location`, `max_employees`, `status`, `created_by`) VALUES
('Morning Shift', CURDATE(), '08:00:00', '16:00:00', 'Regular morning shift', 'Main Store', 5, 'scheduled', 1),
('Evening Shift', CURDATE(), '16:00:00', '00:00:00', 'Regular evening shift', 'Main Store', 4, 'scheduled', 1),
('Weekend Day Shift', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:00:00', '17:00:00', 'Weekend coverage', 'Main Store', 6, 'scheduled', 1),
('Night Shift', CURDATE(), '00:00:00', '08:00:00', 'Overnight shift', 'Main Store', 3, 'scheduled', 1);
