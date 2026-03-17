<?php

class OrderService
{
    public static function calculateOrderSummary(array $order): array
    {
        $total = isset($order['total_amount']) ? (float) $order['total_amount'] : 0.0;
        $paid = isset($order['paid_amount']) ? (float) $order['paid_amount'] : 0.0;
        $cost = isset($order['total_cost']) ? (float) $order['total_cost'] : 0.0;

        $debt = $total - $paid;
        $profit = $total - $cost;

        return [
            'total' => $total,
            'paid' => $paid,
            'debt' => $debt,
            'cost' => $cost,
            'profit' => $profit,
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
}
