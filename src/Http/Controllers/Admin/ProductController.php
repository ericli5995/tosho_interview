<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\App;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductImageRepository;
use App\Repository\ProductRepository;
use App\Service\ImageUploadService;
use App\Service\ProductService;
use App\Service\UploadException;
use App\Validation\Validator;

/**
 * Admin product CRUD. Create/update take multipart form data
 * (fields + specs[i][label|value|unit] + images[] + primary_image_index).
 */
final class ProductController extends Controller
{
    private ProductRepository $products;
    private CategoryRepository $categories;
    private ProductService $service;

    public function __construct()
    {
        $db = App::db();
        $this->products = new ProductRepository($db);
        $this->categories = new CategoryRepository($db);
        $this->service = new ProductService(
            $this->products,
            new ProductImageRepository($db),
            new ImageUploadService((string) config('app.paths.uploads'), (int) config('app.upload_max_bytes')),
            $db
        );
    }

    /** GET /api/admin/products?page= */
    public function index(Request $request): Response
    {
        $result = $this->products->paginateForAdmin(max(1, (int) $request->query('page', 1)));
        $result['items'] = array_map(static fn (Product $p): array => $p->toArray(), $result['items']);

        return $this->json($result);
    }

    /** GET /api/admin/products/{id} */
    public function show(Request $request, array $params): Response
    {
        $product = $this->products->find((int) $params['id']);

        return $product === null ? $this->notFound() : $this->json(['product' => $product->toArray()]);
    }

    /** POST /api/admin/products */
    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    /** POST /api/admin/products/{id} */
    public function update(Request $request, array $params): Response
    {
        $id = (int) $params['id'];

        return $this->products->find($id) === null ? $this->notFound() : $this->save($request, $id);
    }

    /** DELETE /api/admin/products/{id} */
    public function destroy(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        if ($this->products->find($id) === null) {
            return $this->notFound();
        }
        $this->service->delete($id);

        return Response::noContent();
    }

    /* ------------------------------------------------------------------ */

    private function save(Request $request, ?int $id): Response
    {
        $data = $this->gather($request);
        if ($errors = $this->validate($data, $id)) {
            return $this->error('入力内容を確認してください。', 422, $errors);
        }

        $files = normalize_files($request->file('images'));
        $primary = $request->post('primary_image_index');
        $primary = ($primary === null || $primary === '') ? null : max(0, (int) $primary);

        try {
            $product = $id === null
                ? $this->service->create($data, $files, $primary)
                : $this->service->update($id, $data, $files, $primary);
        } catch (UploadException $e) {
            return $this->error($e->getMessage(), 422, ['images' => [$e->getMessage()]]);
        }

        return $this->json(['product' => $product->toArray()], $id === null ? 201 : 200);
    }

    /** @return array<string,mixed> */
    private function gather(Request $r): array
    {
        $category = $r->post('category_id');
        $specs = [];
        foreach ((array) $r->post('specs', []) as $row) {
            if (is_array($row)) {
                $specs[] = [
                    'label' => trim((string) ($row['label'] ?? '')),
                    'value' => trim((string) ($row['value'] ?? '')),
                    'unit' => trim((string) ($row['unit'] ?? '')),
                ];
            }
        }

        return [
            'model_code' => trim((string) $r->post('model_code', '')),
            'name' => trim((string) $r->post('name', '')),
            'slug' => trim((string) $r->post('slug', '')),
            'category_id' => ($category === null || $category === '') ? null : (int) $category,
            'motor_type' => (string) $r->post('motor_type', ''),
            'rated_voltage' => $r->post('rated_voltage', ''),
            'gear_ratio' => trim((string) $r->post('gear_ratio', '')),
            'body_diameter' => $r->post('body_diameter', ''),
            'rated_torque' => $r->post('rated_torque', ''),
            'rated_speed' => $r->post('rated_speed', ''),
            'noise_level' => $r->post('noise_level', ''),
            'life_hours' => $r->post('life_hours', ''),
            'description' => trim((string) $r->post('description', '')),
            'is_published' => filter_var($r->post('is_published', false), FILTER_VALIDATE_BOOL),
            'is_featured' => filter_var($r->post('is_featured', false), FILTER_VALIDATE_BOOL),
            'sort_order' => (int) $r->post('sort_order', 0),
            'specs' => $specs,
        ];
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,list<string>> field => messages; empty when valid
     */
    private function validate(array $data, ?int $exceptId): array
    {
        $validator = new Validator($data);
        $validator->validate(
            [
                'model_code' => 'required|string|max:60',
                'name' => 'required|string|max:200',
                'slug' => 'string|max:220',
                'motor_type' => 'in:,brushed,brushless',
                'rated_voltage' => 'numeric',
                'body_diameter' => 'integer',
                'rated_torque' => 'numeric',
                'rated_speed' => 'integer',
                'noise_level' => 'numeric',
                'life_hours' => 'integer',
                'description' => 'string|max:5000',
            ],
            [
                'model_code' => '型番', 'name' => '製品名', 'slug' => 'スラッグ', 'motor_type' => 'モータ種類',
                'rated_voltage' => '定格電圧', 'body_diameter' => '外径', 'rated_torque' => '定格トルク',
                'rated_speed' => '定格回転数', 'noise_level' => '騒音', 'life_hours' => '寿命', 'description' => '説明',
            ]
        );
        $errors = $validator->errors();

        $slug = str_slug($data['slug'] !== '' ? $data['slug'] : $data['model_code']);
        if ($slug === '') {
            $errors['slug'][] = '型番またはスラッグから URL を生成できません。';
        } elseif ($this->products->slugExists($slug, $exceptId)) {
            $errors['slug'][] = "スラッグ「{$slug}」は既に使われています。";
        }

        if ($data['category_id'] !== null && !$this->categories->exists($data['category_id'])) {
            $errors['category_id'][] = 'カテゴリの選択が不正です。';
        }

        return $errors;
    }
}
