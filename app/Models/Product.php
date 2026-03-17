<?php

class Product
{
protected static function useProductSalesSummary(): bool
	{
		$pdo = Database::getInstance();
		$stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
		$stmt->execute(['product_sales_summary']);
		return (int) $stmt->fetchColumn() > 0;
	}

	protected static function buildBaseProductQuery($stockFilter = 'all', $categoryId = null, $keyword = null)
	{
	    $sql = "SELECT p.*, u.name AS base_unit_name, c.name AS category_name, COALESCE(i.qty_base, 0) AS inventory_qty_base, COALESCE(s.sold_qty, 0) AS sold_qty\n"
	        . "FROM products p\n"
	        . "JOIN units u ON p.base_unit_id = u.id\n"
	        . "LEFT JOIN product_categories c ON p.category_id = c.id\n"
	        . "LEFT JOIN inventory i ON i.product_id = p.id\n";

	    if (self::useProductSalesSummary()) {
	        $sql .= "LEFT JOIN product_sales_summary s ON s.product_id = p.id\n";
	    } else {
	        $sql .= "LEFT JOIN (\n"
	            . "    SELECT oi.product_id, SUM(oi.qty_base) AS sold_qty\n"
	            . "    FROM order_items oi\n"
	            . "    JOIN orders o ON oi.order_id = o.id\n"
	            . "    WHERE o.deleted_at IS NULL\n"
	            . "      AND (o.order_status IS NULL OR o.order_status <> 'cancelled')\n"
	            . "    GROUP BY oi.product_id\n"
	            . ") s ON s.product_id = p.id\n";
	    }

	    $sql .= "WHERE p.deleted_at IS NULL";

	    if ($keyword !== null && $keyword !== '') {
	        $sql .= ' AND (p.name LIKE :kw OR p.code LIKE :kw)';
	    }

	    self::applyStockFilterSql($sql, $stockFilter, $categoryId);
	    return $sql;
    }

	public static function countByKeyword($keyword, $stockFilter = 'all', $categoryId = null)
	{
		$pdo = Database::getInstance();
		$sql = 'SELECT COUNT(*) FROM products p LEFT JOIN inventory i ON i.product_id = p.id WHERE p.deleted_at IS NULL AND (p.name LIKE :kw OR p.code LIKE :kw)';
        self::applyStockFilterSql($sql, $stockFilter, $categoryId);
		$stmt = $pdo->prepare($sql);
		$stmt->bindValue(':kw', '%' . $keyword . '%', PDO::PARAM_STR);
		if ($categoryId !== null && (int) $categoryId > 0) {
			$stmt->bindValue(':category_id', (int) $categoryId, PDO::PARAM_INT);
		}
		$stmt->execute();
		return (int) $stmt->fetchColumn();
	}

	public static function searchPaginate($keyword, $limit, $offset, $stockFilter = 'all', $categoryId = null)
	{
		$pdo = Database::getInstance();
		$sql = self::buildBaseProductQuery($stockFilter, $categoryId, $keyword);
		$sql .= ' ORDER BY p.name LIMIT :limit OFFSET :offset';
		$stmt = $pdo->prepare($sql);
		$stmt->bindValue(':kw', '%' . $keyword . '%', PDO::PARAM_STR);
		$stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
		if ($categoryId !== null && (int) $categoryId > 0) {
			$stmt->bindValue(':category_id', (int) $categoryId, PDO::PARAM_INT);
		}
		$stmt->execute();
		return $stmt->fetchAll();
	}

	public static function countAll($stockFilter = 'all', $categoryId = null)
	{
		$pdo = Database::getInstance();
		$sql = 'SELECT COUNT(*) FROM products p LEFT JOIN inventory i ON i.product_id = p.id WHERE p.deleted_at IS NULL';
        self::applyStockFilterSql($sql, $stockFilter, $categoryId);
		if ($categoryId !== null && (int) $categoryId > 0) {
			$stmt = $pdo->prepare($sql);
			$stmt->bindValue(':category_id', (int) $categoryId, PDO::PARAM_INT);
			$stmt->execute();
		} else {
			$stmt = $pdo->query($sql);
		}
		return (int) $stmt->fetchColumn();
	}

	public static function paginate($limit, $offset, $stockFilter = 'all', $categoryId = null)
	{
		$pdo = Database::getInstance();
		$sql = self::buildBaseProductQuery($stockFilter, $categoryId);
		$sql .= ' ORDER BY p.name LIMIT :limit OFFSET :offset';
		$stmt = $pdo->prepare($sql);
		if ($categoryId !== null && (int) $categoryId > 0) {
			$stmt->bindValue(':category_id', (int) $categoryId, PDO::PARAM_INT);
		}
		$stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll();
	}

    public static function all()
    {
        $pdo = Database::getInstance();
        $sql = 'SELECT p.*, u.name AS base_unit_name, c.name AS category_name FROM products p JOIN units u ON p.base_unit_id = u.id LEFT JOIN product_categories c ON p.category_id = c.id WHERE p.deleted_at IS NULL ORDER BY p.name';
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    public static function find($id)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT p.*, u.name AS base_unit_name, c.name AS category_name FROM products p JOIN units u ON p.base_unit_id = u.id LEFT JOIN product_categories c ON p.category_id = c.id WHERE p.id = ? AND p.deleted_at IS NULL');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create($data)
    {
        $pdo = Database::getInstance();
        $code = isset($data['code']) ? trim($data['code']) : '';

        if ($code === '') {
            do {
                $code = 'P' . substr(uniqid('', true), -8);
                $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE code = ?');
                $checkStmt->execute([$code]);
                $exists = (int) $checkStmt->fetchColumn() > 0;
            } while ($exists);
        }

        $stmt = $pdo->prepare('INSERT INTO products (name, code, category_id, base_unit_id, min_stock_qty) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['name'],
            $code,
            $data['category_id'],
            $data['base_unit_id'],
            isset($data['min_stock_qty']) ? $data['min_stock_qty'] : null,
        ]);
        return $pdo->lastInsertId();
    }

    public static function update($id, $data)
    {
        $pdo = Database::getInstance();
        $code = isset($data['code']) ? trim($data['code']) : '';

        if ($code === '') {
            $stmtCurrent = $pdo->prepare('SELECT code FROM products WHERE id = ?');
            $stmtCurrent->execute([$id]);
            $row = $stmtCurrent->fetch();
            if ($row && isset($row['code']) && $row['code'] !== '') {
                $code = $row['code'];
            } else {
                do {
                    $code = 'P' . substr(uniqid('', true), -8);
                    $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE code = ? AND id <> ?');
                    $checkStmt->execute([
                        $code,
                        $id,
                    ]);
                    $exists = (int) $checkStmt->fetchColumn() > 0;
                } while ($exists);
            }
        }

        $stmt = $pdo->prepare('UPDATE products SET name = ?, code = ?, category_id = ?, base_unit_id = ?, min_stock_qty = ? WHERE id = ?');
        return $stmt->execute([
            $data['name'],
            $code,
            $data['category_id'],
            $data['base_unit_id'],
            isset($data['min_stock_qty']) ? $data['min_stock_qty'] : null,
            $id,
        ]);
    }

    public static function updateImagePath($id, $imagePath)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('UPDATE products SET image_path = ? WHERE id = ?');
        return $stmt->execute([
            $imagePath,
            $id,
        ]);
    }

    public static function delete($id)
    {
        $pdo = Database::getInstance();
		$checkStmt = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
		$checkStmt->execute([$id]);
		$usageCount = (int) $checkStmt->fetchColumn();
		if ($usageCount > 0) {
			return false;
		}

		$stmt = $pdo->prepare('UPDATE products SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
		return $stmt->execute([$id]);
    }

    public static function findLowStock($limit = 10)
    {
        $pdo = Database::getInstance();
        $limit = (int) $limit;
        if ($limit < 1) {
            $limit = 10;
        }
        $sql = 'SELECT p.id, p.code, p.name, c.name AS category_name, u.name AS base_unit_name,
            COALESCE(i.qty_base, 0) AS qty_base, p.min_stock_qty
            FROM products p
            JOIN units u ON p.base_unit_id = u.id
            LEFT JOIN product_categories c ON p.category_id = c.id
            LEFT JOIN inventory i ON i.product_id = p.id
            WHERE p.deleted_at IS NULL
              AND p.min_stock_qty IS NOT NULL
              AND p.min_stock_qty > 0
              AND COALESCE(i.qty_base, 0) > 0
              AND COALESCE(i.qty_base, 0) <= p.min_stock_qty
            ORDER BY COALESCE(i.qty_base, 0) / p.min_stock_qty ASC, p.name
            LIMIT ' . $limit;
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    protected static function applyStockFilterSql(&$sql, $stockFilter, $categoryId)
    {
        if ($stockFilter === 'in_stock') {
            $sql .= ' AND COALESCE(i.qty_base, 0) > 0';
        } elseif ($stockFilter === 'out_of_stock') {
            $sql .= ' AND COALESCE(i.qty_base, 0) <= 0';
        } elseif ($stockFilter === 'low_stock') {
            $sql .= ' AND COALESCE(i.qty_base, 0) > 0 AND p.min_stock_qty IS NOT NULL AND p.min_stock_qty > 0 AND COALESCE(i.qty_base, 0) <= p.min_stock_qty';
        }
        if ($categoryId !== null && (int) $categoryId > 0) {
            $sql .= ' AND p.category_id = :category_id';
        }
    }
}
