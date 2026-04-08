<?php

class SupplierService
{
    public static function getSupplierListData(array $queryParams, int $perPage = 20): array
    {
        $keyword = isset($queryParams['q']) ? trim((string) $queryParams['q']) : '';
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $suppliers = [];
        $totalPages = 1;

        if (class_exists('Supplier')) {
            $totalCount = $keyword !== '' ? Supplier::countByKeyword($keyword) : Supplier::countAll();
            $totalPages = (int) ceil($totalCount / $perPage);
            if ($totalPages < 1) {
                $totalPages = 1;
            }
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $offset = ($page - 1) * $perPage;
            $suppliers = $keyword !== ''
                ? Supplier::searchPaginate($keyword, $perPage, $offset)
                : Supplier::paginate($perPage, $offset);

            // Tổng hợp công nợ cho từng supplier
            $pdo = Database::getInstance();
            foreach ($suppliers as &$supplier) {
                $stmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount),0) AS total_amount, COALESCE(SUM(paid_amount),0) AS paid_amount FROM purchases WHERE supplier_id = ?');
                $stmt->execute([$supplier['id']]);
                $row = $stmt->fetch();
                $supplier['total_amount'] = isset($row['total_amount']) ? (float)$row['total_amount'] : 0.0;
                $supplier['paid_amount'] = isset($row['paid_amount']) ? (float)$row['paid_amount'] : 0.0;
                $supplier['debt_amount'] = $supplier['total_amount'] - $supplier['paid_amount'];
            }
            unset($supplier);
        }

        return [
            'suppliers' => $suppliers,
            'keyword' => $keyword,
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
        $name = isset($payload['name']) ? trim((string) $payload['name']) : '';
        $phone = isset($payload['phone']) ? trim((string) $payload['phone']) : '';
        $address = isset($payload['address']) ? trim((string) $payload['address']) : '';

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

        $name = isset($payload['name']) ? trim((string) $payload['name']) : '';
        $phone = isset($payload['phone']) ? trim((string) $payload['phone']) : '';
        $address = isset($payload['address']) ? trim((string) $payload['address']) : '';

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
