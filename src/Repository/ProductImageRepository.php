<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Repository;
use App\Entity\ProductImage;

final class ProductImageRepository extends Repository
{
    /** @return list<ProductImage> */
    public function forProduct(int $productId): array
    {
        $rows = $this->fetchAll(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC',
            [$productId]
        );

        return array_map([ProductImage::class, 'fromRow'], $rows);
    }

    public function find(int $id): ?ProductImage
    {
        $row = $this->fetch('SELECT * FROM product_images WHERE id = ?', [$id]);

        return $row === null ? null : ProductImage::fromRow($row);
    }

    public function insert(ProductImage $image): int
    {
        $this->execute(
            'INSERT INTO product_images '
            . '(product_id, path, thumb_path, medium_path, alt, is_primary, sort_order, width, height, size_bytes, mime, created_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $image->productId,
                $image->path,
                $image->thumbPath,
                $image->mediumPath,
                $image->alt,
                $image->isPrimary ? 1 : 0,
                $image->sortOrder,
                $image->width,
                $image->height,
                $image->sizeBytes,
                $image->mime,
                date('Y-m-d H:i:s'),
            ]
        );

        return $this->lastId();
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM product_images WHERE id = ?', [$id]);
    }

    public function clearPrimary(int $productId): void
    {
        $this->execute('UPDATE product_images SET is_primary = 0 WHERE product_id = ?', [$productId]);
    }

    public function setPrimary(int $imageId, int $productId): void
    {
        $this->clearPrimary($productId);
        $this->execute(
            'UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?',
            [$imageId, $productId]
        );
    }

    public function hasPrimary(int $productId): bool
    {
        return $this->scalar(
            'SELECT 1 FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1',
            [$productId]
        ) !== false;
    }

    public function maxSortOrder(int $productId): int
    {
        return (int) $this->scalar(
            'SELECT COALESCE(MAX(sort_order), -1) FROM product_images WHERE product_id = ?',
            [$productId]
        );
    }

    public function countForProduct(int $productId): int
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM product_images WHERE product_id = ?', [$productId]);
    }
}
