<?php

class ValidationService
{
    public static function validateOrderData(array $data)
    {
        $errors = [];

        if (empty($data['order_date'])) {
            $errors[] = 'Ngày đơn hàng không được để trống.';
        } elseif (strtotime($data['order_date']) === false) {
            $errors[] = 'Ngày đơn hàng không hợp lệ.';
        }

        if (empty($data['customer_name']) && empty($data['customer_id'])) {
            $errors[] = 'Khách hàng phải được chọn hoặc nhập thông tin khách lẻ.';
        }

        if (!isset($data['product_unit_id']) || !is_array($data['product_unit_id']) || empty($data['product_unit_id'])) {
            $errors[] = 'Phải có ít nhất một sản phẩm trong đơn hàng.';
        }

        if (!empty($data['status']) && !in_array($data['status'], [PaymentStatus::PAID, PaymentStatus::DEBT], true)) {
            $errors[] = 'Trạng thái thanh toán không hợp lệ.';
        }

        return $errors;
    }

    public static function validateProductData(array $data)
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Tên sản phẩm không được để trống.';
        }

        if (empty($data['base_unit_id']) || (int) $data['base_unit_id'] <= 0) {
            $errors[] = 'Đơn vị cơ sở không hợp lệ.';
        }

        if (!isset($data['category_id']) || (int) $data['category_id'] <= 0) {
            $errors[] = 'Danh mục sản phẩm chưa hợp lệ.';
        }

        if (isset($data['min_stock_qty']) && $data['min_stock_qty'] < 0) {
            $errors[] = 'Số lượng tồn tối thiểu không được âm.';
        }

        return $errors;
    }
}
