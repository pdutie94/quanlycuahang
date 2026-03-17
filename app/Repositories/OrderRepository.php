<?php

class OrderRepository
{
    public static function findWithCustomer($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT o.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function findForEdit($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function findForPayment($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}

