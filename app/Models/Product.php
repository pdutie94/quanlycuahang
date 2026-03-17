<?php

class Product
{
    protected static function toAscii($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $map = [
            'a' => 'a',
            'A' => 'A',
            'd' => 'd',
            'D' => 'D',
            'e' => 'e',
            'E' => 'E',
            'i' => 'i',
            'I' => 'I',
            'o' => 'o',
            'O' => 'O',
            'u' => 'u',
            'U' => 'U',
            'y' => 'y',
            'Y' => 'Y',
        ];

        $value = strtr($value, [
            'à' => $map['a'], 'á' => $map['a'], 'ạ' => $map['a'], 'ả' => $map['a'], 'ã' => $map['a'],
            'â' => $map['a'], 'ầ' => $map['a'], 'ấ' => $map['a'], 'ậ' => $map['a'], 'ẩ' => $map['a'], 'ẫ' => $map['a'],
            'ă' => $map['a'], 'ằ' => $map['a'], 'ắ' => $map['a'], 'ặ' => $map['a'], 'ẳ' => $map['a'], 'ẵ' => $map['a'],
            'À' => $map['A'], 'Á' => $map['A'], 'Ạ' => $map['A'], 'Ả' => $map['A'], 'Ã' => $map['A'],
            'Â' => $map['A'], 'Ầ' => $map['A'], 'Ấ' => $map['A'], 'Ậ' => $map['A'], 'Ẩ' => $map['A'], 'Ẫ' => $map['A'],
            'Ă' => $map['A'], 'Ằ' => $map['A'], 'Ắ' => $map['A'], 'Ặ' => $map['A'], 'Ẳ' => $map['A'], 'Ẵ' => $map['A'],
            'đ' => $map['d'], 'Đ' => $map['D'],
            'è' => $map['e'], 'é' => $map['e'], 'ẹ' => $map['e'], 'ẻ' => $map['e'], 'ẽ' => $map['e'],
            'ê' => $map['e'], 'ề' => $map['e'], 'ế' => $map['e'], 'ệ' => $map['e'], 'ể' => $map['e'], 'ễ' => $map['e'],
            'È' => $map['E'], 'É' => $map['E'], 'Ẹ' => $map['E'], 'Ẻ' => $map['E'], 'Ẽ' => $map['E'],
            'Ê' => $map['E'], 'Ề' => $map['E'], 'Ế' => $map['E'], 'Ệ' => $map['E'], 'Ể' => $map['E'], 'Ễ' => $map['E'],
            'ì' => $map['i'], 'í' => $map['i'], 'ị' => $map['i'], 'ỉ' => $map['i'], 'ĩ' => $map['i'],
            'Ì' => $map['I'], 'Í' => $map['I'], 'Ị' => $map['I'], 'Ỉ' => $map['I'], 'Ĩ' => $map['I'],
            'ò' => $map['o'], 'ó' => $map['o'], 'ọ' => $map['o'], 'ỏ' => $map['o'], 'õ' => $map['o'],
            'ô' => $map['o'], 'ồ' => $map['o'], 'ố' => $map['o'], 'ộ' => $map['o'], 'ổ' => $map['o'], 'ỗ' => $map['o'],
            'ơ' => $map['o'], 'ờ' => $map['o'], 'ớ' => $map['o'], 'ợ' => $map['o'], 'ở' => $map['o'], 'ỡ' => $map['o'],
            'Ò' => $map['O'], 'Ó' => $map['O'], 'Ọ' => $map['O'], 'Ỏ' => $map['O'], 'Õ' => $map['O'],
            'Ô' => $map['O'], 'Ồ' => $map['O'], 'Ố' => $map['O'], 'Ộ' => $map['O'], 'Ổ' => $map['O'], 'Ỗ' => $map['O'],
            'Ơ' => $map['O'], 'Ờ' => $map['O'], 'Ớ' => $map['O'], 'Ợ' => $map['O'], 'Ở' => $map['O'], 'Ỡ' => $map['O'],
            'ù' => $map['u'], 'ú' => $map['u'], 'ụ' => $map['u'], 'ủ' => $map['u'], 'ũ' => $map['u'],
            'ư' => $map['u'], 'ừ' => $map['u'], 'ứ' => $map['u'], 'ự' => $map['u'], 'ử' => $map['u'], 'ữ' => $map['u'],
            'Ù' => $map['U'], 'Ú' => $map['U'], 'Ụ' => $map['U'], 'Ủ' => $map['U'], 'Ũ' => $map['U'],
            'Ư' => $map['U'], 'Ừ' => $map['U'], 'Ứ' => $map['U'], 'Ự' => $map['U'], 'Ử' => $map['U'], 'Ữ' => $map['U'],
            'ỳ' => $map['y'], 'ý' => $map['y'], 'ỵ' => $map['y'], 'ỷ' => $map['y'], 'ỹ' => $map['y'],
            'Ỳ' => $map['Y'], 'Ý' => $map['Y'], 'Ỵ' => $map['Y'], 'Ỷ' => $map['Y'], 'Ỹ' => $map['Y'],
        ]);

        return $value;
    }

    protected static function buildCodeBaseFromName($name)
    {
        $asciiName = self::toAscii($name);
        $parts = preg_split('/[^A-Za-z0-9]+/', $asciiName, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts)) {
            return 'sp';
        }

        $base = '';
        foreach ($parts as $part) {
            if (ctype_digit($part)) {
                $base .= $part;
                continue;
            }

            $base .= strtolower(substr($part, 0, 1));
        }

        if ($base === '') {
            $base = 'sp';
        }

        return $base;
    }

    protected static function generateUniqueCode($name, $excludeId = null)
    {
        $pdo = Database::getInstance();
        $baseCode = self::buildCodeBaseFromName($name);
        $candidate = $baseCode;
        $suffix = 2;

        while (true) {
            if ($excludeId !== null) {
                $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE code = ? AND id <> ?');
                $checkStmt->execute([
                    $candidate,
                    (int) $excludeId,
                ]);
            } else {
                $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE code = ?');
                $checkStmt->execute([$candidate]);
            }

            $exists = (int) $checkStmt->fetchColumn() > 0;
            if (!$exists) {
                return $candidate;
            }

            $candidate = $baseCode . $suffix;
            $suffix++;
        }
    }

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
            $code = self::generateUniqueCode(isset($data['name']) ? $data['name'] : '');
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
            $code = self::generateUniqueCode(isset($data['name']) ? $data['name'] : '', $id);
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
