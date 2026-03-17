<?php

class Unit
{
    public static function all()
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query('SELECT * FROM units ORDER BY name');
        return $stmt->fetchAll();
    }

    public static function create($data)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('INSERT INTO units (name) VALUES (?)');
        $stmt->execute([
            $data['name'],
        ]);
        return $pdo->lastInsertId();
    }

    public static function update($id, $data)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('UPDATE units SET name = ? WHERE id = ?');
        return $stmt->execute([
            $data['name'],
            $id,
        ]);
    }

    public static function delete($id)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('DELETE FROM units WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
