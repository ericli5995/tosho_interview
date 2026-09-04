<?php

declare(strict_types=1);

namespace App\Entity;

final class Category
{
    public function __construct(
        public int $id = 0,
        public string $name = '',
        public string $slug = '',
        public ?int $parentId = null,
        public int $sortOrder = 0,
    ) {
    }

    /** @param array<string,mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            parentId: isset($row['parent_id']) ? (int) $row['parent_id'] : null,
            sortOrder: (int) ($row['sort_order'] ?? 0),
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'slug' => $this->slug];
    }
}
