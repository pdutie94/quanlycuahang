-- 1. Tạo bảng purchase_logs (code PurchaseLog.php đang reference nhưng bảng chưa tồn tại)
CREATE TABLE IF NOT EXISTS `purchase_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `detail` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_purchase_logs_purchase_id` (`purchase_id`),
  CONSTRAINT `fk_purchase_logs_purchase`
    FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Thêm UNIQUE constraint cho inventory.product_id (tránh duplicate inventory rows)
ALTER TABLE `inventory`
  ADD UNIQUE KEY `uniq_inventory_product` (`product_id`);

-- 3. Index cho orders.deleted_at (mọi query đều filter IS NULL)
ALTER TABLE `orders`
  ADD KEY `idx_orders_deleted_at` (`deleted_at`);

-- 4. FK constraint cho project_issues.project_id
ALTER TABLE `project_issues`
  ADD CONSTRAINT `fk_project_issues_project`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- 5. FK constraints cho project_issue_items
ALTER TABLE `project_issue_items`
  ADD CONSTRAINT `fk_project_issue_items_issue`
    FOREIGN KEY (`project_issue_id`) REFERENCES `project_issues` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_project_issue_items_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_project_issue_items_product_unit`
    FOREIGN KEY (`product_unit_id`) REFERENCES `product_units` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE;

-- 6. Sửa collation cho product_logs (đồng nhất với các bảng khác)
ALTER TABLE `product_logs`
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
