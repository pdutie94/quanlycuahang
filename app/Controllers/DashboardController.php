<?php

class DashboardController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $pdo = Database::getInstance();

        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        $ordersToday = ReportService::sumOrdersByDateRange($pdo, $today . ' 00:00:00', $today . ' 23:59:59');
        $ordersMonth = ReportService::sumOrdersByDateRange($pdo, $monthStart . ' 00:00:00', $monthEnd . ' 23:59:59');

        $purchasesMonth = ReportService::sumPurchasesByDateRange($pdo, $monthStart . ' 00:00:00', $monthEnd . ' 23:59:59');

        $customerDebt = ReportService::sumCustomerDebt($pdo);
        $supplierDebt = ReportService::sumSupplierDebt($pdo);

        $recentOrders = $this->getRecentOrders($pdo, 5);

        $lowStockItems = [];
        if (class_exists('Product')) {
            $lowStockItems = Product::findLowStock(10);
        }

        $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'ordersToday' => $ordersToday,
            'ordersMonth' => $ordersMonth,
            'purchasesMonth' => $purchasesMonth,
            'customerDebt' => $customerDebt,
            'supplierDebt' => $supplierDebt,
            'recentOrders' => $recentOrders,
            'lowStockItems' => $lowStockItems,
        ]);
    }

    private function getRecentOrders(PDO $pdo, $limit = 10)
    {
        $limit = (int) $limit;
        if ($limit < 1) {
            $limit = 10;
        }

        $sql = 'SELECT
            o.*,
            c.name AS customer_name,
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
            WHERE o.deleted_at IS NULL
              AND (o.order_status IS NULL OR o.order_status <> \'cancelled\')
            ORDER BY o.order_date DESC, o.id DESC
            LIMIT ' . $limit;

        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    }

}
