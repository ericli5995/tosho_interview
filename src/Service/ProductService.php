<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Db;
use App\Entity\Product;
use App\Repository\ProductRepository;

/**
 * Product writes: row + labels + the optional image in one transaction.
 * Replaced/removed image files are deleted only after the commit; files
 * written by a failed save are cleaned up.
 */
final class ProductService
{
    public function __construct(
        private ProductRepository $products,
        private ImageUploadService $uploads,
        private Db $db,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed>|null $image normalized $_FILES row, or null
     */
    public function create(array $data, ?array $image): Product
    {
        return $this->save((new Product())->applyInput($data), $image, false);
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed>|null $image
     */
    public function update(Product $product, array $data, ?array $image, bool $removeImage): Product
    {
        return $this->save($product->applyInput($data), $image, $removeImage);
    }

    public function delete(Product $product): void
    {
        $this->products->delete($product->id);
        $this->uploads->deleteFiles($product->imageFiles());
    }

    /** @param array<string,mixed>|null $image */
    private function save(Product $product, ?array $image, bool $removeImage): Product
    {
        $written = [];
        $stale = [];

        try {
            $this->db->transaction(function () use ($product, $image, $removeImage, &$written, &$stale): void {
                if ($product->id === 0) {
                    $product->id = $this->products->insert($product);
                }
                if ($removeImage || $image !== null) {
                    $stale = $product->imageFiles();
                    $product->imagePath = null;
                }
                if ($image !== null) {
                    $written = $this->uploads->store($image, $product->id);
                    $product->imagePath = $written[0];
                }
                $this->products->update($product);
            });
        } catch (\Throwable $e) {
            $this->uploads->deleteFiles($written);
            throw $e;
        }

        $this->uploads->deleteFiles($stale);

        return $this->products->find($product->id) ?? $product;
    }
}
