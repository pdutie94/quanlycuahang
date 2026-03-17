<?php

class Customer
{
    public static function all()
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query('SELECT * FROM customers WHERE deleted_at IS NULL ORDER BY name');
        return $stmt->fetchAll();
    }

    public static function find($id)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($data)
    {
        $pdo = Database::getInstance();
        $name = isset($data['name']) ? trim($data['name']) : '';
        $phone = isset($data['phone']) ? trim($data['phone']) : '';
        $address = isset($data['address']) ? trim($data['address']) : '';
        if ($phone === '') {
            $phone = null;
        }
        $stmt = $pdo->prepare('INSERT INTO customers (name, phone, address, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([
            $name,
            $phone,
            $address,
        ]);
        return $pdo->lastInsertId();
    }

    public static function delete($id)
    {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE customers SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
            $stmt->execute([$id]);

            $orderStmt = $pdo->prepare('UPDATE orders SET customer_id = NULL WHERE customer_id = ?');
            $orderStmt->execute([$id]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }
}
