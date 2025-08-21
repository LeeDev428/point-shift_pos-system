-- Update orders table to support new POS features
-- Run this SQL to add the missing columns

ALTER TABLE `orders` 
ADD COLUMN `subtotal` DECIMAL(10,2) DEFAULT 0.00 AFTER `total_amount`,
ADD COLUMN `discount_percent` DECIMAL(5,2) DEFAULT 0.00 AFTER `subtotal`,
ADD COLUMN `discount_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `discount_percent`,
ADD COLUMN `tax_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `discount_amount`,
ADD COLUMN `payment_method` VARCHAR(50) DEFAULT 'cash' AFTER `tax_amount`,
ADD COLUMN `amount_received` DECIMAL(10,2) DEFAULT 0.00 AFTER `payment_method`;

-- Update existing orders to have default values
UPDATE `orders` SET 
    `subtotal` = `total_amount`,
    `tax_amount` = `total_amount` * 0.12,
    `payment_method` = 'cash',
    `amount_received` = `total_amount`
WHERE `subtotal` = 0.00;

-- Show the updated table structure
DESCRIBE `orders`;
