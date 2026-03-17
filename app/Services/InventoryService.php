<?php

class InventoryService
{
    public static function adjustForNewPurchaseItems($items)
    {
        if (!class_exists('Inventory')) {
            return;
        }

        if (!is_array($items) || empty($items)) {
            return;
        }

        foreach ($items as $row) {
            $productId = isset($row['product_id']) ? (int) $row['product_id'] : 0;
            $qtyBase = isset($row['qty_base']) ? (float) $row['qty_base'] : 0.0;
            if ($productId > 0 && $qtyBase != 0.0) {
                Inventory::adjust($productId, $qtyBase);
            }
        }
    }

    public static function rollbackOldPurchaseItems($items)
    {
        if (!class_exists('Inventory')) {
            return;
        }

        if (!is_array($items) || empty($items)) {
            return;
        }

        foreach ($items as $oldItem) {
            $productId = isset($oldItem['product_id']) ? (int) $oldItem['product_id'] : 0;
            $qtyBaseOld = isset($oldItem['qty_base']) ? (float) $oldItem['qty_base'] : 0.0;
            if ($productId > 0 && $qtyBaseOld > 0) {
                Inventory::adjust($productId, -$qtyBaseOld);
            }
        }
    }

    public static function adjustForOrderStatusChange($items, $direction)
    {
        if (!class_exists('Inventory')) {
            return;
        }

        $direction = (int) $direction;
        if ($direction === 0) {
            return;
        }

        if (!is_array($items) || empty($items)) {
            return;
        }

        foreach ($items as $row) {
            $productId = isset($row['product_id']) ? (int) $row['product_id'] : 0;
            $qtyBase = isset($row['qty_base']) ? (float) $row['qty_base'] : 0.0;
            if ($productId <= 0 || $qtyBase == 0.0) {
                continue;
            }
            Inventory::adjust($productId, $direction * $qtyBase);
        }
    }

    public static function applyOrderReturnAdjustments($items, $direction)
    {
        if (!class_exists('Inventory')) {
            return;
        }

        $direction = (int) $direction;
        if ($direction === 0) {
            return;
        }

        if (!is_array($items) || empty($items)) {
            return;
        }

        foreach ($items as $row) {
            $productId = isset($row['product_id']) ? (int) $row['product_id'] : 0;
            $qtyBase = isset($row['qty_base']) ? (float) $row['qty_base'] : 0.0;
            if ($productId <= 0 || $qtyBase == 0.0) {
                continue;
            }
            Inventory::adjust($productId, $direction * $qtyBase);
        }
    }
}

