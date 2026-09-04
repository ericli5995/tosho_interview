<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Db;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Service\SearchCriteria;

final class ProductRepository
{
    public function __construct(private Db $db)
    {
    }

    public function find(int $id): ?Product
    {
        $row = $this->db->fetch('SELECT * FROM products WHERE id = ?', [$id]);

        return $row === null ? null : $this->loadAggregate(Product::fromRow($row));
    }

    public function findBySlug(string $slug): ?Product
    {
        $row = $this->db->fetch('SELECT * FROM products WHERE slug = ?', [$slug]);

        return $row === null ? null : $this->loadAggregate(Product::fromRow($row));
    }

    public function featured(): ?Product
    {
        $row = $this->db->fetch(
            'SELECT * FROM products WHERE is_published = 1 ORDER BY is_featured DESC, created_at DESC LIMIT 1'
        );

        return $row === null ? null : $this->loadAggregate(Product::fromRow($row));
    }

    /**
     * @return array{items:list<Product>,total:int,page:int,per_page:int,pages:int}
     */
    public function search(SearchCriteria $criteria): array
    {
        $where = ['p.is_published = 1'];
        $params = [];

        if ($criteria->keyword !== '') {
            // Distinct placeholders: PDO with emulation disabled does not allow
            // one named parameter to appear more than once.
            $like = '%' . $this->escapeLike($criteria->keyword) . '%';
            $where[] = '(p.model_code LIKE :kw_code OR p.name LIKE :kw_name OR p.description LIKE :kw_desc)';
            $params[':kw_code'] = $like;
            $params[':kw_name'] = $like;
            $params[':kw_desc'] = $like;
        }

        if ($criteria->motorType !== '') {
            $where[] = 'p.motor_type = :mt';
            $params[':mt'] = $criteria->motorType;
        }

        if ($criteria->diameters !== []) {
            $where[] = 'p.body_diameter IN (' . $this->bindList($params, 'dia', $criteria->diameters) . ')';
        }

        if ($criteria->voltages !== []) {
            $where[] = 'p.rated_voltage IN (' . $this->bindList($params, 'vol', $criteria->voltages) . ')';
        }

        $whereSql = implode(' AND ', $where);

        $total = (int) $this->db->scalar("SELECT COUNT(*) FROM products p WHERE {$whereSql}", $params);

        $order = match ($criteria->sort) {
            'code' => 'p.model_code ASC',
            'diameter' => 'p.body_diameter ASC, p.model_code ASC',
            default => 'p.is_featured DESC, p.created_at DESC',
        };

        // page / perPage are validated integers in SearchCriteria; PDO cannot bind
        // LIMIT/OFFSET placeholders with emulation disabled, so they are inlined here.
        $limit = max(1, $criteria->perPage);
        $offset = max(0, $criteria->offset());

        $rows = $this->db->fetchAll(
            "SELECT p.* FROM products p WHERE {$whereSql} ORDER BY {$order} LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        $products = array_map([Product::class, 'fromRow'], $rows);
        $this->attachImages($products);

        return [
            'items' => $products,
            'total' => $total,
            'page' => $criteria->page,
            'per_page' => $criteria->perPage,
            'pages' => (int) max(1, (int) ceil($total / $limit)),
        ];
    }

    /**
     * @return array{items:list<Product>,total:int,page:int,per_page:int,pages:int}
     */
    public function paginateForAdmin(int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $total = (int) $this->db->scalar('SELECT COUNT(*) FROM products');
        $rows = $this->db->fetchAll(
            "SELECT * FROM products ORDER BY updated_at DESC, id DESC LIMIT {$perPage} OFFSET {$offset}"
        );

        $products = array_map([Product::class, 'fromRow'], $rows);
        $this->attachImages($products);

        return [
            'items' => $products,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => (int) max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function insert(Product $product): int
    {
        $now = date('Y-m-d H:i:s');

        return $this->db->insert('products', $this->row($product) + [
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function update(Product $product): void
    {
        $this->db->update(
            'products',
            $this->row($product) + ['updated_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$product->id]
        );
    }

    public function delete(int $id): void
    {
        // product_specs and product_images rows are removed by ON DELETE CASCADE.
        $this->db->execute('DELETE FROM products WHERE id = ?', [$id]);
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        if ($exceptId !== null) {
            return $this->db->scalar(
                'SELECT 1 FROM products WHERE slug = ? AND id <> ? LIMIT 1',
                [$slug, $exceptId]
            ) !== false;
        }

        return $this->db->scalar('SELECT 1 FROM products WHERE slug = ? LIMIT 1', [$slug]) !== false;
    }

    /** @param list<array{label?:string,value?:string,unit?:string}> $specs */
    public function replaceSpecs(int $productId, array $specs): void
    {
        $this->db->execute('DELETE FROM product_specs WHERE product_id = ?', [$productId]);

        $order = 0;
        foreach ($specs as $spec) {
            $label = trim((string) ($spec['label'] ?? ''));
            $value = trim((string) ($spec['value'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }
            $unit = trim((string) ($spec['unit'] ?? ''));

            $this->db->insert('product_specs', [
                'product_id' => $productId,
                'label' => mb_substr($label, 0, 80),
                'value' => mb_substr($value, 0, 160),
                'unit' => $unit === '' ? null : mb_substr($unit, 0, 30),
                'sort_order' => $order++,
            ]);
        }
    }

    public function countAll(): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM products');
    }

    public function countPublished(): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM products WHERE is_published = 1');
    }

    /** @return list<int> */
    public function distinctDiameters(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT DISTINCT body_diameter FROM products '
            . 'WHERE is_published = 1 AND body_diameter IS NOT NULL ORDER BY body_diameter ASC'
        );

        return array_map(static fn (array $r): int => (int) $r['body_diameter'], $rows);
    }

    /** @return list<int> */
    public function distinctVoltages(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT DISTINCT rated_voltage FROM products '
            . 'WHERE is_published = 1 AND rated_voltage IS NOT NULL ORDER BY rated_voltage ASC'
        );

        return array_map(static fn (array $r): int => (int) round((float) $r['rated_voltage']), $rows);
    }

    /* ------------------------------------------------------------------ */

    private function loadAggregate(Product $product): Product
    {
        $this->attachImages([$product]);

        $product->specs = array_map(
            static fn (array $r): array => [
                'label' => (string) $r['label'],
                'value' => (string) $r['value'],
                'unit' => $r['unit'] !== null ? (string) $r['unit'] : null,
            ],
            $this->db->fetchAll(
                'SELECT label, value, unit FROM product_specs WHERE product_id = ? ORDER BY sort_order ASC, id ASC',
                [$product->id]
            )
        );

        if ($product->categoryId !== null) {
            $row = $this->db->fetch('SELECT * FROM categories WHERE id = ?', [$product->categoryId]);
            if ($row !== null) {
                $product->category = Category::fromRow($row);
            }
        }

        return $product;
    }

    /** @param list<Product> $products */
    private function attachImages(array $products): void
    {
        if ($products === []) {
            return;
        }

        $ids = array_map(static fn (Product $p): int => $p->id, $products);
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        $rows = $this->db->fetchAll(
            "SELECT * FROM product_images WHERE product_id IN ({$placeholders}) "
            . 'ORDER BY is_primary DESC, sort_order ASC, id ASC',
            $ids
        );

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['product_id']][] = ProductImage::fromRow($row);
        }

        foreach ($products as $product) {
            $product->images = $grouped[$product->id] ?? [];
        }
    }

    /**
     * Column => value map for INSERT/UPDATE.
     *
     * @return array<string,mixed>
     */
    private function row(Product $p): array
    {
        return [
            'model_code' => $p->modelCode,
            'name' => $p->name,
            'slug' => $p->slug,
            'category_id' => $p->categoryId,
            'motor_type' => $p->motorType,
            'rated_voltage' => $p->ratedVoltage,
            'gear_ratio' => $p->gearRatio,
            'body_diameter' => $p->bodyDiameter,
            'rated_torque' => $p->ratedTorque,
            'rated_speed' => $p->ratedSpeed,
            'noise_level' => $p->noiseLevel,
            'life_hours' => $p->lifeHours,
            'description' => $p->description,
            'outline_drawing_path' => $p->outlineDrawingPath,
            'is_published' => $p->isPublished ? 1 : 0,
            'is_featured' => $p->isFeatured ? 1 : 0,
            'sort_order' => $p->sortOrder,
        ];
    }

    /**
     * Adds `:prefix0, :prefix1 ...` params to $params and returns the placeholder list.
     *
     * @param array<string,mixed> $params
     * @param list<int|string> $values
     */
    private function bindList(array &$params, string $prefix, array $values): string
    {
        $placeholders = [];
        foreach (array_values($values) as $i => $value) {
            $key = ':' . $prefix . $i;
            $placeholders[] = $key;
            $params[$key] = $value;
        }

        return implode(', ', $placeholders);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
