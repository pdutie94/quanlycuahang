<?php

class Purchase
{
    public static function create($data)
    {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare('INSERT INTO purchases (purchase_code, supplier_id, purchase_date, total_amount, paid_amount, status, note) VALUES ("", ?, NOW(), ?, ?, ?, ?)');
        $stmt->execute([
            $data['supplier_id'],
            $data['total_amount'],
            $data['paid_amount'],
            $data['status'],
            $data['note'],
        ]);

        $id = (int) $pdo->lastInsertId();

        $code = isset($data['purchase_code']) && $data['purchase_code'] !== '' ? $data['purchase_code'] : self::generateCode($id);
        $updateStmt = $pdo->prepare('UPDATE purchases SET purchase_code = ? WHERE id = ?');
        $updateStmt->execute([
            $code,
            $id,
        ]);

        return $id;
    }

    protected static function generateCode($id)
    {
        $num = (int) $id;
        if ($num < 1) {
            $num = 1;
        }
        return 'PN-' . (string) $num;
    }
}
