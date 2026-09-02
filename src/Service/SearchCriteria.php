<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Validated, clamped product-search parameters. Produced by ProductSearchService
 * and consumed by ProductRepository::search().
 */
final class SearchCriteria
{
    /** @var list<string> */
    public const SORTS = ['featured', 'code', 'diameter'];

    /**
     * @param list<int> $diameters
     * @param list<int> $voltages
     */
    public function __construct(
        public string $keyword = '',
        public string $motorType = '',
        public array $diameters = [],
        public array $voltages = [],
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
