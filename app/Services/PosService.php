<?php

class PosService
{
    public static function getPosBootstrapData(): array
    {
        $products = PosRepository::findProductsForPos();
        $unitRows = PosRepository::findProductUnitsForPos();
        $customers = PosRepository::findCustomersForPos();

        $productUnitsByProduct = [];
        foreach ($unitRows as $row) {
            $productId = isset($row['product_id']) ? (int) $row['product_id'] : 0;
            if ($productId <= 0) {
                continue;
            }

            if (!isset($productUnitsByProduct[$productId])) {
                $productUnitsByProduct[$productId] = [];
            }

            $productUnitsByProduct[$productId][] = [
                'id' => isset($row['id']) ? (int) $row['id'] : 0,
                'unit_id' => isset($row['unit_id']) ? (int) $row['unit_id'] : 0,
                'unit_name' => isset($row['unit_name']) ? (string) $row['unit_name'] : '',
                'factor' => isset($row['factor']) ? (float) $row['factor'] : 1.0,
                'price_sell' => isset($row['price_sell']) ? (float) $row['price_sell'] : 0.0,
                'price_cost' => isset($row['price_cost']) ? (float) $row['price_cost'] : 0.0,
                'allow_fraction' => isset($row['allow_fraction']) ? (int) $row['allow_fraction'] : 0,
                'min_step' => isset($row['min_step']) ? (float) $row['min_step'] : 1.0,
            ];
        }

        return [
            'products' => array_map(function ($product) {
                return [
                    'id' => isset($product['id']) ? (int) $product['id'] : 0,
                    'name' => isset($product['name']) ? (string) $product['name'] : '',
                    'code' => isset($product['code']) ? (string) $product['code'] : '',
                    'base_unit_name' => isset($product['base_unit_name']) ? (string) $product['base_unit_name'] : '',
                    'category_name' => isset($product['category_name']) ? (string) $product['category_name'] : '',
                    'image_path' => isset($product['image_path']) ? (string) $product['image_path'] : '',
                ];
            }, $products),
            'product_units_by_product' => $productUnitsByProduct,
            'customers' => array_map(function ($customer) {
                return [
                    'id' => isset($customer['id']) ? (int) $customer['id'] : 0,
                    'name' => isset($customer['name']) ? (string) $customer['name'] : '',
                    'phone' => isset($customer['phone']) ? (string) $customer['phone'] : '',
                    'address' => isset($customer['address']) ? (string) $customer['address'] : '',
                ];
            }, $customers),
        ];
    }
}