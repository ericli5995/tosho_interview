<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Repository\ProductImageRepository;
use App\Repository\ProductRepository;
use PDO;

/**
 * Orchestrates product writes: one DB transaction covering the product row, its
 * spec rows and any uploaded images. On failure it rolls back and removes any
 * files already written.
 */
final class ProductService
{
    public function __construct(
        private ProductRepository $products,
        private ProductImageRepository $images,
        private ImageUploadService $uploads,
        private PDO $db,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     * @param list<array<string,mixed>> $imageFiles normalized $_FILES rows
     */
    public function create(array $data, array $imageFiles = [], ?int $primaryIndex = null): Product
    {
        $this->db->beginTransaction();
        $written = [];

        try {
            $product = $this->hydrate(new Product(), $data);
            $product->id = $this->products->insert($product);

            $this->products->replaceSpecs($product->id, $data['specs'] ?? []);
            $this->storeImages($product, $imageFiles, $primaryIndex, $written, 0);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $this->uploads->deleteFiles($written);
            throw $e;
        }

        return $this->products->find($product->id) ?? $product;
    }

    /**
     * @param array<string,mixed> $data
     * @param list<array<string,mixed>> $imageFiles
     */
    public function update(int $id, array $data, array $imageFiles = [], ?int $primaryIndex = null): Product
    {
        $existing = $this->products->find($id);
        if ($existing === null) {
            throw new \RuntimeException("Product {$id} not found");
        }

        $this->db->beginTransaction();
        $written = [];

        try {
            $product = $this->hydrate($existing, $data);
            $product->id = $id;
            $this->products->update($product);
            $this->products->replaceSpecs($id, $data['specs'] ?? []);

            $existingCount = $this->images->countForProduct($id);
            $this->storeImages($product, $imageFiles, $primaryIndex, $written, $existingCount);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $this->uploads->deleteFiles($written);
            throw $e;
        }

        return $this->products->find($id) ?? $existing;
    }

    public function delete(int $id): void
    {
        $images = $this->images->forProduct($id);

        $this->products->delete($id);

        $paths = [];
        foreach ($images as $image) {
            $paths[] = $image->path;
            $paths[] = $image->thumbPath;
            $paths[] = $image->mediumPath;
        }
        $this->uploads->deleteFiles($paths);
        $this->uploads->removeProductDirectory($id);
    }

    /* ------------------------------------------------------------------ */

    /**
     * @param array<string,mixed> $data
     */
    private function hydrate(Product $product, array $data): Product
    {
        $product->modelCode = trim((string) ($data['model_code'] ?? ''));
        $product->name = trim((string) ($data['name'] ?? ''));

        $slug = (string) ($data['slug'] ?? '');
        $product->slug = str_slug($slug !== '' ? $slug : $product->modelCode);

        $product->categoryId = isset($data['category_id']) && $data['category_id'] !== null
            ? (int) $data['category_id']
            : null;

        $motorType = (string) ($data['motor_type'] ?? '');
        $product->motorType = in_array($motorType, Product::MOTOR_TYPES, true) ? $motorType : '';

        $product->ratedVoltage = $this->toFloat($data['rated_voltage'] ?? null);
        $product->gearRatio = $this->toNullableString($data['gear_ratio'] ?? null);
        $product->bodyDiameter = $this->toInt($data['body_diameter'] ?? null);
        $product->ratedTorque = $this->toFloat($data['rated_torque'] ?? null);
        $product->ratedSpeed = $this->toInt($data['rated_speed'] ?? null);
        $product->noiseLevel = $this->toFloat($data['noise_level'] ?? null);
        $product->lifeHours = $this->toInt($data['life_hours'] ?? null);
        $product->description = trim((string) ($data['description'] ?? ''));
        $product->isPublished = !empty($data['is_published']);
        $product->isFeatured = !empty($data['is_featured']);
        $product->sortOrder = (int) ($data['sort_order'] ?? 0);

        return $product;
    }

    /**
     * @param list<array<string,mixed>> $files
     * @param list<string> $written
     */
    private function storeImages(
        Product $product,
        array $files,
        ?int $primaryIndex,
        array &$written,
        int $existingCount,
    ): void {
        if ($files === []) {
            return;
        }

        $order = $this->images->maxSortOrder($product->id) + 1;
        $storedIds = [];
        $index = 0;

        foreach ($files as $file) {
            if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                $index++;
                continue;
            }

            $meta = $this->uploads->store($file, $product->id);
            $written[] = $meta['path'];
            $written[] = $meta['medium_path'];
            $written[] = $meta['thumb_path'];

            $image = new ProductImage(
                productId: $product->id,
                path: $meta['path'],
                thumbPath: $meta['thumb_path'],
                mediumPath: $meta['medium_path'],
                alt: $product->name,
                isPrimary: false,
                sortOrder: $order++,
                width: $meta['width'],
                height: $meta['height'],
                sizeBytes: $meta['size_bytes'],
                mime: $meta['mime'],
            );

            $storedIds[$index] = $this->images->insert($image);
            $index++;
        }

        if ($storedIds === []) {
            return;
        }

        if ($primaryIndex !== null && isset($storedIds[$primaryIndex])) {
            $this->images->setPrimary($storedIds[$primaryIndex], $product->id);
        } elseif ($existingCount === 0 && !$this->images->hasPrimary($product->id)) {
            $this->images->setPrimary((int) reset($storedIds), $product->id);
        }
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function toInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function toNullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
