<?php

class CustomerController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $pdo = Database::getInstance();
        $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
        $debtStatus = isset($_GET['debt_status']) ? $_GET['debt_status'] : '';
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $perPage = 20;

        $where = ['c.deleted_at IS NULL'];
        $params = [];

        if ($keyword !== '') {
            $where[] = 'c.name LIKE ?';
            $kw = '%' . $keyword . '%';
            $params[] = $kw;
        }

        $having = '';
        if ($debtStatus === 'debt') {
            $having = 'HAVING debt_amount > 0';
        } elseif ($debtStatus === 'nodebt') {
            $having = 'HAVING debt_amount <= 0';
        }

        $whereSql = '';
        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        $countSql = 'SELECT COUNT(*) FROM (
                SELECT c.id, COALESCE(SUM(o.total_amount - o.paid_amount), 0) AS debt_amount
                FROM customers c
                LEFT JOIN orders o ON o.customer_id = c.id
                ' . $whereSql . '
                GROUP BY c.id
                ' . $having . '
            ) t';
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

        $sql = 'SELECT c.*, COALESCE(SUM(o.total_amount - o.paid_amount), 0) AS debt_amount
                FROM customers c
                LEFT JOIN orders o ON o.customer_id = c.id
                ' . $whereSql . '
                GROUP BY c.id
                ' . $having . '
                ORDER BY c.name
                LIMIT ? OFFSET ?';
        $stmt = $pdo->prepare($sql);
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $paramIndex = count($params) + 1;
        $stmt->bindValue($paramIndex, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex + 1, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $customers = $stmt->fetchAll();

        $this->render('customers/index', [
            'title' => 'Khách hàng',
            'customers' => $customers,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
            'debtStatus' => $debtStatus,
			'listHeader' => [
                'title' => 'Khách hàng',
                'subtitle' => 'Quản lý danh sách khách hàng và công nợ.',
                'primary' => [
                    'url' => 'customer/create',
                    'tooltip' => 'Thêm khách hàng',
                ],
                'sticky' => true,
                'form' => [
                    'method' => 'get',
                    'action' => '',
                    'attrs' => [],
                ],
                'search' => [
                    'param' => 'q',
                    'placeholder' => 'Tìm kiếm theo tên, SĐT, địa chỉ...',
                    'value' => $keyword,
                    'clear_url' => 'customer',
                    'show_clear' => $keyword !== '',
                ],
                'hidden' => [],
                'extra_buttons' => [],
                'chips' => [
                    'class' => 'mt-2 flex items-center gap-2 overflow-x-auto text-sm',
                    'items' => [
                        [
                            'kind' => 'submit',
                            'name' => 'debt_status',
                            'value' => '',
                            'label' => 'Tất cả',
                            'active' => $debtStatus === '',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-emerald-600 text-white border-emerald-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'submit',
                            'name' => 'debt_status',
                            'value' => 'debt',
                            'label' => 'Còn nợ',
                            'active' => $debtStatus === 'debt',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-emerald-600 text-white border-emerald-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                        [
                            'kind' => 'submit',
                            'name' => 'debt_status',
                            'value' => 'nodebt',
                            'label' => 'Không nợ',
                            'active' => $debtStatus === 'nodebt',
                            'base_class' => 'border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium',
                            'active_class' => 'bg-emerald-600 text-white border-emerald-600',
                            'inactive_class' => 'bg-white text-slate-700 border-slate-200',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function view()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            $this->redirect('customer');
        }

        $customer = Customer::find($id);
        if (!$customer) {
            $this->redirect('customer');
        }

		$pdo = Database::getInstance();
		$stmt = $pdo->prepare('SELECT o.*, (o.total_amount - o.paid_amount) AS debt_amount,
			(
			    SELECT COALESCE(SUM(count_items), 0) FROM (
			        SELECT COUNT(*) AS count_items FROM order_items oi WHERE oi.order_id = o.id
			        UNION ALL
			        SELECT COUNT(*) AS count_items FROM order_manual_items omi WHERE omi.order_id = o.id
			    ) t
			) AS items_count
			FROM orders o
			WHERE o.customer_id = ?
			  AND o.deleted_at IS NULL
			  AND (o.order_status IS NULL OR o.order_status <> \'cancelled\')
			ORDER BY o.order_date DESC, o.id DESC');
		$stmt->execute([$id]);
		$orders = $stmt->fetchAll();

        $totalAmountSum = 0.0;
        $totalPaidSum = 0.0;
        $totalDebt = 0.0;

        foreach ($orders as $order) {
            $total = isset($order['total_amount']) ? (float) $order['total_amount'] : 0.0;
            $paid = isset($order['paid_amount']) ? (float) $order['paid_amount'] : 0.0;
            $debt = $total - $paid;
            if ($debt < 0) {
                $debt = 0.0;
            }

            $totalAmountSum += $total;
            $totalPaidSum += $paid;
            $totalDebt += $debt;
        }

        $this->render('customers/view', [
            'title' => 'Khách hàng',
            'customer' => $customer,
            'orders' => $orders,
            'totalAmountSum' => $totalAmountSum,
            'totalPaidSum' => $totalPaidSum,
            'totalDebt' => $totalDebt,
            'detailHeader' => [
                'title' => 'Khách hàng',
                'back_url' => 'customer',
                'back_label' => 'Quay lại',
                'actions_view' => 'customers/_detail_header_actions',
            ],
        ]);
    }

    public function edit()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            $this->redirect('customer');
        }

        $customer = Customer::find($id);
        if (!$customer) {
            $this->setFlash('error', 'Không tìm thấy khách hàng.');
            $this->redirect('customer');
        }

        $this->render('customers/form', [
            'title' => 'Sửa khách hàng',
            'customer' => $customer,
            'detailHeader' => [
                'title' => 'Sửa khách hàng',
                'back_url' => 'customer/view?id=' . $id,
                'back_label' => 'Quay lại',
                'actions_view' => '',
            ],
        ]);
    }

    public function create()
    {
        $this->requireLogin();

        $this->render('customers/form', [
            'title' => 'Thêm khách hàng',
            'customer' => null,
            'detailHeader' => [
                'title' => 'Thêm khách hàng',
                'back_url' => 'customer',
                'back_label' => 'Quay lại',
                'actions_view' => '',
            ],
        ]);
    }

    public function update()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('customer');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $this->redirect('customer');
        }

        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';

        if ($name === '') {
            $this->setFlash('error', 'Vui lòng nhập tên khách hàng.');
            $this->redirect('customer/edit?id=' . $id);
        }

        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare('UPDATE customers SET name = ?, phone = ?, address = ? WHERE id = ? AND deleted_at IS NULL');
            if ($phone === '') {
                $phone = null;
            }
            $stmt->execute([
                $name,
                $phone,
                $address,
                $id,
            ]);
        } catch (Exception $e) {
            $this->setFlash('error', 'Không thể cập nhật khách hàng: ' . $e->getMessage());
            $this->redirect('customer/edit?id=' . $id);
        }

        $this->setFlash('success', 'Đã cập nhật thông tin khách hàng.');
        $this->redirect('customer/view?id=' . $id);
    }

    public function store()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('customer');
        }

        $this->verifyCsrfToken();

        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';

        if ($name === '') {
            $this->setFlash('error', 'Vui lòng nhập tên khách hàng.');
            $this->redirect('customer/create');
        }

        try {
            $id = Customer::create([
                'name' => $name,
                'phone' => $phone,
                'address' => $address,
            ]);
        } catch (Exception $e) {
            $this->setFlash('error', 'Không thể tạo khách hàng: ' . $e->getMessage());
            $this->redirect('customer');
        }

        $this->setFlash('success', 'Đã thêm khách hàng mới.');
        $this->redirect('customer/view?id=' . (int) $id);
    }

    public function delete()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('customer');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $this->redirect('customer');
        }

        $success = Customer::delete($id);
        if ($success) {
            $this->setFlash('success', 'Đã xóa khách hàng. Các đơn hàng liên quan chuyển thành khách lẻ.');
        } else {
            $this->setFlash('error', 'Không thể xóa khách hàng.');
        }

        $this->redirect('customer');
    }

    public function payment()
    {
        $this->requireLogin();

        $orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
        if (!$orderId) {
            $this->redirect('customer');
        }

        $order = OrderRepository::findWithCustomer($orderId);

        if (!$order || !$order['customer_id']) {
            $this->redirect('customer');
        }

        $remaining = $order['total_amount'] - $order['paid_amount'];
        if ($remaining <= 0) {
            $this->redirect('customer/view?id=' . $order['customer_id']);
        }

        $this->render('customers/payment', [
            'title' => 'Thu tiền khách hàng',
            'order' => $order,
            'remaining' => $remaining,
        ]);
    }

    public function paymentStore()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('customer');
        }

        $this->verifyCsrfToken();

		$orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
		$amount = isset($_POST['amount']) ? Money::parseAmount($_POST['amount']) : 0;
        $note = isset($_POST['note']) ? trim($_POST['note']) : '';

        if ($orderId <= 0 || $amount <= 0) {
            $this->setFlash('error', 'Dữ liệu thanh toán không hợp lệ.');
			$this->redirect('customer');
		}

		$pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id, customer_id FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order || !$order['customer_id']) {
            $this->setFlash('error', 'Không tìm thấy đơn hàng.');
            $this->redirect('customer');
        }

        $customerId = (int) $order['customer_id'];

        try {
            PaymentService::recordOrderPayment($orderId, $amount, $note, 'cash');
            $this->setFlash('success', 'Đã ghi nhận thanh toán.');
            $this->redirect('customer/view?id=' . $customerId);
        } catch (Exception $e) {
			$this->setFlash('error', 'Không thể ghi nhận thanh toán: ' . $e->getMessage());
			$this->redirect('customer/view?id=' . $customerId);
		}
	}
}
