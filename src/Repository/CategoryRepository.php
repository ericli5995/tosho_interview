<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Repository;
use App\Entity\Category;

final class CategoryRepository extends Repository
{
    /** @return list<Category> */
    public function all(): array
    {
        $rows = $this->fetchAll('SELECT * FROM categories ORDER BY sort_order ASC, name ASC');

        return array_map([Category::class, 'fromRow'], $rows);
    }

    public function find(int $id): ?Category
    {
        $row = $this->fetch('SELECT * FROM categories WHERE id = ?', [$id]);

        return $row === null ? null : Category::fromRow($row);
    }

    public function exists(int $id): bool
    {
        return $this->scalar('SELECT 1 FROM categories WHERE id = ? LIMIT 1', [$id]) !== false;
    }
}
