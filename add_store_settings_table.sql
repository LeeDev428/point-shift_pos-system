-- Add store_settings table for system configuration
-- This stores all store/business configuration details

CREATE TABLE IF NOT EXISTS `store_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` varchar(50) DEFAULT 'text' COMMENT 'text, number, boolean, image',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insert default store settings
INSERT INTO `store_settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
('store_name', 'PointShift POS', 'text'),
('store_branch', 'Main Branch', 'text'),
('store_address', '123 Main Street, City, Country', 'text'),
('store_phone', '+1234567890', 'text'),
('store_email', 'info@pointshift.com', 'text'),
('business_hours_open', '08:00', 'text'),
('business_hours_close', '20:00', 'text'),
('business_days', 'Monday to Sunday', 'text'),
('store_logo', '', 'image'),
('receipt_header', 'Thank you for your purchase!', 'text'),
('receipt_footer', 'Please come again!', 'text'),
('tax_rate', '12', 'number'),
('currency_symbol', '₱', 'text'),
('receipt_show_logo', '1', 'boolean'),
('receipt_show_cashier', '1', 'boolean')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
