<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Turns raw request input into a validated SearchCriteria: whitelists the
 * enum-like fields, coerces and bounds the numeric lists, clamps pagination.
 */
final class ProductSearchService
{
    private const KEYWORD_MAX = 100;
    private const PER_PAGE_DEFAULT = 12;
    private const PER_PAGE_MAX = 48;
    private const VALUE_MAX = 1000;

    /** @param array<string,mixed> $input */
    public function fromInput(array $input): SearchCriteria
    {
        $keyword = trim((string) ($input['q'] ?? ''));
        if (mb_strlen($keyword) > self::KEYWORD_MAX) {
            $keyword = mb_substr($keyword, 0, self::KEYWORD_MAX);
        }

        $motorType = (string) ($input['motor_type'] ?? '');
        if (!in_array($motorType, \App\Entity\Product::MOTOR_TYPES, true)) {
            $motorType = '';
        }

        $diameters = $this->intList($input['diameter'] ?? []);
        $voltages = $this->intList($input['voltage'] ?? []);

        $sort = (string) ($input['sort'] ?? 'featured');
        if (!in_array($sort, SearchCriteria::SORTS, true)) {
            $sort = 'featured';
        }

        $page = max(1, (int) ($input['page'] ?? 1));

        $perPage = (int) ($input['per_page'] ?? self::PER_PAGE_DEFAULT);
        $perPage = max(1, min(self::PER_PAGE_MAX, $perPage ?: self::PER_PAGE_DEFAULT));

        return new SearchCriteria($keyword, $motorType, $diameters, $voltages, $sort, $page, $perPage);
    }

    /**
     * @param mixed $raw
     * @return list<int>
     */
    private function intList(mixed $raw): array
    {
        if (!is_array($raw)) {
            $raw = $raw === null || $raw === '' ? [] : [$raw];
        }

        $out = [];
        foreach ($raw as $value) {
            $int = (int) $value;
            if ($int > 0 && $int <= self::VALUE_MAX && !in_array($int, $out, true)) {
                $out[] = $int;
            }
        }

        return $out;
    }
}
