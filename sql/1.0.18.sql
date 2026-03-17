-- Tối ưu hiệu năng cho các báo cáo và danh sách lớn

-- Index cho đơn hàng: lọc theo ngày, trạng thái, khách hàng, thanh toán, soft delete
SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'orders'
    AND index_name = 'idx_orders_order_date'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `orders` ADD KEY `idx_orders_order_date` (`order_date`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'orders'
    AND index_name = 'idx_orders_customer_id'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `orders` ADD KEY `idx_orders_customer_id` (`customer_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'orders'
    AND index_name = 'idx_orders_status'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `orders` ADD KEY `idx_orders_status` (`status`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'orders'
    AND index_name = 'idx_orders_order_status'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `orders` ADD KEY `idx_orders_order_status` (`order_status`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index cho chi tiết đơn hàng: thống kê theo đơn và theo sản phẩm
SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'order_items'
    AND index_name = 'idx_order_items_order_id'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `order_items` ADD KEY `idx_order_items_order_id` (`order_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'order_items'
    AND index_name = 'idx_order_items_product_id'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `order_items` ADD KEY `idx_order_items_product_id` (`product_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index cho phiếu nhập: lọc theo ngày và nhà cung cấp
SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'purchases'
    AND index_name = 'idx_purchases_purchase_date'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `purchases` ADD KEY `idx_purchases_purchase_date` (`purchase_date`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'purchases'
    AND index_name = 'idx_purchases_supplier_id'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `purchases` ADD KEY `idx_purchases_supplier_id` (`supplier_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index cho payments: tra cứu theo loại chứng từ
SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'payments'
    AND index_name = 'idx_payments_type_order'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `payments` ADD KEY `idx_payments_type_order` (`type`, `order_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'payments'
    AND index_name = 'idx_payments_type_purchase'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `payments` ADD KEY `idx_payments_type_purchase` (`type`, `purchase_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'payments'
    AND index_name = 'idx_payments_customer'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `payments` ADD KEY `idx_payments_customer` (`customer_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'payments'
    AND index_name = 'idx_payments_supplier'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `payments` ADD KEY `idx_payments_supplier` (`supplier_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
