<?php

declare(strict_types=1);

namespace App\Services\Product;

/** Validated, clamped product-search parameters (see ProductSearchService). */
final class SearchCriteria
{
    /** @var list<string> */
    public const SORTS = ['featured', 'code', 'stock'];

    public function __construct(
        public string $keyword = '',
        public string $sort = 'featured',
        public int $page = 1,
        public int $perPage = 12,
    ) {
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
