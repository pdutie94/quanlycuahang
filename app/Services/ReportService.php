<?php

class ReportService
{
    protected static function getCache($key)
    {
        if (function_exists('apcu_fetch') && ini_get('apc.enabled')) {
            $success = false;
            $value = apcu_fetch($key, $success);
            return $success ? $value : null;
        }

        if (extension_loaded('redis')) {
            try {
                $redis = new Redis();
                $redis->connect('127.0.0.1', 6379);
                return $redis->get($key) !== false ? unserialize($redis->get($key)) : null;
            } catch (Exception $e) {
                // fallback
            }
        }

        $cacheFile = sys_get_temp_dir() . '/report_cache_' . md5($key) . '.cache';
        if (!is_file($cacheFile)) {
            return null;
        }

        $data = @file_get_contents($cacheFile);
        if ($data === false) {
            return null;
        }

        $decoded = @unserialize($data);
        if (!is_array($decoded) || !isset($decoded['expires']) || !isset($decoded['value'])) {
            return null;
        }

        if (time() > $decoded['expires']) {
            @unlink($cacheFile);
            return null;
        }

        return $decoded['value'];
    }

    public static function updateProductCost(int $productId, $priceCost): array
    {
        $productId = (int)$productId;
        $priceCost = (float)str_replace([',', ' '], ['', ''], (string)$priceCost);
        if ($productId <= 0) {
            return [
                'success' => false,
                'message' => 'Sản phẩm không hợp lệ.'
            ];
        }
        $pdo = Database::getInstance();

        // Get base_unit_id for this product
        $baseUnitStmt = $pdo->prepare('SELECT base_unit_id FROM products WHERE id = ?');
        $baseUnitStmt->execute([$productId]);
        $baseUnitRow = $baseUnitStmt->fetch();
        if (!$baseUnitRow || !isset($baseUnitRow['base_unit_id'])) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy đơn vị cơ bản của sản phẩm.'
            ];
        }
        $baseUnitId = (int)$baseUnitRow['base_unit_id'];

        // Cập nhật giá vốn cho đơn vị cơ bản
        $stmt = $pdo->prepare('UPDATE product_units SET price_cost = ? WHERE product_id = ? AND unit_id = ?');
        $ok = $stmt->execute([$priceCost, $productId, $baseUnitId]);
        if (!$ok) {
            return [
                'success' => false,
                'message' => 'Không thể cập nhật giá vốn.'
            ];
        }

        // Check if auto_price_enabled and recalculate price_sell
        $productStmt = $pdo->prepare('SELECT auto_price_enabled, auto_price_value FROM products WHERE id = ?');
        $productStmt->execute([$productId]);
        $productRow = $productStmt->fetch();
        if ($productRow && (int)$productRow['auto_price_enabled'] === 1) {
            $autoPriceValue = isset($productRow['auto_price_value']) ? (float)$productRow['auto_price_value'] : 0;
            if ($autoPriceValue > 0) {
                // Calculate new price_sell: round to thousands
                $newPriceSell = round($priceCost + $autoPriceValue);
                // Update price_sell for base unit
                $updateSellStmt = $pdo->prepare('UPDATE product_units SET price_sell = ? WHERE product_id = ? AND unit_id = ?');
                $updateSellStmt->execute([$newPriceSell, $productId, $baseUnitId]);
            }
        }

        return [
            'success' => true,
            'message' => 'Đã cập nhật giá vốn.'
        ];
    }
    public static function getCostUpdateData(): array
    {
        $pdo = Database::getInstance();

        $sql = 'SELECT p.id, p.code, p.name, c.name AS category_name, u.name AS base_unit_name,
            COALESCE(i.qty_base, 0) AS qty_base, i.updated_at,
            pu.price_cost AS price_cost, pu.min_step AS min_step
            FROM products p
            JOIN units u ON p.base_unit_id = u.id
            LEFT JOIN product_categories c ON p.category_id = c.id
            LEFT JOIN inventory i ON i.product_id = p.id
            LEFT JOIN product_units pu ON pu.product_id = p.id AND pu.unit_id = p.base_unit_id
            WHERE p.deleted_at IS NULL
            ORDER BY p.name';

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    protected static function setCache($key, $value, $ttl = 600)
    {
        if (function_exists('apcu_store') && ini_get('apc.enabled')) {
            apcu_store($key, $value, $ttl);
            return;
        }

        if (extension_loaded('redis')) {
            try {
                $redis = new Redis();
                $redis->connect('127.0.0.1', 6379);
                $redis->set($key, serialize($value), $ttl);
                return;
            } catch (Exception $e) {
                // fallback
            }
        }

        $cacheFile = sys_get_temp_dir() . '/report_cache_' . md5($key) . '.cache';
        $payload = ['expires' => time() + $ttl, 'value' => $value];
        @file_put_contents($cacheFile, serialize($payload));
    }

    public static function sumOrdersByDateRange(PDO $pdo, $start, $end)
    {
        $cacheKey = 'sumOrdersByDateRange:' . $start . ':' . $end;
        $cached = self::getCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $sqlOrders = 'SELECT SUM(total_amount) AS total_amount, SUM(total_cost) AS total_cost, SUM(paid_amount) AS paid_amount
            FROM orders
            WHERE order_date BETWEEN ? AND ?
              AND deleted_at IS NULL
              AND (order_status IS NULL OR order_status <> \'cancelled\')';
        $stmt = $pdo->prepare($sqlOrders);
        $stmt->execute([$start, $end]);
        $rowOrders = $stmt->fetch();

        $total = isset($rowOrders['total_amount']) ? (float) $rowOrders['total_amount'] : 0.0;
        $cost = isset($rowOrders['total_cost']) ? (float) $rowOrders['total_cost'] : 0.0;
        $paid = isset($rowOrders['paid_amount']) ? (float) $rowOrders['paid_amount'] : 0.0;
        $debt = $total - $paid;

        $result = [
            'total_amount' => $total,
            'total_cost' => $cost,
            'profit' => $total - $cost,
            'paid_amount' => $paid,
            'debt_amount' => $debt,
        ];

        self::setCache($cacheKey, $result, 600);
        return $result;
    }

    public static function sumCustomerDebt(PDO $pdo)
    {
        $cacheKey = 'sumCustomerDebt';
        $cached = self::getCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $sql = 'SELECT SUM(total_amount - paid_amount) AS debt
            FROM orders
            WHERE deleted_at IS NULL
              AND (order_status IS NULL OR order_status <> \'cancelled\')
              AND total_amount > paid_amount';
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        $debt = isset($row['debt']) ? (float) $row['debt'] : 0.0;

        self::setCache($cacheKey, $debt, 600);
        return $debt;
    }

    public static function sumPurchasesByDateRange(PDO $pdo, $start, $end)
    {
        $cacheKey = 'sumPurchasesByDateRange:' . $start . ':' . $end;
        $cached = self::getCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $sql = 'SELECT SUM(total_amount) AS total_amount, SUM(paid_amount) AS paid_amount FROM purchases WHERE purchase_date BETWEEN ? AND ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$start, $end]);
        $row = $stmt->fetch();

        $total = isset($row['total_amount']) ? (float) $row['total_amount'] : 0.0;
        $paid = isset($row['paid_amount']) ? (float) $row['paid_amount'] : 0.0;
        $debt = $total - $paid;

        $result = [
            'total_amount' => $total,
            'paid_amount' => $paid,
            'debt_amount' => $debt,
        ];

        self::setCache($cacheKey, $result, 600);
        return $result;
    }

    public static function sumSupplierDebt(PDO $pdo)
    {
        $cacheKey = 'sumSupplierDebt';
        $cached = self::getCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $sql = 'SELECT SUM(total_amount - paid_amount) AS debt FROM purchases WHERE total_amount > paid_amount';
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        $debt = isset($row['debt']) ? (float) $row['debt'] : 0.0;

        self::setCache($cacheKey, $debt, 600);
        return $debt;
    }

    public static function getOverviewData(): array
    {
        $pdo = Database::getInstance();

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        $previousMonthStart = date('Y-m-01', strtotime('first day of last month'));
        $previousMonthEnd = date('Y-m-t', strtotime('last day of last month'));

        $ordersToday = self::sumOrdersByDateRange($pdo, $today . ' 00:00:00', $today . ' 23:59:59');
        $ordersYesterday = self::sumOrdersByDateRange($pdo, $yesterday . ' 00:00:00', $yesterday . ' 23:59:59');
        $ordersMonth = self::sumOrdersByDateRange($pdo, $monthStart . ' 00:00:00', $monthEnd . ' 23:59:59');
        $ordersPreviousMonth = self::sumOrdersByDateRange($pdo, $previousMonthStart . ' 00:00:00', $previousMonthEnd . ' 23:59:59');

        $purchasesMonth = self::sumPurchasesByDateRange($pdo, $monthStart . ' 00:00:00', $monthEnd . ' 23:59:59');
        $purchasesPreviousMonth = self::sumPurchasesByDateRange($pdo, $previousMonthStart . ' 00:00:00', $previousMonthEnd . ' 23:59:59');

        $customerDebt = self::sumCustomerDebt($pdo);
        $supplierDebt = self::sumSupplierDebt($pdo);

        $openingCustomerDebtStmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount - paid_amount), 0) AS debt
            FROM orders
            WHERE deleted_at IS NULL
              AND (order_status IS NULL OR order_status <> \'cancelled\')
              AND total_amount > paid_amount
              AND order_date < ?');
        $openingCustomerDebtStmt->execute([$monthStart . ' 00:00:00']);
        $openingCustomerDebt = (float) $openingCustomerDebtStmt->fetchColumn();

        $openingSupplierDebtStmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount - paid_amount), 0) AS debt
            FROM purchases
            WHERE total_amount > paid_amount
              AND purchase_date < ?');
        $openingSupplierDebtStmt->execute([$monthStart . ' 00:00:00']);
        $openingSupplierDebt = (float) $openingSupplierDebtStmt->fetchColumn();

        return [
            'ordersToday' => $ordersToday,
            'ordersMonth' => $ordersMonth,
            'purchasesMonth' => $purchasesMonth,
            'customerDebt' => $customerDebt,
            'supplierDebt' => $supplierDebt,
            'delta' => [
                'orders_month_total' => self::buildDelta($ordersMonth['total_amount'], $ordersPreviousMonth['total_amount']),
                'orders_month_profit' => self::buildDelta($ordersMonth['profit'], $ordersPreviousMonth['profit']),
                'orders_today_total' => self::buildDelta($ordersToday['total_amount'], $ordersYesterday['total_amount']),
                'orders_today_profit' => self::buildDelta($ordersToday['profit'], $ordersYesterday['profit']),
                'purchases_month_total' => self::buildDelta($purchasesMonth['total_amount'], $purchasesPreviousMonth['total_amount']),
                'customer_debt' => self::buildDelta($customerDebt, $openingCustomerDebt),
                'supplier_debt' => self::buildDelta($supplierDebt, $openingSupplierDebt),
            ],
            'updatedAtText' => date('H:i d/m'),
        ];
    }

    public static function getSalesData(array $queryParams): array
    {
        $pdo = Database::getInstance();
        $dateRange = self::resolveSalesDateRange($queryParams);

        $conditions = [
            'o.deleted_at IS NULL',
            '(o.order_status IS NULL OR o.order_status <> \'cancelled\')',
        ];
        $params = [];

        if ($dateRange['startDate'] !== '' && $dateRange['endDate'] !== '') {
            $conditions[] = 'o.order_date BETWEEN ? AND ?';
            $params[] = $dateRange['startDate'] . ' 00:00:00';
            $params[] = $dateRange['endDate'] . ' 23:59:59';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $conditions);

        $summarySqlOrders = 'SELECT
                COUNT(*) AS doc_count,
                COALESCE(SUM(o.total_amount), 0) AS total_amount,
                COALESCE(SUM(o.total_cost), 0) AS total_cost,
                COALESCE(SUM(o.paid_amount), 0) AS paid_amount
            FROM orders o
            ' . $whereSql;

        $summaryStmtOrders = $pdo->prepare($summarySqlOrders);
        $summaryStmtOrders->execute($params);
        $summaryRowOrders = $summaryStmtOrders->fetch();

        $docCount = isset($summaryRowOrders['doc_count']) ? (int) $summaryRowOrders['doc_count'] : 0;
        $totalAmount = isset($summaryRowOrders['total_amount']) ? (float) $summaryRowOrders['total_amount'] : 0.0;
        $totalCost = isset($summaryRowOrders['total_cost']) ? (float) $summaryRowOrders['total_cost'] : 0.0;
        $paidAmount = isset($summaryRowOrders['paid_amount']) ? (float) $summaryRowOrders['paid_amount'] : 0.0;

        $summary = [
            'order_count' => $docCount,
            'total_amount' => $totalAmount,
            'total_cost' => $totalCost,
            'profit' => $totalAmount - $totalCost,
            'paid_amount' => $paidAmount,
            'debt_amount' => $totalAmount - $paidAmount,
        ];

        $dailyStats = [];
        if ($dateRange['filterMode'] === 'month' && $dateRange['startDate'] !== '' && $dateRange['endDate'] !== '') {
            $dailySql = 'SELECT
                    DATE(o.order_date) AS day,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(o.total_amount), 0) AS total_amount,
                    COALESCE(SUM(o.total_cost), 0) AS total_cost
                FROM orders o
                ' . $whereSql . '
                GROUP BY DATE(o.order_date)
                ORDER BY day ASC';

            $dailyStmt = $pdo->prepare($dailySql);
            $dailyStmt->execute($params);
            $dailyStats = $dailyStmt->fetchAll();
        }

        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $perPage = 30;
        $totalPages = $summary['order_count'] > 0 ? (int) ceil($summary['order_count'] / $perPage) : 1;
        if ($totalPages < 1) {
            $totalPages = 1;
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT o.*,
                    c.name AS customer_name,
                    c.phone AS customer_phone,
                    COALESCE(ic.items_count, 0) AS items_count
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN (
                    SELECT order_id, SUM(count_items) AS items_count
                    FROM (
                        SELECT order_id, COUNT(*) AS count_items
                        FROM order_items
                        GROUP BY order_id
                        UNION ALL
                        SELECT order_id, COUNT(*) AS count_items
                        FROM order_manual_items
                        GROUP BY order_id
                    ) t
                    GROUP BY order_id
                ) ic ON ic.order_id = o.id
                ' . $whereSql . '
                ORDER BY o.order_date DESC, o.id DESC
                LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return [
            'items' => $stmt->fetchAll(),
            'summary' => $summary,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
                'total_count' => $summary['order_count'],
            ],
            'filters' => [
                'start_date' => $dateRange['startDate'],
                'end_date' => $dateRange['endDate'],
                'range_mode' => $dateRange['filterMode'] !== '' ? $dateRange['filterMode'] : 'day',
            ],
            'dailyStats' => $dailyStats,
        ];
    }

    public static function getCustomerDebtData(array $queryParams): array
    {
        $pdo = Database::getInstance();

        $startDate = isset($queryParams['start_date']) && $queryParams['start_date'] !== '' ? $queryParams['start_date'] : '';
        $endDate = isset($queryParams['end_date']) && $queryParams['end_date'] !== '' ? $queryParams['end_date'] : '';
        $keyword = isset($queryParams['q']) ? trim($queryParams['q']) : '';
        $showAll = isset($queryParams['show_all']) && $queryParams['show_all'] === '1';

        $conditions = [
            'o.deleted_at IS NULL',
            '(o.order_status IS NULL OR o.order_status <> \'cancelled\')',
        ];
        $params = [];

        if ($startDate !== '') {
            $conditions[] = 'o.order_date >= ?';
            $params[] = $startDate . ' 00:00:00';
        }
        if ($endDate !== '') {
            $conditions[] = 'o.order_date <= ?';
            $params[] = $endDate . ' 23:59:59';
        }
        if ($keyword !== '') {
            $conditions[] = '(c.name LIKE ? OR c.phone LIKE ? OR c.address LIKE ?)';
            $kw = '%' . $keyword . '%';
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }

        $whereSql = 'WHERE ' . implode(' AND ', $conditions);
        $sql = 'SELECT
            c.id,
            c.name,
            c.phone,
            c.address,
            COALESCE(SUM(o.total_amount), 0) AS total_amount,
            COALESCE(SUM(o.paid_amount), 0) AS paid_amount,
            COALESCE(SUM(o.total_amount - o.paid_amount), 0) AS debt_amount
        FROM customers c
        JOIN orders o ON o.customer_id = c.id
        ' . $whereSql . '
        GROUP BY c.id, c.name, c.phone, c.address';

        if (!$showAll) {
            $sql .= ' HAVING debt_amount > 0';
        }

        $sql .= ' ORDER BY debt_amount DESC, c.name ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $summary = [
            'total_amount' => 0.0,
            'paid_amount' => 0.0,
            'debt_amount' => 0.0,
        ];

        foreach ($rows as $row) {
            $summary['total_amount'] += isset($row['total_amount']) ? (float) $row['total_amount'] : 0.0;
            $summary['paid_amount'] += isset($row['paid_amount']) ? (float) $row['paid_amount'] : 0.0;
            $summary['debt_amount'] += isset($row['debt_amount']) ? (float) $row['debt_amount'] : 0.0;
        }

        return [
            'rows' => $rows,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'keyword' => $keyword,
            'showAll' => $showAll,
        ];
    }

    public static function getSupplierDebtData(array $queryParams): array
    {
        $pdo = Database::getInstance();

        $startDate = isset($queryParams['start_date']) && $queryParams['start_date'] !== '' ? $queryParams['start_date'] : '';
        $endDate = isset($queryParams['end_date']) && $queryParams['end_date'] !== '' ? $queryParams['end_date'] : '';
        $keyword = isset($queryParams['q']) ? trim($queryParams['q']) : '';
        $showAll = isset($queryParams['show_all']) && $queryParams['show_all'] === '1';
        $page = isset($queryParams['page']) ? max(1, (int)$queryParams['page']) : 1;
        $perPage = isset($queryParams['per_page']) ? max(1, (int)$queryParams['per_page']) : 30;

        $conditions = [];
        $params = [];

        if ($startDate !== '') {
            $conditions[] = 'p.purchase_date >= ?';
            $params[] = $startDate . ' 00:00:00';
        }
        if ($endDate !== '') {
            $conditions[] = 'p.purchase_date <= ?';
            $params[] = $endDate . ' 23:59:59';
        }
        if ($keyword !== '') {
            $conditions[] = '(s.name LIKE ? OR s.phone LIKE ? OR s.address LIKE ?)';
            $kw = '%' . $keyword . '%';
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }

        $whereSql = '';
        if (!empty($conditions)) {
            $whereSql = 'WHERE ' . implode(' AND ', $conditions);
        }

        // Đếm tổng số supplier phù hợp
        $countSql = 'SELECT COUNT(*) FROM (
            SELECT s.id
            FROM suppliers s
            JOIN purchases p ON p.supplier_id = s.id
            ' . $whereSql . '
            GROUP BY s.id, s.name, s.phone, s.address
            ' . (!$showAll ? 'HAVING COALESCE(SUM(p.total_amount - p.paid_amount), 0) > 0' : '') . '
        ) t';
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = (int)$countStmt->fetchColumn();

        $totalPages = $totalCount > 0 ? (int)ceil($totalCount / $perPage) : 1;
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT
            s.id,
            s.name,
            s.phone,
            s.address,
            COALESCE(SUM(p.total_amount), 0) AS total_amount,
            COALESCE(SUM(p.paid_amount), 0) AS paid_amount,
            COALESCE(SUM(p.total_amount - p.paid_amount), 0) AS debt_amount
        FROM suppliers s
        JOIN purchases p ON p.supplier_id = s.id
        ' . $whereSql . '
        GROUP BY s.id, s.name, s.phone, s.address';

        if (!$showAll) {
            $sql .= ' HAVING debt_amount > 0';
        }

        $sql .= ' ORDER BY debt_amount DESC, s.name ASC';
        $sql .= ' LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $summary = [
            'total_amount' => 0.0,
            'paid_amount' => 0.0,
            'debt_amount' => 0.0,
        ];

        // Tổng hợp lại toàn bộ (không phân trang) để lấy summary
        $summarySql = 'SELECT
            COALESCE(SUM(p.total_amount), 0) AS total_amount,
            COALESCE(SUM(p.paid_amount), 0) AS paid_amount,
            COALESCE(SUM(p.total_amount - p.paid_amount), 0) AS debt_amount
        FROM suppliers s
        JOIN purchases p ON p.supplier_id = s.id
        ' . $whereSql;
        $summaryStmt = $pdo->prepare($summarySql);
        $summaryStmt->execute($params);
        $summaryRow = $summaryStmt->fetch();
        if ($summaryRow) {
            $summary['total_amount'] = (float)$summaryRow['total_amount'];
            $summary['paid_amount'] = (float)$summaryRow['paid_amount'];
            $summary['debt_amount'] = (float)$summaryRow['debt_amount'];
        }

        return [
            'rows' => $rows,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'keyword' => $keyword,
            'showAll' => $showAll,
            'meta' => [
                'page' => $page,
                'total_pages' => $totalPages,
                'total_count' => $totalCount,
                'per_page' => $perPage
            ]
        ];
    }

    public static function processMissingCostUpdate(array $payload): array
    {
        $mode = isset($payload['mode']) && $payload['mode'] === 'selected' ? 'selected' : 'all';
        $itemIds = [];

        if ($mode === 'selected') {
            $rawIds = isset($payload['item_ids']) && is_array($payload['item_ids']) ? $payload['item_ids'] : [];
            foreach ($rawIds as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $itemIds[] = $id;
                }
            }

            if (empty($itemIds)) {
                return [
                    'success' => false,
                    'flashType' => 'error',
                    'message' => 'Vui lòng chọn ít nhất một dòng để cập nhật.',
                    'redirect' => 'report/missing-cost',
                ];
            }
        }

        $pdo = Database::getInstance();

        try {
            $pdo->beginTransaction();

            $conditions = [
                'oi.price_cost <= 0',
                'o.deleted_at IS NULL',
                '(o.order_status IS NULL OR o.order_status <> \'cancelled\')',
            ];
            $params = [];

            if ($mode === 'selected') {
                $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
                $conditions[] = 'oi.id IN (' . $placeholders . ')';
                foreach ($itemIds as $id) {
                    $params[] = $id;
                }
            }

            $sql = 'SELECT
                    oi.id,
                    oi.order_id,
                    oi.qty,
                    oi.price_cost AS old_price_cost,
                    pu.price_cost AS unit_price_cost
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN product_units pu ON oi.product_unit_id = pu.id
                WHERE ' . implode(' AND ', $conditions) . '
                ORDER BY oi.id';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            if (empty($rows)) {
                $pdo->rollBack();
                return [
                    'success' => true,
                    'flashType' => 'info',
                    'message' => 'Không có dòng đơn hàng nào cần cập nhật giá vốn.',
                    'redirect' => 'report/missing-cost',
                ];
            }

            $updateItemStmt = $pdo->prepare('UPDATE order_items SET price_cost = ? WHERE id = ?');
            $updateOrderStmt = $pdo->prepare('UPDATE orders SET total_cost = total_cost + ? WHERE id = ?');

            $orderDeltas = [];
            $updatedCount = 0;

            foreach ($rows as $row) {
                $itemId = isset($row['id']) ? (int) $row['id'] : 0;
                $orderId = isset($row['order_id']) ? (int) $row['order_id'] : 0;
                $qty = isset($row['qty']) ? (float) $row['qty'] : 0.0;
                $oldPriceCost = isset($row['old_price_cost']) ? (float) $row['old_price_cost'] : 0.0;
                $unitPriceCost = isset($row['unit_price_cost']) ? (float) $row['unit_price_cost'] : 0.0;

                if ($itemId <= 0 || $orderId <= 0 || $qty <= 0 || $unitPriceCost <= 0) {
                    continue;
                }

                if ($oldPriceCost < 0) {
                    $oldPriceCost = 0;
                }

                $delta = ($unitPriceCost - $oldPriceCost) * $qty;
                if ($delta <= 0) {
                    continue;
                }

                $updateItemStmt->execute([$unitPriceCost, $itemId]);
                if (!isset($orderDeltas[$orderId])) {
                    $orderDeltas[$orderId] = 0.0;
                }
                $orderDeltas[$orderId] += $delta;
                $updatedCount++;
            }

            if ($updatedCount === 0) {
                $pdo->rollBack();
                return [
                    'success' => true,
                    'flashType' => 'info',
                    'message' => 'Không có dòng nào được cập nhật do thiếu giá vốn hiện tại.',
                    'redirect' => 'report/missing-cost',
                ];
            }

            foreach ($orderDeltas as $orderId => $deltaCost) {
                if ($deltaCost > 0) {
                    $updateOrderStmt->execute([$deltaCost, (int) $orderId]);
                }
            }

            $pdo->commit();
            ReportService::clearReportCache();

            return [
                'success' => true,
                'flashType' => 'success',
                'message' => 'Đã cập nhật giá vốn cho ' . (int) $updatedCount . ' dòng đơn hàng.',
                'redirect' => 'report/missing-cost',
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [
                'success' => false,
                'flashType' => 'error',
                'message' => 'Không thể cập nhật giá vốn: ' . $e->getMessage(),
                'redirect' => 'report/missing-cost',
            ];
        }
    }

    public static function getMissingCostData(array $queryParams): array
    {
        $pdo = Database::getInstance();

        $keyword = isset($queryParams['q']) ? trim($queryParams['q']) : '';
        $startDate = isset($queryParams['start_date']) ? trim($queryParams['start_date']) : '';
        $endDate = isset($queryParams['end_date']) ? trim($queryParams['end_date']) : '';

        if ($startDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $startDate = '';
        }
        if ($endDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $endDate = '';
        }

        $conditions = [
            'oi.price_cost <= 0',
            'o.deleted_at IS NULL',
            '(o.order_status IS NULL OR o.order_status <> \'cancelled\')',
        ];
        $params = [];

        if ($keyword !== '') {
            $conditions[] = '(o.order_code LIKE ? OR p.name LIKE ? OR p.code LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)';
            $kw = '%' . $keyword . '%';
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }
        if ($startDate !== '') {
            $conditions[] = 'o.order_date >= ?';
            $params[] = $startDate . ' 00:00:00';
        }
        if ($endDate !== '') {
            $conditions[] = 'o.order_date <= ?';
            $params[] = $endDate . ' 23:59:59';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $conditions);
        $sql = 'SELECT
                oi.id AS item_id,
                oi.order_id,
                oi.qty,
                oi.price_sell,
                oi.price_cost AS item_price_cost,
                o.order_code,
                o.order_date,
                o.total_amount,
                o.total_cost,
                c.name AS customer_name,
                c.phone AS customer_phone,
                p.name AS product_name,
                p.code AS product_code,
                u.name AS unit_name,
                pu.price_cost AS unit_price_cost
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            JOIN product_units pu ON oi.product_unit_id = pu.id
            JOIN units u ON pu.unit_id = u.id
            LEFT JOIN customers c ON o.customer_id = c.id
            ' . $whereSql . '
            ORDER BY o.order_date DESC, oi.id DESC
            LIMIT 200';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        $summary = [
            'item_count' => 0,
            'order_count' => 0,
            'total_delta_cost' => 0.0,
        ];

        $orderIds = [];
        foreach ($items as $row) {
            $summary['item_count']++;

            $orderId = isset($row['order_id']) ? (int) $row['order_id'] : 0;
            if ($orderId > 0) {
                $orderIds[$orderId] = true;
            }

            $qty = isset($row['qty']) ? (float) $row['qty'] : 0.0;
            $itemPriceCost = isset($row['item_price_cost']) ? (float) $row['item_price_cost'] : 0.0;
            $unitPriceCost = isset($row['unit_price_cost']) ? (float) $row['unit_price_cost'] : 0.0;

            if ($qty <= 0 || $unitPriceCost <= 0) {
                continue;
            }

            if ($itemPriceCost < 0) {
                $itemPriceCost = 0;
            }

            $oldCostTotal = $itemPriceCost * $qty;
            $newCostTotal = $unitPriceCost * $qty;
            $delta = $newCostTotal - $oldCostTotal;
            if ($delta > 0) {
                $summary['total_delta_cost'] += $delta;
            }
        }

        $summary['order_count'] = count($orderIds);

        return [
            'items' => $items,
            'summary' => $summary,
            'keyword' => $keyword,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    public static function getInventoryData(): array
    {
        $pdo = Database::getInstance();

        $sql = 'SELECT p.id, p.code, p.name, c.name AS category_name, u.name AS base_unit_name,
            COALESCE(i.qty_base, 0) AS qty_base, i.updated_at,
            pu.min_step AS min_step
            FROM products p
            JOIN units u ON p.base_unit_id = u.id
            LEFT JOIN product_categories c ON p.category_id = c.id
            LEFT JOIN inventory i ON i.product_id = p.id
            LEFT JOIN product_units pu ON pu.product_id = p.id AND pu.unit_id = p.base_unit_id
            WHERE p.deleted_at IS NULL
            ORDER BY p.name';

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

    public static function adjustInventoryQty(int $productId, $qtyRaw): array
    {
        if ($productId <= 0) {
            return [
                'success' => false,
                'message' => 'Dữ liệu kiểm kê không hợp lệ.',
                'redirect' => 'report/inventory',
            ];
        }

        $qty = (float) str_replace([',', ' '], ['', ''], (string) $qtyRaw);
        if ($qty < 0) {
            $qty = 0;
        }

        if (class_exists('Inventory')) {
            Inventory::setQtyBase($productId, $qty);
        }

        return [
            'success' => true,
            'message' => 'Đã cập nhật tồn kho sản phẩm.',
            'redirect' => 'report/inventory',
        ];
    }

    public static function buildDelta($current, $previous): array
    {
        $currentVal = (float) $current;
        $previousVal = (float) $previous;
        $amount = $currentVal - $previousVal;

        if (abs($previousVal) > 0.00001) {
            $percent = ($amount / abs($previousVal)) * 100;
        } elseif (abs($currentVal) > 0.00001) {
            $percent = $amount > 0 ? 100.0 : -100.0;
        } else {
            $percent = 0.0;
        }

        return [
            'amount' => $amount,
            'percent' => $percent,
        ];
    }

    private static function resolveSalesDateRange(array $queryParams): array
    {
        $filterMode = isset($queryParams['filter_mode']) ? $queryParams['filter_mode'] : '';
        if (!in_array($filterMode, ['day', 'month', 'quarter', 'year'], true)) {
            $filterMode = '';
        }

        $startDate = '';
        $endDate = '';
        $hasDateFilter = false;

        if ($filterMode === 'day') {
            $day = isset($queryParams['day']) ? trim($queryParams['day']) : '';
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
                $startDate = $day;
                $endDate = $day;
                $hasDateFilter = true;
            }
        } elseif ($filterMode === 'month') {
            $month = isset($queryParams['month']) ? trim($queryParams['month']) : '';
            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                $parts = explode('-', $month);
                $yearNum = (int) $parts[0];
                $monthNum = (int) $parts[1];
                if ($yearNum >= 2000 && $yearNum <= 2100 && $monthNum >= 1 && $monthNum <= 12) {
                    $startDate = sprintf('%04d-%02d-01', $yearNum, $monthNum);
                    $endDateObj = new DateTime($startDate);
                    $endDateObj->modify('last day of this month');
                    $endDate = $endDateObj->format('Y-m-d');
                    $hasDateFilter = true;
                }
            }
        } elseif ($filterMode === 'quarter') {
            $quarter = isset($queryParams['quarter']) ? (int) $queryParams['quarter'] : 0;
            $quarterYear = isset($queryParams['quarter_year']) ? (int) $queryParams['quarter_year'] : 0;
            if ($quarter >= 1 && $quarter <= 4 && $quarterYear >= 2000 && $quarterYear <= 2100) {
                $startMonth = ($quarter - 1) * 3 + 1;
                $startDate = sprintf('%04d-%02d-01', $quarterYear, $startMonth);
                $endDateObj = new DateTime($startDate);
                $endDateObj->modify('+2 months');
                $endDateObj->modify('last day of this month');
                $endDate = $endDateObj->format('Y-m-d');
                $hasDateFilter = true;
            }
        } elseif ($filterMode === 'year') {
            $yearVal = isset($queryParams['year']) ? (int) $queryParams['year'] : 0;
            if ($yearVal >= 2000 && $yearVal <= 2100) {
                $startDate = sprintf('%04d-01-01', $yearVal);
                $endDate = sprintf('%04d-12-31', $yearVal);
                $hasDateFilter = true;
            }
        }

        return [
            'filterMode' => $filterMode,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'hasDateFilter' => $hasDateFilter,
        ];
    }

    public static function clearReportCache()
    {
        $patterns = [
            'sumOrdersByDateRange:*',
            'sumCustomerDebt',
            'sumPurchasesByDateRange:*',
            'sumSupplierDebt',
        ];

        if (function_exists('apcu_delete') && ini_get('apc.enabled')) {
            foreach ($patterns as $pattern) {
                apcu_delete(new APCUIterator($pattern));
            }
            return;
        }

        if (extension_loaded('redis')) {
            try {
                $redis = new Redis();
                $redis->connect('127.0.0.1', 6379);
                foreach ($patterns as $pattern) {
                    $pattern = str_replace('*', '', $pattern);
                    $keys = $redis->keys($pattern . '*');
                    if (!empty($keys)) {
                        $redis->del(...$keys);
                    }
                }
                return;
            } catch (Exception $e) {
                // fallback
            }
        }

        $tempDir = sys_get_temp_dir();
        $pattern = 'report_cache_';
        $files = @glob($tempDir . DIRECTORY_SEPARATOR . $pattern . '*.cache');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
}
