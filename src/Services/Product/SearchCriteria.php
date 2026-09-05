<?php

declare(strict_types=1);

namespace App\Services\Product;

/**
 * Validated, clamped product-search parameters. fromInput() is the only way
 * raw query-string values become criteria: keyword trimmed and capped, sort
 * whitelisted, pagination clamped.
 */
final class SearchCriteria
{
    /** @var list<string> */
    public const SORTS = ['featured', 'code', 'stock'];

    private const KEYWORD_MAX = 60;
    private const PER_PAGE_DEFAULT = 12;
    private const PER_PAGE_MAX = 48;

    public function __construct(
        public string $keyword = '',
        public string $sort = 'featured',
        public int $page = 1,
        public int $perPage = self::PER_PAGE_DEFAULT,
    ) {
    }

    /** @param array<string,mixed> $input */
    public static function fromInput(array $input): self
    {
        $keyword = is_scalar($input['q'] ?? null) ? (string) $input['q'] : '';
        $sort = (string) ($input['sort'] ?? 'featured');
        $perPage = (int) ($input['per_page'] ?? 0) ?: self::PER_PAGE_DEFAULT;

        return new self(
            keyword: mb_substr(trim($keyword), 0, self::KEYWORD_MAX),
            sort: in_array($sort, self::SORTS, true) ? $sort : 'featured',
            page: max(1, (int) ($input['page'] ?? 1)),
            perPage: max(1, min(self::PER_PAGE_MAX, $perPage)),
        );
    }
}
