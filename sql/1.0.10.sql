SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'customers'
    AND index_name = 'uniq_customers_phone'
);

SET @drop_sql := IF(
  @idx_exists > 0,
  'ALTER TABLE `customers` DROP INDEX `uniq_customers_phone`',
  'SELECT 1'
);

PREPARE stmt FROM @drop_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
