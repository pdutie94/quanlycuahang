<?php

class CustomerService
{
    public static function getCustomerListData(array $queryParams, int $perPage = 20): array
    {
        $filters = self::normalizeListFilters($queryParams);
        $totalCount = CustomerRepository::countFiltered($filters);
        $pagination = self::resolvePagination($filters['page'], $totalCount, $perPage);

        return [
            'customers' => CustomerRepository::paginateFiltered($filters, $pagination['perPage'], $pagination['offset']),
            'keyword' => $filters['keyword'],
            'debtStatus' => $filters['debtStatus'],
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
        ];
    }

    public static function getCustomerViewData($id): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['success' => false, 'redirect' => 'customer'];
        }

        $customer = Customer::find($id);
        if (!$customer) {
            return ['success' => false, 'redirect' => 'customer'];
        }

        $orders = CustomerRepository::findOrdersByCustomerId($id);
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

        return [
            'success' => true,
            'customer' => $customer,
            'orders' => $orders,
            'totalAmountSum' => $totalAmountSum,
            'totalPaidSum' => $totalPaidSum,
            'totalDebt' => $totalDebt,
        ];
    }

    public static function getCustomerFormData($id = 0): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return [
                'success' => true,
                'title' => 'Thêm khách hàng',
                'customer' => null,
                'detailHeader' => [
                    'title' => 'Thêm khách hàng',
                    'back_url' => 'customer',
                    'back_label' => 'Quay lại',
                    'actions_view' => '',
                ],
            ];
        }

        $customer = Customer::find($id);
        if (!$customer) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy khách hàng.',
                'redirect' => 'customer',
            ];
        }

        return [
            'success' => true,
            'title' => 'Sửa khách hàng',
            'customer' => $customer,
            'detailHeader' => [
                'title' => 'Sửa khách hàng',
                'back_url' => 'customer/view?id=' . $id,
                'back_label' => 'Quay lại',
                'actions_view' => '',
            ],
        ];
    }

    public static function createCustomer(array $payload): array
    {
        $contact = ServiceHelper::sanitizeContactFields($payload);
        $name = $contact['name'];
        $phone = $contact['phone'];
        $address = $contact['address'];

        if ($name === '') {
            return [
                'success' => false,
                'message' => 'Vui lòng nhập tên khách hàng.',
                'redirect' => 'customer/create',
            ];
        }

        try {
            $id = Customer::create([
                'name' => $name,
                'phone' => $phone,
                'address' => $address,
            ]);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Không thể tạo khách hàng: ' . $e->getMessage(),
                'redirect' => 'customer',
            ];
        }

        return [
            'success' => true,
            'message' => 'Đã thêm khách hàng mới.',
            'redirect' => 'customer/view?id=' . (int) $id,
        ];
    }

    public static function updateCustomer(int $id, array $payload): array
    {
        if ($id <= 0) {
            return ['success' => false, 'redirect' => 'customer'];
        }

        $contact = ServiceHelper::sanitizeContactFields($payload);
        $name = $contact['name'];
        $phone = $contact['phone'];
        $address = $contact['address'];

        if ($name === '') {
            return [
                'success' => false,
                'message' => 'Vui lòng nhập tên khách hàng.',
                'redirect' => 'customer/edit?id=' . $id,
            ];
        }

        try {
            if ($phone === '') {
                $phone = null;
            }
            CustomerRepository::updateById($id, [
                'name' => $name,
                'phone' => $phone,
                'address' => $address,
            ]);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Không thể cập nhật khách hàng: ' . $e->getMessage(),
                'redirect' => 'customer/edit?id=' . $id,
            ];
        }

        return [
            'success' => true,
            'message' => 'Đã cập nhật thông tin khách hàng.',
            'redirect' => 'customer/view?id=' . $id,
        ];
    }

    public static function deleteCustomer(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'redirect' => 'customer'];
        }

        $success = Customer::delete($id);
        if ($success) {
            return [
                'success' => true,
                'message' => 'Đã xóa khách hàng. Các đơn hàng liên quan chuyển thành khách lẻ.',
                'redirect' => 'customer',
            ];
        }

        return [
            'success' => false,
            'message' => 'Không thể xóa khách hàng.',
            'redirect' => 'customer',
        ];
    }

    public static function getCustomerPaymentData($orderId): array
    {
        $orderId = (int) $orderId;
        if ($orderId <= 0) {
            return ['success' => false, 'redirect' => 'customer'];
        }

        $order = OrderRepository::findWithCustomer($orderId);
        if (!$order || empty($order['customer_id'])) {
            return ['success' => false, 'redirect' => 'customer'];
        }

        $remaining = (float) $order['total_amount'] - (float) $order['paid_amount'];
        if ($remaining <= 0) {
            return ['success' => false, 'redirect' => 'customer/view?id=' . (int) $order['customer_id']];
        }

        return [
            'success' => true,
            'order' => $order,
            'remaining' => $remaining,
        ];
    }

    public static function getCustomerBulkPaymentData($customerId): array
    {
        $customerId = (int) $customerId;
        if ($customerId <= 0) {
            return ['success' => false, 'redirect' => 'customer'];
        }

        $customer = Customer::find($customerId);
        if (!$customer) {
            return ['success' => false, 'redirect' => 'customer'];
        }

        $orders = CustomerRepository::findDebtOrdersByCustomerId($customerId);
        $totalDebt = 0.0;
        foreach ($orders as $order) {
            $debt = isset($order['debt_amount']) ? (float) $order['debt_amount'] : 0.0;
            if ($debt > 0) {
                $totalDebt += $debt;
            }
        }

        return [
            'success' => true,
            'customer' => $customer,
            'orders' => $orders,
            'totalDebt' => $totalDebt,
        ];
    }

    public static function recordCustomerPayment(int $orderId, $amount, string $note): array
    {
        if ($orderId <= 0 || $amount <= 0) {
            return [
                'success' => false,
                'message' => 'Dữ liệu thanh toán không hợp lệ.',
                'redirect' => 'customer',
            ];
        }

        $order = CustomerRepository::findOrderCustomerByOrderId($orderId);
        if (!$order || empty($order['customer_id'])) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng.',
                'redirect' => 'customer',
            ];
        }

        $customerId = (int) $order['customer_id'];

        try {
            PaymentService::recordOrderPayment($orderId, $amount, $note, 'cash');
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Không thể ghi nhận thanh toán: ' . $e->getMessage(),
                'redirect' => 'customer/view?id=' . $customerId,
            ];
        }

        return [
            'success' => true,
            'message' => 'Đã ghi nhận thanh toán.',
            'redirect' => 'customer/view?id=' . $customerId,
        ];
    }

    public static function recordCustomerBulkPayment(int $customerId, $amount, string $note): array
    {
        $customerId = (int) $customerId;
        if ($customerId <= 0 || $amount <= 0) {
            return [
                'success' => false,
                'message' => 'Dữ liệu thanh toán không hợp lệ.',
                'redirect' => 'customer',
            ];
        }

        $customer = Customer::find($customerId);
        if (!$customer) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy khách hàng.',
                'redirect' => 'customer',
            ];
        }

        try {
            $result = PaymentService::recordCustomerDebtPayment($customerId, $amount, $note, 'cash');
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Không thể ghi nhận thanh toán: ' . $e->getMessage(),
                'redirect' => 'customer/view?id=' . $customerId,
            ];
        }

        return [
            'success' => true,
            'message' => 'Đã ghi nhận thanh toán công nợ.',
            'redirect' => 'customer/view?id=' . $customerId,
            'appliedAmount' => isset($result['applied_amount']) ? (float) $result['applied_amount'] : 0.0,
            'allocations' => isset($result['allocations']) ? $result['allocations'] : [],
        ];
    }

    public static function normalizeListFilters(array $queryParams): array
    {
        $keyword = isset($queryParams['q']) ? trim((string) $queryParams['q']) : '';
        $debtStatus = isset($queryParams['debt_status']) ? (string) $queryParams['debt_status'] : '';
        if (!in_array($debtStatus, ['', 'debt', 'nodebt'], true)) {
            $debtStatus = '';
        }

        $page = ServiceHelper::normalizePage(isset($queryParams['page']) ? $queryParams['page'] : 1);

        return [
            'keyword' => $keyword,
            'debtStatus' => $debtStatus,
            'page' => $page,
        ];
    }

    private static function resolvePagination(int $page, int $totalCount, int $perPage): array
    {
        return ServiceHelper::resolvePagination($page, $totalCount, $perPage);
    }
}
