-- 1.0.22.sql
-- Thêm trường tính giá theo cân nặng cho bảng products
-- Tạo bảng material_prices để quản lý giá vật liệu theo kg

-- Thêm các trường vào bảng products
ALTER TABLE `products`
  ADD COLUMN `weight_price_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `auto_price_value`,
  ADD COLUMN `weight_value` DECIMAL(10,3) NOT NULL DEFAULT 0 AFTER `weight_price_enabled`,
  ADD COLUMN `material_type` VARCHAR(50) NULL DEFAULT NULL AFTER `weight_value`;

-- Tạo bảng material_prices để quản lý giá vật liệu theo kg
CREATE TABLE `material_prices` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `material_type` VARCHAR(50) NOT NULL,
  `price_per_kg` BIGINT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_material_type` (`material_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert dữ liệu mẫu cho material_prices
INSERT INTO `material_prices` (`material_type`, `price_per_kg`) VALUES
('Sắt', 17000.00),
('Thép', 18600.00);
