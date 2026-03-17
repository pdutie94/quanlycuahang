<?php

class OrderLog
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

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('INSERT INTO order_logs (order_id, action, detail) VALUES (?, ?, ?)');
        $stmt->execute([
            $data['order_id'],
            $data['action'],
			$detail,
        ]);
        return $pdo->lastInsertId();
    }

    public static function findByOrder($orderId)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM order_logs WHERE order_id = ? ORDER BY created_at DESC, id DESC');
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
}
