<?php

class ServiceHelper
{
    public static function resolvePagination(int $page, int $totalCount, int $perPage): array
    {
        if ($perPage < 1) {
            $perPage = 20;
        }

        if ($page < 1) {
            $page = 1;
        }

        $totalPages = (int) ceil($totalCount / $perPage);
        if ($totalPages < 1) {
            $totalPages = 1;
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'offset' => ($page - 1) * $perPage,
        ];
    }

    public static function normalizePage($value): int
    {
        $page = (int) $value;
        return $page < 1 ? 1 : $page;
    }

    public static function sanitizeContactFields(array $payload): array
    {
        $name = isset($payload['name']) ? trim((string) $payload['name']) : '';
        $phone = isset($payload['phone']) ? trim((string) $payload['phone']) : '';
        $address = isset($payload['address']) ? trim((string) $payload['address']) : '';

        return [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
        ];
    }
}
