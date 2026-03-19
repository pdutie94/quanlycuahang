<?php

class OrderController extends Controller
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
                    SELECT order_id, COUNT(*) AS items_count
                    FROM order_items
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
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
            'statusFilter' => $status,
            'orderStatusFilter' => $orderStatus,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'listHeader' => [
                'title' => 'Đơn hàng',
                'subtitle' => 'Quản lý danh sách đơn hàng bán ra.',
                'primary' => [
                    'url' => 'pos',
                    'tooltip' => 'Tạo đơn bán hàng',
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

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('order');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $this->redirect('order');
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT order_status FROM orders WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            $this->setFlash('error', 'Không tìm thấy đơn hàng hoặc đơn đã bị xóa.');
            $this->redirect('order');
        }

        $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
        if ($orderStatus === 'completed') {
            $this->setFlash('error', 'Đơn hàng đã hoàn thành, không thể xóa. Vui lòng hủy hoặc chỉnh trạng thái trước.');
            $this->redirect('order/view?id=' . $id);
        }

        if (!class_exists('OrderSoftDelete')) {
            $this->setFlash('error', 'Chức năng xóa đơn hàng chưa sẵn sàng.');
            $this->redirect('order/view?id=' . $id);
        }

        $ok = OrderSoftDelete::softDelete($id);
        if ($ok) {
            $this->setFlash('success', 'Đã xóa tạm đơn hàng. Có thể khôi phục trong vòng 30 ngày.');
        } else {
            $this->setFlash('error', 'Không thể xóa đơn hàng.');
        }

        $this->redirect('order');
    }

    public function restore()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('order');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0 || !class_exists('OrderSoftDelete')) {
            $this->redirect('order');
        }

        $ok = OrderSoftDelete::restore($id);
        if ($ok) {
            $this->setFlash('success', 'Đã khôi phục đơn hàng.');
        } else {
            $this->setFlash('error', 'Không thể khôi phục đơn hàng.');
        }

        $this->redirect('order');
    }

    public function purgeDeleted()
    {
        $this->requireLogin();

        if (!class_exists('OrderSoftDelete')) {
            $this->redirect('order');
        }

        $days = isset($_GET['days']) ? (int) $_GET['days'] : 30;
        $count = OrderSoftDelete::purgeOlderThanDays($days);

        $this->setFlash('success', 'Đã xóa vĩnh viễn ' . (int) $count . ' đơn hàng đã xóa tạm quá ' . (int) $days . ' ngày.');
        $this->redirect('order');
    }

    public function view()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            $this->redirect('order');
        }

        $order = OrderRepository::findWithCustomer($id);

        if (!$order || (isset($order['deleted_at']) && $order['deleted_at'] !== null)) {
            $this->redirect('order');
        }

        $pdo = Database::getInstance();

        // Combined query for items and payments to reduce queries
        $combinedStmt = $pdo->prepare("
            SELECT 'item' AS type, oi.id, oi.product_id, oi.product_unit_id, oi.qty, oi.qty_base, oi.real_weight, oi.price_sell, oi.price_cost, oi.amount,
                   p.name AS product_name, p.image_path AS product_image_path, u.name AS unit_name, pu.price_sell AS current_price_sell,
                   NULL AS paid_at, NULL AS paid_amount, NULL AS payment_note
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN product_units pu ON oi.product_unit_id = pu.id
            JOIN units u ON p.base_unit_id = u.id
            WHERE oi.order_id = ?
            UNION ALL
            SELECT 'payment' AS type, pay.id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                   NULL, NULL, NULL, NULL,
                   pay.paid_at, pay.amount, pay.note
            FROM payments pay
            WHERE pay.type = 'customer' AND pay.order_id = ?
            ORDER BY type, id
        ");
        $combinedStmt->execute([$id, $id]);
        $combinedResults = $combinedStmt->fetchAll();

        $items = [];
        $payments = [];
        foreach ($combinedResults as $row) {
            if ($row['type'] === 'item') {
                $items[] = $row;
            } elseif ($row['type'] === 'payment') {
                $payments[] = [
                    'id' => $row['id'],
                    'paid_at' => $row['paid_at'],
                    'amount' => $row['paid_amount'],
                    'note' => $row['payment_note'],
                ];
            }
        }

        $manualItems = [];
        if (class_exists('OrderManualItem')) {
            $manualItems = OrderManualItem::findByOrder($id);
        }

        $logs = [];
        if (class_exists('OrderLog')) {
            $logs = OrderLog::findByOrder($id);
        }

        $this->render('orders/view', [
            'title' => 'Chi tiết đơn hàng',
            'order' => $order,
            'items' => $items,
            'manualItems' => $manualItems,
            'payments' => $payments,
            'logs' => $logs,
            'detailHeader' => [
                'title' => 'Chi tiết đơn hàng',
                'back_url' => 'order',
                'back_label' => 'Quay lại',
                'actions_view' => 'orders/_detail_header_actions',
            ],
        ]);
    }

    public function invoice()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            $this->redirect('order');
        }

        $order = OrderRepository::findWithCustomer($id);

        if (!$order || (isset($order['deleted_at']) && $order['deleted_at'] !== null)) {
            $this->redirect('order');
        }

        $pdo = Database::getInstance();
        $itemStmt = $pdo->prepare('SELECT oi.*, p.name AS product_name, u.name AS unit_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN product_units pu ON oi.product_unit_id = pu.id
            JOIN units u ON p.base_unit_id = u.id
            WHERE oi.order_id = ?
            ORDER BY oi.id');
        $itemStmt->execute([$id]);
        $items = $itemStmt->fetchAll();

        if (empty($items)) {
            $this->redirect('order/view?id=' . $id);
        }

        $this->render('orders/invoice', [
            'title' => 'Hóa đơn đơn hàng ' . $order['order_code'],
            'order' => $order,
            'items' => $items,
        ]);
    }

    public function addForm()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            $this->redirect('order');
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            $this->redirect('order');
        }

        $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
        if ($orderStatus === 'completed' || $orderStatus === 'cancelled') {
            $this->setFlash('error', 'Đơn hàng đã hoàn thành hoặc đã hủy, không thể thêm sản phẩm.');
            $this->redirect('order/view?id=' . $id);
        }

		$unitStmt = $pdo->query('SELECT pu.id, pu.product_id, pu.factor, pu.price_sell, pu.price_cost, pu.allow_fraction, pu.min_step, p.name AS product_name, p.image_path AS product_image_path, u.name AS unit_name
			FROM product_units pu
			JOIN products p ON pu.product_id = p.id
			JOIN units u ON pu.unit_id = u.id
			WHERE p.deleted_at IS NULL
			ORDER BY p.name, u.name');
        $productUnits = $unitStmt->fetchAll();

        $this->render('orders/add', [
            'title' => 'Thêm sản phẩm vào đơn',
            'order' => $order,
            'productUnits' => $productUnits,
        ]);
    }

    public function update()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('order');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if (!$id) {
            $this->redirect('order');
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND deleted_at IS NULL FOR UPDATE');
            $stmt->execute([$id]);
            $order = $stmt->fetch();

            if (!$order) {
                $pdo->rollBack();
                $this->redirect('order');
            }

            $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
            if ($orderStatus === 'completed' || $orderStatus === 'cancelled') {
                $pdo->rollBack();
                $this->setFlash('error', 'Đơn hàng đã hoàn thành hoặc đã hủy, không thể chỉnh sửa.');
                $this->redirect('order/view?id=' . $id);
            }

			$orderDate = isset($order['order_date']) ? $order['order_date'] : null;
			$orderDateInput = isset($_POST['order_date']) ? trim($_POST['order_date']) : '';
			if ($orderDateInput !== '') {
				$normalizedOrderDate = str_replace('T', ' ', $orderDateInput);
				$orderDateTs = strtotime($normalizedOrderDate);
				if ($orderDateTs !== false) {
					$orderDate = date('Y-m-d H:i:s', $orderDateTs);
				}
			}

            $manualItemsOld = [];
            $manualSellOld = 0.0;
            $manualBuyOld = 0.0;
            if (class_exists('OrderManualItem')) {
                $manualItemsOld = OrderManualItem::findByOrder($id);
                if (is_array($manualItemsOld) && !empty($manualItemsOld)) {
                    foreach ($manualItemsOld as $row) {
                        $buy = isset($row['amount_buy']) ? (float) $row['amount_buy'] : 0.0;
                        $sell = isset($row['amount_sell']) ? (float) $row['amount_sell'] : 0.0;
                        if ($buy > 0) {
                            $manualBuyOld += $buy;
                        }
                        if ($sell > 0) {
                            $manualSellOld += $sell;
                        }
                    }
                }
            }

			$rawCustomerId = isset($_POST['customer_id']) ? $_POST['customer_id'] : '';
			$customerName = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
			$customerPhone = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : '';
			$customerAddress = isset($_POST['customer_address']) ? trim($_POST['customer_address']) : '';
			$note = isset($_POST['note']) ? trim($_POST['note']) : '';

            $customerId = null;

            $candidateId = (int) $rawCustomerId;
            if ($candidateId > 0) {
                try {
                    if (class_exists('Customer')) {
                        $customer = Customer::find($candidateId);
                        if ($customer) {
                            $customerId = $candidateId;
                        }
                    }
                } catch (Exception $e) {
                    $customerId = null;
                }
            } elseif ($customerName !== '' || $customerPhone !== '' || $customerAddress !== '') {
                try {
                    if (class_exists('Customer')) {
                        $customerId = Customer::create([
                            'name' => $customerName,
                            'phone' => $customerPhone,
                            'address' => $customerAddress,
                        ]);
                    }
                } catch (Exception $e) {
                    $customerId = null;
                }
            }

            $productUnitIds = isset($_POST['product_unit_id']) ? $_POST['product_unit_id'] : [];
            $qtys = isset($_POST['qty']) ? $_POST['qty'] : [];
            $prices = isset($_POST['price']) ? $_POST['price'] : [];
            $removeExisting = isset($_POST['remove_existing']) ? $_POST['remove_existing'] : [];

            $totalAddAmount = 0;
            $totalAddCost = 0;
            $addLogItems = [];
            $totalReduceAmount = 0;
            $totalReduceCost = 0;
            $removeLogItems = [];
            $changeLogMessages = [];
            $priceLogMessages = [];

            $existingPriceChanges = isset($_POST['existing_price']) && is_array($_POST['existing_price']) ? $_POST['existing_price'] : [];

            if (is_array($removeExisting) && !empty($removeExisting)) {
                $removeIds = [];
                foreach ($removeExisting as $rid) {
                    $rid = (int) $rid;
                    if ($rid > 0) {
                        $removeIds[] = $rid;
                    }
                }

                if (!empty($removeIds)) {
                    $placeholders = implode(',', array_fill(0, count($removeIds), '?'));
                    $params = array_merge([$id], $removeIds);

                    $itemStmt = $pdo->prepare('SELECT oi.*, p.name AS product_name, u.name AS unit_name
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        JOIN product_units pu ON oi.product_unit_id = pu.id
                        JOIN units u ON pu.unit_id = u.id
                        WHERE oi.order_id = ? AND oi.id IN (' . $placeholders . ')');
                    $itemStmt->execute($params);
                    $itemsRemove = $itemStmt->fetchAll();

                    if (!empty($itemsRemove)) {
                        foreach ($itemsRemove as $row) {
                            $qtyRow = isset($row['qty']) ? (float) $row['qty'] : 0;
                            $amountRow = isset($row['amount']) ? (float) $row['amount'] : 0;
                            $priceCostRow = isset($row['price_cost']) ? (float) $row['price_cost'] : 0;
                            if ($qtyRow < 0) {
                                $qtyRow = 0;
                            }
                            if ($amountRow < 0) {
                                $amountRow = 0;
                            }
                            if ($priceCostRow < 0) {
                                $priceCostRow = 0;
                            }

                            $totalReduceAmount += $amountRow;
                            $totalReduceCost += $priceCostRow * $qtyRow;

                            $qtyText = rtrim(rtrim(number_format($qtyRow, 2, ',', ''), '0'), ',');
                            $nameSafe = htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8');
                            $unitSafe = htmlspecialchars($row['unit_name'], ENT_QUOTES, 'UTF-8');
                            $removeLogItems[] = $nameSafe . ' - ' . $unitSafe . ' x ' . $qtyText . ' (-' . number_format($amountRow, 0, ',', '.') . ' đ)';

                            $qtyFromText = $qtyText;
                            $qtyToText = '0';
                            $changeLogMessages[] = $nameSafe . ' - ' . $unitSafe . ': SL ' . $qtyFromText . ' -> ' . $qtyToText;
                        }

                        $deletePlaceholders = implode(',', array_fill(0, count($removeIds), '?'));
                        $deleteParams = $removeIds;
                        $deleteStmt = $pdo->prepare('DELETE FROM order_items WHERE order_id = ? AND id IN (' . $deletePlaceholders . ')');
                        array_unshift($deleteParams, $id);
                        $deleteStmt->execute($deleteParams);
                    }
                }
            }

            if (!empty($existingPriceChanges)) {
                $priceStmt = $pdo->prepare('SELECT oi.*, p.name AS product_name, u.name AS unit_name
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    JOIN product_units pu ON oi.product_unit_id = pu.id
                    JOIN units u ON pu.unit_id = u.id
                    WHERE oi.order_id = ? AND oi.id = ? LIMIT 1');
                $updatePriceStmt = $pdo->prepare('UPDATE order_items SET price_sell = ?, amount = ? WHERE id = ?');

                foreach ($existingPriceChanges as $itemId => $priceRaw) {
                    $itemId = (int) $itemId;
                    if ($itemId <= 0) {
                        continue;
                    }
                    $priceNumber = (float) str_replace([',', ' '], ['', ''], (string) $priceRaw);
                    if ($priceNumber < 0) {
                        $priceNumber = 0;
                    }

                    $priceStmt->execute([$id, $itemId]);
                    $row = $priceStmt->fetch();
                    if (!$row) {
                        continue;
                    }

                    $qtyRow = isset($row['qty']) ? (float) $row['qty'] : 0;
                    if ($qtyRow < 0) {
                        $qtyRow = 0;
                    }
                    $oldPriceSell = isset($row['price_sell']) ? (float) $row['price_sell'] : 0;
                    if ($oldPriceSell < 0) {
                        $oldPriceSell = 0;
                    }
                    $oldAmount = isset($row['amount']) ? (float) $row['amount'] : ($qtyRow * $oldPriceSell);
                    if ($oldAmount < 0) {
                        $oldAmount = 0;
                    }

                    $newPriceSell = $priceNumber;
                    $newAmount = $qtyRow * $newPriceSell;

                    $diff = $newAmount - $oldAmount;
                    if ($diff > 0) {
                        $totalAddAmount += $diff;
                    } elseif ($diff < 0) {
                        $totalReduceAmount += -$diff;
                    }

                    $updatePriceStmt->execute([
                        $newPriceSell,
                        $newAmount,
                        $itemId,
                    ]);

                    if ($oldPriceSell !== $newPriceSell) {
                        $nameSafePrice = htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8');
                        $unitSafePrice = htmlspecialchars($row['unit_name'], ENT_QUOTES, 'UTF-8');
                        $fromText = Money::format($oldPriceSell);
                        $toText = Money::format($newPriceSell);
                        $priceLogMessages[] = $nameSafePrice . ' - ' . $unitSafePrice . ': Giá ' . $fromText . ' -> ' . $toText;
                    }
                }
            }

            if (is_array($productUnitIds) && is_array($qtys) && !empty($productUnitIds)) {
                $insertStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_unit_id, qty, qty_base, real_weight, price_sell, price_cost, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $selectExistingStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? AND product_unit_id = ? LIMIT 1');
                $selectExistingSamePriceStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? AND product_unit_id = ? AND price_sell = ? LIMIT 1');
                $updateExistingStmt = $pdo->prepare('UPDATE order_items SET qty = ?, qty_base = ?, price_sell = ?, price_cost = ?, amount = ? WHERE id = ?');
                $modes = isset($_POST['mode']) && is_array($_POST['mode']) ? $_POST['mode'] : [];

                foreach ($productUnitIds as $index => $productUnitId) {
                    $productUnitId = (int) $productUnitId;
                    $qtyRaw = isset($qtys[$index]) ? $qtys[$index] : '';
                    $qty = (float) str_replace([',', ' '], ['', ''], $qtyRaw);

                    if ($productUnitId <= 0 || $qty <= 0) {
                        continue;
                    }

                    $mode = isset($modes[$index]) ? (string) $modes[$index] : '';
                    if ($mode !== 'new') {
                        $mode = 'delta';
                    }

                    $puStmt = $pdo->prepare('SELECT pu.*, p.id AS p_id, p.name AS product_name, u.name AS unit_name FROM product_units pu JOIN products p ON pu.product_id = p.id JOIN units u ON pu.unit_id = u.id WHERE pu.id = ? AND p.deleted_at IS NULL');
                    $puStmt->execute([$productUnitId]);
                    $productUnit = $puStmt->fetch();
                    if (!$productUnit) {
                        continue;
                    }

                    $factor = isset($productUnit['factor']) ? (float) $productUnit['factor'] : 0;
                    if ($factor <= 0) {
                        $factor = 1;
                    }

                    $qtyBaseDelta = $qty * $factor;
                    $priceSellNew = isset($productUnit['price_sell']) ? (float) $productUnit['price_sell'] : 0;
                    $priceCostNew = isset($productUnit['price_cost']) ? (float) $productUnit['price_cost'] : 0;
                    if ($priceSellNew < 0) {
                        $priceSellNew = 0;
                    }
                    if ($priceCostNew < 0) {
                        $priceCostNew = 0;
                    }

                    $priceOverrideRaw = isset($prices[$index]) ? $prices[$index] : null;
                    if ($priceOverrideRaw !== null && $priceOverrideRaw !== '') {
                        $priceOverride = (float) str_replace([',', ' '], ['', ''], (string) $priceOverrideRaw);
                        if ($priceOverride >= 0) {
                            $priceSellNew = $priceOverride;
                        }
                    }

                    $qtyText = rtrim(rtrim(number_format($qty, 2, ',', ''), '0'), ',');
                    $nameSafe = htmlspecialchars($productUnit['product_name'], ENT_QUOTES, 'UTF-8');
                    $unitSafe = htmlspecialchars($productUnit['unit_name'], ENT_QUOTES, 'UTF-8');

                    if ($mode === 'new') {
                        $selectExistingSamePriceStmt->execute([$id, $productUnitId, $priceSellNew]);
                        $existingSame = $selectExistingSamePriceStmt->fetch();

                        if ($existingSame) {
                            $existingQty = isset($existingSame['qty']) ? (float) $existingSame['qty'] : 0;
                            if ($existingQty < 0) {
                                $existingQty = 0;
                            }
                            $existingPriceSell = isset($existingSame['price_sell']) ? (float) $existingSame['price_sell'] : 0;
                            $existingPriceCost = isset($existingSame['price_cost']) ? (float) $existingSame['price_cost'] : 0;
                            if ($existingPriceSell < 0) {
                                $existingPriceSell = 0;
                            }
                            if ($existingPriceCost < 0) {
                                $existingPriceCost = 0;
                            }
                            $existingAmount = isset($existingSame['amount']) ? (float) $existingSame['amount'] : ($existingQty * $existingPriceSell);
                            if ($existingAmount < 0) {
                                $existingAmount = 0;
                            }

                            $deltaAmountMerge = $qty * $existingPriceSell;
                            $deltaCostMerge = $qty * $priceCostNew;

                            if ($deltaAmountMerge <= 0) {
                                continue;
                            }

                            $newQtyMerge = $existingQty + $qty;
                            $newQtyBaseMerge = $newQtyMerge * $factor;
                            $newAmountMerge = $existingAmount + $deltaAmountMerge;

                            $totalAddAmount += $deltaAmountMerge;
                            $totalAddCost += $deltaCostMerge;

                            $updateExistingStmt->execute([
                                $newQtyMerge,
                                $newQtyBaseMerge,
                                $existingPriceSell,
                                $existingPriceCost,
                                $newAmountMerge,
                                (int) $existingSame['id'],
                            ]);

                            $qtyFromText = rtrim(rtrim(number_format($existingQty, 2, ',', ''), '0'), ',');
                            $qtyToText = rtrim(rtrim(number_format($newQtyMerge, 2, ',', ''), '0'), ',');
                            if ($qtyFromText !== $qtyToText) {
                                $changeLogMessages[] = $nameSafe . ' - ' . $unitSafe . ': SL ' . $qtyFromText . ' -> ' . $qtyToText;
                            }

                            continue;
                        }

                        $amount = $qty * $priceSellNew;
                        if ($amount <= 0) {
                            continue;
                        }

                        $totalAddAmount += $amount;
                        $totalAddCost += $priceCostNew * $qty;

                        $insertStmt->execute([
                            $id,
                            (int) $productUnit['p_id'],
                            $productUnitId,
                            $qty,
                            $qtyBaseDelta,
                            null,
                            $priceSellNew,
                            $priceCostNew,
                            $amount,
                        ]);

                        $qtyFromText = '0';
                        $qtyToText = $qtyText;
                        $changeLogMessages[] = $nameSafe . ' - ' . $unitSafe . ': SL ' . $qtyFromText . ' -> ' . $qtyToText;

                        continue;
                    }

                    $selectExistingStmt->execute([$id, $productUnitId]);
                    $existing = $selectExistingStmt->fetch();

                    if ($existing) {
                        $existingQty = isset($existing['qty']) ? (float) $existing['qty'] : 0;
                        if ($existingQty < 0) {
                            $existingQty = 0;
                        }
                        $existingPriceSell = isset($existing['price_sell']) ? (float) $existing['price_sell'] : 0;
                        $existingPriceCost = isset($existing['price_cost']) ? (float) $existing['price_cost'] : 0;
                        if ($existingPriceSell < 0) {
                            $existingPriceSell = 0;
                        }
                        if ($existingPriceCost < 0) {
                            $existingPriceCost = 0;
                        }
                        $existingAmount = isset($existing['amount']) ? (float) $existing['amount'] : ($existingQty * $existingPriceSell);
                        if ($existingAmount < 0) {
                            $existingAmount = 0;
                        }

                        $deltaAmount = $qty * $existingPriceSell;
                        $deltaCost = $qty * $existingPriceCost;

                        if ($deltaAmount <= 0) {
                            continue;
                        }

                        $newQty = $existingQty + $qty;
                        $newQtyBase = $newQty * $factor;
                        $newAmount = $existingAmount + $deltaAmount;

                        $totalAddAmount += $deltaAmount;
                        $totalAddCost += $deltaCost;

                        $updateExistingStmt->execute([
                            $newQty,
                            $newQtyBase,
                            $existingPriceSell,
                            $existingPriceCost,
                            $newAmount,
                            (int) $existing['id'],
                        ]);

                        $qtyFromText = rtrim(rtrim(number_format($existingQty, 2, ',', ''), '0'), ',');
                        $qtyToText = rtrim(rtrim(number_format($newQty, 2, ',', ''), '0'), ',');
                        if ($qtyFromText !== $qtyToText) {
                            $changeLogMessages[] = $nameSafe . ' - ' . $unitSafe . ': SL ' . $qtyFromText . ' -> ' . $qtyToText;
                        }
                    } else {
                        $amount = $qty * $priceSellNew;
                        if ($amount <= 0) {
                            continue;
                        }

                        $totalAddAmount += $amount;
                        $totalAddCost += $priceCostNew * $qty;

                        $insertStmt->execute([
                            $id,
                            (int) $productUnit['p_id'],
                            $productUnitId,
                            $qty,
                            $qtyBaseDelta,
                            null,
                            $priceSellNew,
                            $priceCostNew,
                            $amount,
                        ]);

                        $qtyFromText = '0';
                        $qtyToText = $qtyText;
                        $changeLogMessages[] = $nameSafe . ' - ' . $unitSafe . ': SL ' . $qtyFromText . ' -> ' . $qtyToText;
                    }
                }
            }

            $totalAmountOld = isset($order['total_amount']) ? (float) $order['total_amount'] : 0;
            $totalCostOld = isset($order['total_cost']) ? (float) $order['total_cost'] : 0;
            $discountAmountOld = isset($order['discount_amount']) ? (float) $order['discount_amount'] : 0;
            if ($totalAmountOld < 0) {
                $totalAmountOld = 0;
            }
            if ($totalCostOld < 0) {
                $totalCostOld = 0;
            }
            if ($discountAmountOld < 0) {
                $discountAmountOld = 0;
            }

			$manualNames = isset($_POST['manual_item_name']) ? $_POST['manual_item_name'] : [];
			$manualUnits = isset($_POST['manual_unit_name']) ? $_POST['manual_unit_name'] : [];
			$manualQtys = isset($_POST['manual_qty']) ? $_POST['manual_qty'] : [];
			$manualPricesBuy = isset($_POST['manual_price_buy']) ? $_POST['manual_price_buy'] : [];
			$manualPricesSell = isset($_POST['manual_price_sell']) ? $_POST['manual_price_sell'] : [];

            $manualNewItems = [];
            $manualBuyNew = 0.0;
            $manualSellNew = 0.0;

            if (class_exists('ManualLineItemBuilder')) {
                $manualBuild = ManualLineItemBuilder::buildFromArrays($manualNames, $manualUnits, $manualQtys, $manualPricesBuy, $manualPricesSell);
                if (is_array($manualBuild)) {
                    $manualNewItems = isset($manualBuild['items']) && is_array($manualBuild['items']) ? $manualBuild['items'] : [];
                    $manualBuyNew = isset($manualBuild['total_buy_amount']) ? (float) $manualBuild['total_buy_amount'] : 0.0;
                    $manualSellNew = isset($manualBuild['total_sell_amount']) ? (float) $manualBuild['total_sell_amount'] : 0.0;
                    if ($manualBuyNew < 0) {
                        $manualBuyNew = 0.0;
                    }
                    if ($manualSellNew < 0) {
                        $manualSellNew = 0.0;
                    }
                }
            }

            $manualDeltaSell = $manualSellNew - $manualSellOld;
            if ($manualDeltaSell > 0) {
                $totalAddAmount += $manualDeltaSell;
            } elseif ($manualDeltaSell < 0) {
                $totalReduceAmount += -$manualDeltaSell;
            }

            $manualDeltaBuy = $manualBuyNew - $manualBuyOld;
            if ($manualDeltaBuy > 0) {
                $totalAddCost += $manualDeltaBuy;
            } elseif ($manualDeltaBuy < 0) {
                $totalReduceCost += -$manualDeltaBuy;
            }

            if (class_exists('OrderManualItem')) {
                $deleteManualStmt = $pdo->prepare('DELETE FROM order_manual_items WHERE order_id = ?');
                $deleteManualStmt->execute([$id]);
                if (!empty($manualNewItems)) {
                    foreach ($manualNewItems as $row) {
                        $row['order_id'] = $id;
                        OrderManualItem::create($row);
                    }
                }
            }

			$subtotalOld = $totalAmountOld + $discountAmountOld;
			$subtotal = $subtotalOld - $totalReduceAmount + $totalAddAmount;
			if ($subtotal < 0) {
				$subtotal = 0;
			}

			$discountType = isset($_POST['discount_type']) ? $_POST['discount_type'] : (isset($order['discount_type']) ? $order['discount_type'] : 'none');
			if (!in_array($discountType, ['none', 'fixed', 'percent'], true)) {
				$discountType = 'none';
			}
			$discountValueRaw = isset($_POST['discount_value']) ? (string) $_POST['discount_value'] : (isset($order['discount_value']) ? (string) $order['discount_value'] : '0');
			$discountValue = (float) str_replace([',', ' '], ['', ''], $discountValueRaw);
			if ($discountValue < 0) {
				$discountValue = 0;
			}
			$discountAmount = 0;
			if ($discountType === 'fixed') {
				$discountAmount = $discountValue;
			} elseif ($discountType === 'percent') {
				if ($discountValue > 100) {
					$discountValue = 100;
				}
				$discountAmount = round($subtotal * $discountValue / 100);
			}
			if ($discountAmount < 0) {
				$discountAmount = 0;
			}
			if ($discountAmount > $subtotal) {
				$discountAmount = $subtotal;
			}

			$surchargeRaw = isset($_POST['surcharge_amount']) ? (string) $_POST['surcharge_amount'] : (isset($order['surcharge_amount']) ? (string) $order['surcharge_amount'] : '0');
			$surchargeAmount = Money::parseAmount($surchargeRaw);
			if ($surchargeAmount < 0) {
				$surchargeAmount = 0;
			}
			
			$totalAmount = $subtotal - $discountAmount + $surchargeAmount;
			if ($totalAmount < 0) {
				$totalAmount = 0;
			}
			$totalAmount = Money::roundDownThousand($totalAmount);

			$totalCost = $totalCostOld - $totalReduceCost + $totalAddCost;
			if ($totalCost < 0) {
				$totalCost = 0;
			}
		
			$paidAmountOld = isset($order['paid_amount']) ? (float) $order['paid_amount'] : 0;
			if ($paidAmountOld < 0) {
				$paidAmountOld = 0;
			}
			$paidAmount = $paidAmountOld;
			if ($totalAmount <= 0) {
				$paidAmount = 0;
				$status = $order['status'];
			} else {
				if ($paidAmount > $totalAmount) {
					$paidAmount = $totalAmount;
				}
				$status = $paidAmount >= $totalAmount ? 'paid' : 'debt';
			}

            $updateStmt = $pdo->prepare('UPDATE orders SET order_date = ?, customer_id = ?, note = ?, paid_amount = ?, status = ?, total_amount = ?, total_cost = ?, discount_type = ?, discount_value = ?, discount_amount = ?, surcharge_amount = ? WHERE id = ?');
            $updateStmt->execute([
				$orderDate,
                $customerId,
                $note,
                $paidAmount,
                $status,
                $totalAmount,
                $totalCost,
				$discountType,
				$discountValue,
				$discountAmount,
				$surchargeAmount,
                $id,
            ]);

            if (class_exists('OrderLog')) {
                if (!empty($changeLogMessages)) {
                    foreach ($changeLogMessages as $message) {
                        OrderLog::create([
                            'order_id' => $id,
                            'action' => 'update_item_qty',
                            'detail' => $message,
                        ]);
                    }
                }
                if (!empty($priceLogMessages)) {
                    foreach ($priceLogMessages as $message) {
                        OrderLog::create([
                            'order_id' => $id,
                            'action' => 'update_item_price',
                            'detail' => $message,
                        ]);
                    }
                }
            }

            $pdo->commit();

            $this->setFlash('success', 'Đã cập nhật đơn hàng.');
            $this->redirect('order/view?id=' . $id);
        } catch (Exception $e) {
            $pdo->rollBack();
            $this->setFlash('error', 'Không thể cập nhật đơn hàng: ' . $e->getMessage());
            $this->redirect('order/edit?id=' . $id);
        }
    }

    public function addStore()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('order');
        }

        $this->verifyCsrfToken();

        $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        if ($orderId <= 0) {
            $this->redirect('order');
        }

        $productUnitIds = isset($_POST['product_unit_id']) ? $_POST['product_unit_id'] : [];
        $qtys = isset($_POST['qty']) ? $_POST['qty'] : [];

        if (!is_array($productUnitIds) || !is_array($qtys)) {
            $this->setFlash('error', 'Dữ liệu thêm hàng không hợp lệ.');
            $this->redirect('order/addForm?id=' . $orderId);
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $pdo->rollBack();
                $this->setFlash('error', 'Không tìm thấy đơn hàng.');
                $this->redirect('order');
            }

            $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
            if ($orderStatus === 'completed' || $orderStatus === 'cancelled') {
                $pdo->rollBack();
                $this->setFlash('error', 'Đơn hàng đã hoàn thành hoặc đã hủy, không thể thêm sản phẩm.');
                $this->redirect('order/view?id=' . $orderId);
            }

            $totalAddAmount = 0;
            $totalAddCost = 0;
            $addLogItems = [];

            $insertStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_unit_id, qty, qty_base, real_weight, price_sell, price_cost, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');

            foreach ($productUnitIds as $index => $productUnitId) {
                $productUnitId = (int) $productUnitId;
                $qtyRaw = isset($qtys[$index]) ? $qtys[$index] : '';
                $qty = (float) str_replace([',', ' '], ['', ''], $qtyRaw);

                if ($productUnitId <= 0 || $qty <= 0) {
                    continue;
                }

                $puStmt = $pdo->prepare('SELECT pu.*, p.id AS p_id, p.name AS product_name, u.name AS unit_name FROM product_units pu JOIN products p ON pu.product_id = p.id JOIN units u ON pu.unit_id = u.id WHERE pu.id = ? AND p.deleted_at IS NULL');
                $puStmt->execute([$productUnitId]);
                $productUnit = $puStmt->fetch();
                if (!$productUnit) {
                    continue;
                }

                $factor = isset($productUnit['factor']) ? (float) $productUnit['factor'] : 0;
                if ($factor <= 0) {
                    $factor = 1;
                }

                $allowFraction = isset($productUnit['allow_fraction']) ? (int) $productUnit['allow_fraction'] : 0;
                $minStep = isset($productUnit['min_step']) ? (float) $productUnit['min_step'] : 1;
                if ($minStep <= 0) {
                    $minStep = 1;
                }

                if ($allowFraction === 0) {
                    $qtyInt = (int) round($qty);
                    if (abs($qty - $qtyInt) > 0.0001) {
                        continue;
                    }
                    $qty = $qtyInt;
                } else {
                    if ($minStep > 0) {
                        $steps = floor(($qty + 0.0000001) / $minStep);
                        $qty = $steps * $minStep;
                        if ($qty <= 0) {
                            continue;
                        }
                    }
                }

                $qtyBase = $qty * $factor;
                $priceSell = isset($productUnit['price_sell']) ? (float) $productUnit['price_sell'] : 0;
                $priceCost = isset($productUnit['price_cost']) ? (float) $productUnit['price_cost'] : 0;
                if ($priceSell < 0) {
                    $priceSell = 0;
                }
                if ($priceCost < 0) {
                    $priceCost = 0;
                }

                $amount = $qty * $priceSell;
                if ($amount <= 0) {
                    continue;
                }

                $totalAddAmount += $amount;
                $totalAddCost += $priceCost * $qty;

                $qtyText = rtrim(rtrim(number_format($qty, 2, ',', ''), '0'), ',');
                $nameSafe = htmlspecialchars($productUnit['product_name'], ENT_QUOTES, 'UTF-8');
                $unitSafe = htmlspecialchars($productUnit['unit_name'], ENT_QUOTES, 'UTF-8');
                $addLogItems[] = $nameSafe . ' - ' . $unitSafe . ' x ' . $qtyText . ' (+' . number_format($amount, 0, ',', '.') . ' đ)';

                $insertStmt->execute([
                    $orderId,
                    (int) $productUnit['p_id'],
                    $productUnitId,
                    $qty,
                    $qtyBase,
                    null,
                    $priceSell,
                    $priceCost,
                    $amount,
                ]);
            }

            if ($totalAddAmount <= 0) {
                $pdo->rollBack();
                $this->setFlash('error', 'Không có mặt hàng hợp lệ để thêm.');
                $this->redirect('order/addForm?id=' . $orderId);
            }

            $totalAmountOld = (float) $order['total_amount'];
            $totalCostOld = (float) $order['total_cost'];
            $paidOld = (float) $order['paid_amount'];

            $newTotalAmount = $totalAmountOld + $totalAddAmount;
            $newTotalCost = $totalCostOld + $totalAddCost;

            $remaining = $newTotalAmount - $paidOld;
            if ($remaining < 0) {
                $remaining = 0;
            }

            $status = $remaining > 0 ? 'debt' : 'paid';

			$orderUpdateStmt = $pdo->prepare('UPDATE orders SET total_amount = ?, total_cost = ?, status = ? WHERE id = ?');
			$orderUpdateStmt->execute([
				$newTotalAmount,
				$newTotalCost,
				$status,
				$orderId,
			]);

			if (class_exists('OrderLog')) {
				OrderLog::create([
					'order_id' => $orderId,
					'action' => 'add_items',
					'detail' => [
						'type' => 'add_items',
						'context' => 'add',
						'items_count' => count($addLogItems),
						'total_amount' => $totalAddAmount,
					],
				]);
			}

            $pdo->commit();
            $this->setFlash('success', 'Đã thêm sản phẩm vào đơn hàng.');
            $this->redirect('order/view?id=' . $orderId);
        } catch (Exception $e) {
            $pdo->rollBack();
            $this->setFlash('error', 'Không thể thêm sản phẩm: ' . $e->getMessage());
            $this->redirect('order/addForm?id=' . $orderId);
        }
    }

    public function edit()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            $this->redirect('order');
        }

        $order = OrderRepository::findForEdit($id);

        if (!$order) {
            $this->redirect('order');
        }

        $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
        if ($orderStatus === 'completed' || $orderStatus === 'cancelled') {
            $this->setFlash('error', 'Đơn hàng đã hoàn thành hoặc đã hủy, không thể chỉnh sửa.');
            $this->redirect('order/view?id=' . $id);
        }

        $paymentMethod = null;
        $noteForEdit = isset($order['note']) ? (string) $order['note'] : '';
        if ($noteForEdit !== '') {
            $noteTrim = rtrim($noteForEdit);
            if (substr($noteTrim, -9) === '[TT:cash]') {
                $paymentMethod = 'cash';
                $noteForEdit = rtrim(substr($noteTrim, 0, -9));
            } elseif (substr($noteTrim, -9) === '[TT:bank]') {
                $paymentMethod = 'bank';
                $noteForEdit = rtrim(substr($noteTrim, 0, -9));
            }
        }

        $paymentStatus = $order['status'] === 'paid' ? 'pay' : 'debt';

        $pdo = Database::getInstance();

        $unitStmt = $pdo->query('SELECT pu.id, pu.product_id, pu.factor, pu.price_sell, pu.price_cost, pu.allow_fraction, pu.min_step, p.name AS product_name, p.image_path AS product_image_path, u.name AS unit_name
            FROM product_units pu
            JOIN products p ON pu.product_id = p.id
            JOIN units u ON pu.unit_id = u.id
            WHERE p.deleted_at IS NULL
            ORDER BY p.name, u.name');
        $productUnits = $unitStmt->fetchAll();

        $itemStmt = $pdo->prepare('SELECT oi.*, p.name AS product_name, p.image_path AS product_image_path, u.name AS unit_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN product_units pu ON oi.product_unit_id = pu.id
            JOIN units u ON pu.unit_id = u.id
            WHERE oi.order_id = ?
            ORDER BY oi.id');
        $itemStmt->execute([$id]);
        $items = $itemStmt->fetchAll();

        $manualItems = [];
        if (class_exists('OrderManualItem')) {
            $manualItems = OrderManualItem::findByOrder($id);
        }

        $customers = [];
        try {
            if (class_exists('Customer')) {
                $customers = Customer::all();
            }
        } catch (Exception $e) {
            $customers = [];
        }

        $this->render('orders/form', [
            'title' => 'Sửa đơn hàng',
            'order' => $order,
            'customers' => $customers,
            'paymentMethod' => $paymentMethod,
            'noteForEdit' => $noteForEdit,
            'paymentStatus' => $paymentStatus,
            'productUnits' => $productUnits,
            'items' => $items,
            'manualItems' => $manualItems,
            'detailHeader' => [
                'title' => 'Sửa đơn hàng',
                'back_url' => 'order/view?id=' . $id,
                'back_label' => 'Quay lại',
                'actions_view' => '',
            ],
        ]);
    }

    public function updateStatus()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('order');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $orderStatus = isset($_POST['order_status']) ? $_POST['order_status'] : '';

        if ($id <= 0) {
            $this->redirect('order');
        }

        $allowed = ['pending', 'completed', 'cancelled'];
        if (!in_array($orderStatus, $allowed, true)) {
            $this->setFlash('error', 'Trạng thái đơn hàng không hợp lệ.');
            $this->redirect('order/view?id=' . $id);
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $order = $stmt->fetch();

            if (!$order) {
                $pdo->rollBack();
                $this->redirect('order');
            }

            $oldStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
            $newStatus = $orderStatus;

            if ($oldStatus === $newStatus) {
                $updateStmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ?');
                $updateStmt->execute([
                    $newStatus,
                    $id,
                ]);
            } else {
                $updateStmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ?');
                $updateStmt->execute([
                    $newStatus,
                    $id,
                ]);

                $direction = 0;
                if ($oldStatus !== 'completed' && $newStatus === 'completed') {
                    $direction = -1;
                } elseif ($oldStatus === 'completed' && $newStatus !== 'completed') {
                    $direction = 1;
                }

                if ($direction !== 0) {
                    $itemStmt = $pdo->prepare('SELECT product_id, SUM(qty_base) AS qty_base FROM order_items WHERE order_id = ? GROUP BY product_id');
                    $itemStmt->execute([$id]);
                    $items = $itemStmt->fetchAll();

                    InventoryService::adjustForOrderStatusChange($items, $direction);
                }
            }

            if (class_exists('OrderLog')) {
				$statusText = 'Chưa hoàn thành';
				if ($orderStatus === 'completed') {
					$statusText = 'Đã hoàn thành';
				} elseif ($orderStatus === 'cancelled') {
					$statusText = 'Đã hủy';
				}
				OrderLog::create([
					'order_id' => $id,
					'action' => 'update_status',
					'detail' => [
						'type' => 'update_status',
						'from' => $oldStatus,
						'to' => $newStatus,
						'text' => $statusText,
					],
				]);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $this->setFlash('error', 'Không thể cập nhật trạng thái đơn hàng: ' . $e->getMessage());
            $this->redirect('order/view?id=' . $id);
        }
        $this->setFlash('success', 'Đã cập nhật trạng thái đơn hàng.');
        $this->redirect('order/view?id=' . $id);
    }

	public function paymentStore()
	{
		$this->requireLogin();

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->redirect('order');
		}

        $this->verifyCsrfToken();

		$orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
		$amount = isset($_POST['amount']) ? Money::parseAmount($_POST['amount']) : 0;
		$note = isset($_POST['note']) ? trim($_POST['note']) : '';
		$paymentMethod = isset($_POST['payment_method']) && $_POST['payment_method'] === 'bank' ? 'bank' : 'cash';

		if ($orderId <= 0 || $amount <= 0) {
			$this->setFlash('error', 'Dữ liệu thanh toán không hợp lệ.');
			$this->redirect('order');
		}

		try {
			PaymentService::recordOrderPayment($orderId, $amount, $note, $paymentMethod);
			$this->setFlash('success', 'Đã ghi nhận thanh toán.');
			$this->redirect('order/view?id=' . $orderId);
		} catch (Exception $e) {
			$this->setFlash('error', 'Không thể ghi nhận thanh toán: ' . $e->getMessage());
			$this->redirect('order/view?id=' . $orderId);
		}
	}

	public function paymentReset()
	{
		$this->requireLogin();

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->redirect('order');
		}

        $this->verifyCsrfToken();

		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		if ($id <= 0) {
			$this->redirect('order');
		}

		$pdo = Database::getInstance();
		$pdo->beginTransaction();

		try {
			$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND deleted_at IS NULL FOR UPDATE');
			$stmt->execute([$id]);
			$order = $stmt->fetch();

			if (!$order) {
				$pdo->rollBack();
				$this->setFlash('error', 'Không tìm thấy đơn hàng.');
				$this->redirect('order');
			}

			$totalAmount = isset($order['total_amount']) ? (float) $order['total_amount'] : 0.0;
			$paidOld = isset($order['paid_amount']) ? (float) $order['paid_amount'] : 0.0;
			$statusOld = isset($order['status']) ? (string) $order['status'] : 'debt';

			if ($totalAmount <= 0 || $paidOld <= 0) {
				$pdo->rollBack();
				$this->setFlash('error', 'Đơn hàng chưa có khoản thanh toán để đặt lại.');
				$this->redirect('order/view?id=' . $id);
			}

			$paymentsStmt = $pdo->prepare('SELECT id, amount FROM payments WHERE type = \'customer\' AND order_id = ?');
			$paymentsStmt->execute([$id]);
			$payments = $paymentsStmt->fetchAll();

			if (empty($payments)) {
				$pdo->rollBack();
				$this->setFlash('error', 'Đơn hàng không có lịch sử thanh toán để đặt lại.');
				$this->redirect('order/view?id=' . $id);
			}

			$sumPayments = 0.0;
			$hasNegative = false;
			foreach ($payments as $row) {
				$amountRow = isset($row['amount']) ? (float) $row['amount'] : 0.0;
				$sumPayments += $amountRow;
				if ($amountRow < 0) {
					$hasNegative = true;
				}
			}

			if ($hasNegative) {
				$pdo->rollBack();
				$this->setFlash('error', 'Đơn hàng có lịch sử hoàn trả/điều chỉnh, không thể đặt lại thanh toán tự động.');
				$this->redirect('order/view?id=' . $id);
			}

			if (abs($sumPayments - $paidOld) > 0.0001) {
				$pdo->rollBack();
				$this->setFlash('error', 'Dữ liệu thanh toán không khớp, không thể đặt lại tự động.');
				$this->redirect('order/view?id=' . $id);
			}

			$noteRaw = isset($order['note']) ? (string) $order['note'] : '';
			$noteTrim = rtrim($noteRaw);
			if ($noteTrim !== '') {
				$noteCheck = rtrim($noteTrim);
				$tail = substr($noteCheck, -9);
				if ($tail === '[TT:cash]' || $tail === '[TT:bank]') {
					$noteTrim = rtrim(substr($noteCheck, 0, -9));
				}
			}

			$newPaid = 0.0;
			$newStatus = $totalAmount > 0 ? 'debt' : $statusOld;

			$updateStmt = $pdo->prepare('UPDATE orders SET paid_amount = ?, status = ?, note = ? WHERE id = ?');
			$updateStmt->execute([
				$newPaid,
				$newStatus,
				$noteTrim,
				$id,
			]);

			$deleteStmt = $pdo->prepare('DELETE FROM payments WHERE type = \'customer\' AND order_id = ?');
			$deleteStmt->execute([$id]);

			if (class_exists('OrderLog')) {
				OrderLog::create([
					'order_id' => $id,
					'action' => 'payment_reset',
					'detail' => [
						'type' => 'payment_reset',
						'paid_before' => $paidOld,
						'paid_after' => $newPaid,
						'payments_count' => count($payments),
					],
				]);
			}

			$pdo->commit();
			$this->setFlash('success', 'Đã đặt lại thanh toán về trạng thái còn nợ.');
			$this->redirect('order/view?id=' . $id);
		} catch (Exception $e) {
			$pdo->rollBack();
			$this->setFlash('error', 'Không thể đặt lại thanh toán: ' . $e->getMessage());
			$this->redirect('order/view?id=' . $id);
		}
	}

    public function returnForm()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            $this->redirect('order');
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT o.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.id = ? AND o.deleted_at IS NULL');
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            $this->redirect('order');
        }

        $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
        if ($orderStatus === 'completed' || $orderStatus === 'cancelled') {
            $this->setFlash('error', 'Đơn hàng đã hoàn thành hoặc đã hủy, không thể trả hàng.');
            $this->redirect('order/view?id=' . $id);
        }

        $itemStmt = $pdo->prepare('SELECT oi.*, p.name AS product_name, u.name AS unit_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN product_units pu ON oi.product_unit_id = pu.id
            JOIN units u ON pu.unit_id = u.id
            WHERE oi.order_id = ?
            ORDER BY oi.id');
        $itemStmt->execute([$id]);
        $items = $itemStmt->fetchAll();

        if (empty($items)) {
            $this->setFlash('error', 'Đơn hàng không có mặt hàng nào để trả.');
            $this->redirect('order/view?id=' . $id);
        }

        $this->render('orders/return', [
            'title' => 'Trả hàng đơn ' . $order['order_code'],
            'order' => $order,
            'items' => $items,
            'detailHeader' => [
                'title' => 'Trả hàng đơn ' . $order['order_code'],
                'back_url' => 'order/view?id=' . $id,
                'back_label' => 'Quay lại',
                'actions_view' => '',
            ],
        ]);
    }

    public function returnStore()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('order');
        }

        $this->verifyCsrfToken();

        $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        if ($orderId <= 0) {
            $this->redirect('order');
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND deleted_at IS NULL FOR UPDATE');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $pdo->rollBack();
                $this->setFlash('error', 'Không tìm thấy đơn hàng.');
                $this->redirect('order');
            }

            $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
            if ($orderStatus === 'completed' || $orderStatus === 'cancelled') {
                $pdo->rollBack();
                $this->setFlash('error', 'Đơn hàng đã hoàn thành hoặc đã hủy, không thể trả hàng.');
                $this->redirect('order/view?id=' . $orderId);
            }

            $itemStmt = $pdo->prepare('SELECT oi.*, p.name AS product_name, u.name AS unit_name
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN product_units pu ON oi.product_unit_id = pu.id
                JOIN units u ON pu.unit_id = u.id
                WHERE oi.order_id = ?
                ORDER BY oi.id');
            $itemStmt->execute([$orderId]);
            $items = $itemStmt->fetchAll();

            if (empty($items)) {
                $pdo->rollBack();
                $this->setFlash('error', 'Đơn hàng không có mặt hàng nào để trả.');
                $this->redirect('order/view?id=' . $orderId);
            }

            $returnAll = isset($_POST['return_all']) && $_POST['return_all'] === '1';
            $returnQtyInput = isset($_POST['return_qty']) && is_array($_POST['return_qty']) ? $_POST['return_qty'] : [];

            $totalReduceAmount = 0;
            $totalReduceCost = 0;
            $updates = [];
            $deletes = [];
            $returnLogItems = [];
            $returnChangeMessages = [];

            foreach ($items as $item) {
                $itemId = (int) $item['id'];
                $originalQty = (float) $item['qty'];
                $priceSell = (float) $item['price_sell'];
                $priceCost = (float) $item['price_cost'];

                if ($originalQty <= 0) {
                    continue;
                }

                if ($returnAll) {
                    $returnQty = $originalQty;
                } else {
                    $raw = isset($returnQtyInput[$itemId]) ? $returnQtyInput[$itemId] : '';
                    $returnQty = (float) str_replace([',', ' '], ['', ''], $raw);
                    if ($returnQty <= 0) {
                        continue;
                    }
                    if ($returnQty > $originalQty) {
                        $returnQty = $originalQty;
                    }
                }

                if ($returnQty <= 0) {
                    continue;
                }

                $newQty = $originalQty - $returnQty;

                $originalQtyBase = (float) $item['qty_base'];
                $basePerUnit = $originalQty > 0 ? ($originalQtyBase / $originalQty) : 0;
                $newQtyBase = $newQty > 0 ? ($basePerUnit * $newQty) : 0;

                $reduceAmount = $returnQty * $priceSell;
                $reduceCost = $returnQty * $priceCost;
                $totalReduceAmount += $reduceAmount;
                $totalReduceCost += $reduceCost;

                $qtyText = rtrim(rtrim(number_format($returnQty, 2, ',', ''), '0'), ',');
                $nameSafe = htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8');
                $unitSafe = htmlspecialchars($item['unit_name'], ENT_QUOTES, 'UTF-8');
                $returnLogItems[] = $nameSafe . ' - ' . $unitSafe . ' x ' . $qtyText . ' (-' . number_format($reduceAmount, 0, ',', '.') . ' đ)';

                $qtyFromText = rtrim(rtrim(number_format($originalQty, 2, ',', ''), '0'), ',');
                $qtyToText = rtrim(rtrim(number_format($newQty, 2, ',', ''), '0'), ',');
                if ($qtyFromText !== $qtyToText) {
                    $returnChangeMessages[] = $nameSafe . ' - ' . $unitSafe . ': SL ' . $qtyFromText . ' -> ' . $qtyToText;
                }

                if ($newQty > 0) {
                    $newAmount = $newQty * $priceSell;
                    $updates[] = [
                        'id' => $itemId,
                        'qty' => $newQty,
                        'qty_base' => $newQtyBase,
                        'amount' => $newAmount,
                    ];
                } else {
                    $deletes[] = $itemId;
                }
            }

            if ($totalReduceAmount <= 0) {
                $pdo->rollBack();
                $this->setFlash('error', 'Không có số lượng trả hợp lệ.');
                $this->redirect('order/returnForm?id=' . $orderId);
            }

            if (!empty($updates)) {
                $updateStmt = $pdo->prepare('UPDATE order_items SET qty = ?, qty_base = ?, amount = ? WHERE id = ?');
                foreach ($updates as $row) {
                    $updateStmt->execute([
                        $row['qty'],
                        $row['qty_base'],
                        $row['amount'],
                        $row['id'],
                    ]);
                }
            }

            if (!empty($deletes)) {
                $placeholders = implode(',', array_fill(0, count($deletes), '?'));
                $deleteStmt = $pdo->prepare('DELETE FROM order_items WHERE id IN (' . $placeholders . ')');
                $deleteStmt->execute($deletes);
            }

            $totalAmountOld = (float) $order['total_amount'];
            $totalCostOld = (float) $order['total_cost'];
            $paidOld = (float) $order['paid_amount'];
			$discountTypeOld = isset($order['discount_type']) ? $order['discount_type'] : 'none';
			$discountValueOld = isset($order['discount_value']) ? (float) $order['discount_value'] : 0;
			$discountAmountOld = isset($order['discount_amount']) ? (float) $order['discount_amount'] : 0;

			if ($totalAmountOld < 0) {
				$totalAmountOld = 0;
			}
			if ($totalCostOld < 0) {
				$totalCostOld = 0;
			}
			if ($discountAmountOld < 0) {
				$discountAmountOld = 0;
			}

			$subtotalOld = $totalAmountOld + $discountAmountOld;
			$subtotalNew = $subtotalOld - $totalReduceAmount;
			if ($subtotalNew < 0) {
				$subtotalNew = 0;
			}

			$discountType = $discountTypeOld;
			if (!in_array($discountType, ['none', 'fixed', 'percent'], true)) {
				$discountType = 'none';
			}
			$discountValue = $discountValueOld;
			if ($discountValue < 0) {
				$discountValue = 0;
			}
			$discountAmountNew = 0;
			if ($discountType === 'fixed') {
				$discountAmountNew = $discountValue;
			} elseif ($discountType === 'percent') {
				if ($discountValue > 100) {
					$discountValue = 100;
				}
				$discountAmountNew = round($subtotalNew * $discountValue / 100);
			}
			if ($discountAmountNew < 0) {
				$discountAmountNew = 0;
			}
			if ($discountAmountNew > $subtotalNew) {
				$discountAmountNew = $subtotalNew;
			}

			$surchargeAmountOld = isset($order['surcharge_amount']) ? (float) $order['surcharge_amount'] : 0;
			if ($surchargeAmountOld < 0) {
				$surchargeAmountOld = 0;
			}
			$surchargeAmountNew = $surchargeAmountOld;
			
            $newTotalAmount = $subtotalNew - $discountAmountNew + $surchargeAmountNew;
            $newTotalCost = $totalCostOld - $totalReduceCost;
            if ($newTotalAmount < 0) {
                $newTotalAmount = 0;
            }
            if ($newTotalCost < 0) {
                $newTotalCost = 0;
            }

            $newPaid = $paidOld;
            $refundAmount = 0;
            $remaining = $newTotalAmount - $paidOld;

            if ($remaining < 0) {
                $refundAmount = -$remaining;
                $newPaid = $paidOld - $refundAmount;
                if ($newPaid < 0) {
                    $newPaid = 0;
                }
                $remaining = 0;
            }

            $status = $remaining > 0 ? 'debt' : 'paid';

            $orderUpdateStmt = $pdo->prepare('UPDATE orders SET total_amount = ?, total_cost = ?, paid_amount = ?, status = ?, discount_type = ?, discount_value = ?, discount_amount = ?, surcharge_amount = ? WHERE id = ?');
            $orderUpdateStmt->execute([
                $newTotalAmount,
                $newTotalCost,
                $newPaid,
                $status,
				$discountType,
				$discountValue,
				$discountAmountNew,
				$surchargeAmountNew,
                $orderId,
            ]);

            if ($refundAmount > 0) {
                Payment::create([
                    'type' => 'customer',
                    'customer_id' => $order['customer_id'] ?: null,
                    'supplier_id' => null,
                    'order_id' => $orderId,
                    'purchase_id' => null,
                    'amount' => -$refundAmount,
                    'note' => 'Hoàn trả hàng đơn ' . $order['order_code'],
                ]);
            }

            if (class_exists('OrderLog')) {
                OrderLog::create([
                    'order_id' => $orderId,
                    'action' => 'return_items',
                    'detail' => [
                        'type' => 'return_items',
                        'items_count' => count($returnLogItems),
                        'total_reduce_amount' => $totalReduceAmount,
                        'refund_amount' => $refundAmount,
                    ],
                ]);

                if (!empty($returnChangeMessages)) {
                    foreach ($returnChangeMessages as $message) {
                        OrderLog::create([
                            'order_id' => $orderId,
                            'action' => 'update_item_qty',
                            'detail' => $message,
                        ]);
                    }
                }
            }

            $pdo->commit();
            $this->setFlash('success', 'Đã ghi nhận trả hàng cho đơn #' . $orderId . '.');
            $this->redirect('order/view?id=' . $orderId);
        } catch (Exception $e) {
            $pdo->rollBack();
			$this->setFlash('error', 'Không thể ghi nhận trả hàng: ' . $e->getMessage());
			$this->redirect('order/view?id=' . $orderId);
		}
	}
}
