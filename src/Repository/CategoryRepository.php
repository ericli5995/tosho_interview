<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Db;
use App\Entity\Category;

final class CategoryRepository
{
    public function __construct(private Db $db)
    {
    }

    /** @return list<Category> */
    public function all(): array
    {
        $rows = $this->db->fetchAll('SELECT * FROM categories ORDER BY sort_order ASC, name ASC');

        return array_map([Category::class, 'fromRow'], $rows);
    }

    public function find(int $id): ?Category
    {
        $row = $this->db->fetch('SELECT * FROM categories WHERE id = ?', [$id]);

        return $row === null ? null : Category::fromRow($row);
    }

    public function exists(int $id): bool
    {
        return $this->db->scalar('SELECT 1 FROM categories WHERE id = ? LIMIT 1', [$id]) !== false;
    }
}
