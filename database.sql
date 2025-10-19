-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for pointshift_pos
CREATE DATABASE IF NOT EXISTS `pointshift_pos` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `pointshift_pos`;

-- Dumping structure for table pointshift_pos.categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pointshift_pos.categories: ~6 rows (approximately)
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
	(8, 'Noodles', 'Various types of noodles and pasta dishes', '2025-10-08 19:11:30'),
	(9, 'Beverages', 'Drinks, juices, and refreshments', '2025-10-08 19:11:30'),
	(10, 'Snacks', 'Light snacks and finger foods', '2025-10-08 19:11:30'),
	(11, 'Rice Meals', 'Meals served with rice', '2025-10-08 19:11:30'),
	(12, 'Desserts', 'Sweet treats and desserts', '2025-10-08 19:11:30'),
	(13, 'Breakfast', 'Breakfast items and all-day breakfast', '2025-10-08 19:11:30');

-- Dumping structure for table pointshift_pos.inventory_reports
CREATE TABLE IF NOT EXISTS `inventory_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `product_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `change_type` enum('Added','Removed') NOT NULL,
  `quantity` int NOT NULL DEFAULT '0' COMMENT 'Legacy column - same as quantity_changed',
  `quantity_changed` int NOT NULL DEFAULT '0',
  `previous_quantity` int DEFAULT NULL,
  `new_quantity` int DEFAULT NULL,
  `remarks` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `fk_inventory_reports_user` (`user_id`),
  KEY `idx_inventory_reports_date` (`date` DESC),
  KEY `idx_inventory_reports_created` (`created_at` DESC),
  CONSTRAINT `fk_inventory_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_reports_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pointshift_pos.inventory_reports: ~1 rows (approximately)
INSERT INTO `inventory_reports` (`id`, `date`, `product_id`, `user_id`, `change_type`, `quantity`, `quantity_changed`, `previous_quantity`, `new_quantity`, `remarks`, `created_at`) VALUES
	(2, '2025-10-19', 77, 3, 'Added', 10, 10, 5, 15, 'Stock added by staff. Previous: 5, New: 15', '2025-10-19 11:01:55'),
	(3, '2025-10-19', 77, 3, 'Removed', 10, 10, 15, 5, 'Stock removed by staff. Previous: 15, New: 5', '2025-10-19 11:02:22'),
	(4, '2025-10-19', 77, 3, 'Added', 50, 50, 5, 55, 'Stock added by staff. Previous: 5, New: 55', '2025-10-19 11:04:54');

-- Dumping structure for table pointshift_pos.messages
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `recipient_id` int DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `parent_message_id` int DEFAULT NULL COMMENT 'For threaded conversations',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `recipient_id` (`recipient_id`),
  KEY `parent_message_id` (`parent_message_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`),
  CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`parent_message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pointshift_pos.messages: ~2 rows (approximately)
INSERT INTO `messages` (`id`, `sender_id`, `recipient_id`, `subject`, `message`, `parent_message_id`, `is_read`, `created_at`, `updated_at`) VALUES
	(1, 3, 1, 'Economics', 'wdasd', NULL, 0, '2025-10-09 14:21:01', '2025-10-09 14:21:01'),
	(2, 2, 3, 'Re: Economics', 'wdasd', 1, 0, '2025-10-09 14:21:46', '2025-10-09 14:21:46'),
	(3, 4, 1, 'what is astronomy', 'wdasd', NULL, 0, '2025-10-09 14:22:35', '2025-10-09 14:22:35');

-- Dumping structure for table pointshift_pos.orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) NOT NULL,
  `user_id` int NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT '0.00',
  `discount_percent` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `tax_amount` decimal(10,2) DEFAULT '0.00',
  `payment_method` varchar(50) DEFAULT 'cash',
  `amount_received` decimal(10,2) DEFAULT '0.00',
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pointshift_pos.orders: ~4 rows (approximately)
INSERT INTO `orders` (`id`, `order_number`, `user_id`, `total_amount`, `subtotal`, `discount_percent`, `discount_amount`, `tax_amount`, `payment_method`, `amount_received`, `status`, `created_at`) VALUES
	(13, 'ORD-20251017-053', 4, 33.60, 30.00, 0.00, 0.00, 3.60, 'cash', 50.00, 'completed', '2025-10-17 09:52:40'),
	(14, 'ORD-20251017-956', 4, 201.60, 180.00, 0.00, 0.00, 21.60, 'cash', 1000.00, 'completed', '2025-10-17 09:53:19'),
	(15, 'ORD-20251017-238', 4, 168.00, 150.00, 0.00, 0.00, 18.00, 'cash', 200.00, 'completed', '2025-10-17 10:03:20'),
	(16, 'ORD-20251017-368', 4, 974.40, 870.00, 0.00, 0.00, 104.40, 'cash', 1003.00, 'completed', '2025-10-17 10:31:34'),
	(17, 'ORD-20251019-973', 4, 168.00, 150.00, 0.00, 0.00, 18.00, 'gcash', 500.00, 'completed', '2025-10-19 10:29:54'),
	(18, 'ORD-20251019-084', 4, 168.00, 150.00, 0.00, 0.00, 18.00, 'gcash', 1000.00, 'completed', '2025-10-19 10:31:38'),
	(19, 'ORD-20251019-831', 4, 168.00, 150.00, 0.00, 0.00, 18.00, 'gcash', 200.00, 'completed', '2025-10-19 10:33:15');

-- Dumping structure for table pointshift_pos.order_items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pointshift_pos.order_items: ~6 rows (approximately)
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`, `total_price`) VALUES
	(12, 13, 63, 1, 30.00, 30.00),
	(13, 14, 60, 1, 180.00, 180.00),
	(14, 15, 59, 1, 150.00, 150.00),
	(15, 16, 59, 1, 150.00, 150.00),
	(16, 16, 65, 1, 20.00, 20.00),
	(17, 16, 61, 7, 100.00, 700.00),
	(18, 17, 59, 1, 150.00, 150.00),
	(19, 18, 59, 1, 150.00, 150.00),
	(20, 19, 59, 1, 150.00, 150.00);

-- Dumping structure for table pointshift_pos.payment_qrcodes
CREATE TABLE IF NOT EXISTS `payment_qrcodes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payment_method` varchar(50) NOT NULL DEFAULT 'gcash',
  `qr_code_path` varchar(255) NOT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `payment_method` (`payment_method`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pointshift_pos.payment_qrcodes: ~1 rows (approximately)
INSERT INTO `payment_qrcodes` (`id`, `payment_method`, `qr_code_path`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
	(2, 'gcash', 'uploads/qrcodes/gcash_qr_1760869672.png', 'GCash Payment QR Code', 1, '2025-10-19 10:27:52', '2025-10-19 10:27:52');

-- Dumping structure for table pointshift_pos.products
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `sku` varchar(64) DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int DEFAULT '0',
  `low_stock_threshold` int DEFAULT '10',
  `barcode` varchar(100) DEFAULT NULL,
  `expiry` date DEFAULT NULL,
  `description` text,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_updated_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `fk_products_last_updated_by` (`last_updated_by`),
  CONSTRAINT `fk_products_last_updated_by` FOREIGN KEY (`last_updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pointshift_pos.products: ~20 rows (approximately)
INSERT INTO `products` (`id`, `name`, `sku`, `category_id`, `price`, `stock_quantity`, `low_stock_threshold`, `barcode`, `expiry`, `description`, `status`, `created_at`, `updated_at`, `last_updated_by`) VALUES
	(58, 'Chicken Ramen', 'NOOD001', 8, 120.00, 50, 10, '1234567890123', NULL, 'Spicy chicken ramen bowl', 'active', '2025-10-08 19:13:38', '2025-10-08 19:13:38', NULL),
	(59, 'Beef Udon', 'NOOD002', 8, 150.00, 35, 10, '1234567890124', NULL, 'Japanese beef udon noodles', 'active', '2025-10-08 19:13:38', '2025-10-19 10:33:15', NULL),
	(60, 'Seafood Pasta', 'NOOD003', 8, 180.00, 29, 10, '1234567890125', NULL, 'Creamy seafood pasta', 'active', '2025-10-08 19:13:38', '2025-10-17 09:53:19', NULL),
	(61, 'Pancit Canton', 'NOOD004', 8, 100.00, 53, 15, '1234567890126', NULL, 'Filipino stir-fried noodles', 'active', '2025-10-08 19:13:38', '2025-10-17 10:31:34', NULL),
	(62, 'Iced Tea', 'BEV001', 9, 35.00, 100, 20, '2234567890123', NULL, 'Refreshing iced tea', 'active', '2025-10-08 19:13:38', '2025-10-08 19:13:38', NULL),
	(63, 'Cola', 'BEV002', 9, 30.00, 119, 20, '2234567890124', NULL, 'Carbonated soft drink', 'active', '2025-10-08 19:13:38', '2025-10-17 09:52:40', NULL),
	(64, 'Orange Juice', 'BEV003', 9, 45.00, 80, 15, '2234567890125', NULL, 'Fresh orange juice', 'active', '2025-10-08 19:13:38', '2025-10-08 19:13:38', NULL),
	(65, 'Bottled Water', 'BEV004', 9, 20.00, 149, 30, '2234567890126', NULL, 'Mineral water 500ml', 'active', '2025-10-08 19:13:38', '2025-10-17 10:31:34', NULL),
	(66, 'Coffee', 'BEV005', 9, 50.00, 70, 15, '2234567890127', NULL, 'Hot or iced coffee', 'active', '2025-10-08 19:13:38', '2025-10-08 19:13:38', NULL),
	(67, 'Spring Rolls', 'SNACK001', 10, 50.00, 60, 10, '3234567890123', NULL, 'Vegetable spring rolls (3pcs)', 'active', '2025-10-08 19:13:38', '2025-10-08 19:13:38', NULL),
	(68, 'French Fries', 'SNACK002', 10, 60.00, 50, 10, '3234567890124', NULL, 'Crispy french fries', 'active', '2025-10-08 19:13:38', '2025-10-08 19:13:38', NULL),
	(69, 'Nachos', 'SNACK003', 10, 80.00, 40, 10, '3234567890125', NULL, 'Nachos with cheese dip', 'active', '2025-10-08 19:13:38', '2025-10-08 19:13:38', NULL),
	(70, 'Fried Rice', 'RICE001', 11, 90.00, 70, 10, '4234567890123', NULL, 'Classic fried rice', 'active', '2025-10-08 19:13:38', '2025-10-08 19:13:38', NULL),
	(71, 'Chicken Adobo Rice', 'RICE002', 11, 130.00, 50, 10, '4234567890124', NULL, 'Filipino chicken adobo with rice', 'active', '2025-10-08 19:13:38', '2025-10-08 19:13:38', NULL),
	(72, 'Pork Sisig Rice', 'RICE003', 11, 140.00, 45, 10, '4234567890125', NULL, 'Sizzling sisig with rice', 'active', '2025-10-08 19:13:38', '2025-10-08 19:13:38', NULL),
	(73, 'Halo-Halo', 'DESS001', 12, 85.00, 30, 5, '5234567890123', NULL, 'Filipino mixed dessert', 'active', '2025-10-08 19:13:38', '2025-10-08 19:13:38', NULL),
	(74, 'Ice Cream', 'DESS002', 12, 45.00, 40, 10, '5234567890124', NULL, 'Vanilla ice cream scoop', 'active', '2025-10-08 19:13:38', '2025-10-08 19:13:38', NULL),
	(75, 'Leche Flan', 'DESS003', 12, 60.00, 25, 5, '5234567890125', NULL, 'Caramel custard dessert', 'active', '2025-10-08 19:13:38', '2025-10-08 19:13:38', NULL),
	(76, 'Tapsilog', 'BFAST001', 13, 120.00, 40, 8, '6234567890123', NULL, 'Beef tapa, egg and rice', 'active', '2025-10-08 19:13:38', '2025-10-08 19:13:38', NULL),
	(77, 'Longsilog', 'BFAST002', 13, 110.00, 55, 8, '6234567890124', NULL, 'Longganisa, egg and rice', 'active', '2025-10-08 19:13:38', '2025-10-19 11:04:54', 3);

-- Dumping structure for table pointshift_pos.shifts
CREATE TABLE IF NOT EXISTS `shifts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shift_name` varchar(100) NOT NULL,
  `shift_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `description` text,
  `location` varchar(255) DEFAULT NULL,
  `max_employees` int DEFAULT '10',
  `status` enum('scheduled','in-progress','completed','cancelled') DEFAULT 'scheduled',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `shift_date` (`shift_date`),
  KEY `status` (`status`),
  CONSTRAINT `shifts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pointshift_pos.shifts: ~4 rows (approximately)
INSERT INTO `shifts` (`id`, `shift_name`, `shift_date`, `start_time`, `end_time`, `description`, `location`, `max_employees`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
	(1, 'Morning Shift', '2025-10-10', '08:00:00', '16:00:00', 'Regular morning shift', 'Main Store', 5, 'scheduled', 1, '2025-10-09 19:03:13', '2025-10-09 19:03:13'),
	(2, 'Evening Shift', '2025-10-10', '16:00:00', '00:00:00', 'Regular evening shift', 'Main Store', 4, 'scheduled', 1, '2025-10-09 19:03:13', '2025-10-09 19:03:13'),
	(3, 'Weekend Day Shift', '2025-10-12', '09:00:00', '17:00:00', 'Weekend coverage', 'Main Store', 6, 'scheduled', 1, '2025-10-09 19:03:13', '2025-10-09 19:03:13'),
	(4, 'Night Shift', '2025-10-10', '00:00:00', '08:00:00', 'Overnight shift', 'Main Store', 3, 'scheduled', 1, '2025-10-09 19:03:13', '2025-10-09 19:03:13');

-- Dumping structure for table pointshift_pos.shift_assignments
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
  UNIQUE KEY `unique_shift_user` (`shift_id`,`user_id`),
  KEY `shift_id` (`shift_id`),
  KEY `user_id` (`user_id`),
  KEY `assigned_by` (`assigned_by`),
  CONSTRAINT `shift_assignments_ibfk_1` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pointshift_pos.shift_assignments: ~0 rows (approximately)

-- Dumping structure for table pointshift_pos.store_settings
CREATE TABLE IF NOT EXISTS `store_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` varchar(50) DEFAULT 'text' COMMENT 'text, number, boolean, image',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pointshift_pos.store_settings: ~15 rows (approximately)
INSERT INTO `store_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `created_at`, `updated_at`) VALUES
	(1, 'store_name', 'PointShift POS', 'text', '2025-10-09 14:54:59', '2025-10-09 14:54:59'),
	(2, 'store_branch', 'Main Branch', 'text', '2025-10-09 14:54:59', '2025-10-09 14:54:59'),
	(3, 'store_address', '123 Main Street, City, Country', 'text', '2025-10-09 14:54:59', '2025-10-09 14:54:59'),
	(4, 'store_phone', '+1234567890', 'text', '2025-10-09 14:54:59', '2025-10-09 14:54:59'),
	(5, 'store_email', 'info@pointshift.com', 'text', '2025-10-09 14:54:59', '2025-10-09 14:54:59'),
	(6, 'business_hours_open', '08:00', 'text', '2025-10-09 14:54:59', '2025-10-09 14:54:59'),
	(7, 'business_hours_close', '20:00', 'text', '2025-10-09 14:54:59', '2025-10-09 14:54:59'),
	(8, 'business_days', 'Monday to Sunday', 'text', '2025-10-09 14:54:59', '2025-10-09 14:54:59'),
	(9, 'store_logo', '', 'image', '2025-10-09 14:54:59', '2025-10-09 14:54:59'),
	(10, 'receipt_header', 'Thank you for your purchase!', 'text', '2025-10-09 14:54:59', '2025-10-09 14:54:59'),
	(11, 'receipt_footer', 'Please come again!', 'text', '2025-10-09 14:54:59', '2025-10-09 14:54:59'),
	(12, 'tax_rate', '12', 'number', '2025-10-09 14:54:59', '2025-10-09 14:54:59'),
	(13, 'currency_symbol', '₱', 'text', '2025-10-09 14:54:59', '2025-10-09 14:54:59'),
	(14, 'receipt_show_logo', '1', 'boolean', '2025-10-09 14:54:59', '2025-10-09 14:54:59'),
	(15, 'receipt_show_cashier', '1', 'boolean', '2025-10-09 14:54:59', '2025-10-09 14:54:59');

-- Dumping structure for table pointshift_pos.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','cashier') NOT NULL DEFAULT 'staff',
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pointshift_pos.users: ~4 rows (approximately)
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `first_name`, `last_name`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'admin', 'admin@pointshift.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'User', 'active', '2025-06-30 11:21:50', '2025-06-30 11:21:50'),
	(2, 'admindump', 'grafrafraftorres28@gmail.com', '$2y$10$wB7tFvfD2/K73LcwpMHrMemWZsipWBGrzylb0eyEpWtse0MggD1cm', 'admin', 'Admin', '', 'active', '2025-06-30 11:24:31', '2025-10-09 14:22:04'),
	(3, 'staff', 'staff@gmail.com', '$2y$10$HtaDym39mrOPMVH62.ITAOnQARovPiM/4JWJAGICGLyXKTMdFoSm2', 'staff', 'Staff', '', 'active', '2025-06-30 11:27:39', '2025-08-20 12:18:34'),
	(4, 'cashier', 'cashier@gmail.com', '$2y$10$3k22lTg9xqQCg3gvqZ65luVqUL807amCyu5nZZP6BpwW2THijnupG', 'cashier', 'Cashier', '', 'active', '2025-07-12 14:11:32', '2025-08-20 12:18:43');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
