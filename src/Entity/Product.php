<?php

declare(strict_types=1);

namespace App\Entity;

final class Product
{
    /** @var list<string> */
    public const MOTOR_TYPES = ['brushed', 'brushless'];

    /**
     * @param list<ProductImage> $images
     * @param list<array{label:string,value:string,unit:?string}> $specs
     */
    public function __construct(
        public int $id = 0,
        public string $modelCode = '',
        public string $name = '',
        public string $slug = '',
        public ?int $categoryId = null,
        public string $motorType = '',
        public ?float $ratedVoltage = null,
        public ?string $gearRatio = null,
        public ?int $bodyDiameter = null,
        public ?float $ratedTorque = null,
        public ?int $ratedSpeed = null,
        public ?float $noiseLevel = null,
        public ?int $lifeHours = null,
        public string $description = '',
        public ?string $outlineDrawingPath = null,
        public bool $isPublished = false,
        public bool $isFeatured = false,
        public int $sortOrder = 0,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public array $images = [],
        public array $specs = [],
        public ?Category $category = null,
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
            categoryId: isset($row['category_id']) ? (int) $row['category_id'] : null,
            motorType: (string) ($row['motor_type'] ?? ''),
            ratedVoltage: isset($row['rated_voltage']) ? (float) $row['rated_voltage'] : null,
            gearRatio: $row['gear_ratio'] ?? null,
            bodyDiameter: isset($row['body_diameter']) ? (int) $row['body_diameter'] : null,
            ratedTorque: isset($row['rated_torque']) ? (float) $row['rated_torque'] : null,
            ratedSpeed: isset($row['rated_speed']) ? (int) $row['rated_speed'] : null,
            noiseLevel: isset($row['noise_level']) ? (float) $row['noise_level'] : null,
            lifeHours: isset($row['life_hours']) ? (int) $row['life_hours'] : null,
            description: (string) ($row['description'] ?? ''),
            outlineDrawingPath: $row['outline_drawing_path'] ?? null,
            isPublished: (bool) ($row['is_published'] ?? false),
            isFeatured: (bool) ($row['is_featured'] ?? false),
            sortOrder: (int) ($row['sort_order'] ?? 0),
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
        );
    }

    public function primaryImage(): ?ProductImage
    {
        foreach ($this->images as $image) {
            if ($image->isPrimary) {
                return $image;
            }
        }

        return $this->images[0] ?? null;
    }

    public function motorTypeLabel(): string
    {
        return match ($this->motorType) {
            'brushed' => 'DCブラシ',
            'brushless' => 'DCブラシレス',
            default => '',
        };
    }

    public function voltageLabel(): string
    {
        if ($this->ratedVoltage === null) {
            return '';
        }

        $formatted = rtrim(rtrim(number_format($this->ratedVoltage, 2, '.', ''), '0'), '.');

        return $formatted . ' V';
    }

    /** @return array<string,mixed> */
    /**
     * The API representation (public and admin share it).
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'model_code' => $this->modelCode,
            'name' => $this->name,
            'slug' => $this->slug,
            'url' => '/products/' . $this->slug,
            'category_id' => $this->categoryId,
            'category' => $this->category?->toArray(),
            'motor_type' => $this->motorType,
            'motor_type_label' => $this->motorTypeLabel(),
            'rated_voltage' => $this->ratedVoltage,
            'voltage_label' => $this->voltageLabel(),
            'gear_ratio' => $this->gearRatio,
            'body_diameter' => $this->bodyDiameter,
            'rated_torque' => $this->ratedTorque,
            'rated_speed' => $this->ratedSpeed,
            'noise_level' => $this->noiseLevel,
            'life_hours' => $this->lifeHours,
            'description' => $this->description,
            'is_published' => $this->isPublished,
            'is_featured' => $this->isFeatured,
            'sort_order' => $this->sortOrder,
            'specs' => $this->specs,
            'image' => $this->primaryImage()?->toArray(),
            'images' => array_map(static fn (ProductImage $i): array => $i->toArray(), $this->images),
            'updated_at' => $this->updatedAt,
        ];
    }
}
