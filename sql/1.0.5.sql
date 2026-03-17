ALTER TABLE `orders`
  ADD COLUMN `deleted_at` datetime DEFAULT NULL AFTER `note`;

