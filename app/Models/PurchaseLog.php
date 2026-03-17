<?php

class PurchaseLog
{
    public static function create($data)
    {
        $detail = '';
        if (array_key_exists('detail', $data)) {
            if (is_array($data['detail'])) {
                $detail = json_encode($data['detail'], JSON_UNESCAPED_UNICODE);
            } else {
                $detail = (string) $data['detail'];
            }
        }
        if (strlen($detail) > 250) {
            $detail = substr($detail, 0, 250);
        }

        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare('INSERT INTO purchase_logs (purchase_id, action, detail) VALUES (?, ?, ?)');
            $stmt->execute([
                $data['purchase_id'],
                $data['action'],
                $detail,
            ]);
            return $pdo->lastInsertId();
        } catch (Exception $e) {
            return null;
        }
    }

    public static function findByPurchase($purchaseId)
    {
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare('SELECT * FROM purchase_logs WHERE purchase_id = ? ORDER BY created_at DESC, id DESC');
            $stmt->execute([$purchaseId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}
