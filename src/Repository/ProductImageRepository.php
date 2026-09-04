<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Db;
use App\Entity\ProductImage;

final class ProductImageRepository
{
    public function __construct(private Db $db)
    {
    }

    /** @return list<ProductImage> */
    public function forProduct(int $productId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC',
            [$productId]
        );

        return array_map([ProductImage::class, 'fromRow'], $rows);
    }

    public function find(int $id): ?ProductImage
    {
        $row = $this->db->fetch('SELECT * FROM product_images WHERE id = ?', [$id]);

        return $row === null ? null : ProductImage::fromRow($row);
    }

    public function insert(ProductImage $image): int
    {
        return $this->db->insert('product_images', [
            'product_id' => $image->productId,
            'path' => $image->path,
            'thumb_path' => $image->thumbPath,
            'medium_path' => $image->mediumPath,
            'alt' => $image->alt,
            'is_primary' => $image->isPrimary ? 1 : 0,
            'sort_order' => $image->sortOrder,
            'width' => $image->width,
            'height' => $image->height,
            'size_bytes' => $image->sizeBytes,
            'mime' => $image->mime,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function delete(int $id): void
    {
        $this->db->execute('DELETE FROM product_images WHERE id = ?', [$id]);
    }

    public function clearPrimary(int $productId): void
    {
        $this->db->execute('UPDATE product_images SET is_primary = 0 WHERE product_id = ?', [$productId]);
    }

    public function setPrimary(int $imageId, int $productId): void
    {
        $this->clearPrimary($productId);
        $this->db->execute(
            'UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?',
            [$imageId, $productId]
        );
    }

    public function hasPrimary(int $productId): bool
    {
        return $this->db->scalar(
            'SELECT 1 FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1',
            [$productId]
        ) !== false;
    }

    public function maxSortOrder(int $productId): int
    {
        return (int) $this->db->scalar(
            'SELECT COALESCE(MAX(sort_order), -1) FROM product_images WHERE product_id = ?',
            [$productId]
        );
    }

    public function countForProduct(int $productId): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM product_images WHERE product_id = ?', [$productId]);
    }
}
