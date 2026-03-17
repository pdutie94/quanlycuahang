-- Thêm cột ngưỡng tồn kho tối thiểu cho sản phẩm

ALTER TABLE `products`
ADD COLUMN `min_stock_qty` DECIMAL(18,4) NULL DEFAULT NULL AFTER `base_unit_id`;

