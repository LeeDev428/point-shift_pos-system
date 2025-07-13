-- SQL Update for POS System Orders Table
-- Run these SQL commands in your database (phpMyAdmin, MySQL Workbench, etc.)

-- Step 1: Add new columns to orders table
ALTER TABLE `orders` 
ADD COLUMN `subtotal` DECIMAL(10,2) DEFAULT 0.00 AFTER `total_amount`,
ADD COLUMN `discount_percent` DECIMAL(5,2) DEFAULT 0.00 AFTER `subtotal`,
ADD COLUMN `discount_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `discount_percent`,
ADD COLUMN `tax_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `discount_amount`,
ADD COLUMN `payment_method` VARCHAR(50) DEFAULT 'cash' AFTER `tax_amount`,
ADD COLUMN `amount_received` DECIMAL(10,2) DEFAULT 0.00 AFTER `payment_method`;

-- Step 2: Update created_at column to use proper timezone (if it doesn't exist, add it)
-- Check if created_at column exists, if not add it
ALTER TABLE `orders` 
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `amount_received`,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Step 3: Set MySQL timezone to Philippines (Manila)
SET time_zone = '+08:00';

-- Step 4: Update existing orders with calculated values and proper timestamps
UPDATE `orders` SET 
    `subtotal` = `total_amount` / 1.12,
    `discount_percent` = 0.00,
    `discount_amount` = 0.00,
    `tax_amount` = (`total_amount` / 1.12) * 0.12,
    `payment_method` = 'cash',
    `amount_received` = `total_amount`,
    `created_at` = COALESCE(`created_at`, NOW()),
    `updated_at` = NOW()
WHERE `subtotal` = 0.00;

-- Step 6: Check and update users table to ensure first_name and last_name columns exist
-- This fixes the layout.php undefined array key warnings
ALTER TABLE `users` 
ADD COLUMN `first_name` VARCHAR(50) DEFAULT 'User' AFTER `password`,
ADD COLUMN `last_name` VARCHAR(50) DEFAULT '' AFTER `first_name`;

-- Update any NULL values in existing users
UPDATE `users` SET 
    `first_name` = COALESCE(`first_name`, `username`),
    `last_name` = COALESCE(`last_name`, '')
WHERE `first_name` IS NULL OR `first_name` = '';

-- Step 7: Verify the table structure (optional - shows the updated table)
DESCRIBE `orders`;

-- Step 8: Check a sample record (optional - shows updated data)
SELECT * FROM `orders` ORDER BY `created_at` DESC LIMIT 1;
