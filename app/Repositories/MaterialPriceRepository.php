<?php

class MaterialPriceRepository extends BaseRepository 
{
    public static function getAll(): array
    {
        $sql = "SELECT * FROM material_prices ORDER BY material_type ASC";
        $stmt = self::db()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?MaterialPrice
    {
        $sql = "SELECT * FROM material_prices WHERE id = :id";
        $stmt = self::db()->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        
        $materialPrice = new MaterialPrice();
        $materialPrice->setId($row['id']);
        $materialPrice->setMaterialType($row['material_type']);
        $materialPrice->setPricePerKg($row['price_per_kg']);
        $materialPrice->setCreatedAt($row['created_at']);
        $materialPrice->setUpdatedAt($row['updated_at']);
        
        return $materialPrice;
    }

    public static function findByMaterialType(string $materialType, ?int $excludeId = null): ?MaterialPrice
    {
        $sql = "SELECT * FROM material_prices WHERE material_type = :material_type";
        $params = [':material_type' => $materialType];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $stmt = self::db()->prepare($sql);
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $paramType);
        }
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        
        $materialPrice = new MaterialPrice();
        $materialPrice->setId($row['id']);
        $materialPrice->setMaterialType($row['material_type']);
        $materialPrice->setPricePerKg($row['price_per_kg']);
        $materialPrice->setCreatedAt($row['created_at']);
        $materialPrice->setUpdatedAt($row['updated_at']);
        
        return $materialPrice;
    }

    public static function create(array $data): MaterialPrice
    {
        $sql = "INSERT INTO material_prices (material_type, price_per_kg, created_at, updated_at) 
                VALUES (:material_type, :price_per_kg, NOW(), NOW())";
        
        $stmt = self::db()->prepare($sql);
        $stmt->bindParam(':material_type', $data['material_type'], PDO::PARAM_STR);
        $stmt->bindParam(':price_per_kg', $data['price_per_kg'], PDO::PARAM_STR);
        $stmt->execute();
        
        $id = self::db()->lastInsertId();
        return self::findById($id);
    }

    public static function update(int $id, array $data): MaterialPrice
    {
        $sql = "UPDATE material_prices SET 
                material_type = :material_type, 
                price_per_kg = :price_per_kg, 
                updated_at = NOW()
                WHERE id = :id";
        
        $stmt = self::db()->prepare($sql);
        $stmt->bindParam(':material_type', $data['material_type'], PDO::PARAM_STR);
        $stmt->bindParam(':price_per_kg', $data['price_per_kg'], PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        $sql = "DELETE FROM material_prices WHERE id = :id";
        $stmt = self::db()->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public static function getPaginated(int $page = 1, int $perPage = 30): array
    {
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM material_prices";
        $countStmt = self::db()->prepare($countSql);
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated data
        $sql = "SELECT * FROM material_prices ORDER BY material_type ASC LIMIT :limit OFFSET :offset";
        $stmt = self::db()->prepare($sql);
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $items = $stmt->fetchAll();
        $totalPages = ceil($total / $perPage);
        
        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
                'total' => $total
            ]
        ];
    }
}
