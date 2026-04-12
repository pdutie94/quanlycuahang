-- 1.0.21.sql
-- Thêm trường auto_price_enabled (tinyint), auto_price_value (bigint) cho bảng products

ALTER TABLE `products`
  ADD COLUMN `auto_price_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `deleted_at`,
  ADD COLUMN `auto_price_value` BIGINT NULL DEFAULT NULL AFTER `auto_price_enabled`;
