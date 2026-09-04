<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * A catalogue product: one image, free-form labels, a stock count.
 * fromRow() builds it from a DB row, applyInput() from validated form data.
 */
final class Product
{
    public const MAX_LABELS = 12;

    /** @param list<string> $labels */
    public function __construct(
        public int $id = 0,
        public string $modelCode = '',
        public string $name = '',
        public string $slug = '',
        public string $description = '',
        public ?string $imagePath = null,
        public int $stock = 0,
        public bool $isPublished = false,
        public bool $isFeatured = false,
        public int $sortOrder = 0,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public array $labels = [],
    ) {
    }

    /** @param array<string,mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            modelCode: (string) $row['model_code'],
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            description: (string) ($row['description'] ?? ''),
            imagePath: $row['image_path'] ?? null,
            stock: (int) ($row['stock'] ?? 0),
            isPublished: (bool) ($row['is_published'] ?? false),
            isFeatured: (bool) ($row['is_featured'] ?? false),
            sortOrder: (int) ($row['sort_order'] ?? 0),
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
        );
    }

    /**
     * Apply validated form input (strings/bools): trims, coerces, derives the
     * slug and normalises the comma-separated labels list.
     *
     * @param array<string,mixed> $data
     */
    public function applyInput(array $data): static
    {
        $this->modelCode = trim((string) ($data['model_code'] ?? ''));
        $this->name = trim((string) ($data['name'] ?? ''));

        $slug = trim((string) ($data['slug'] ?? ''));
        $this->slug = str_slug($slug !== '' ? $slug : $this->modelCode);

        $this->description = trim((string) ($data['description'] ?? ''));
        $this->stock = max(0, (int) ($data['stock'] ?? 0));
        $this->isPublished = !empty($data['is_published']);
        $this->isFeatured = !empty($data['is_featured']);
        $this->sortOrder = (int) ($data['sort_order'] ?? 0);
        $this->labels = self::parseLabels((string) ($data['labels'] ?? ''));

        return $this;
    }

    /**
     * "ブラシレス, φ22,24V" -> ["ブラシレス", "φ22", "24V"] (trimmed, unique, capped).
     *
     * @return list<string>
     */
    public static function parseLabels(string $raw): array
    {
        $labels = [];
        foreach (preg_split('/[,、\n]+/u', $raw) ?: [] as $label) {
            $label = mb_substr(trim($label), 0, 40);
            if ($label !== '' && !in_array($label, $labels, true)) {
                $labels[] = $label;
            }
        }

        return array_slice($labels, 0, self::MAX_LABELS);
    }

    /* --- image ------------------------------------------------------------- */

    /** Relative paths of the stored image and its variants (empty when no image). */
    public function imageFiles(): array
    {
        return $this->imagePath === null ? [] : [$this->imagePath, $this->variant('medium'), $this->variant('thumb')];
    }

    /** @return array{url:string,medium_url:string,thumb_url:string}|null */
    public function imageUrls(): ?array
    {
        if ($this->imagePath === null) {
            return null;
        }

        return [
            'url' => '/media/' . $this->imagePath,
            'medium_url' => '/media/' . $this->variant('medium'),
            'thumb_url' => '/media/' . $this->variant('thumb'),
        ];
    }

    /** products/3/abc.jpg -> products/3/abc_thumb.jpg */
    private function variant(string $suffix): string
    {
        return (string) preg_replace('/(\.[a-z0-9]+)$/i', "_{$suffix}$1", (string) $this->imagePath);
    }

    /* --- API shape ------------------------------------------------------- */

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'model_code' => $this->modelCode,
            'name' => $this->name,
            'slug' => $this->slug,
            'url' => '/products/' . $this->slug,
            'description' => $this->description,
            'labels' => $this->labels,
            'stock' => $this->stock,
            'in_stock' => $this->stock > 0,
            'image' => $this->imageUrls(),
            'is_published' => $this->isPublished,
            'is_featured' => $this->isFeatured,
            'sort_order' => $this->sortOrder,
            'updated_at' => $this->updatedAt,
        ];
    }
}
