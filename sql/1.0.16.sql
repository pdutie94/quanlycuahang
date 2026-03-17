-- Thêm bảng chi tiết dòng hàng tự do cho đơn hàng

CREATE TABLE IF NOT EXISTS `order_manual_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `item_name` VARCHAR(255) NOT NULL,
  `unit_name` VARCHAR(50) DEFAULT NULL,
  `qty` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `price_buy` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `amount_buy` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `price_sell` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `amount_sell` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_order_manual_items_order` (`order_id`),
  CONSTRAINT `fk_order_manual_items_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

