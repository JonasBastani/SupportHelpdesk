<?php

namespace App\Helpers;

class ListQueryHelper
{
    /**
     * @param array<int, string> $allowedFields
     */
    public static function normalizeSortBy(mixed $sortBy, array $allowedFields, string $defaultField): string
    {
        return in_array($sortBy, $allowedFields, true) ? $sortBy : $defaultField;
    }

    public static function normalizeSortDirection(mixed $sortDirection, string $defaultDirection = 'desc'): string
    {
        return in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : $defaultDirection;
    }

    public static function normalizePerPage(mixed $perPage, int $defaultPerPage = 10, int $maxPerPage = 100): int
    {
        if (! is_numeric($perPage)) {
            return $defaultPerPage;
        }

        return max(1, min((int) $perPage, $maxPerPage));
    }
}
