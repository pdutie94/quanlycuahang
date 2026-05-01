-- Version: 1.1.0
-- Description: Add performance indexes for Phase 1 optimization
-- Author: System
-- Date: 2026-05-02
-- Note: Only adds indexes that don't exist yet (checked from db-structure-new.sql)

-- ============================================
-- NEW INDEXES TO ADD (not in existing DB):
-- ============================================

-- orders: idx_orders_status_order_status (combined), idx_orders_order_code
SET @idx_exists := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'orders' AND index_name = 'idx_orders_status_order_status');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE `orders` ADD INDEX `idx_orders_status_order_status` (`status`, `order_status`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'orders' AND index_name = 'idx_orders_order_code');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE `orders` ADD INDEX `idx_orders_order_code` (`order_code`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- products: idx_products_name_code (combined for search)
SET @idx_exists := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'products' AND index_name = 'idx_products_name_code');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE `products` ADD INDEX `idx_products_name_code` (`name`(100), `code`(50))', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- customers: idx_customers_name_phone (combined)
SET @idx_exists := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'customers' AND index_name = 'idx_customers_name_phone');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE `customers` ADD INDEX `idx_customers_name_phone` (`name`(100), `phone`(20))', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- product_categories: idx_product_categories_name
SET @idx_exists := (SELECT COUNT(1) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'product_categories' AND index_name = 'idx_product_categories_name');
SET @sql := IF(@idx_exists = 0, 'ALTER TABLE `product_categories` ADD INDEX `idx_product_categories_name` (`name`(100))', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================
-- ALREADY EXISTS (from db-structure-new.sql):
-- products: idx_products_deleted_at, fk_products_category, fk_products_base_unit
-- orders: idx_orders_order_date, idx_orders_status, idx_orders_order_status, 
--         idx_orders_customer_id, idx_orders_deleted_at, idx_orders_filter
-- order_items: fk_order_items_order, fk_order_items_product, fk_order_items_product_unit,
--              idx_order_items_order, idx_order_items_order_id, idx_order_items_product_id
-- order_manual_items: idx_order_manual_items_order
-- inventory: uniq_inventory_product (UNIQUE), fk_inventory_product
-- customers: idx_customers_deleted_at, idx_customers_name
-- payments: idx_payments_customer, idx_payments_supplier, idx_payments_order, 
--           idx_payments_purchase, idx_payments_type, idx_payments_type_order, idx_payments_type_purchase
-- purchases: idx_purchases_purchase_date, idx_purchases_status, idx_purchases_supplier_id
-- purchase_items: fk_purchase_items_purchase, fk_purchase_items_product, fk_purchase_items_product_unit
-- purchase_manual_items: idx_purchase_manual_items_purchase_id
-- suppliers: idx_suppliers_deleted_at, idx_suppliers_name, idx_suppliers_phone
-- product_units: fk_product_units_product, fk_product_units_unit
-- ============================================
