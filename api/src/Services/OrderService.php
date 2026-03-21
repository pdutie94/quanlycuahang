<?php

declare(strict_types=1);

namespace App\Services;

final class OrderService
{
    public static function calculateOrderSummary(array $order): array
    {
        $total = (float) ($order['total_amount'] ?? 0);
        $paid = (float) ($order['paid_amount'] ?? 0);
        $cost = (float) ($order['total_cost'] ?? 0);

        return [
            'total' => $total,
            'paid' => $paid,
            'debt' => max(0, $total - $paid),
            'cost' => $cost,
            'profit' => $total - $cost,
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

    public static function buildOrderCode(int $id): string
    {
        return 'DH-' . max(1, $id);
    }
}
