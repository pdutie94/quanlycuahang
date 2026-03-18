<?php

class ReportController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $pdo = Database::getInstance();

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        $previousMonthStart = date('Y-m-01', strtotime('first day of last month'));
        $previousMonthEnd = date('Y-m-t', strtotime('last day of last month'));

        $ordersToday = ReportService::sumOrdersByDateRange($pdo, $today . ' 00:00:00', $today . ' 23:59:59');
        $ordersYesterday = ReportService::sumOrdersByDateRange($pdo, $yesterday . ' 00:00:00', $yesterday . ' 23:59:59');
        $ordersMonth = ReportService::sumOrdersByDateRange($pdo, $monthStart . ' 00:00:00', $monthEnd . ' 23:59:59');
        $ordersPreviousMonth = ReportService::sumOrdersByDateRange($pdo, $previousMonthStart . ' 00:00:00', $previousMonthEnd . ' 23:59:59');

        $purchasesMonth = ReportService::sumPurchasesByDateRange($pdo, $monthStart . ' 00:00:00', $monthEnd . ' 23:59:59');
        $purchasesPreviousMonth = ReportService::sumPurchasesByDateRange($pdo, $previousMonthStart . ' 00:00:00', $previousMonthEnd . ' 23:59:59');

        $customerDebt = ReportService::sumCustomerDebt($pdo);
        $supplierDebt = ReportService::sumSupplierDebt($pdo);

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

        $delta = [
            'orders_month_total' => $this->buildDelta($ordersMonth['total_amount'], $ordersPreviousMonth['total_amount']),
            'orders_month_profit' => $this->buildDelta($ordersMonth['profit'], $ordersPreviousMonth['profit']),
            'orders_today_total' => $this->buildDelta($ordersToday['total_amount'], $ordersYesterday['total_amount']),
            'orders_today_profit' => $this->buildDelta($ordersToday['profit'], $ordersYesterday['profit']),
            'purchases_month_total' => $this->buildDelta($purchasesMonth['total_amount'], $purchasesPreviousMonth['total_amount']),
            'customer_debt' => $this->buildDelta($customerDebt, $openingCustomerDebt),
            'supplier_debt' => $this->buildDelta($supplierDebt, $openingSupplierDebt),
        ];

        $this->render('reports/index', [
            'title' => 'Báo cáo tổng quan',
            'ordersToday' => $ordersToday,
            'ordersMonth' => $ordersMonth,
            'purchasesMonth' => $purchasesMonth,
            'customerDebt' => $customerDebt,
            'supplierDebt' => $supplierDebt,
            'delta' => $delta,
            'updatedAtText' => date('H:i d/m'),
        ]);
    }

    private function buildDelta($current, $previous)
    {
        $currentVal = (float) $current;
        $previousVal = (float) $previous;
        $amount = $currentVal - $previousVal;
        $percent = null;

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

    public function sales()
    {
        $this->requireLogin();

        $pdo = Database::getInstance();

        $filterMode = isset($_GET['filter_mode']) ? $_GET['filter_mode'] : '';
        if (!in_array($filterMode, ['day', 'month', 'quarter', 'year'], true)) {
            $filterMode = '';
        }

        $startDate = '';
        $endDate = '';
        $hasDateFilter = false;

        if ($filterMode === 'day') {
            $day = isset($_GET['day']) ? trim($_GET['day']) : '';
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
                $startDate = $day;
                $endDate = $day;
                $hasDateFilter = true;
            }
        } elseif ($filterMode === 'month') {
            $month = isset($_GET['month']) ? trim($_GET['month']) : '';
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
            $quarter = isset($_GET['quarter']) ? (int) $_GET['quarter'] : 0;
            $quarterYear = isset($_GET['quarter_year']) ? (int) $_GET['quarter_year'] : 0;
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
            $yearVal = isset($_GET['year']) ? (int) $_GET['year'] : 0;
            if ($yearVal >= 2000 && $yearVal <= 2100) {
                $startDate = sprintf('%04d-01-01', $yearVal);
                $endDate = sprintf('%04d-12-31', $yearVal);
                $hasDateFilter = true;
            }
        }

        $conditions = [
            'o.deleted_at IS NULL',
            '(o.order_status IS NULL OR o.order_status <> \'cancelled\')',
        ];
        $params = [];

        if ($startDate !== '' && $endDate !== '') {
            $conditions[] = 'o.order_date BETWEEN ? AND ?';
            $params[] = $startDate . ' 00:00:00';
            $params[] = $endDate . ' 23:59:59';
        }

        $whereSql = '';
        if (!empty($conditions)) {
            $whereSql = 'WHERE ' . implode(' AND ', $conditions);
        }

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
        if ($filterMode === 'month' && $startDate !== '' && $endDate !== '') {
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

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
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

        $listWhereOrders = $whereSql;
        $listParamsOrders = $params;

        $sql = 'SELECT
                    \'order\' AS doc_type,
                    o.id,
                    o.order_code AS code,
                    o.order_date AS doc_date,
                    o.total_amount,
                    o.total_cost,
                    o.paid_amount,
                    o.status,
                    o.order_status,
                    c.name AS customer_name,
                    c.phone AS customer_phone
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                ' . $listWhereOrders . '
                ORDER BY doc_date DESC, id DESC
                LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($listParamsOrders);
        $rows = $stmt->fetchAll();

        if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
            $this->renderPartial('reports/sales_orders_list', [
                'rows' => $rows,
                'page' => $page,
                'totalPages' => $totalPages,
            ]);
            return;
        }

        $this->render('reports/sales', [
            'title' => 'Báo cáo doanh thu chi tiết',
            'rows' => $rows,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'rangeMode' => $filterMode !== '' ? $filterMode : 'day',
            'page' => $page,
            'totalPages' => $totalPages,
            'hasDateFilter' => $hasDateFilter,
            'dailyStats' => $dailyStats,
        ]);
    }

    public function customerDebt()
    {
        $this->requireLogin();

        $pdo = Database::getInstance();

        $startDate = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] : '';
        $endDate = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] : '';
        $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
        $showAll = isset($_GET['show_all']) && $_GET['show_all'] === '1';

        $conditions = [];
        $params = [];

        // Chỉ lấy đơn hàng chưa xóa tạm và không ở trạng thái đã hủy
        $conditions[] = 'o.deleted_at IS NULL';
        $conditions[] = '(o.order_status IS NULL OR o.order_status <> \'cancelled\')';

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

        $whereSql = '';
        if (!empty($conditions)) {
            $whereSql = 'WHERE ' . implode(' AND ', $conditions);
        }

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

        $this->render('reports/customer_debt', [
            'title' => 'Công nợ khách hàng',
            'rows' => $rows,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'keyword' => $keyword,
            'showAll' => $showAll,
        ]);
    }

    public function supplierDebt()
    {
        $this->requireLogin();

        $pdo = Database::getInstance();

        $startDate = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] : '';
        $endDate = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] : '';
        $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
        $showAll = isset($_GET['show_all']) && $_GET['show_all'] === '1';

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

        $this->render('reports/supplier_debt', [
            'title' => 'Công nợ nhà cung cấp',
            'rows' => $rows,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'keyword' => $keyword,
            'showAll' => $showAll,
        ]);
    }

    public function missingCost()
    {
        $this->requireLogin();

        $pdo = Database::getInstance();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrfToken();
            $mode = isset($_POST['mode']) && $_POST['mode'] === 'selected' ? 'selected' : 'all';

            $itemIds = [];
            if ($mode === 'selected') {
                $rawIds = isset($_POST['item_ids']) && is_array($_POST['item_ids']) ? $_POST['item_ids'] : [];
                foreach ($rawIds as $id) {
                    $id = (int) $id;
                    if ($id > 0) {
                        $itemIds[] = $id;
                    }
                }
                if (empty($itemIds)) {
                    $this->setFlash('error', 'Vui lòng chọn ít nhất một dòng để cập nhật.');
                    $this->redirect('report/missing-cost');
                }
            }

            try {
                $pdo->beginTransaction();

                $conditions = [];
                $params = [];

                $conditions[] = 'oi.price_cost <= 0';
                $conditions[] = 'o.deleted_at IS NULL';
                $conditions[] = '(o.order_status IS NULL OR o.order_status <> \'cancelled\')';

                if ($mode === 'selected') {
                    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
                    $conditions[] = 'oi.id IN (' . $placeholders . ')';
                    foreach ($itemIds as $id) {
                        $params[] = $id;
                    }
                }

                if (empty($conditions)) {
                    $this->setFlash('error', 'Điều kiện cập nhật không hợp lệ.');
                    $pdo->rollBack();
                    $this->redirect('report/missing-cost');
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
                    $this->setFlash('info', 'Không có dòng đơn hàng nào cần cập nhật giá vốn.');
                    $this->redirect('report/missing-cost');
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

                    if ($itemId <= 0 || $orderId <= 0) {
                        continue;
                    }
                    if ($qty <= 0) {
                        continue;
                    }
                    if ($unitPriceCost <= 0) {
                        continue;
                    }
                    if ($oldPriceCost < 0) {
                        $oldPriceCost = 0;
                    }

                    $delta = ($unitPriceCost - $oldPriceCost) * $qty;
                    if ($delta <= 0) {
                        continue;
                    }

                    $updateItemStmt->execute([
                        $unitPriceCost,
                        $itemId,
                    ]);

                    if (!isset($orderDeltas[$orderId])) {
                        $orderDeltas[$orderId] = 0.0;
                    }
                    $orderDeltas[$orderId] += $delta;
                    $updatedCount++;
                }

                if ($updatedCount === 0) {
                    $pdo->rollBack();
                    $this->setFlash('info', 'Không có dòng nào được cập nhật do thiếu giá vốn hiện tại.');
                    $this->redirect('report/missing-cost');
                }

                foreach ($orderDeltas as $orderId => $deltaCost) {
                    if ($deltaCost <= 0) {
                        continue;
                    }
                    $updateOrderStmt->execute([
                        $deltaCost,
                        (int) $orderId,
                    ]);
                }

                $pdo->commit();

                $message = 'Đã cập nhật giá vốn cho ' . (int) $updatedCount . ' dòng đơn hàng.';
                $this->setFlash('success', $message);
                $this->redirect('report/missing-cost');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $this->setFlash('error', 'Không thể cập nhật giá vốn: ' . $e->getMessage());
                $this->redirect('report/missing-cost');
            }
        }

        $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
        $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
        $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

        if ($startDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $startDate = '';
        }
        if ($endDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $endDate = '';
        }

        $conditions = [];
        $params = [];

        $conditions[] = 'oi.price_cost <= 0';
        $conditions[] = 'o.deleted_at IS NULL';
        $conditions[] = '(o.order_status IS NULL OR o.order_status <> \'cancelled\')';

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

        $whereSql = '';
        if (!empty($conditions)) {
            $whereSql = 'WHERE ' . implode(' AND ', $conditions);
        }

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

        $this->render('reports/missing_cost', [
            'title' => 'Cập nhật giá vốn thiếu',
            'items' => $items,
            'summary' => $summary,
            'keyword' => $keyword,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    protected function sumOrdersByDateRange(PDO $pdo, $start, $end)
    {
        $sql = 'SELECT
            COALESCE(SUM(total_amount), 0) AS total_amount,
            COALESCE(SUM(total_cost), 0) AS total_cost,
            COALESCE(SUM(paid_amount), 0) AS paid_amount
        FROM orders
        WHERE order_date BETWEEN ? AND ?
          AND deleted_at IS NULL
          AND (order_status IS NULL OR order_status <> \'cancelled\')';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$start, $end]);
        $row = $stmt->fetch();

        $total = isset($row['total_amount']) ? (float) $row['total_amount'] : 0;
        $cost = isset($row['total_cost']) ? (float) $row['total_cost'] : 0;
        $paid = isset($row['paid_amount']) ? (float) $row['paid_amount'] : 0;

        return [
            'total_amount' => $total,
            'total_cost' => $cost,
            'profit' => $total - $cost,
            'paid_amount' => $paid,
            'debt_amount' => $total - $paid,
        ];
    }

    protected function sumPurchasesByDateRange(PDO $pdo, $start, $end)
    {
        $sql = 'SELECT
            COALESCE(SUM(total_amount), 0) AS total_amount,
            COALESCE(SUM(paid_amount), 0) AS paid_amount
        FROM purchases
        WHERE purchase_date BETWEEN ? AND ?';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$start, $end]);
        $row = $stmt->fetch();

        $total = isset($row['total_amount']) ? (float) $row['total_amount'] : 0;
        $paid = isset($row['paid_amount']) ? (float) $row['paid_amount'] : 0;

        return [
            'total_amount' => $total,
            'paid_amount' => $paid,
            'debt_amount' => $total - $paid,
        ];
    }

    protected function sumCustomerDebt(PDO $pdo)
    {
        return ReportService::sumCustomerDebt($pdo);
    }

    protected function sumSupplierDebt(PDO $pdo)
    {
        $sql = 'SELECT COALESCE(SUM(total_amount - paid_amount), 0) AS debt_amount FROM purchases';
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        return isset($row['debt_amount']) ? (float) $row['debt_amount'] : 0;
    }

    public function inventory()
    {
        $this->requireLogin();

        $pdo = Database::getInstance();

        $sql = 'SELECT p.id, p.code, p.name, c.name AS category_name, u.name AS base_unit_name,
            COALESCE(i.qty_base, 0) AS qty_base, i.updated_at
            FROM products p
            JOIN units u ON p.base_unit_id = u.id
            LEFT JOIN product_categories c ON p.category_id = c.id
            LEFT JOIN inventory i ON i.product_id = p.id
            WHERE p.deleted_at IS NULL
            ORDER BY p.name';

        $stmt = $pdo->query($sql);
        $items = $stmt->fetchAll();

        $this->render('reports/inventory', [
            'title' => 'Cập nhật tồn kho',
            'items' => $items,
        ]);
    }

    public function inventoryAdjust()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('report/inventory');
        }

        $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $qtyRaw = isset($_POST['qty_base']) ? $_POST['qty_base'] : '';
        $qty = (float) str_replace([',', ' '], ['', ''], $qtyRaw);

        if ($productId <= 0) {
            $this->setFlash('error', 'Dữ liệu kiểm kê không hợp lệ.');
            $this->redirect('report/inventory');
        }

        if ($qty < 0) {
            $qty = 0;
        }

        if (class_exists('Inventory')) {
            Inventory::setQtyBase($productId, $qty);
        }

        $this->setFlash('success', 'Đã cập nhật tồn kho sản phẩm.');
        $this->redirect('report/inventory');
    }
}
