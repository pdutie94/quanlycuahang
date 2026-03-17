<?php

class ProductCategory
{
    public static function countAll()
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query('SELECT COUNT(*) FROM product_categories');
        return (int) $stmt->fetchColumn();
    }

    public static function paginate($limit, $offset)
    {
        $pdo = Database::getInstance();
        $sql = 'SELECT * FROM product_categories ORDER BY name LIMIT :limit OFFSET :offset';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function all()
    {
        $pdo = Database::getInstance();
        $sql = 'SELECT * FROM product_categories ORDER BY name';
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    public static function find($id)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM product_categories WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($data)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('INSERT INTO product_categories (name) VALUES (?)');
        $stmt->execute([
            $data['name'],
        ]);
        return $pdo->lastInsertId();
    }

    public static function update($id, $data)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('UPDATE product_categories SET name = ? WHERE id = ?');
        return $stmt->execute([
            $data['name'],
            $id,
        ]);
    }

    public static function delete($id)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('DELETE FROM product_categories WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
