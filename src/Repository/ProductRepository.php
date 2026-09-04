<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Db;
use App\Entity\Product;
use App\Services\Product\SearchCriteria;

final class ProductRepository
{
    public function __construct(private Db $db)
    {
    }

    public function find(int $id): ?Product
    {
        return $this->one('SELECT * FROM products WHERE id = ?', [$id]);
    }

    public function findBySlug(string $slug): ?Product
    {
        return $this->one('SELECT * FROM products WHERE slug = ?', [$slug]);
    }

    public function featured(): ?Product
    {
        return $this->one('SELECT * FROM products WHERE is_published = 1 ORDER BY is_featured DESC, sort_order ASC, id ASC LIMIT 1');
    }

    /**
     * Published products matching the criteria. A keyword matches the model
     * code / name (LIKE) or one of the product's labels exactly.
     *
     * @return array{items:list<Product>,total:int,page:int,per_page:int,pages:int}
     */
    public function search(SearchCriteria $c): array
    {
        $where = ['p.is_published = 1'];
        $params = [];

        if ($c->keyword !== '') {
            $where[] = '(p.model_code LIKE :kw_code OR p.name LIKE :kw_name'
                . ' OR EXISTS (SELECT 1 FROM product_labels l WHERE l.product_id = p.id AND l.label = :kw_label))';
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $c->keyword) . '%';
            $params += [':kw_code' => $like, ':kw_name' => $like, ':kw_label' => $c->keyword];
        }

        $order = match ($c->sort) {
            'code' => 'p.model_code ASC',
            'stock' => 'p.stock DESC, p.model_code ASC',
            default => 'p.is_featured DESC, p.sort_order ASC, p.id ASC',
        };

        return $this->page(implode(' AND ', $where), $params, $order, $c->page, $c->perPage);
    }

    /** @return array{items:list<Product>,total:int,page:int,per_page:int,pages:int} */
    public function paginateForAdmin(int $page, int $perPage = 20): array
    {
        return $this->page('1', [], 'p.updated_at DESC, p.id DESC', $page, $perPage);
    }

    public function insert(Product $product): int
    {
        $now = date('Y-m-d H:i:s');
        $id = $this->db->insert('products', $this->row($product) + ['created_at' => $now, 'updated_at' => $now]);
        $this->replaceLabels($id, $product->labels);

        return $id;
    }

    public function update(Product $product): void
    {
        $this->db->update('products', $this->row($product) + ['updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$product->id]);
        $this->replaceLabels($product->id, $product->labels);
    }

    public function delete(int $id): void
    {
        $this->db->execute('DELETE FROM products WHERE id = ?', [$id]); // labels cascade
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        return $this->db->scalar(
            'SELECT 1 FROM products WHERE slug = ? AND id <> ? LIMIT 1',
            [$slug, $exceptId ?? 0]
        ) !== false;
    }

    /* ------------------------------------------------------------------ */

    /** @param array<int,mixed> $params */
    private function one(string $sql, array $params = []): ?Product
    {
        $row = $this->db->fetch($sql, $params);
        if ($row === null) {
            return null;
        }
        $product = Product::fromRow($row);
        $this->attachLabels([$product]);

        return $product;
    }

    /**
     * @param array<string,mixed> $params
     * @return array{items:list<Product>,total:int,page:int,per_page:int,pages:int}
     */
    private function page(string $where, array $params, string $order, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $total = (int) $this->db->scalar("SELECT COUNT(*) FROM products p WHERE {$where}", $params);
        // $perPage / $offset are clamped ints (PDO cannot bind LIMIT with emulation off).
        $rows = $this->db->fetchAll("SELECT p.* FROM products p WHERE {$where} ORDER BY {$order} LIMIT {$perPage} OFFSET {$offset}", $params);

        $items = array_map([Product::class, 'fromRow'], $rows);
        $this->attachLabels($items);

        return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    /** One query for all products' labels (no N+1). @param list<Product> $products */
    private function attachLabels(array $products): void
    {
        if ($products === []) {
            return;
        }
        $ids = array_map(static fn (Product $p): int => $p->id, $products);
        $rows = $this->db->fetchAll(
            'SELECT product_id, label FROM product_labels WHERE product_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY sort_order',
            $ids
        );
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[(int) $r['product_id']][] = (string) $r['label'];
        }
        foreach ($products as $p) {
            $p->labels = $grouped[$p->id] ?? [];
        }
    }

    /** @param list<string> $labels */
    private function replaceLabels(int $productId, array $labels): void
    {
        $this->db->execute('DELETE FROM product_labels WHERE product_id = ?', [$productId]);
        foreach ($labels as $i => $label) {
            $this->db->insert('product_labels', ['product_id' => $productId, 'label' => $label, 'sort_order' => $i]);
        }
    }

    /** @return array<string,mixed> */
    private function row(Product $p): array
    {
        return [
            'model_code' => $p->modelCode,
            'name' => $p->name,
            'slug' => $p->slug,
            'description' => $p->description,
            'image_path' => $p->imagePath,
            'stock' => $p->stock,
            'is_published' => $p->isPublished ? 1 : 0,
            'is_featured' => $p->isFeatured ? 1 : 0,
            'sort_order' => $p->sortOrder,
        ];
    }
}
