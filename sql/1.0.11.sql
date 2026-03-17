-- Thêm cột giảm giá cho đơn hàng

ALTER TABLE `orders`
ADD COLUMN `discount_type` ENUM('none','fixed','percent') NOT NULL DEFAULT 'none' AFTER `note`,
ADD COLUMN `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `discount_type`,
ADD COLUMN `discount_amount` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `discount_value`;

