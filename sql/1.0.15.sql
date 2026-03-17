-- Thêm phụ thu cho đơn hàng và phiếu giao dịch

ALTER TABLE `orders`
  ADD COLUMN `surcharge_amount` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `discount_amount`;

ALTER TABLE `transactions`
  ADD COLUMN `surcharge_amount` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `paid_amount`;

