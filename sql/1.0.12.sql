-- Tạo bảng phiếu giao dịch (transactions) và chi tiết phiếu (transaction_items)

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `transaction_code` VARCHAR(50) NOT NULL,
  `customer_id` INT UNSIGNED DEFAULT NULL,
  `customer_name` VARCHAR(255) DEFAULT NULL,
  `customer_phone` VARCHAR(50) DEFAULT NULL,
  `customer_address` VARCHAR(255) DEFAULT NULL,
  `transaction_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_buy_amount` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `total_sell_amount` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `profit_amount` BIGINT NOT NULL DEFAULT 0,
  `note` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_transactions_date` (`transaction_date`),
  KEY `idx_transactions_customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `transaction_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `transaction_id` INT UNSIGNED NOT NULL,
  `item_name` VARCHAR(255) NOT NULL,
  `unit_name` VARCHAR(50) DEFAULT NULL,
  `qty` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  `price_buy` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `amount_buy` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `price_sell` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `amount_sell` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_transaction_items_transaction` (`transaction_id`),
  CONSTRAINT `fk_transaction_items_transaction`
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

