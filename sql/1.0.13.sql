-- Loại bỏ các cột không còn sử dụng trên form Phiếu giao dịch

ALTER TABLE `transactions`
  DROP COLUMN `customer_address`;

