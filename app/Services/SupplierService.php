<?php

class SupplierService
{
    public static function getSupplierListData(array $queryParams, int $perPage = 20): array
    {
        $keyword = isset($queryParams['q']) ? trim((string) $queryParams['q']) : '';
        $page = ServiceHelper::normalizePage(isset($queryParams['page']) ? $queryParams['page'] : 1);
        $debtStatus = isset($queryParams['debt_status']) ? trim((string) $queryParams['debt_status']) : '';

        $suppliers = [];
        $totalPages = 1;

        if (class_exists('Supplier')) {
            $totalCount = $keyword !== '' ? Supplier::countByKeyword($keyword) : Supplier::countAll();
            $pagination = ServiceHelper::resolvePagination($page, $totalCount, $perPage);
            $page = $pagination['page'];
            $totalPages = $pagination['totalPages'];
            $offset = $pagination['offset'];
            $suppliers = $keyword !== ''
                ? Supplier::searchPaginate($keyword, $perPage, $offset)
                : Supplier::paginate($perPage, $offset);

            $supplierIds = [];
            foreach ($suppliers as $supplier) {
                $supplierId = isset($supplier['id']) ? (int) $supplier['id'] : 0;
                if ($supplierId > 0) {
                    $supplierIds[] = $supplierId;
                }
            }
            $debtMap = SupplierRepository::getPurchaseTotalsBySupplierIds($supplierIds);

            foreach ($suppliers as &$supplier) {
                $supplierId = isset($supplier['id']) ? (int) $supplier['id'] : 0;
                $debtMeta = isset($debtMap[$supplierId]) ? $debtMap[$supplierId] : [
                    'total_amount' => 0.0,
                    'paid_amount' => 0.0,
                    'debt_amount' => 0.0,
                ];
                $supplier['total_amount'] = (float) $debtMeta['total_amount'];
                $supplier['paid_amount'] = (float) $debtMeta['paid_amount'];
                $supplier['debt_amount'] = (float) $debtMeta['debt_amount'];
            }
            unset($supplier);

            // Filter by debt_status
            if ($debtStatus === 'debt') {
                $suppliers = array_filter($suppliers, function ($supplier) {
                    return ($supplier['debt_amount'] ?? 0) > 0;
                });
                $suppliers = array_values($suppliers);
            } elseif ($debtStatus === 'nodebt') {
                $suppliers = array_filter($suppliers, function ($supplier) {
                    return ($supplier['debt_amount'] ?? 0) <= 0;
                });
                $suppliers = array_values($suppliers);
            }
        }

        return [
            'suppliers' => $suppliers,
            'keyword' => $keyword,
            'debtStatus' => $debtStatus,
            'page' => $page,
            'totalPages' => $totalPages,
        ];
    }

    public static function getSupplierViewData($id): array
    {
        $id = (int) $id;
        if ($id <= 0 || !class_exists('Supplier')) {
            return ['success' => false, 'redirect' => 'supplier'];
        }

        $supplier = Supplier::find($id);
        if (!$supplier) {
            return ['success' => false, 'redirect' => 'supplier'];
        }

        $purchases = SupplierRepository::findPurchasesBySupplierId($id);
        $paymentHistory = SupplierRepository::findPaymentsBySupplierId($id);
        $totalDebt = 0.0;
        foreach ($purchases as $purchase) {
            $debtAmount = isset($purchase['debt_amount']) ? (float) $purchase['debt_amount'] : 0.0;
            if ($debtAmount > 0) {
                $totalDebt += $debtAmount;
            }
        }

        return [
            'success' => true,
            'supplier' => $supplier,
            'purchases' => $purchases,
            'paymentHistory' => $paymentHistory,
            'totalDebt' => $totalDebt,
        ];
    }

    public static function getSupplierFormData($id = 0): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return [
                'success' => true,
                'title' => 'Thêm nhà cung cấp',
                'supplier' => null,
                'detailHeader' => [
                    'title' => 'Thêm nhà cung cấp',
                    'back_url' => 'supplier',
                    'back_label' => 'Quay lại',
                    'actions_view' => '',
                ],
            ];
        }

        if (!class_exists('Supplier')) {
            return ['success' => false, 'redirect' => 'supplier'];
        }

        $supplier = Supplier::find($id);
        if (!$supplier) {
            return ['success' => false, 'redirect' => 'supplier'];
        }

        return [
            'success' => true,
            'title' => 'Chỉnh sửa nhà cung cấp',
            'supplier' => $supplier,
            'detailHeader' => [
                'title' => 'Chỉnh sửa nhà cung cấp',
                'back_url' => 'supplier/view?id=' . $id,
                'back_label' => 'Quay lại',
                'actions_view' => '',
            ],
        ];
    }

    public static function createSupplier(array $payload): array
    {
        $contact = ServiceHelper::sanitizeContactFields($payload);
        $name = $contact['name'];
        $phone = $contact['phone'];
        $address = $contact['address'];

        if ($name === '') {
            return [
                'success' => false,
                'message' => 'Tên nhà cung cấp là bắt buộc.',
                'redirect' => 'supplier/create',
            ];
        }

        $supplierId = 0;
        if (class_exists('Supplier')) {
            $supplierId = (int) Supplier::create([
                'name' => $name,
                'phone' => $phone,
                'address' => $address,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Đã thêm nhà cung cấp.',
            'supplierId' => $supplierId,
            'redirect' => 'supplier',
        ];
    }

    public static function updateSupplier(int $id, array $payload): array
    {
        if ($id <= 0) {
            return ['success' => false, 'redirect' => 'supplier'];
        }

        $contact = ServiceHelper::sanitizeContactFields($payload);
        $name = $contact['name'];
        $phone = $contact['phone'];
        $address = $contact['address'];

        if ($name === '' || !class_exists('Supplier')) {
            return [
                'success' => false,
                'message' => 'Tên nhà cung cấp là bắt buộc.',
                'redirect' => 'supplier/edit?id=' . $id,
            ];
        }

        Supplier::update($id, [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
        ]);

        return [
            'success' => true,
            'message' => 'Đã cập nhật nhà cung cấp.',
            'redirect' => 'supplier',
        ];
    }

    public static function recordSupplierBulkPayment(int $supplierId, $amount, string $note, string $paymentMethod = 'cash'): array
    {
        if ($supplierId <= 0 || $amount <= 0) {
            return ['success' => false, 'message' => 'Dữ liệu thanh toán không hợp lệ.'];
        }

        if (!class_exists('Supplier') || !Supplier::find($supplierId)) {
            return ['success' => false, 'message' => 'Không tìm thấy nhà cung cấp.'];
        }

        try {
            $result = PaymentService::recordSupplierDebtPayment($supplierId, $amount, $note, $paymentMethod);
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Không thể ghi nhận thanh toán: ' . $e->getMessage()];
        }

        return [
            'success' => true,
            'message' => 'Đã ghi nhận thanh toán công nợ nhà cung cấp.',
            'appliedAmount' => isset($result['applied_amount']) ? (float) $result['applied_amount'] : 0.0,
        ];
    }

    public static function deleteSupplier(int $id): array
    {
        if ($id <= 0 || !class_exists('Supplier')) {
            return ['success' => false, 'redirect' => 'supplier'];
        }

        Supplier::delete($id);
        return [
            'success' => true,
            'message' => 'Đã xóa nhà cung cấp.',
            'redirect' => 'supplier',
        ];
    }
}
