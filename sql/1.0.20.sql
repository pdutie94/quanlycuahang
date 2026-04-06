-- 1.0.20: Thêm sản phẩm khác cho phiếu nhập, không ảnh hưởng tồn kho

CREATE TABLE IF NOT EXISTS purchase_manual_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT UNSIGNED NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    unit_name VARCHAR(50) DEFAULT NULL,
    qty DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    price_cost BIGINT UNSIGNED NOT NULL DEFAULT 0,
    amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
    KEY idx_purchase_manual_items_purchase_id (purchase_id),
    CONSTRAINT fk_purchase_manual_items_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;