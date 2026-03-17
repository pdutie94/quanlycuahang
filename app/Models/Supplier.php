<?php

class Supplier
{
    public static function countByKeyword($keyword)
    {
        $pdo = Database::getInstance();
        $sql = 'SELECT COUNT(*) FROM suppliers WHERE deleted_at IS NULL AND (name LIKE :kw OR phone LIKE :kw OR address LIKE :kw)';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':kw', '%' . $keyword . '%', PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public static function countAll()
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query('SELECT COUNT(*) FROM suppliers WHERE deleted_at IS NULL');
        return (int) $stmt->fetchColumn();
    }

    public static function paginate($limit, $offset)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE deleted_at IS NULL ORDER BY name LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function searchPaginate($keyword, $limit, $offset)
    {
        $pdo = Database::getInstance();
        $sql = 'SELECT * FROM suppliers WHERE deleted_at IS NULL AND (name LIKE :kw OR phone LIKE :kw OR address LIKE :kw) ORDER BY name LIMIT :limit OFFSET :offset';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':kw', '%' . $keyword . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function all()
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query('SELECT * FROM suppliers WHERE deleted_at IS NULL ORDER BY name');
        return $stmt->fetchAll();
    }

    public static function find($id)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($data)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('INSERT INTO suppliers (name, phone, address, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([
            $data['name'],
            $data['phone'],
            $data['address'],
        ]);
        return $pdo->lastInsertId();
    }

    public static function update($id, $data)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('UPDATE suppliers SET name = ?, phone = ?, address = ? WHERE id = ?');
        return $stmt->execute([
            $data['name'],
            $data['phone'],
            $data['address'],
            $id,
        ]);
    }

    public static function delete($id)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('UPDATE suppliers SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
        return $stmt->execute([$id]);
    }
}
