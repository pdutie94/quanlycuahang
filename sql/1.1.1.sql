-- Version: 1.1.1
-- Description: Add an index for active-order reporting queries.
-- Safe to re-run: only creates the index when it is missing.

SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'orders'
    AND index_name = 'idx_orders_report_active'
);
SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `orders` ADD INDEX `idx_orders_report_active` (`deleted_at`, `order_date`, `id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
