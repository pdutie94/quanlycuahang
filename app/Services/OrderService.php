<?php

class OrderService
{
    public static function getOrderListData(array $queryParams, int $perPage = 20): array
    {
        $filters = self::normalizeListFilters($queryParams);
        $totalCount = OrderRepository::countFiltered($filters);
        $pagination = self::resolvePagination($filters['page'], $totalCount, $perPage);
        $orders = OrderRepository::paginateFiltered($filters, $pagination['perPage'], $pagination['offset']);

        return [
            'orders' => $orders,
            'keyword' => $filters['keyword'],
            'status' => $filters['status'],
            'orderStatus' => $filters['orderStatus'],
            'fromDate' => $filters['fromDate'],
            'toDate' => $filters['toDate'],
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'totalCount' => $totalCount,
            'perPage' => $pagination['perPage'],
        ];
    }

    public static function getDeletedOrderListData(array $queryParams, int $perPage = 20): array
    {
        $filters = self::normalizeListFilters($queryParams);
        $totalCount = OrderRepository::countDeletedFiltered($filters);
        $pagination = self::resolvePagination($filters['page'], $totalCount, $perPage);
        $orders = OrderRepository::paginateDeletedFiltered($filters, $pagination['perPage'], $pagination['offset']);

        return [
            'orders' => $orders,
            'keyword' => $filters['keyword'],
            'status' => $filters['status'],
            'orderStatus' => $filters['orderStatus'],
            'fromDate' => $filters['fromDate'],
            'toDate' => $filters['toDate'],
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'totalCount' => $totalCount,
            'perPage' => $pagination['perPage'],
        ];
    }

    public static function normalizeListFilters(array $queryParams): array
    {
        $keyword = isset($queryParams['q']) ? trim((string) $queryParams['q']) : '';

        $status = isset($queryParams['status']) ? (string) $queryParams['status'] : '';
        if (!in_array($status, ['', 'paid', 'debt'], true)) {
            $status = '';
        }

        $orderStatus = isset($queryParams['order_status']) ? (string) $queryParams['order_status'] : '';
        if (!in_array($orderStatus, ['', 'completed', 'pending', 'cancelled'], true)) {
            $orderStatus = '';
        }

        $page = ServiceHelper::normalizePage(isset($queryParams['page']) ? $queryParams['page'] : 1);

        return [
            'keyword' => $keyword,
            'status' => $status,
            'orderStatus' => $orderStatus,
            'fromDate' => self::normalizeFilterDate(isset($queryParams['from_date']) ? $queryParams['from_date'] : ''),
            'toDate' => self::normalizeFilterDate(isset($queryParams['to_date']) ? $queryParams['to_date'] : ''),
            'page' => $page,
        ];
    }

    /**
     * Recalculate an order total from the current order lines instead of
     * applying a delta to the value previously stored on the order.
     *
     * Order lines keep the price selected at the time of the order. The
     * current product-unit price must therefore never be used here.
     */
    public static function reconcileOrderTotals($id, array $input = []): ?array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $order = OrderRepository::findActiveWithCustomer($id);
        if (!$order) {
            return null;
        }

        $totals = self::calculateCanonicalOrderTotals($id, $order, $input);
        $pdo = Database::getInstance();

        $storedTotal = self::normalizeMoneyValue(isset($order['total_amount']) ? $order['total_amount'] : 0);
        $storedCost = self::normalizeMoneyValue(isset($order['total_cost']) ? $order['total_cost'] : 0);
        $storedPaid = self::normalizeMoneyValue(isset($order['paid_amount']) ? $order['paid_amount'] : 0);
        $storedDiscount = self::normalizeMoneyValue(isset($order['discount_amount']) ? $order['discount_amount'] : 0);
        $storedStatus = isset($order['status']) ? (string) $order['status'] : '';

        if (
            abs($storedTotal - $totals['totalAmount']) > 0.0001
            || abs($storedCost - $totals['totalCost']) > 0.0001
            || abs($storedPaid - $totals['paidAmount']) > 0.0001
            || abs($storedDiscount - $totals['discountAmount']) > 0.0001
            || $storedStatus !== $totals['status']
        ) {
            $stmt = $pdo->prepare('UPDATE orders SET total_amount = ?, total_cost = ?, paid_amount = ?, status = ?, discount_amount = ? WHERE id = ? AND deleted_at IS NULL');
            $stmt->execute([
                $totals['totalAmount'],
                $totals['totalCost'],
                $totals['paidAmount'],
                $totals['status'],
                $totals['discountAmount'],
                $id,
            ]);
            if (class_exists('ReportService')) {
                ReportService::clearReportCache();
            }
        }

        $order['total_amount'] = $totals['totalAmount'];
        $order['total_cost'] = $totals['totalCost'];
        $order['paid_amount'] = $totals['paidAmount'];
        $order['status'] = $totals['status'];
        $order['discount_amount'] = $totals['discountAmount'];

        return $order;
    }

    public static function getOrderViewData($id): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return [
                'success' => false,
                'redirect' => 'order',
            ];
        }

        $order = self::reconcileOrderTotals($id);
        if (!$order) {
            return [
                'success' => false,
                'redirect' => 'order',
            ];
        }

        $rows = OrderRepository::findDetailRows($id);
        $items = [];
        $payments = [];

        foreach ($rows as $row) {
            if (isset($row['type']) && $row['type'] === 'item') {
                $items[] = $row;
            } elseif (isset($row['type']) && $row['type'] === 'payment') {
                $paymentNote = $row['payment_note'] ?? '';
                $paymentMethod = 'cash';
                $cleanNote = $paymentNote;
                if (str_ends_with($paymentNote, '[TT:bank]')) {
                    $paymentMethod = 'bank';
                    $cleanNote = rtrim(substr($paymentNote, 0, -9));
                } elseif (str_ends_with($paymentNote, '[TT:cash]')) {
                    $paymentMethod = 'cash';
                    $cleanNote = rtrim(substr($paymentNote, 0, -9));
                }
                $payments[] = [
                    'id' => $row['id'],
                    'paid_at' => $row['paid_at'],
                    'amount' => $row['paid_amount'],
                    'note' => $cleanNote,
                    'payment_method' => $paymentMethod,
                ];
            }
        }

        $manualItems = class_exists('OrderManualItem') ? OrderManualItem::findByOrder($id) : [];
        $logs = class_exists('OrderLog') ? OrderLog::findByOrder($id) : [];

        return [
            'success' => true,
            'order' => $order,
            'items' => $items,
            'manualItems' => $manualItems,
            'payments' => $payments,
            'logs' => $logs,
        ];
    }

    public static function getOrderPreviewData($id): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return [
                'success' => false,
                'statusCode' => 400,
                'message' => 'Thiếu id đơn hàng.',
            ];
        }

        $order = self::reconcileOrderTotals($id);
        if (!$order) {
            return [
                'success' => false,
                'statusCode' => 404,
                'message' => 'Không tìm thấy đơn hàng.',
            ];
        }

        $manualItems = class_exists('OrderManualItem') ? OrderManualItem::findByOrder($id) : [];

        return [
            'success' => true,
            'order' => $order,
            'items' => OrderRepository::findReturnItems($id),
            'manualItems' => $manualItems,
        ];
    }

    public static function getOrderInvoiceData($id): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return [
                'success' => false,
                'redirect' => 'order',
            ];
        }

        $order = self::reconcileOrderTotals($id);
        if (!$order) {
            return [
                'success' => false,
                'redirect' => 'order',
            ];
        }

        $items = OrderRepository::findReturnItems($id);
        if (empty($items)) {
            return [
                'success' => false,
                'redirect' => 'order/view?id=' . $id,
            ];
        }

        return [
            'success' => true,
            'order' => $order,
            'items' => $items,
        ];
    }

    public static function getOrderAddFormData($id): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return [
                'success' => false,
                'redirect' => 'order',
            ];
        }

        $order = self::reconcileOrderTotals($id);
        if (!$order) {
            return [
                'success' => false,
                'redirect' => 'order',
            ];
        }

        $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
        if ($orderStatus === 'completed' || $orderStatus === 'cancelled') {
            return [
                'success' => false,
                'message' => 'Đơn hàng đã hoàn thành hoặc đã hủy, không thể thêm sản phẩm.',
                'redirect' => 'order/view?id=' . $id,
            ];
        }

        return [
            'success' => true,
            'order' => $order,
            'productUnits' => OrderRepository::findAvailableProductUnits(),
        ];
    }

    public static function getOrderReturnFormData($id): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return [
                'success' => false,
                'redirect' => 'order',
            ];
        }

        $order = self::reconcileOrderTotals($id);
        if (!$order) {
            return [
                'success' => false,
                'redirect' => 'order',
            ];
        }

        $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
        if ($orderStatus === 'completed' || $orderStatus === 'cancelled') {
            return [
                'success' => false,
                'message' => 'Đơn hàng đã hoàn thành hoặc đã hủy, không thể trả hàng.',
                'redirect' => 'order/view?id=' . $id,
            ];
        }

        $items = OrderRepository::findReturnItems($id);
        if (empty($items)) {
            return [
                'success' => false,
                'message' => 'Đơn hàng không có mặt hàng nào để trả.',
                'redirect' => 'order/view?id=' . $id,
            ];
        }

        return [
            'success' => true,
            'order' => $order,
            'items' => $items,
        ];
    }

    public static function processOrderReturn($orderId, array $payload): array
    {
        $orderId = (int) $orderId;
        if ($orderId <= 0) {
            return [
                'success' => false,
                'redirect' => 'order',
            ];
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND deleted_at IS NULL FOR UPDATE');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng.',
                    'redirect' => 'order',
                ];
            }

            $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
            if ($orderStatus === 'completed' || $orderStatus === 'cancelled') {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Đơn hàng đã hoàn thành hoặc đã hủy, không thể trả hàng.',
                    'redirect' => 'order/view?id=' . $orderId,
                ];
            }

            $items = OrderRepository::findReturnItems($orderId);
            if (empty($items)) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Đơn hàng không có mặt hàng nào để trả.',
                    'redirect' => 'order/view?id=' . $orderId,
                ];
            }

            $returnAll = isset($payload['return_all']) && $payload['return_all'] === '1';
            $returnQtyInput = isset($payload['return_qty']) && is_array($payload['return_qty']) ? $payload['return_qty'] : [];

            $totalReduceAmount = 0;
            $totalReduceCost = 0;
            $updates = [];
            $deletes = [];
            $returnLogItems = [];
            $returnChangeMessages = [];

            foreach ($items as $item) {
                $itemId = (int) $item['id'];
                $originalQty = isset($item['qty']) ? (float) $item['qty'] : 0.0;
                $priceSell = isset($item['price_sell']) ? (float) $item['price_sell'] : 0.0;
                $priceCost = isset($item['price_cost']) ? (float) $item['price_cost'] : 0.0;

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

                $originalQtyBase = isset($item['qty_base']) ? (float) $item['qty_base'] : 0.0;
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
                return [
                    'success' => false,
                    'message' => 'Không có số lượng trả hợp lệ.',
                    'redirect' => 'order/returnForm?id=' . $orderId,
                ];
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

            $totals = self::calculateAdjustedOrderSummary($order, [
                'total_reduce_amount' => $totalReduceAmount,
                'total_reduce_cost' => $totalReduceCost,
            ], [], [
                'allowRefundAdjustment' => true,
            ]);

            $newTotalAmount = $totals['totalAmount'];
            $newTotalCost = $totals['totalCost'];
            $newPaid = $totals['paidAmount'];
            $status = $totals['status'];
            $discountType = $totals['discountType'];
            $discountValue = $totals['discountValue'];
            $discountAmountNew = $totals['discountAmount'];
            $surchargeAmountNew = $totals['surchargeAmount'];
            $refundAmount = $totals['refundAmount'];

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
                    'customer_id' => !empty($order['customer_id']) ? $order['customer_id'] : null,
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
            ReportService::clearReportCache();

            return [
                'success' => true,
                'message' => 'Đã ghi nhận trả hàng cho đơn #' . $orderId . '.',
                'redirect' => 'order/view?id=' . $orderId,
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [
                'success' => false,
                'message' => 'Không thể ghi nhận trả hàng: ' . $e->getMessage(),
                'redirect' => 'order/view?id=' . $orderId,
            ];
        }
    }

    public static function updateOrderDetails($id, array $payload): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return [
                'success' => false,
                'redirect' => 'order',
            ];
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND deleted_at IS NULL FOR UPDATE');
            $stmt->execute([$id]);
            $order = $stmt->fetch();

            if (!$order) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'redirect' => 'order',
                ];
            }

            $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
            if ($orderStatus === 'completed' || $orderStatus === 'cancelled') {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Đơn hàng đã hoàn thành hoặc đã hủy, không thể chỉnh sửa.',
                    'redirect' => 'order/view?id=' . $id,
                ];
            }

            $orderDate = isset($order['order_date']) ? $order['order_date'] : null;
            $orderDateInput = isset($payload['order_date']) ? trim((string) $payload['order_date']) : '';
            $orderDate = self::normalizeOrderDate($orderDateInput, $orderDate);

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

            $rawCustomerId = isset($payload['customer_id']) ? $payload['customer_id'] : '';
            $customerName = isset($payload['customer_name']) ? trim((string) $payload['customer_name']) : '';
            $customerPhone = isset($payload['customer_phone']) ? trim((string) $payload['customer_phone']) : '';
            $customerAddress = isset($payload['customer_address']) ? trim((string) $payload['customer_address']) : '';
            $note = isset($payload['note']) ? trim((string) $payload['note']) : '';

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

            $productUnitIds = isset($payload['product_unit_id']) ? $payload['product_unit_id'] : [];
            $qtys = isset($payload['qty']) ? $payload['qty'] : [];
            $prices = isset($payload['price']) ? $payload['price'] : [];
            $removeExisting = isset($payload['remove_existing']) ? $payload['remove_existing'] : [];

            $totalAddAmount = 0;
            $totalAddCost = 0;
            $totalReduceAmount = 0;
            $totalReduceCost = 0;
            $changeLogMessages = [];
            $priceLogMessages = [];

            $existingPriceChanges = isset($payload['existing_price']) && is_array($payload['existing_price']) ? $payload['existing_price'] : [];

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
                $modes = isset($payload['mode']) && is_array($payload['mode']) ? $payload['mode'] : [];

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

            $manualNames = isset($payload['manual_item_name']) ? $payload['manual_item_name'] : [];
            $manualUnits = isset($payload['manual_unit_name']) ? $payload['manual_unit_name'] : [];
            $manualQtys = isset($payload['manual_qty']) ? $payload['manual_qty'] : [];
            $manualPricesBuy = isset($payload['manual_price_buy']) ? $payload['manual_price_buy'] : [];
            $manualPricesSell = isset($payload['manual_price_sell']) ? $payload['manual_price_sell'] : [];

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

            $totals = self::calculateCanonicalOrderTotals($id, $order, [
                'discount_type' => isset($payload['discount_type']) ? $payload['discount_type'] : (isset($order['discount_type']) ? $order['discount_type'] : 'none'),
                'discount_value' => isset($payload['discount_value']) ? $payload['discount_value'] : (isset($order['discount_value']) ? $order['discount_value'] : '0'),
                'surcharge_amount' => isset($payload['surcharge_amount']) ? $payload['surcharge_amount'] : (isset($order['surcharge_amount']) ? $order['surcharge_amount'] : '0'),
            ]);

            $totalAmount = $totals['totalAmount'];
            $totalCost = $totals['totalCost'];
            $paidAmount = $totals['paidAmount'];
            $status = $totals['status'];
            $discountType = $totals['discountType'];
            $discountValue = $totals['discountValue'];
            $discountAmount = $totals['discountAmount'];
            $surchargeAmount = $totals['surchargeAmount'];

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
            ReportService::clearReportCache();

            return [
                'success' => true,
                'message' => 'Đã cập nhật đơn hàng.',
                'redirect' => 'order/view?id=' . $id,
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (class_exists('LogService')) {
                LogService::logError('OrderDetailController::update exception', ['exception' => $e->getMessage(), 'id' => $id]);
            }

            return [
                'success' => false,
                'message' => 'Không thể cập nhật đơn hàng: ' . $e->getMessage(),
                'redirect' => 'order/edit?id=' . $id,
            ];
        }
    }

    public static function updateOrderStatus($id, $orderStatus): array
    {
        $id = (int) $id;
        $orderStatus = is_string($orderStatus) ? trim($orderStatus) : '';

        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Mã đơn hàng không hợp lệ.',
            ];
        }

        $allowed = ['pending', 'completed', 'cancelled'];
        if (!in_array($orderStatus, $allowed, true)) {
            return [
                'success' => false,
                'message' => 'Trạng thái đơn hàng không hợp lệ.',
            ];
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $order = $stmt->fetch();

            if (!$order) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng.',
                ];
            }

            $oldStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
            $newStatus = $orderStatus;

            $updateStmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ?');
            $updateStmt->execute([$newStatus, $id]);

            if ($oldStatus !== $newStatus) {
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
                    ProductSalesSummaryService::adjustForOrderStatusChange($items, -$direction);
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
                    'detail' => 'Cập nhật trạng thái: ' . $statusText,
                ]);
            }

            $pdo->commit();
            ReportService::clearReportCache();

            return [
                'success' => true,
                'message' => 'Đã cập nhật trạng thái đơn hàng.',
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if (class_exists('LogService')) {
                LogService::logError('OrderService::updateOrderStatus exception', ['exception' => $e->getMessage(), 'id' => $id]);
            }

            return [
                'success' => false,
                'message' => 'Không thể cập nhật trạng thái đơn hàng: ' . $e->getMessage(),
            ];
        }
    }

    public static function addItemsToOrder($orderId, array $payload): array
    {
        $orderId = (int) $orderId;
        if ($orderId <= 0) {
            return [
                'success' => false,
                'redirect' => 'order',
            ];
        }

        $productUnitIds = isset($payload['product_unit_id']) ? $payload['product_unit_id'] : [];
        $qtys = isset($payload['qty']) ? $payload['qty'] : [];

        if (!is_array($productUnitIds) || !is_array($qtys)) {
            return [
                'success' => false,
                'message' => 'Dữ liệu thêm hàng không hợp lệ.',
                'redirect' => 'order/addForm?id=' . $orderId,
            ];
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng.',
                    'redirect' => 'order',
                ];
            }

            $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
            if ($orderStatus === 'completed' || $orderStatus === 'cancelled') {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Đơn hàng đã hoàn thành hoặc đã hủy, không thể thêm sản phẩm.',
                    'redirect' => 'order/view?id=' . $orderId,
                ];
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
                return [
                    'success' => false,
                    'message' => 'Không có mặt hàng hợp lệ để thêm.',
                    'redirect' => 'order/addForm?id=' . $orderId,
                ];
            }

            $totals = self::calculateAddedItemsSummary($order, $totalAddAmount, $totalAddCost);
            $newTotalAmount = $totals['totalAmount'];
            $newTotalCost = $totals['totalCost'];
            $status = $totals['status'];

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
            ReportService::clearReportCache();

            return [
                'success' => true,
                'message' => 'Đã thêm sản phẩm vào đơn hàng.',
                'redirect' => 'order/view?id=' . $orderId,
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (class_exists('LogService')) {
                LogService::logError('OrderDetailController::addStore exception', ['exception' => $e->getMessage(), 'orderId' => $orderId]);
            }

            return [
                'success' => false,
                'message' => 'Không thể thêm sản phẩm: ' . $e->getMessage(),
                'redirect' => 'order/addForm?id=' . $orderId,
            ];
        }
    }

    public static function deleteOrderById($id): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'ID đơn hàng không hợp lệ.',
                'redirect' => 'order',
            ];
        }

        $order = OrderRepository::findActiveById($id);
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng hoặc đơn đã bị xóa.',
                'redirect' => 'order',
            ];
        }

        $orderStatus = isset($order['order_status']) ? $order['order_status'] : 'pending';
        if ($orderStatus === 'completed') {
            return [
                'success' => false,
                'message' => 'Đơn hàng đã hoàn thành, không thể xóa. Vui lòng hủy hoặc chỉnh trạng thái trước.',
                'redirect' => 'order/view?id=' . $id,
            ];
        }

        if (!class_exists('OrderSoftDelete')) {
            return [
                'success' => false,
                'message' => 'Chức năng xóa đơn hàng chưa sẵn sàng.',
                'redirect' => 'order/view?id=' . $id,
            ];
        }

        $ok = OrderSoftDelete::softDelete($id);

        return [
            'success' => $ok,
            'message' => $ok
                ? 'Đã xóa tạm đơn hàng. Có thể khôi phục trong vòng 7 ngày.'
                : 'Không thể xóa đơn hàng.',
            'redirect' => 'order',
        ];
    }

    public static function restoreOrderById($id): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'ID đơn hàng không hợp lệ.',
                'redirect' => 'order',
            ];
        }

        if (!class_exists('OrderSoftDelete')) {
            return [
                'success' => false,
                'message' => 'Chức năng khôi phục đơn hàng chưa sẵn sàng.',
                'redirect' => 'order',
            ];
        }

        $ok = OrderSoftDelete::restore($id);

        return [
            'success' => $ok,
            'message' => $ok ? 'Đã khôi phục đơn hàng.' : 'Không thể khôi phục đơn hàng.',
            'redirect' => 'order',
        ];
    }

    public static function purgeDeletedOrders($days = 7): array
    {
        $days = (int) $days;
        if ($days <= 0) {
            $days = 7;
        }

        if (!class_exists('OrderSoftDelete')) {
            return [
                'success' => false,
                'message' => 'Chức năng xóa vĩnh viễn chưa sẵn sàng.',
                'redirect' => 'order',
            ];
        }

        $count = OrderSoftDelete::purgeOlderThanDays($days);

        return [
            'success' => true,
            'message' => 'Đã xóa vĩnh viễn ' . (int) $count . ' đơn hàng đã xóa tạm quá ' . (int) $days . ' ngày.',
            'redirect' => 'order',
        ];
    }

    public static function purgeDeletedOrderIds(array $ids): array
    {
        if (!class_exists('OrderSoftDelete')) {
            return [
                'success' => false,
                'message' => 'Chức năng xóa vĩnh viễn chưa sẵn sàng.',
                'redirect' => 'order',
            ];
        }

        $count = OrderSoftDelete::purgeByIds($ids);
        if ($count <= 0) {
            return [
                'success' => false,
                'message' => 'Không có đơn hàng đã xóa tạm hợp lệ để xóa vĩnh viễn.',
                'redirect' => 'order',
            ];
        }

        return [
            'success' => true,
            'message' => 'Đã xóa vĩnh viễn ' . (int) $count . ' đơn hàng đã chọn.',
            'redirect' => 'order',
        ];
    }

    public static function autoPurgeExpiredDeletedOrders(int $days = 7, int $throttleSeconds = 3600): void
    {
        $days = (int) $days;
        if ($days <= 0) {
            $days = 7;
        }

        $throttleSeconds = (int) $throttleSeconds;
        if ($throttleSeconds <= 0) {
            $throttleSeconds = 3600;
        }

        if (!class_exists('OrderSoftDelete')) {
            return;
        }

        $runtimeFile = sys_get_temp_dir() . '/order_soft_delete_auto_purge.runtime';
        $lockFile = sys_get_temp_dir() . '/order_soft_delete_auto_purge.lock';
        $now = time();

        $lastRun = 0;
        if (is_file($runtimeFile)) {
            $lastRunRaw = @file_get_contents($runtimeFile);
            $lastRun = (int) $lastRunRaw;
        }

        if ($lastRun > 0 && ($now - $lastRun) < $throttleSeconds) {
            return;
        }

        $lockHandle = @fopen($lockFile, 'c');
        if (!$lockHandle) {
            return;
        }

        try {
            if (!@flock($lockHandle, LOCK_EX | LOCK_NB)) {
                return;
            }

            clearstatcache(true, $runtimeFile);
            $lastRun = 0;
            if (is_file($runtimeFile)) {
                $lastRunRaw = @file_get_contents($runtimeFile);
                $lastRun = (int) $lastRunRaw;
            }

            if ($lastRun > 0 && ($now - $lastRun) < $throttleSeconds) {
                return;
            }

            OrderSoftDelete::purgeOlderThanDays($days);
            @file_put_contents($runtimeFile, (string) $now, LOCK_EX);
        } catch (\Throwable $e) {
            if (class_exists('LogService')) {
                LogService::logError('OrderService::autoPurgeExpiredDeletedOrders exception', [
                    'exception' => $e->getMessage(),
                ]);
            }
        } finally {
            @flock($lockHandle, LOCK_UN);
            @fclose($lockHandle);
        }
    }

    private static function calculateCanonicalOrderTotals(int $orderId, array $order, array $input = []): array
    {
        $pdo = Database::getInstance();
        $itemStmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) AS subtotal, COALESCE(SUM(price_cost * qty), 0) AS total_cost FROM order_items WHERE order_id = ?');
        $itemStmt->execute([$orderId]);
        $itemSummary = $itemStmt->fetch() ?: [];

        $subtotal = self::normalizeMoneyValue(isset($itemSummary['subtotal']) ? $itemSummary['subtotal'] : 0);
        $totalCost = self::normalizeMoneyValue(isset($itemSummary['total_cost']) ? $itemSummary['total_cost'] : 0);

        if (class_exists('OrderManualItem')) {
            $manualItems = OrderManualItem::findByOrder($orderId);
            foreach ($manualItems as $manualItem) {
                $subtotal += self::normalizeMoneyValue(isset($manualItem['amount_sell']) ? $manualItem['amount_sell'] : 0);
                $totalCost += self::normalizeMoneyValue(isset($manualItem['amount_buy']) ? $manualItem['amount_buy'] : 0);
            }
        }

        $discountType = array_key_exists('discount_type', $input)
            ? (string) $input['discount_type']
            : (isset($order['discount_type']) ? (string) $order['discount_type'] : 'none');
        if (!in_array($discountType, ['none', 'fixed', 'percent'], true)) {
            $discountType = 'none';
        }

        $discountValueSource = array_key_exists('discount_value', $input)
            ? $input['discount_value']
            : (isset($order['discount_value']) ? $order['discount_value'] : 0);
        $discountValue = self::parseFlexibleMoneyValue($discountValueSource);
        if ($discountValue < 0) {
            $discountValue = 0;
        }

        $discountAmount = 0.0;
        if ($discountType === 'fixed') {
            $discountAmount = $discountValue;
        } elseif ($discountType === 'percent') {
            $discountValue = min($discountValue, 100);
            $discountAmount = round($subtotal * $discountValue / 100);
        }
        $discountAmount = min(max($discountAmount, 0), $subtotal);

        $surchargeSource = array_key_exists('surcharge_amount', $input)
            ? $input['surcharge_amount']
            : (isset($order['surcharge_amount']) ? $order['surcharge_amount'] : 0);
        $surchargeAmount = self::parseFlexibleMoneyValue($surchargeSource, true);
        if ($surchargeAmount < 0) {
            $surchargeAmount = 0;
        }

        $totalAmount = $subtotal - $discountAmount + $surchargeAmount;
        if ($totalAmount < 0) {
            $totalAmount = 0;
        }
        $totalAmount = Money::roundDownThousand($totalAmount);

        $paidAmount = self::normalizeMoneyValue(isset($order['paid_amount']) ? $order['paid_amount'] : 0);
        if ($paidAmount < 0) {
            $paidAmount = 0;
        }
        $paidAmount = min($paidAmount, $totalAmount);

        $status = $totalAmount <= 0 || $paidAmount >= $totalAmount ? 'paid' : 'debt';

        return [
            'subtotal' => $subtotal,
            'totalAmount' => $totalAmount,
            'totalCost' => max($totalCost, 0),
            'paidAmount' => $paidAmount,
            'status' => $status,
            'discountType' => $discountType,
            'discountValue' => $discountValue,
            'discountAmount' => $discountAmount,
            'surchargeAmount' => $surchargeAmount,
        ];
    }

    public static function calculateAdjustedOrderSummary(array $order, array $adjustments = [], array $input = [], array $options = []): array
    {
        $summary = self::calculateOrderSummary($order);

        $totalAddAmount = self::normalizeMoneyValue(isset($adjustments['total_add_amount']) ? $adjustments['total_add_amount'] : 0);
        $totalReduceAmount = self::normalizeMoneyValue(isset($adjustments['total_reduce_amount']) ? $adjustments['total_reduce_amount'] : 0);
        $totalAddCost = self::normalizeMoneyValue(isset($adjustments['total_add_cost']) ? $adjustments['total_add_cost'] : 0);
        $totalReduceCost = self::normalizeMoneyValue(isset($adjustments['total_reduce_cost']) ? $adjustments['total_reduce_cost'] : 0);

        $subtotal = $summary['subtotal'] - $totalReduceAmount + $totalAddAmount;
        if ($subtotal < 0) {
            $subtotal = 0;
        }

        $discountType = isset($input['discount_type']) ? (string) $input['discount_type'] : $summary['discountType'];
        if (!in_array($discountType, ['none', 'fixed', 'percent'], true)) {
            $discountType = 'none';
        }

        $discountValueSource = array_key_exists('discount_value', $input) ? $input['discount_value'] : $summary['discountValue'];
        $discountValue = self::parseFlexibleMoneyValue($discountValueSource);
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

        $surchargeSource = array_key_exists('surcharge_amount', $input) ? $input['surcharge_amount'] : $summary['surchargeAmount'];
        $surchargeAmount = self::parseFlexibleMoneyValue($surchargeSource, true);
        if ($surchargeAmount < 0) {
            $surchargeAmount = 0;
        }

        $totalAmount = $subtotal - $discountAmount + $surchargeAmount;
        if ($totalAmount < 0) {
            $totalAmount = 0;
        }
        if (!empty($options['roundDownThousand'])) {
            $totalAmount = Money::roundDownThousand($totalAmount);
        }

        $totalCost = $summary['cost'] - $totalReduceCost + $totalAddCost;
        if ($totalCost < 0) {
            $totalCost = 0;
        }

        $paidAmount = $summary['paid'];
        $refundAmount = 0;
        $preserveStatusOnZero = !empty($options['preserveStatusOnZero']);
        $allowRefundAdjustment = !empty($options['allowRefundAdjustment']);

        if ($totalAmount <= 0) {
            if ($allowRefundAdjustment && $paidAmount > 0) {
                $refundAmount = $paidAmount;
            }
            $paidAmount = 0;
            $status = $preserveStatusOnZero ? $summary['status'] : 'paid';
        } else {
            if ($paidAmount > $totalAmount) {
                if ($allowRefundAdjustment) {
                    $refundAmount = $paidAmount - $totalAmount;
                }
                $paidAmount = $totalAmount;
            }
            $status = $paidAmount >= $totalAmount ? 'paid' : 'debt';
        }

        return [
            'subtotal' => $subtotal,
            'totalAmount' => $totalAmount,
            'totalCost' => $totalCost,
            'paidAmount' => $paidAmount,
            'status' => $status,
            'discountType' => $discountType,
            'discountValue' => $discountValue,
            'discountAmount' => $discountAmount,
            'surchargeAmount' => $surchargeAmount,
            'refundAmount' => $refundAmount,
        ];
    }

    public static function calculateAddedItemsSummary(array $order, float $totalAddAmount, float $totalAddCost): array
    {
        $summary = self::calculateOrderSummary($order);

        $newTotalAmount = $summary['total'] + self::normalizeMoneyValue($totalAddAmount);
        $newTotalCost = $summary['cost'] + self::normalizeMoneyValue($totalAddCost);
        if ($newTotalAmount < 0) {
            $newTotalAmount = 0;
        }
        if ($newTotalCost < 0) {
            $newTotalCost = 0;
        }

        $remaining = $newTotalAmount - $summary['paid'];
        if ($remaining < 0) {
            $remaining = 0;
        }

        return [
            'totalAmount' => $newTotalAmount,
            'totalCost' => $newTotalCost,
            'status' => $remaining > 0 ? 'debt' : 'paid',
        ];
    }

    public static function calculateOrderSummary(array $order): array
    {
        $total = self::normalizeMoneyValue(isset($order['total_amount']) ? $order['total_amount'] : 0);
        $paid = self::normalizeMoneyValue(isset($order['paid_amount']) ? $order['paid_amount'] : 0);
        $cost = self::normalizeMoneyValue(isset($order['total_cost']) ? $order['total_cost'] : 0);
        $discountAmount = self::normalizeMoneyValue(isset($order['discount_amount']) ? $order['discount_amount'] : 0);
        $surchargeAmount = self::normalizeMoneyValue(isset($order['surcharge_amount']) ? $order['surcharge_amount'] : 0);
        $discountType = isset($order['discount_type']) ? (string) $order['discount_type'] : 'none';
        if (!in_array($discountType, ['none', 'fixed', 'percent'], true)) {
            $discountType = 'none';
        }
        $discountValue = self::parseFlexibleMoneyValue(isset($order['discount_value']) ? $order['discount_value'] : 0);
        if ($discountValue < 0) {
            $discountValue = 0;
        }

        $subtotal = $total + $discountAmount;
        if ($subtotal < 0) {
            $subtotal = 0;
        }

        $debt = $total - $paid;
        if ($debt < 0) {
            $debt = 0;
        }

        $profit = $total - $cost;
        $status = isset($order['status']) ? (string) $order['status'] : 'debt';
        if (!in_array($status, ['paid', 'debt'], true)) {
            $status = $debt > 0 ? 'debt' : 'paid';
        }

        return [
            'total' => $total,
            'paid' => $paid,
            'debt' => $debt,
            'cost' => $cost,
            'profit' => $profit,
            'subtotal' => $subtotal,
            'status' => $status,
            'discountType' => $discountType,
            'discountValue' => $discountValue,
            'discountAmount' => $discountAmount,
            'surchargeAmount' => $surchargeAmount,
        ];
    }

    public static function normalizeOrderDate(string $orderDateInput, ?string $existingDate = null): ?string
    {
        if ($orderDateInput === '') {
            return $existingDate;
        }

        $normalizedOrderDate = str_replace('T', ' ', $orderDateInput);
        $orderDateTs = strtotime($normalizedOrderDate);
        if ($orderDateTs === false) {
            return $existingDate;
        }

        return date('Y-m-d H:i:s', $orderDateTs);
    }

    private static function normalizeFilterDate($value): string
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            return '';
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    private static function parseFlexibleMoneyValue($value, bool $useMoneyParser = false): float
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return 0.0;
            }

            if ($useMoneyParser) {
                return (float) Money::parseAmount($value);
            }

            return (float) str_replace([',', ' '], ['', ''], $value);
        }

        return (float) $value;
    }

    private static function normalizeMoneyValue($value): float
    {
        $number = is_numeric($value) ? (float) $value : self::parseFlexibleMoneyValue($value, true);
        if ($number < 0) {
            return 0.0;
        }

        return $number;
    }

    private static function resolvePagination(int $page, int $totalCount, int $perPage): array
    {
        return ServiceHelper::resolvePagination($page, $totalCount, $perPage);
    }
}
