<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Turns raw query-string input into a SearchCriteria: trims and caps the
 * keyword, whitelists the sort, clamps pagination.
 */
final class ProductSearchService
{
    private const TEXT_MAX = 60;
    private const PER_PAGE_DEFAULT = 12;
    private const PER_PAGE_MAX = 48;

    /** @param array<string,mixed> $input */
    public function fromInput(array $input): SearchCriteria
    {
        $text = static fn (mixed $v): string => mb_substr(trim((string) (is_scalar($v) ? $v : '')), 0, self::TEXT_MAX);

        $sort = (string) ($input['sort'] ?? 'featured');
        $perPage = (int) ($input['per_page'] ?? self::PER_PAGE_DEFAULT);

        return new SearchCriteria(
            keyword: $text($input['q'] ?? ''),
            sort: in_array($sort, SearchCriteria::SORTS, true) ? $sort : 'featured',
            page: max(1, (int) ($input['page'] ?? 1)),
            perPage: max(1, min(self::PER_PAGE_MAX, $perPage ?: self::PER_PAGE_DEFAULT)),
        );
    }
}
