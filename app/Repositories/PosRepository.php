<?php

class PosRepository extends BaseRepository
{
    public static function findProductsForPos(): array
    {
        $stmt = self::db()->query('SELECT p.*, u.name AS base_unit_name, c.name AS category_name
            FROM products p
            JOIN units u ON p.base_unit_id = u.id
            LEFT JOIN product_categories c ON p.category_id = c.id
            WHERE p.deleted_at IS NULL
            ORDER BY p.name');

        return $stmt->fetchAll();
    }

    public static function findProductUnitsForPos(): array
    {
        $stmt = self::db()->query('SELECT pu.*, u.name AS unit_name
            FROM product_units pu
            JOIN units u ON pu.unit_id = u.id
            ORDER BY pu.product_id, u.name');

        return $stmt->fetchAll();
    }

    public static function findCustomersForPos(): array
    {
        $stmt = self::db()->query('SELECT id, name, phone, address FROM customers ORDER BY name, id');

        return $stmt->fetchAll();
    }
}