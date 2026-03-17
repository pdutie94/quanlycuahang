-- Thêm cột đã thu tiền cho bảng phiếu giao dịch

ALTER TABLE `transactions`
  ADD COLUMN `paid_amount` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `total_sell_amount`;

