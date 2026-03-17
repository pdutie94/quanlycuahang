-- Xóa module phiếu giao dịch cũ
-- Chỉ chạy file này khi chắc chắn không còn dùng bảng transactions và transaction_items

ALTER TABLE `transaction_items`
  DROP FOREIGN KEY `fk_transaction_items_transaction`;

DROP TABLE IF EXISTS `transaction_items`;
DROP TABLE IF EXISTS `transactions`;

