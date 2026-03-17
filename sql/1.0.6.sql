-- Thêm bảng lưu lịch sử giá và tồn kho sản phẩm

CREATE TABLE IF NOT EXISTS `product_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `detail` VARCHAR(250) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_logs_product_id` (`product_id`),
  CONSTRAINT `fk_product_logs_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

