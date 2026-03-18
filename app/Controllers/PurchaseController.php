<?php

class PurchaseController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $pdo = Database::getInstance();
        $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
        $fromDate = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
        $toDate = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
        $supplierId = isset($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : 0;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $perPage = 20;

        $where = [];
        $params = [];

        if ($keyword !== '') {
            $where[] = '(p.purchase_code LIKE ? OR s.name LIKE ? OR s.phone LIKE ?)';
            $kw = '%' . $keyword . '%';
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }

        if ($fromDate !== '') {
            $where[] = 'p.purchase_date >= ?';
            $params[] = $fromDate . ' 00:00:00';
        }
        if ($toDate !== '') {
            $where[] = 'p.purchase_date <= ?';
            $params[] = $toDate . ' 23:59:59';
        }

        if ($supplierId > 0) {
            $where[] = 'p.supplier_id = ?';
            $params[] = $supplierId;
        }

        $whereSql = '';
        if (!empty($where)) {
            $whereSql = 'WHERE ' . implode(' AND ', $where);
        }

        $countSql = 'SELECT COUNT(*) FROM purchases p JOIN suppliers s ON p.supplier_id = s.id ' . $whereSql;
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

        $sql = 'SELECT p.*, s.name AS supplier_name, s.phone AS supplier_phone
                FROM purchases p
                JOIN suppliers s ON p.supplier_id = s.id
                ' . $whereSql . '
                ORDER BY p.purchase_date DESC, p.id DESC
                LIMIT ? OFFSET ?';
        $stmt = $pdo->prepare($sql);
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $paramIndex = count($params) + 1;
        $stmt->bindValue($paramIndex, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex + 1, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $purchases = $stmt->fetchAll();

        $suppliers = [];
        if (class_exists('Supplier')) {
            $suppliers = Supplier::all();
        }

        $this->render('purchases/index', [
            'title' => 'Phiếu nhập hàng',
            'purchases' => $purchases,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'suppliers' => $suppliers,
            'supplierId' => $supplierId,
			'listHeader' => [
                'title' => 'Phiếu nhập hàng',
                'subtitle' => 'Quản lý danh sách phiếu nhập hàng và công nợ nhập.',
                'primary' => [
                    'url' => 'purchase/create',
                    'tooltip' => 'Tạo phiếu nhập hàng',
                ],
                'sticky' => true,
                'form' => [
                    'method' => 'get',
                    'action' => '',
                    'attrs' => [],
                ],
                'search' => [
                    'param' => 'q',
                    'placeholder' => 'Tìm theo mã phiếu, nhà cung cấp, SĐT...',
                    'value' => $keyword,
                    'clear_url' => 'purchase',
                    'show_clear' => $keyword !== '',
                ],
                'hidden' => [],
                'extra_buttons' => [
                    [
                        'icon' => 'filter',
                        'attrs' => [
                            'data-purchase-advanced-filter-open' => '1',
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
        if ($id <= 0) {
            $this->redirect('purchase');
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT p.*, s.name AS supplier_name, s.phone AS supplier_phone, s.address AS supplier_address
            FROM purchases p
            JOIN suppliers s ON p.supplier_id = s.id
            WHERE p.id = ?');
        $stmt->execute([$id]);
        $purchase = $stmt->fetch();

        if (!$purchase) {
            $this->redirect('purchase');
        }

        $itemStmt = $pdo->prepare('SELECT pi.*, pr.name AS product_name, u.name AS unit_name
            FROM purchase_items pi
            JOIN products pr ON pi.product_id = pr.id
            JOIN product_units pu ON pi.product_unit_id = pu.id
            JOIN units u ON pu.unit_id = u.id
            WHERE pi.purchase_id = ?
            ORDER BY pi.id');
        $itemStmt->execute([$id]);
        $items = $itemStmt->fetchAll();

        $paymentStmt = $pdo->prepare('SELECT * FROM payments WHERE type = \'supplier\' AND purchase_id = ? ORDER BY paid_at DESC, id DESC');
        $paymentStmt->execute([$id]);
        $payments = $paymentStmt->fetchAll();

        $logs = [];
        if (class_exists('PurchaseLog')) {
            $logs = PurchaseLog::findByPurchase($id);
        }

        $this->render('purchases/view', [
            'title' => 'Chi tiết phiếu nhập',
            'purchase' => $purchase,
            'items' => $items,
            'payments' => $payments,
            'logs' => $logs,
            'detailHeader' => [
                'title' => 'Chi tiết phiếu nhập',
                'back_url' => 'purchase',
                'back_label' => 'Quay lại',
                'actions_view' => 'purchases/_detail_header_actions',
            ],
        ]);
    }

    public function create()
    {
        $this->requireLogin();

        $suppliers = [];
        if (class_exists('Supplier')) {
            $suppliers = Supplier::all();
        }

        $pdo = Database::getInstance();
        $unitStmt = $pdo->query('SELECT pu.id, pu.product_id, pu.factor, pu.price_cost, p.name AS product_name, p.image_path AS product_image_path, u.name AS unit_name
            FROM product_units pu
            JOIN products p ON pu.product_id = p.id
            JOIN units u ON pu.unit_id = u.id
            WHERE p.deleted_at IS NULL
            ORDER BY p.name, u.name');
        $productUnits = $unitStmt->fetchAll();

        $this->render('purchases/form', [
            'title' => 'Tạo phiếu nhập hàng',
            'suppliers' => $suppliers,
            'productUnits' => $productUnits,
            'detailHeader' => [
                'title' => 'Tạo phiếu nhập hàng',
                'back_url' => 'purchase',
                'back_label' => 'Quay lại',
                'actions_view' => '',
            ],
        ]);
    }

    public function edit()
    {
        $this->requireLogin();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            $this->redirect('purchase');
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = ?');
        $stmt->execute([$id]);
        $purchase = $stmt->fetch();

        if (!$purchase) {
            $this->redirect('purchase');
        }

        $suppliers = [];
        if (class_exists('Supplier')) {
            $suppliers = Supplier::all();
        }

        $unitStmt = $pdo->query('SELECT pu.id, pu.product_id, pu.factor, pu.price_cost, p.name AS product_name, u.name AS unit_name
            FROM product_units pu
            JOIN products p ON pu.product_id = p.id
            JOIN units u ON pu.unit_id = u.id
            WHERE p.deleted_at IS NULL
            ORDER BY p.name, u.name');
        $productUnits = $unitStmt->fetchAll();

        $itemStmt = $pdo->prepare('SELECT pi.*, pr.name AS product_name, u.name AS unit_name
            FROM purchase_items pi
            JOIN products pr ON pi.product_id = pr.id
            JOIN product_units pu ON pi.product_unit_id = pu.id
            JOIN units u ON pu.unit_id = u.id
            WHERE pi.purchase_id = ?
            ORDER BY pi.id');
        $itemStmt->execute([$id]);
        $items = $itemStmt->fetchAll();

        $paymentMethod = null;
        $noteForEdit = isset($purchase['note']) ? (string) $purchase['note'] : '';
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

        $this->render('purchases/form', [
            'title' => 'Chỉnh sửa phiếu nhập',
            'suppliers' => $suppliers,
            'productUnits' => $productUnits,
            'purchase' => $purchase,
            'items' => $items,
            'paymentMethod' => $paymentMethod,
            'noteForEdit' => $noteForEdit,
            'detailHeader' => [
                'title' => 'Chỉnh sửa phiếu nhập',
                'back_url' => 'purchase/view?id=' . $id,
                'back_label' => 'Quay lại',
                'actions_view' => '',
            ],
        ]);
    }

    public function store()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('purchase');
        }

        $this->verifyCsrfToken();

        $supplierId = isset($_POST['supplier_id']) ? (int) $_POST['supplier_id'] : 0;
        $note = isset($_POST['note']) ? trim($_POST['note']) : '';
        $paymentMethod = isset($_POST['payment_method']) && $_POST['payment_method'] === 'bank' ? 'bank' : 'cash';

        if ($supplierId <= 0) {
            $this->setFlash('error', 'Vui lòng chọn nhà cung cấp.');
            $this->redirect('purchase/create');
        }

        $productUnitIds = isset($_POST['product_unit_id']) ? $_POST['product_unit_id'] : [];
        $qtys = isset($_POST['qty']) ? $_POST['qty'] : [];
        $priceCosts = isset($_POST['price_cost']) ? $_POST['price_cost'] : [];
        $amountInputs = isset($_POST['amount']) ? $_POST['amount'] : [];
        $updateCostFlags = isset($_POST['update_cost']) && is_array($_POST['update_cost']) ? $_POST['update_cost'] : [];

        if (!is_array($productUnitIds) || !is_array($qtys) || !is_array($priceCosts) || (!empty($amountInputs) && !is_array($amountInputs))) {
            $this->setFlash('error', 'Dữ liệu mặt hàng không hợp lệ.');
            $this->redirect('purchase/create');
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $totalAmount = 0;
            $itemsPrepared = [];

            $puStmt = $pdo->prepare('SELECT pu.*, p.id AS p_id FROM product_units pu JOIN products p ON pu.product_id = p.id WHERE pu.id = ? AND p.deleted_at IS NULL');
            $updateCostByUnit = [];

            foreach ($productUnitIds as $index => $productUnitId) {
                $productUnitId = (int) $productUnitId;
                $qtyRaw = isset($qtys[$index]) ? $qtys[$index] : '';
                $qty = (float) str_replace([',', ' '], ['', ''], $qtyRaw);
                $priceCostRaw = isset($priceCosts[$index]) ? $priceCosts[$index] : '';
                $priceCost = Money::parseAmount($priceCostRaw);
                $amountRaw = isset($amountInputs[$index]) ? $amountInputs[$index] : '';
                $amountInput = Money::parseAmount($amountRaw);
                $updateCostFlag = !empty($updateCostFlags[$index]);

                if ($productUnitId <= 0 || $qty <= 0) {
                    continue;
                }

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

                if ($amountInput > 0 && $qty > 0) {
                    $amount = $amountInput;
                    $priceCost = $amount / $qty;
                } else {
                    if ($priceCost <= 0) {
                        $priceCost = isset($productUnit['price_cost']) ? (float) $productUnit['price_cost'] : 0;
                    }

                    if ($priceCost < 0) {
                        $priceCost = 0;
                    }

                    $amount = $qty * $priceCost;
                }

                $qtyBase = $qty * $factor;

                if ($amount <= 0) {
                    continue;
                }

                $totalAmount += $amount;

                $itemsPrepared[] = [
                    'product_id' => (int) $productUnit['p_id'],
                    'product_unit_id' => $productUnitId,
                    'qty' => $qty,
                    'qty_base' => $qtyBase,
                    'price_cost' => $priceCost,
                    'amount' => $amount,
                ];

                if ($updateCostFlag && $priceCost > 0) {
                    $updateCostByUnit[$productUnitId] = (float) $priceCost;
                }
            }

            if (empty($itemsPrepared)) {
                $pdo->rollBack();
                $this->setFlash('error', 'Vui lòng nhập ít nhất một mặt hàng hợp lệ.');
                $this->redirect('purchase/create');
            }

            $paidAmount = isset($_POST['paid_amount']) ? Money::parseAmount($_POST['paid_amount']) : 0;
            if ($paidAmount < 0) {
                $paidAmount = 0;
            }
            if ($paidAmount > $totalAmount) {
                $paidAmount = $totalAmount;
            }

            $status = $paidAmount >= $totalAmount ? 'paid' : 'debt';

            $purchaseNoteForSave = $note;
            if ($paidAmount > 0) {
                $purchaseNoteTrim = rtrim($purchaseNoteForSave);
                if ($purchaseNoteTrim !== '') {
                    $noteCheck = rtrim($purchaseNoteTrim);
                    $tail = substr($noteCheck, -9);
                    if ($tail === '[TT:cash]' || $tail === '[TT:bank]') {
                        $purchaseNoteTrim = rtrim(substr($noteCheck, 0, -9));
                    }
                }
                $methodTag = $paymentMethod === 'bank' ? '[TT:bank]' : '[TT:cash]';
                $purchaseNoteWithMethod = $purchaseNoteTrim;
                if ($purchaseNoteWithMethod === '') {
                    $purchaseNoteWithMethod = $methodTag;
                } else {
                    $purchaseNoteWithMethod .= ' ' . $methodTag;
                }
                $purchaseNoteForSave = $purchaseNoteWithMethod;
            }

            $purchaseId = Purchase::create([
                'supplier_id' => $supplierId,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'status' => $status,
                'note' => $purchaseNoteForSave,
            ]);

            foreach ($itemsPrepared as $row) {
                $row['purchase_id'] = $purchaseId;
                PurchaseItem::create($row);

                if (class_exists('Inventory')) {
                    Inventory::adjust($row['product_id'], $row['qty_base']);
                }
            }

            if ($paidAmount > 0) {
                $methodText = $paymentMethod === 'bank' ? 'Chuyển khoản' : 'Tiền mặt';
                $paymentNote = $note;
                if ($paymentNote === '') {
                    $paymentNote = 'Thanh toán ' . $methodText;
                } else {
                    $paymentNote .= ' (Thanh toán ' . $methodText . ')';
                }

                Payment::create([
                    'type' => 'supplier',
                    'customer_id' => null,
                    'supplier_id' => $supplierId,
                    'order_id' => null,
                    'purchase_id' => $purchaseId,
                    'amount' => $paidAmount,
                    'note' => $paymentNote,
                ]);
            }

            if (class_exists('PurchaseLog')) {
                PurchaseLog::create([
                    'purchase_id' => $purchaseId,
                    'action' => 'create',
                    'detail' => [
                        'type' => 'create',
                        'items_count' => count($itemsPrepared),
                        'total_amount' => $totalAmount,
                        'paid_amount' => $paidAmount,
                        'status' => $status,
                        'payment_method' => $paymentMethod,
                    ],
                ]);
            }

            if (!empty($updateCostByUnit)) {
                $updateCostStmt = $pdo->prepare('UPDATE product_units SET price_cost = ? WHERE id = ?');
                foreach ($updateCostByUnit as $unitId => $priceCostValue) {
                    $updateCostStmt->execute([(int) round($priceCostValue), (int) $unitId]);
                }
            }

            $pdo->commit();
            ReportService::clearReportCache();
            $this->setFlash('success', 'Đã tạo phiếu nhập hàng #' . $purchaseId . '.');
            $this->redirect('purchase');
        } catch (Exception $e) {
            $pdo->rollBack();
            $this->setFlash('error', 'Không thể tạo phiếu nhập hàng: ' . $e->getMessage());
            $this->redirect('purchase/create');
		}
	}

    public function update()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('purchase');
        }

        $this->verifyCsrfToken();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $this->redirect('purchase');
        }

        $supplierId = isset($_POST['supplier_id']) ? (int) $_POST['supplier_id'] : 0;
        $note = isset($_POST['note']) ? trim($_POST['note']) : '';
        $paymentMethod = isset($_POST['payment_method']) && $_POST['payment_method'] === 'bank' ? 'bank' : 'cash';

        if ($supplierId <= 0) {
            $this->setFlash('error', 'Vui lòng chọn nhà cung cấp.');
            $this->redirect('purchase/edit?id=' . $id);
        }

        $productUnitIds = isset($_POST['product_unit_id']) ? $_POST['product_unit_id'] : [];
        $qtys = isset($_POST['qty']) ? $_POST['qty'] : [];
        $priceCosts = isset($_POST['price_cost']) ? $_POST['price_cost'] : [];
        $amountInputs = isset($_POST['amount']) ? $_POST['amount'] : [];
        $updateCostFlags = isset($_POST['update_cost']) && is_array($_POST['update_cost']) ? $_POST['update_cost'] : [];

        if (!is_array($productUnitIds) || !is_array($qtys) || !is_array($priceCosts) || (!empty($amountInputs) && !is_array($amountInputs))) {
            $this->setFlash('error', 'Dữ liệu mặt hàng không hợp lệ.');
            $this->redirect('purchase/edit?id=' . $id);
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $purchase = $stmt->fetch();

            if (!$purchase) {
                $pdo->rollBack();
                $this->setFlash('error', 'Không tìm thấy phiếu nhập.');
                $this->redirect('purchase');
            }

            $totalAmount = 0;
            $itemsPrepared = [];

            $puStmt = $pdo->prepare('SELECT pu.*, p.id AS p_id FROM product_units pu JOIN products p ON pu.product_id = p.id WHERE pu.id = ?');
            $updateCostByUnit = [];

            foreach ($productUnitIds as $index => $productUnitId) {
                $productUnitId = (int) $productUnitId;
                $qtyRaw = isset($qtys[$index]) ? $qtys[$index] : '';
                $qty = (float) str_replace([',', ' '], ['', ''], $qtyRaw);
                $priceCostRaw = isset($priceCosts[$index]) ? $priceCosts[$index] : '';
                $priceCost = Money::parseAmount($priceCostRaw);
                $amountRaw = isset($amountInputs[$index]) ? $amountInputs[$index] : '';
                $amountInput = Money::parseAmount($amountRaw);

                $updateCostFlag = !empty($updateCostFlags[$index]);

                if ($productUnitId <= 0 || $qty <= 0) {
                    continue;
                }

                $puStmt->execute([$productUnitId]);
                $productUnit = $puStmt->fetch();
                if (!$productUnit) {
                    continue;
                }

                $factor = isset($productUnit['factor']) ? (float) $productUnit['factor'] : 0;
                if ($factor <= 0) {
                    $factor = 1;
                }

                $updateCostFlag = !empty($updateCostFlags[$index]);

                if ($amountInput > 0 && $qty > 0) {
                    $amount = $amountInput;
                    $priceCost = $amount / $qty;
                } else {
                    if ($priceCost <= 0) {
                        $priceCost = isset($productUnit['price_cost']) ? (float) $productUnit['price_cost'] : 0;
                    }

                    if ($priceCost < 0) {
                        $priceCost = 0;
                    }

                    $amount = $qty * $priceCost;
                }

                $qtyBase = $qty * $factor;

                if ($amount <= 0) {
                    continue;
                }

                $totalAmount += $amount;

                $itemsPrepared[] = [
                    'product_id' => (int) $productUnit['p_id'],
                    'product_unit_id' => $productUnitId,
                    'qty' => $qty,
                    'qty_base' => $qtyBase,
                    'price_cost' => $priceCost,
                    'amount' => $amount,
                ];

                if ($updateCostFlag && $priceCost > 0) {
                    $updateCostByUnit[$productUnitId] = (float) $priceCost;
                }
            }

            if (empty($itemsPrepared)) {
                $pdo->rollBack();
                $this->setFlash('error', 'Vui lòng nhập ít nhất một mặt hàng hợp lệ.');
                $this->redirect('purchase/edit?id=' . $id);
            }

            $paidAmount = isset($purchase['paid_amount']) ? (float) $purchase['paid_amount'] : 0.0;
            if ($paidAmount < 0) {
                $paidAmount = 0.0;
            }
            if ($paidAmount > $totalAmount) {
                $paidAmount = $totalAmount;
            }

            $status = $paidAmount >= $totalAmount ? 'paid' : 'debt';

            $oldItemsStmt = $pdo->prepare('SELECT * FROM purchase_items WHERE purchase_id = ?');
            $oldItemsStmt->execute([$id]);
            $oldItems = $oldItemsStmt->fetchAll();

            InventoryService::rollbackOldPurchaseItems($oldItems);

            $deleteItemsStmt = $pdo->prepare('DELETE FROM purchase_items WHERE purchase_id = ?');
            $deleteItemsStmt->execute([$id]);

            $purchaseNoteForSave = $note;
            if ($paidAmount > 0) {
                $purchaseNoteTrim = rtrim($purchaseNoteForSave);
                if ($purchaseNoteTrim !== '') {
                    $noteCheck = rtrim($purchaseNoteTrim);
                    $tail = substr($noteCheck, -9);
                    if ($tail === '[TT:cash]' || $tail === '[TT:bank]') {
                        $purchaseNoteTrim = rtrim(substr($noteCheck, 0, -9));
                    }
                }
                $methodTag = $paymentMethod === 'bank' ? '[TT:bank]' : '[TT:cash]';
                $purchaseNoteWithMethod = $purchaseNoteTrim;
                if ($purchaseNoteWithMethod === '') {
                    $purchaseNoteWithMethod = $methodTag;
                } else {
                    $purchaseNoteWithMethod .= ' ' . $methodTag;
                }
                $purchaseNoteForSave = $purchaseNoteWithMethod;
            }

            $updatePurchaseStmt = $pdo->prepare('UPDATE purchases SET supplier_id = ?, total_amount = ?, paid_amount = ?, status = ?, note = ? WHERE id = ?');
            $updatePurchaseStmt->execute([
                $supplierId,
                $totalAmount,
                $paidAmount,
                $status,
                $purchaseNoteForSave,
                $id,
            ]);

            foreach ($itemsPrepared as $row) {
                $row['purchase_id'] = $id;
                PurchaseItem::create($row);
            }

            InventoryService::adjustForNewPurchaseItems($itemsPrepared);

            if (!empty($updateCostByUnit)) {
                $updateCostStmt = $pdo->prepare('UPDATE product_units SET price_cost = ? WHERE id = ?');
                foreach ($updateCostByUnit as $unitId => $priceCostValue) {
                    $updateCostStmt->execute([(int) round($priceCostValue), (int) $unitId]);
                }
            }

            if (class_exists('PurchaseLog')) {
                $oldTotal = isset($purchase['total_amount']) ? (float) $purchase['total_amount'] : 0.0;
                $oldPaid = isset($purchase['paid_amount']) ? (float) $purchase['paid_amount'] : 0.0;
                $oldStatus = isset($purchase['status']) ? $purchase['status'] : '';

                PurchaseLog::create([
                    'purchase_id' => $id,
                    'action' => 'update',
                    'detail' => [
                        'type' => 'update',
                        'old_total' => $oldTotal,
                        'new_total' => $totalAmount,
                        'old_paid' => $oldPaid,
                        'new_paid' => $paidAmount,
                        'old_status' => $oldStatus,
                        'new_status' => $status,
                        'items_count' => count($itemsPrepared),
                    ],
                ]);
            }

            $pdo->commit();
            ReportService::clearReportCache();
            $this->setFlash('success', 'Đã cập nhật phiếu nhập hàng #' . $id . '.');
            $this->redirect('purchase/view?id=' . $id);
        } catch (Exception $e) {
            $pdo->rollBack();
            $this->setFlash('error', 'Không thể cập nhật phiếu nhập hàng: ' . $e->getMessage());
            $this->redirect('purchase/edit?id=' . $id);
        }
    }

	public function paymentStore()
	{
		$this->requireLogin();

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			$this->redirect('purchase');
		}

        $this->verifyCsrfToken();

        $purchaseId = isset($_POST['purchase_id']) ? (int) $_POST['purchase_id'] : 0;
        $amount = isset($_POST['amount']) ? Money::parseAmount($_POST['amount']) : 0;
        $note = isset($_POST['note']) ? trim($_POST['note']) : '';
        $paymentMethod = isset($_POST['payment_method']) && $_POST['payment_method'] === 'bank' ? 'bank' : 'cash';

        if ($purchaseId <= 0 || $amount <= 0) {
            $this->setFlash('error', 'Dữ liệu thanh toán không hợp lệ.');
            $this->redirect('purchase');
        }

        try {
            PaymentService::recordPurchasePayment($purchaseId, $amount, $note, $paymentMethod);
            $this->setFlash('success', 'Đã ghi nhận thanh toán phiếu nhập.');
            $this->redirect('purchase/view?id=' . $purchaseId);
        } catch (Exception $e) {
            $this->setFlash('error', 'Không thể ghi nhận thanh toán: ' . $e->getMessage());
            $this->redirect('purchase/view?id=' . $purchaseId);
        }
    }
}
