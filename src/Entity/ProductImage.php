<?php

declare(strict_types=1);

namespace App\Entity;

final class ProductImage
{
    public function __construct(
        public int $id = 0,
        public int $productId = 0,
        public string $path = '',
        public ?string $thumbPath = null,
        public ?string $mediumPath = null,
        public string $alt = '',
        public bool $isPrimary = false,
        public int $sortOrder = 0,
        public ?int $width = null,
        public ?int $height = null,
        public ?int $sizeBytes = null,
        public ?string $mime = null,
        public ?string $createdAt = null,
    ) {
    }

    /** @param array<string,mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            productId: (int) $row['product_id'],
            path: (string) $row['path'],
            thumbPath: $row['thumb_path'] ?? null,
            mediumPath: $row['medium_path'] ?? null,
            alt: (string) ($row['alt'] ?? ''),
            isPrimary: (bool) ($row['is_primary'] ?? false),
            sortOrder: (int) ($row['sort_order'] ?? 0),
            width: isset($row['width']) ? (int) $row['width'] : null,
            height: isset($row['height']) ? (int) $row['height'] : null,
            sizeBytes: isset($row['size_bytes']) ? (int) $row['size_bytes'] : null,
            mime: $row['mime'] ?? null,
            createdAt: $row['created_at'] ?? null,
        );
    }

    public function url(): string
    {
        return '/media/' . ltrim($this->path, '/');
    }

    public function thumbUrl(): string
    {
        return '/media/' . ltrim($this->thumbPath ?? $this->path, '/');
    }

    public function mediumUrl(): string
    {
        return '/media/' . ltrim($this->mediumPath ?? $this->path, '/');
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url(),
            'thumb_url' => $this->thumbUrl(),
            'medium_url' => $this->mediumUrl(),
            'alt' => $this->alt,
            'is_primary' => $this->isPrimary,
        ];
    }
}
