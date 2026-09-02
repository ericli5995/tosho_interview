<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Page-number math for list views. Given a total row count it produces the
 * page count and a small window of page numbers for the pager UI.
 */
final class Paginator
{
    public function __construct(
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }

    public function pages(): int
    {
        return (int) max(1, (int) ceil($this->total / max(1, $this->perPage)));
    }

    public function hasPrev(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->pages();
    }

    /** @return list<int> */
    public function window(int $span = 2): array
    {
        $start = max(1, $this->page - $span);
        $end = min($this->pages(), $this->page + $span);

        return range($start, $end);
    }
}
