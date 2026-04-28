<?php

class PurchaseService
{
    private static function normalizePurchaseDate($value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return date('Y-m-d H:i:s');
        }

        $raw = str_replace('T', ' ', $raw);
        $dt = date_create($raw);
        if ($dt === false) {
            return date('Y-m-d H:i:s');
        }

        return $dt->format('Y-m-d H:i:s');
    }

    public static function getPurchaseListData(array $queryParams, int $perPage = 20): array
    {
        $filters = self::normalizeListFilters($queryParams);
        $totalCount = PurchaseRepository::countFiltered($filters);
        $pagination = self::resolvePagination($filters['page'], $totalCount, $perPage);

        $purchases = PurchaseRepository::paginateFiltered($filters, $pagination['perPage'], $pagination['offset']);

        // Filter by payment status
        $paymentStatus = $filters['paymentStatus'];
        if ($paymentStatus === 'paid') {
            $purchases = array_filter($purchases, function ($purchase) {
                $total = (float) ($purchase['total_amount'] ?? 0);
                $paid = (float) ($purchase['paid_amount'] ?? 0);
                return $paid >= $total;
            });
            $purchases = array_values($purchases);
        } elseif ($paymentStatus === 'debt') {
            $purchases = array_filter($purchases, function ($purchase) {
                $total = (float) ($purchase['total_amount'] ?? 0);
                $paid = (float) ($purchase['paid_amount'] ?? 0);
                return $paid < $total;
            });
            $purchases = array_values($purchases);
        }

        return [
            'purchases' => $purchases,
            'suppliers' => class_exists('Supplier') ? Supplier::all() : [],
            'keyword' => $filters['keyword'],
            'fromDate' => $filters['fromDate'],
            'toDate' => $filters['toDate'],
            'supplierId' => $filters['supplierId'],
            'paymentStatus' => $paymentStatus,
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'totalCount' => $totalCount,
            'perPage' => $pagination['perPage'],
        ];
    }

    public static function getPurchaseViewData($id): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return [
                'success' => false,
                'redirect' => 'purchase',
            ];
        }

        $purchase = PurchaseRepository::findWithSupplierById($id);
        if (!$purchase) {
            return [
                'success' => false,
                'redirect' => 'purchase',
            ];
        }

        $items = PurchaseRepository::findItemsByPurchaseId($id);

        return [
            'success' => true,
            'purchase' => $purchase,
            'items' => $items,
            'manualItems' => PurchaseRepository::findManualItemsByPurchaseId($id),
            'payments' => self::formatPayments(PurchaseRepository::findPaymentsByPurchaseId($id)),
            'logs' => class_exists('PurchaseLog') ? PurchaseLog::findByPurchase($id) : [],
        ];
    }

    public static function getCreateFormData(): array
    {
        return [
            'suppliers' => class_exists('Supplier') ? Supplier::all() : [],
            'productUnits' => PurchaseRepository::findPurchaseUnitsForCreate(),
        ];
    }

    public static function getEditFormData($id): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return [
                'success' => false,
                'redirect' => 'purchase',
            ];
        }

        $purchase = PurchaseRepository::findById($id);
        if (!$purchase) {
            return [
                'success' => false,
                'redirect' => 'purchase',
            ];
        }

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

        return [
            'success' => true,
            'purchase' => $purchase,
            'suppliers' => class_exists('Supplier') ? Supplier::all() : [],
            'productUnits' => PurchaseRepository::findPurchaseUnitsForEdit(),
            'items' => PurchaseRepository::findItemsByPurchaseId($id),
            'manualItems' => PurchaseRepository::findManualItemsByPurchaseId($id),
            'paymentMethod' => $paymentMethod,
            'noteForEdit' => $noteForEdit,
        ];
    }

    public static function createPurchase(array $payload): array
    {
        $supplierId = isset($payload['supplier_id']) ? (int) $payload['supplier_id'] : 0;
        if ($supplierId <= 0) {
            return [
                'success' => false,
                'message' => 'Vui lòng chọn nhà cung cấp.',
                'redirect' => 'purchase/create',
            ];
        }

        $itemsPayload = self::extractItemsPayload($payload);
        $manualItemsPayload = self::extractManualItemsPayload($payload);
        if (!$itemsPayload['valid']) {
            return [
                'success' => false,
                'message' => 'Dữ liệu mặt hàng không hợp lệ.',
                'redirect' => 'purchase/create',
            ];
        }

        if (!$manualItemsPayload['valid']) {
            return [
                'success' => false,
                'message' => 'Dữ liệu sản phẩm khác không hợp lệ.',
                'redirect' => 'purchase/create',
            ];
        }

        $note = isset($payload['note']) ? trim((string) $payload['note']) : '';
        $paymentMethod = isset($payload['payment_method']) && $payload['payment_method'] === 'bank' ? 'bank' : 'cash';
        $purchaseDate = self::normalizePurchaseDate(isset($payload['purchase_date']) ? $payload['purchase_date'] : '');

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $itemsResult = self::preparePurchaseItems($itemsPayload, true);
            $manualItemsResult = self::preparePurchaseManualItems($manualItemsPayload);

            if (empty($itemsResult['items']) && empty($manualItemsResult['items'])) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Vui lòng nhập ít nhất một mặt hàng hợp lệ.',
                    'redirect' => 'purchase/create',
                ];
            }

            $paidAmount = isset($payload['paid_amount']) ? Money::parseAmount($payload['paid_amount']) : 0;
            if ($paidAmount < 0) {
                $paidAmount = 0;
            }
            $grandTotalAmount = $itemsResult['totalAmount'] + $manualItemsResult['totalAmount'];

            if ($paidAmount > $grandTotalAmount) {
                $paidAmount = $grandTotalAmount;
            }

            $status = $paidAmount >= $grandTotalAmount ? 'paid' : 'debt';
            $purchaseNoteForSave = self::buildPurchaseNoteWithMethod($note, $paymentMethod, $paidAmount > 0);

            $purchaseId = Purchase::create([
                'supplier_id' => $supplierId,
                'purchase_date' => $purchaseDate,
                'total_amount' => $grandTotalAmount,
                'paid_amount' => $paidAmount,
                'status' => $status,
                'note' => $purchaseNoteForSave,
            ]);

            foreach ($itemsResult['items'] as $row) {
                $row['purchase_id'] = $purchaseId;
                PurchaseItem::create($row);
            }

            foreach ($manualItemsResult['items'] as $row) {
                $row['purchase_id'] = $purchaseId;
                PurchaseManualItem::create($row);
            }

            InventoryService::adjustForNewPurchaseItems($itemsResult['items']);
            self::applyUpdateCostByUnit($itemsResult['updateCostByUnit']);

            if ($paidAmount > 0 && class_exists('Payment')) {
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
                        'items_count' => count($itemsResult['items']) + count($manualItemsResult['items']),
                        'total_amount' => $grandTotalAmount,
                        'paid_amount' => $paidAmount,
                        'status' => $status,
                        'payment_method' => $paymentMethod,
                    ],
                ]);
            }

            $pdo->commit();
            ReportService::clearReportCache();

            return [
                'success' => true,
                'message' => 'Đã tạo phiếu nhập hàng #' . $purchaseId . '.',
                'purchaseId' => $purchaseId,
                'redirect' => 'purchase',
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Không thể tạo phiếu nhập hàng: ' . $e->getMessage(),
                'redirect' => 'purchase/create',
            ];
        }
    }

    public static function updatePurchase($id, array $payload): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return [
                'success' => false,
                'redirect' => 'purchase',
            ];
        }

        $supplierId = isset($payload['supplier_id']) ? (int) $payload['supplier_id'] : 0;
        if ($supplierId <= 0) {
            return [
                'success' => false,
                'message' => 'Vui lòng chọn nhà cung cấp.',
                'redirect' => 'purchase/edit?id=' . $id,
            ];
        }

        $itemsPayload = self::extractItemsPayload($payload);
        $manualItemsPayload = self::extractManualItemsPayload($payload);
        if (!$itemsPayload['valid']) {
            return [
                'success' => false,
                'message' => 'Dữ liệu mặt hàng không hợp lệ.',
                'redirect' => 'purchase/edit?id=' . $id,
            ];
        }

        if (!$manualItemsPayload['valid']) {
            return [
                'success' => false,
                'message' => 'Dữ liệu sản phẩm khác không hợp lệ.',
                'redirect' => 'purchase/edit?id=' . $id,
            ];
        }

        $note = isset($payload['note']) ? trim((string) $payload['note']) : '';
        $paymentMethod = isset($payload['payment_method']) && $payload['payment_method'] === 'bank' ? 'bank' : 'cash';
        $purchaseDate = self::normalizePurchaseDate(isset($payload['purchase_date']) ? $payload['purchase_date'] : '');

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $purchase = PurchaseRepository::findByIdForUpdate($id);
            if (!$purchase) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy phiếu nhập.',
                    'redirect' => 'purchase',
                ];
            }

            $itemsResult = self::preparePurchaseItems($itemsPayload, false);
            $manualItemsResult = self::preparePurchaseManualItems($manualItemsPayload);
            if (empty($itemsResult['items']) && empty($manualItemsResult['items'])) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Vui lòng nhập ít nhất một mặt hàng hợp lệ.',
                    'redirect' => 'purchase/edit?id=' . $id,
                ];
            }

            $paidAmount = isset($purchase['paid_amount']) ? (float) $purchase['paid_amount'] : 0.0;
            if ($paidAmount < 0) {
                $paidAmount = 0.0;
            }
            $grandTotalAmount = $itemsResult['totalAmount'] + $manualItemsResult['totalAmount'];
            if ($paidAmount > $grandTotalAmount) {
                $paidAmount = $grandTotalAmount;
            }

            $status = $paidAmount >= $grandTotalAmount ? 'paid' : 'debt';

            $oldItems = PurchaseRepository::findItemsByPurchaseId($id);
            InventoryService::rollbackOldPurchaseItems($oldItems);
            PurchaseRepository::deleteItemsByPurchaseId($id);
            PurchaseRepository::deleteManualItemsByPurchaseId($id);

            PurchaseRepository::updatePurchaseById($id, [
                'supplier_id' => $supplierId,
                'purchase_date' => $purchaseDate,
                'total_amount' => $grandTotalAmount,
                'paid_amount' => $paidAmount,
                'status' => $status,
                'note' => self::buildPurchaseNoteWithMethod($note, $paymentMethod, $paidAmount > 0),
            ]);

            foreach ($itemsResult['items'] as $row) {
                $row['purchase_id'] = $id;
                PurchaseItem::create($row);
            }

            foreach ($manualItemsResult['items'] as $row) {
                $row['purchase_id'] = $id;
                PurchaseManualItem::create($row);
            }

            InventoryService::adjustForNewPurchaseItems($itemsResult['items']);
            self::applyUpdateCostByUnit($itemsResult['updateCostByUnit']);

            if (class_exists('PurchaseLog')) {
                PurchaseLog::create([
                    'purchase_id' => $id,
                    'action' => 'update',
                    'detail' => [
                        'type' => 'update',
                        'old_total' => isset($purchase['total_amount']) ? (float) $purchase['total_amount'] : 0.0,
                        'new_total' => $grandTotalAmount,
                        'old_paid' => isset($purchase['paid_amount']) ? (float) $purchase['paid_amount'] : 0.0,
                        'new_paid' => $paidAmount,
                        'old_status' => isset($purchase['status']) ? $purchase['status'] : '',
                        'new_status' => $status,
                        'items_count' => count($itemsResult['items']) + count($manualItemsResult['items']),
                    ],
                ]);
            }

            $pdo->commit();
            ReportService::clearReportCache();

            return [
                'success' => true,
                'message' => 'Đã cập nhật phiếu nhập hàng #' . $id . '.',
                'redirect' => 'purchase/view?id=' . $id,
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Không thể cập nhật phiếu nhập hàng: ' . $e->getMessage(),
                'redirect' => 'purchase/edit?id=' . $id,
            ];
        }
    }

    public static function normalizeListFilters(array $queryParams): array
    {
        $keyword = isset($queryParams['q']) ? trim((string) $queryParams['q']) : '';
        $fromDate = isset($queryParams['from_date']) ? trim((string) $queryParams['from_date']) : '';
        $toDate = isset($queryParams['to_date']) ? trim((string) $queryParams['to_date']) : '';
        $supplierId = isset($queryParams['supplier_id']) ? (int) $queryParams['supplier_id'] : 0;
        $paymentStatus = isset($queryParams['payment_status']) ? trim((string) $queryParams['payment_status']) : '';
        $page = ServiceHelper::normalizePage(isset($queryParams['page']) ? $queryParams['page'] : 1);

        if ($supplierId < 0) {
            $supplierId = 0;
        }
        return [
            'keyword' => $keyword,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'supplierId' => $supplierId,
            'paymentStatus' => $paymentStatus,
            'page' => $page,
        ];
    }

    public static function deletePurchase(int $id): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'Phiếu nhập không hợp lệ.',
                'redirect' => 'purchase',
            ];
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $purchase = PurchaseRepository::findByIdForUpdate($id);
            if (!$purchase) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy phiếu nhập.',
                    'redirect' => 'purchase',
                ];
            }

            $items = PurchaseRepository::findItemsByPurchaseId($id);
            InventoryService::rollbackOldPurchaseItems($items);

            PurchaseRepository::deletePaymentsByPurchaseId($id);
            PurchaseRepository::deleteLogsByPurchaseId($id);
            PurchaseRepository::deleteItemsByPurchaseId($id);
            PurchaseRepository::deleteManualItemsByPurchaseId($id);
            PurchaseRepository::deletePurchaseById($id);

            $pdo->commit();
            ReportService::clearReportCache();

            return [
                'success' => true,
                'message' => 'Đã xóa phiếu nhập hàng #' . $id . '.',
                'redirect' => 'purchase',
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Không thể xóa phiếu nhập hàng: ' . $e->getMessage(),
                'redirect' => 'purchase/view?id=' . $id,
            ];
        }
    }

    private static function resolvePagination(int $page, int $totalCount, int $perPage): array
    {
        return ServiceHelper::resolvePagination($page, $totalCount, $perPage);
    }

    private static function extractItemsPayload(array $payload): array
    {
        $productUnitIds = isset($payload['product_unit_id']) ? $payload['product_unit_id'] : [];
        $qtys = isset($payload['qty']) ? $payload['qty'] : [];
        $priceCosts = isset($payload['price_cost']) ? $payload['price_cost'] : [];
        $amountInputs = isset($payload['amount']) ? $payload['amount'] : [];
        $updateCostFlags = isset($payload['update_cost']) && is_array($payload['update_cost']) ? $payload['update_cost'] : [];

        $valid = is_array($productUnitIds) && is_array($qtys) && is_array($priceCosts) && (empty($amountInputs) || is_array($amountInputs));

        return [
            'valid' => $valid,
            'productUnitIds' => $productUnitIds,
            'qtys' => $qtys,
            'priceCosts' => $priceCosts,
            'amountInputs' => is_array($amountInputs) ? $amountInputs : [],
            'updateCostFlags' => $updateCostFlags,
        ];
    }

    private static function extractManualItemsPayload(array $payload): array
    {
        $itemNames = isset($payload['manual_item_name']) ? $payload['manual_item_name'] : [];
        $unitNames = isset($payload['manual_unit_name']) ? $payload['manual_unit_name'] : [];
        $qtys = isset($payload['manual_qty']) ? $payload['manual_qty'] : [];
        $priceCosts = isset($payload['manual_price_cost']) ? $payload['manual_price_cost'] : [];
        $amounts = isset($payload['manual_amount']) ? $payload['manual_amount'] : [];

        $valid = is_array($itemNames)
            && is_array($unitNames)
            && is_array($qtys)
            && is_array($priceCosts)
            && (empty($amounts) || is_array($amounts));

        return [
            'valid' => $valid,
            'itemNames' => $itemNames,
            'unitNames' => $unitNames,
            'qtys' => $qtys,
            'priceCosts' => $priceCosts,
            'amounts' => is_array($amounts) ? $amounts : [],
        ];
    }

    private static function preparePurchaseItems(array $itemsPayload, bool $enforceUnitRules): array
    {
        $totalAmount = 0.0;
        $itemsPrepared = [];
        $updateCostByUnit = [];

        foreach ($itemsPayload['productUnitIds'] as $index => $productUnitIdRaw) {
            $productUnitId = (int) $productUnitIdRaw;
            $qtyRaw = isset($itemsPayload['qtys'][$index]) ? $itemsPayload['qtys'][$index] : '';
            $qty = (float) str_replace([',', ' '], ['', ''], (string) $qtyRaw);
            $priceCostRaw = isset($itemsPayload['priceCosts'][$index]) ? $itemsPayload['priceCosts'][$index] : '';
            $priceCost = Money::parseAmount($priceCostRaw);
            $amountRaw = isset($itemsPayload['amountInputs'][$index]) ? $itemsPayload['amountInputs'][$index] : '';
            $amountInput = Money::parseAmount($amountRaw);
            $updateCostFlag = !empty($itemsPayload['updateCostFlags'][$index]);

            if ($productUnitId <= 0 || $qty <= 0) {
                continue;
            }

            $productUnit = PurchaseRepository::findProductUnitForPurchase($productUnitId, $enforceUnitRules);
            if (!$productUnit) {
                continue;
            }

            $factor = isset($productUnit['factor']) ? (float) $productUnit['factor'] : 0;
            if ($factor <= 0) {
                $factor = 1;
            }

            if ($enforceUnitRules) {
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

            if ($amount <= 0) {
                continue;
            }

            $qtyBase = $qty * $factor;
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

        return [
            'items' => $itemsPrepared,
            'totalAmount' => $totalAmount,
            'updateCostByUnit' => $updateCostByUnit,
        ];
    }

    private static function preparePurchaseManualItems(array $itemsPayload): array
    {
        $totalAmount = 0.0;
        $itemsPrepared = [];

        foreach ($itemsPayload['itemNames'] as $index => $itemNameRaw) {
            $itemName = trim((string) $itemNameRaw);
            $unitName = isset($itemsPayload['unitNames'][$index]) ? trim((string) $itemsPayload['unitNames'][$index]) : '';
            $qtyRaw = isset($itemsPayload['qtys'][$index]) ? $itemsPayload['qtys'][$index] : '';
            $qty = (float) str_replace([',', ' '], ['', ''], (string) $qtyRaw);
            $priceCostRaw = isset($itemsPayload['priceCosts'][$index]) ? $itemsPayload['priceCosts'][$index] : '';
            $priceCost = Money::parseAmount($priceCostRaw);
            $amountRaw = isset($itemsPayload['amounts'][$index]) ? $itemsPayload['amounts'][$index] : '';
            $amount = Money::parseAmount($amountRaw);

            if ($itemName === '' || $qty <= 0) {
                continue;
            }

            if ($amount > 0 && $qty > 0) {
                $priceCost = $amount / $qty;
            } else {
                $amount = $qty * max(0, $priceCost);
            }

            if ($amount < 0 || $priceCost < 0) {
                continue;
            }

            $totalAmount += $amount;
            $itemsPrepared[] = [
                'item_name' => $itemName,
                'unit_name' => $unitName,
                'qty' => $qty,
                'price_cost' => $priceCost,
                'amount' => $amount,
            ];
        }

        return [
            'items' => $itemsPrepared,
            'totalAmount' => $totalAmount,
        ];
    }

    private static function applyUpdateCostByUnit(array $updateCostByUnit)
    {
        if (empty($updateCostByUnit)) {
            return;
        }

        foreach ($updateCostByUnit as $unitId => $priceCostValue) {
            PurchaseRepository::updateProductUnitCost((int) $unitId, (float) $priceCostValue);
        }
    }

    private static function buildPurchaseNoteWithMethod(string $note, string $paymentMethod, bool $appendTag): string
    {
        if (!$appendTag) {
            return $note;
        }

        $purchaseNoteTrim = rtrim($note);
        if ($purchaseNoteTrim !== '') {
            $noteCheck = rtrim($purchaseNoteTrim);
            $tail = substr($noteCheck, -9);
            if ($tail === '[TT:cash]' || $tail === '[TT:bank]') {
                $purchaseNoteTrim = rtrim(substr($noteCheck, 0, -9));
            }
        }

        $methodTag = $paymentMethod === 'bank' ? '[TT:bank]' : '[TT:cash]';
        if ($purchaseNoteTrim === '') {
            return $methodTag;
        }

        return $purchaseNoteTrim . ' ' . $methodTag;
    }

    private static function formatPayments(array $payments): array
    {
        return array_map(function ($payment) {
            $note = $payment['note'] ?? '';
            $paymentMethod = 'cash';
            $cleanNote = $note;

            // Parse payment method from note format "note (Chuyển khoản)" or "note (Tiền mặt)"
            if (str_ends_with($note, ' (Chuyển khoản)')) {
                $paymentMethod = 'bank';
                $cleanNote = substr($note, 0, -16);
            } elseif (str_ends_with($note, ' (Tiền mặt)')) {
                $paymentMethod = 'cash';
                $cleanNote = substr($note, 0, -12);
            } elseif ($note === 'Chuyển khoản') {
                $paymentMethod = 'bank';
                $cleanNote = '';
            } elseif ($note === 'Tiền mặt') {
                $paymentMethod = 'cash';
                $cleanNote = '';
            }

            return [
                'id' => $payment['id'],
                'paid_at' => $payment['paid_at'],
                'amount' => $payment['amount'],
                'note' => $cleanNote,
                'payment_method' => $paymentMethod,
            ];
        }, $payments);
    }
}
