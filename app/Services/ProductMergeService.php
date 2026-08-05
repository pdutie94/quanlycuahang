<?php

class ProductMergeException extends RuntimeException
{
    public $status;
    public $data;

    public function __construct($message, $status = 422, $data = null)
    {
        parent::__construct($message);
        $this->status = (int) $status;
        $this->data = $data;
    }
}

/**
 * Moves references from a duplicate product to a chosen master product.
 * Transaction values (prices, quantities and amounts) are deliberately not recalculated.
 */
class ProductMergeService
{
    private static $tableCache = [];

    public static function getOptions(array $query = [])
    {
        $pdo = Database::getInstance();
        $keyword = isset($query['q']) ? trim((string) $query['q']) : '';
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = (int) ($query['per_page'] ?? 20);
        if ($perPage < 1) {
            $perPage = 20;
        }
        $perPage = min($perPage, 50);

        $where = 'p.deleted_at IS NULL';
        $params = [];
        if ($keyword !== '') {
            $where .= ' AND (p.name LIKE ? OR p.code LIKE ?)';
            $params[] = '%' . $keyword . '%';
            $params[] = '%' . $keyword . '%';
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM products p WHERE ' . $where);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT p.id, p.name, p.code, p.base_unit_id, bu.name AS base_unit_name,
                    COALESCE(i.qty_base, 0) AS inventory_qty_base,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id) AS order_item_count,
                    (SELECT COUNT(*) FROM purchase_items pi WHERE pi.product_id = p.id) AS purchase_item_count';
        if (self::tableExists('project_issue_items')) {
            $sql .= ', (SELECT COUNT(*) FROM project_issue_items pii WHERE pii.product_id = p.id) AS project_issue_item_count';
        } else {
            $sql .= ', 0 AS project_issue_item_count';
        }
        $sql .= ' FROM products p
                  JOIN units bu ON bu.id = p.base_unit_id
                  LEFT JOIN inventory i ON i.product_id = p.id
                  WHERE ' . $where . ' ORDER BY p.name, p.id LIMIT ? OFFSET ?';
        $stmt = $pdo->prepare($sql);
        $bindIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($bindIndex++, $param, PDO::PARAM_STR);
        }
        $stmt->bindValue($bindIndex++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($bindIndex, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        $ids = [];
        foreach ($items as $item) {
            $ids[] = (int) $item['id'];
        }
        $units = self::loadUnits($pdo, $ids);
        foreach ($items as &$item) {
            $item['id'] = (int) $item['id'];
            $item['base_unit_id'] = (int) $item['base_unit_id'];
            $item['inventory_qty_base'] = (float) $item['inventory_qty_base'];
            $item['order_item_count'] = (int) $item['order_item_count'];
            $item['purchase_item_count'] = (int) $item['purchase_item_count'];
            $item['project_issue_item_count'] = (int) $item['project_issue_item_count'];
            $item['product_units'] = $units[$item['id']] ?? [];
        }
        unset($item);

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_count' => $total,
                'total_pages' => max(1, (int) ceil($total / $perPage)),
            ],
            'filters' => ['q' => $keyword],
        ];
    }

    public static function preview($sourceId, $targetId)
    {
        $sourceId = self::normalizeId($sourceId);
        $targetId = self::normalizeId($targetId);
        if ($sourceId <= 0 || $targetId <= 0 || $sourceId === $targetId) {
            throw new ProductMergeException('Sản phẩm nguồn và đích phải là hai sản phẩm khác nhau.', 422);
        }

        $pdo = Database::getInstance();
        return self::buildPreview($pdo, $sourceId, $targetId);
    }

    public static function merge(array $payload)
    {
        $sourceId = self::normalizeId($payload['source_id'] ?? 0);
        $targetId = self::normalizeId($payload['target_id'] ?? 0);
        if ($sourceId <= 0 || $targetId <= 0 || $sourceId === $targetId) {
            throw new ProductMergeException('Sản phẩm nguồn và đích phải là hai sản phẩm khác nhau.', 422);
        }

        if (!array_key_exists('final_inventory_qty', $payload) || trim((string) $payload['final_inventory_qty']) === '') {
            throw new ProductMergeException('Vui lòng nhập tồn thực tế sau khi gộp.', 422);
        }
        $finalInventory = filter_var($payload['final_inventory_qty'], FILTER_VALIDATE_FLOAT);
        if ($finalInventory === false || !is_finite((float) $finalInventory) || (float) $finalInventory < 0) {
            throw new ProductMergeException('Tồn thực tế phải là số không âm.', 422);
        }
        $deleteSource = filter_var($payload['delete_source'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $pdo = Database::getInstance();
        $pdo->beginTransaction();
        try {
            $source = self::findProduct($pdo, $sourceId, true);
            $target = self::findProduct($pdo, $targetId, true);
            if (!$source || !$target) {
                throw new ProductMergeException('Không tìm thấy sản phẩm nguồn hoặc sản phẩm đích.', 404);
            }

            $context = self::buildMergeContext($pdo, $sourceId, $targetId, true);
            if (!empty($context['conflicts'])) {
                throw new ProductMergeException('Không thể gộp vì đơn vị sản phẩm không tương thích.', 422, [
                    'conflicts' => $context['conflicts'],
                ]);
            }

            $moved = [
                'order_items' => 0,
                'purchase_items' => 0,
                'project_issue_items' => 0,
                'product_logs' => 0,
            ];

            foreach (self::referenceTables() as $table => $definition) {
                if (!self::tableExists($table)) {
                    continue;
                }
                $updateProduct = $pdo->prepare('UPDATE ' . $table . ' SET product_id = ? WHERE product_id = ?');
                $updateProduct->execute([$targetId, $sourceId]);
                $moved[$table] = $updateProduct->rowCount();

                if (!empty($definition['has_unit'])) {
                    foreach ($context['unit_mapping'] as $mapping) {
                        $updateUnit = $pdo->prepare('UPDATE ' . $table . ' SET product_unit_id = ? WHERE product_id = ? AND product_unit_id = ?');
                        $updateUnit->execute([
                            (int) $mapping['target_unit_id'],
                            $targetId,
                            (int) $mapping['source_unit_id'],
                        ]);
                    }
                }
            }

            $inventory = self::lockInventory($pdo, $sourceId, $targetId);
            $targetInventory = $inventory['target'];
            if ($targetInventory) {
                $updateTargetInventory = $pdo->prepare('UPDATE inventory SET qty_base = ?, updated_at = NOW() WHERE id = ?');
                $updateTargetInventory->execute([(float) $finalInventory, (int) $targetInventory['id']]);
            } else {
                $insertTargetInventory = $pdo->prepare('INSERT INTO inventory (product_id, qty_base, updated_at) VALUES (?, ?, NOW())');
                $insertTargetInventory->execute([$targetId, (float) $finalInventory]);
            }
            if ($inventory['source']) {
                $updateSourceInventory = $pdo->prepare('UPDATE inventory SET qty_base = 0, updated_at = NOW() WHERE id = ?');
                $updateSourceInventory->execute([(int) $inventory['source']['id']]);
            }

            if ($deleteSource) {
                $deleteStmt = $pdo->prepare('UPDATE products SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
                $deleteStmt->execute([$sourceId]);
            }

            if (class_exists('ProductSalesSummaryService')) {
                ProductSalesSummaryService::rebuild($sourceId);
                ProductSalesSummaryService::rebuild($targetId);
            }

            if (self::tableExists('product_logs')) {
                $logStmt = $pdo->prepare('INSERT INTO product_logs (product_id, action, detail) VALUES (?, ?, ?)');
                $logStmt->execute([
                    $targetId,
                    'merge_product',
                    'Gộp sản phẩm #' . $sourceId . ' vào #' . $targetId . '; tồn thực tế sau gộp: ' . self::formatQuantity($finalInventory),
                ]);
            }

            $pdo->commit();

            if (class_exists('ReportService') && method_exists('ReportService', 'clearReportCache')) {
                ReportService::clearReportCache();
            }

            $targetProduct = Product::find($targetId);
            return [
                'source_id' => $sourceId,
                'target_id' => $targetId,
                'source_deleted' => $deleteSource,
                'final_inventory_qty' => (float) $finalInventory,
                'moved' => $moved,
                'target_product' => $targetProduct,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function buildPreview(PDO $pdo, $sourceId, $targetId)
    {
        $source = self::findProduct($pdo, $sourceId, false);
        $target = self::findProduct($pdo, $targetId, false);
        if (!$source || !$target) {
            throw new ProductMergeException('Không tìm thấy sản phẩm nguồn hoặc sản phẩm đích.', 404);
        }

        $context = self::buildMergeContext($pdo, $sourceId, $targetId, false);
        $source['inventory_qty_base'] = self::inventoryQty($pdo, $sourceId);
        $target['inventory_qty_base'] = self::inventoryQty($pdo, $targetId);
        $source['product_units'] = $context['source_units'];
        $target['product_units'] = $context['target_units'];

        return [
            'source' => $source,
            'target' => $target,
            'references' => $context['references'],
            'unit_mapping' => $context['unit_mapping'],
            'conflicts' => $context['conflicts'],
            'can_merge' => empty($context['conflicts']),
            'warnings' => [
                'inventory' => 'Tồn kho hai sản phẩm không được cộng tự động. Hãy nhập tồn thực tế sau khi gộp.',
            ],
        ];
    }

    private static function buildMergeContext(PDO $pdo, $sourceId, $targetId, $forUpdate)
    {
        $sourceUnits = self::loadUnits($pdo, [$sourceId], $forUpdate)[$sourceId] ?? [];
        $targetUnits = self::loadUnits($pdo, [$targetId], $forUpdate)[$targetId] ?? [];
        $sourceById = [];
        $targetByUnit = [];
        foreach ($sourceUnits as $unit) {
            $sourceById[(int) $unit['id']] = $unit;
        }
        foreach ($targetUnits as $unit) {
            $unitId = (int) $unit['unit_id'];
            if (!isset($targetByUnit[$unitId])) {
                $targetByUnit[$unitId] = [];
            }
            $targetByUnit[$unitId][] = $unit;
        }

        $usedUnitIds = self::usedUnitIds($pdo, $sourceId, $forUpdate);
        $mapping = [];
        $conflicts = [];
        foreach ($usedUnitIds as $sourceUnitId) {
            if (!isset($sourceById[$sourceUnitId])) {
                $conflicts[] = ['source_unit_id' => $sourceUnitId, 'reason' => 'Không tìm thấy đơn vị nguồn.'];
                continue;
            }
            $sourceUnit = $sourceById[$sourceUnitId];
            $candidates = $targetByUnit[(int) $sourceUnit['unit_id']] ?? [];
            $matches = [];
            foreach ($candidates as $candidate) {
                if (abs((float) $candidate['factor'] - (float) $sourceUnit['factor']) <= 0.000001) {
                    $matches[] = $candidate;
                }
            }
            if (count($matches) !== 1) {
                $conflicts[] = [
                    'source_unit_id' => (int) $sourceUnit['id'],
                    'unit_name' => $sourceUnit['unit_name'],
                    'reason' => empty($matches) ? 'Sản phẩm đích không có đơn vị tương ứng.' : 'Sản phẩm đích có nhiều đơn vị tương ứng.',
                ];
                continue;
            }
            $mapping[] = [
                'source_unit_id' => (int) $sourceUnit['id'],
                'target_unit_id' => (int) $matches[0]['id'],
                'unit_name' => $sourceUnit['unit_name'],
                'factor' => (float) $sourceUnit['factor'],
            ];
        }

        return [
            'source_units' => $sourceUnits,
            'target_units' => $targetUnits,
            'used_unit_ids' => $usedUnitIds,
            'unit_mapping' => $mapping,
            'conflicts' => $conflicts,
            'references' => self::referenceCounts($pdo, $sourceId),
        ];
    }

    private static function referenceTables()
    {
        return [
            'order_items' => ['has_unit' => true],
            'purchase_items' => ['has_unit' => true],
            'project_issue_items' => ['has_unit' => true],
            'product_logs' => ['has_unit' => false],
        ];
    }

    private static function referenceCounts(PDO $pdo, $productId)
    {
        $result = [
            'order_items' => 0,
            'order_count' => 0,
            'purchase_items' => 0,
            'purchase_count' => 0,
            'project_issue_items' => 0,
            'project_issue_count' => 0,
            'product_logs' => 0,
        ];
        if (self::tableExists('order_items')) {
            $stmt = $pdo->prepare('SELECT COUNT(*) AS item_count, COUNT(DISTINCT order_id) AS parent_count FROM order_items WHERE product_id = ?');
            $stmt->execute([$productId]);
            $row = $stmt->fetch() ?: [];
            $result['order_items'] = (int) ($row['item_count'] ?? 0);
            $result['order_count'] = (int) ($row['parent_count'] ?? 0);
        }
        if (self::tableExists('purchase_items')) {
            $stmt = $pdo->prepare('SELECT COUNT(*) AS item_count, COUNT(DISTINCT purchase_id) AS parent_count FROM purchase_items WHERE product_id = ?');
            $stmt->execute([$productId]);
            $row = $stmt->fetch() ?: [];
            $result['purchase_items'] = (int) ($row['item_count'] ?? 0);
            $result['purchase_count'] = (int) ($row['parent_count'] ?? 0);
        }
        if (self::tableExists('project_issue_items')) {
            $stmt = $pdo->prepare('SELECT COUNT(*) AS item_count, COUNT(DISTINCT project_issue_id) AS parent_count FROM project_issue_items WHERE product_id = ?');
            $stmt->execute([$productId]);
            $row = $stmt->fetch() ?: [];
            $result['project_issue_items'] = (int) ($row['item_count'] ?? 0);
            $result['project_issue_count'] = (int) ($row['parent_count'] ?? 0);
        }
        if (self::tableExists('product_logs')) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM product_logs WHERE product_id = ?');
            $stmt->execute([$productId]);
            $result['product_logs'] = (int) $stmt->fetchColumn();
        }
        return $result;
    }

    private static function usedUnitIds(PDO $pdo, $productId, $forUpdate)
    {
        $ids = [];
        foreach (self::referenceTables() as $table => $definition) {
            if (!$definition['has_unit'] || !self::tableExists($table)) {
                continue;
            }
            $sql = 'SELECT DISTINCT product_unit_id FROM ' . $table . ' WHERE product_id = ? AND product_unit_id IS NOT NULL';
            if ($forUpdate) {
                $sql .= ' FOR UPDATE';
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$productId]);
            foreach ($stmt->fetchAll() as $row) {
                $unitId = (int) $row['product_unit_id'];
                if ($unitId > 0) {
                    $ids[$unitId] = $unitId;
                }
            }
        }
        return array_values($ids);
    }

    private static function loadUnits(PDO $pdo, array $productIds, $forUpdate = false)
    {
        $normalized = [];
        foreach ($productIds as $productId) {
            $productId = self::normalizeId($productId);
            if ($productId > 0) {
                $normalized[$productId] = $productId;
            }
        }
        if (empty($normalized)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($normalized), '?'));
        $sql = 'SELECT pu.*, u.name AS unit_name FROM product_units pu JOIN units u ON u.id = pu.unit_id WHERE pu.product_id IN (' . $placeholders . ') ORDER BY pu.product_id, pu.id';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($normalized));
        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $productId = (int) $row['product_id'];
            $row['id'] = (int) $row['id'];
            $row['product_id'] = $productId;
            $row['unit_id'] = (int) $row['unit_id'];
            $row['factor'] = (float) $row['factor'];
            $grouped[$productId][] = $row;
        }
        return $grouped;
    }

    private static function findProduct(PDO $pdo, $productId, $forUpdate = false)
    {
        $sql = 'SELECT p.*, u.name AS base_unit_name FROM products p JOIN units u ON u.id = p.base_unit_id WHERE p.id = ? AND p.deleted_at IS NULL';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([(int) $productId]);
        $row = $stmt->fetch();
        if ($row) {
            $row['id'] = (int) $row['id'];
            $row['base_unit_id'] = (int) $row['base_unit_id'];
        }
        return $row ?: null;
    }

    private static function lockInventory(PDO $pdo, $sourceId, $targetId)
    {
        $stmt = $pdo->prepare('SELECT id, product_id, qty_base FROM inventory WHERE product_id IN (?, ?) ORDER BY product_id FOR UPDATE');
        $stmt->execute([$sourceId, $targetId]);
        $result = ['source' => null, 'target' => null];
        foreach ($stmt->fetchAll() as $row) {
            $key = ((int) $row['product_id'] === (int) $sourceId) ? 'source' : 'target';
            $row['id'] = (int) $row['id'];
            $row['qty_base'] = (float) $row['qty_base'];
            $result[$key] = $row;
        }
        return $result;
    }

    private static function inventoryQty(PDO $pdo, $productId)
    {
        $stmt = $pdo->prepare('SELECT COALESCE(qty_base, 0) FROM inventory WHERE product_id = ? LIMIT 1');
        $stmt->execute([(int) $productId]);
        return (float) ($stmt->fetchColumn() ?: 0);
    }

    private static function tableExists($table)
    {
        if (isset(self::$tableCache[$table])) {
            return self::$tableCache[$table];
        }
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute([$table]);
        self::$tableCache[$table] = ((int) $stmt->fetchColumn() > 0);
        return self::$tableCache[$table];
    }

    private static function normalizeId($value)
    {
        $id = (int) $value;
        return $id > 0 ? $id : 0;
    }

    private static function formatQuantity($value)
    {
        $text = number_format((float) $value, 4, '.', '');
        return rtrim(rtrim($text, '0'), '.');
    }
}
