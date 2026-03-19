<?php

class OrderListController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $pdo = Database::getInstance();
        $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $orderStatus = isset($_GET['order_status']) ? $_GET['order_status'] : '';
        $fromDate = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
        $toDate = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        if ($fromDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
            $fromDate = '';
        }
        if ($toDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
            $toDate = '';
        }

        $perPage = 20;

        $where = [];
        $params = [];

        if ($keyword !== '') {
            $where[] = '(o.order_code LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)';
            $kw = '%' . $keyword . '%';
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }

        if ($status === 'paid') {
            $where[] = 'o.status = "paid"';
        } elseif ($status === 'debt') {
            $where[] = 'o.status = "debt"';
        }

        if ($orderStatus === 'completed') {
            $where[] = 'o.order_status = "completed"';
        } elseif ($orderStatus === 'cancelled') {
            $where[] = 'o.order_status = "cancelled"';
        } elseif ($orderStatus === 'pending') {
            $where[] = '(o.order_status IS NULL OR o.order_status NOT IN ("completed", "cancelled"))';
        }

        if ($fromDate !== '') {
            $where[] = 'o.order_date >= ?';
            $params[] = $fromDate . ' 00:00:00';
        }
        if ($toDate !== '') {
            $where[] = 'o.order_date <= ?';
            $params[] = $toDate . ' 23:59:59';
        }

        $whereSql = 'WHERE o.deleted_at IS NULL';
        if (!empty($where)) {
            $whereSql .= ' AND ' . implode(' AND ', $where);
        }

        $countSql = 'SELECT COUNT(*) FROM orders o LEFT JOIN customers c ON o.customer_id = c.id ' . $whereSql;
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = (int) $countStmt->fetchColumn();
        $totalPages = (int) ceil($totalCount / $perPage);
        if ($totalPages < 1) {
            $totalPages = 1;
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT o.*, c.name AS customer_name, c.phone AS customer_phone, COALESCE(ic.items_count, 0) AS items_count
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
        $orders = $stmt->fetchAll();

        $this->render('orders/index', [
            'title' => 'Đơn hàng',
            'orders' => $orders,
            'keyword' => $keyword,
            'status' => $status,
            'statusFilter' => $status,
            'orderStatus' => $orderStatus,
            'orderStatusFilter' => $orderStatus,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'perPage' => $perPage,
            'listHeader' => [
                'title' => 'Đơn hàng',
                'subtitle' => 'Quản lý danh sách đơn hàng bán ra.',
                'primary' => [
                    'url' => 'pos',
                    'tooltip' => 'Tạo đơn mới',
                ],
                'sticky' => true,
                'form' => [
                    'method' => 'get',
                    'action' => '',
                    'attrs' => [
                        'data-order-list-filter' => '1',
                    ],
                ],
                'search' => [
                    'param' => 'q',
                    'placeholder' => 'Tìm theo mã đơn, tên khách, SĐT...',
                    'value' => $keyword,
                    'clear_url' => 'order',
                    'show_clear' => $keyword !== '',
                ],
                'hidden' => [
                    [
                        'name' => 'status',
                        'value' => $status,
                    ],
                    [
                        'name' => 'from_date',
                        'value' => $fromDate,
                    ],
                    [
                        'name' => 'to_date',
                        'value' => $toDate,
                    ],
                ],
                'extra_buttons' => [
                    [
                        'icon' => 'filter',
                        'attrs' => [
                            'data-order-advanced-filter-open' => '1',
                        ],
                    ],
                ],
                'chips' => [
                    'class' => 'flex items-center gap-1.5 overflow-x-auto overflow-y-hidden whitespace-nowrap text-sm',
                    'items' => [
                        [
                            'kind' => 'submit',
                            'name' => 'order_status',
                            'value' => '',
                            'label' => 'Tất cả',
                            'active' => $orderStatus === '',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-brand-600 text-white border-brand-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'submit',
                            'name' => 'order_status',
                            'value' => 'completed',
                            'label' => 'Hoàn thành',
                            'active' => $orderStatus === 'completed',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-brand-600 text-white border-brand-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'submit',
                            'name' => 'order_status',
                            'value' => 'pending',
                            'label' => 'Chưa hoàn thành',
                            'active' => $orderStatus === 'pending',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-brand-600 text-white border-brand-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'submit',
                            'name' => 'order_status',
                            'value' => 'cancelled',
                            'label' => 'Đã hủy',
                            'active' => $orderStatus === 'cancelled',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-brand-600 text-white border-brand-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                    ],
                ],
            ],
            'filters' => [
                'keyword' => [
                    'name' => 'q',
                    'value' => $keyword,
                    'placeholder' => 'Tìm theo mã đơn, tên khách, SĐT',
                ],
                'date_range' => [
                    'from_name' => 'from_date',
                    'to_name' => 'to_date',
                    'from_value' => $fromDate,
                    'to_value' => $toDate,
                ],
                'chips' => [
                    'class' => 'flex items-center gap-1.5 overflow-x-auto overflow-y-hidden whitespace-nowrap text-sm',
                    'items' => [
                        [
                            'kind' => 'submit',
                            'name' => 'order_status',
                            'value' => '',
                            'label' => 'Tất cả',
                            'active' => $orderStatus === '',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-emerald-600 text-white border-emerald-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'submit',
                            'name' => 'order_status',
                            'value' => 'completed',
                            'label' => 'Hoàn thành',
                            'active' => $orderStatus === 'completed',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-emerald-600 text-white border-emerald-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'submit',
                            'name' => 'order_status',
                            'value' => 'pending',
                            'label' => 'Chưa hoàn thành',
                            'active' => $orderStatus === 'pending',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-emerald-600 text-white border-emerald-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'submit',
                            'name' => 'order_status',
                            'value' => 'cancelled',
                            'label' => 'Đã hủy',
                            'active' => $orderStatus === 'cancelled',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-emerald-600 text-white border-emerald-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function delete()
    {
        $this->requireLogin();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if (!$id) {
            $this->setFlash('error', 'ID đơn hàng không hợp lệ.');
            $this->redirect('order');
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) {
            $this->setFlash('error', 'Đơn hàng không tồn tại.');
            $this->redirect('order');
        }

        $updateStmt = $pdo->prepare('UPDATE orders SET deleted_at = NOW() WHERE id = ?');
        $updateStmt->execute([$id]);

        if (class_exists('OrderLog')) {
            OrderLog::create([
                'order_id' => $id,
                'action' => 'deleted',
                'detail' => 'Đơn hàng đã được xóa.',
            ]);
        }

        $this->setFlash('success', 'Đơn hàng đã được xóa thành công.');
        $this->redirect('order');
    }

    public function restore()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            $this->setFlash('error', 'ID đơn hàng không hợp lệ.');
            $this->redirect('order');
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND deleted_at IS NOT NULL');
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) {
            $this->setFlash('error', 'Đơn hàng không tồn tại hoặc chưa bị xóa.');
            $this->redirect('order');
        }

        $updateStmt = $pdo->prepare('UPDATE orders SET deleted_at = NULL WHERE id = ?');
        $updateStmt->execute([$id]);

        if (class_exists('OrderLog')) {
            OrderLog::create([
                'order_id' => $id,
                'action' => 'restored',
                'detail' => 'Đơn hàng đã được khôi phục.',
            ]);
        }

        $this->setFlash('success', 'Đơn hàng đã được khôi phục thành công.');
        $this->redirect('order');
    }

    public function purgeDeleted()
    {
        $this->requireLogin();

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('DELETE FROM orders WHERE deleted_at IS NOT NULL');
        $stmt->execute();
        $deletedCount = $stmt->rowCount();

        $this->setFlash('success', "Đã xóa vĩnh viễn {$deletedCount} đơn hàng đã xóa.");
        $this->redirect('order');
    }
}