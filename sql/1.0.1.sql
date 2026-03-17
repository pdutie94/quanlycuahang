ALTER TABLE `product_units`
  ADD COLUMN `allow_fraction` TINYINT(1) NOT NULL DEFAULT '0' AFTER `price_cost`,
  ADD COLUMN `min_step` DECIMAL(15,4) NOT NULL DEFAULT '1.0000' AFTER `allow_fraction`;
