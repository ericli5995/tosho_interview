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
            new ImageUploadService(
                (string) config('app.paths.uploads'),
                (int) config('app.upload_max_bytes', 5_242_880)
            ),
            $db
        );
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));

        return $this->view('admin/products/index', [
            'title' => '製品一覧',
            'result' => $this->products->paginateForAdmin($page),
        ], 'layouts/admin');
    }

    public function create(Request $request): Response
    {
        return $this->view('admin/products/form', [
            'title' => '製品を登録',
            'mode' => 'create',
            'product' => new Product(),
            'categories' => $this->categories->all(),
            'action' => '/admin/products',
        ], 'layouts/admin');
    }

    public function store(Request $request): Response
    {
        $data = $this->gather($request);
        $errors = $this->validate($data, null);

        if ($errors !== []) {
            return $this->back($errors, $data, '/admin/products/create');
        }

        try {
            $product = $this->service->create(
                $data,
                normalize_files($request->file('images')),
                $this->primaryIndex($request)
            );
        } catch (UploadException $e) {
            return $this->back([$e->getMessage()], $data, '/admin/products/create');
        }

        flash_set('success', "製品「{$product->modelCode}」を登録しました。");

        return $this->redirect('/admin/products/' . $product->id . '/edit');
    }

    public function edit(Request $request, array $params): Response
    {
        $product = $this->products->find((int) ($params['id'] ?? 0));
        if ($product === null) {
            return $this->notFound();
        }

        return $this->view('admin/products/form', [
            'title' => "製品を編集: {$product->modelCode}",
            'mode' => 'edit',
            'product' => $product,
            'categories' => $this->categories->all(),
            'action' => '/admin/products/' . $product->id,
        ], 'layouts/admin');
    }

    public function update(Request $request, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->products->find($id) === null) {
            return $this->notFound();
        }

        $data = $this->gather($request);
        $errors = $this->validate($data, $id);

        if ($errors !== []) {
            return $this->back($errors, $data, '/admin/products/' . $id . '/edit');
        }

        try {
            $this->service->update(
                $id,
                $data,
                normalize_files($request->file('images')),
                $this->primaryIndex($request)
            );
        } catch (UploadException $e) {
            return $this->back([$e->getMessage()], $data, '/admin/products/' . $id . '/edit');
        }

        flash_set('success', '製品を更新しました。');

        return $this->redirect('/admin/products/' . $id . '/edit');
    }

    public function destroy(Request $request, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);

        if ($this->products->find($id) !== null) {
            $this->service->delete($id);
            flash_set('success', '製品を削除しました。');
        }

        return $this->redirect('/admin/products');
    }

    /* ------------------------------------------------------------------ */

    /** @return array<string,mixed> */
    private function gather(Request $request): array
    {
        $categoryId = $request->post('category_id');

        return [
            'model_code' => trim((string) $request->post('model_code', '')),
            'name' => trim((string) $request->post('name', '')),
            'slug' => trim((string) $request->post('slug', '')),
            'category_id' => ($categoryId === null || $categoryId === '') ? null : (int) $categoryId,
            'motor_type' => (string) $request->post('motor_type', ''),
            'rated_voltage' => $request->post('rated_voltage', ''),
            'gear_ratio' => trim((string) $request->post('gear_ratio', '')),
            'body_diameter' => $request->post('body_diameter', ''),
            'rated_torque' => $request->post('rated_torque', ''),
            'rated_speed' => $request->post('rated_speed', ''),
            'noise_level' => $request->post('noise_level', ''),
            'life_hours' => $request->post('life_hours', ''),
            'description' => trim((string) $request->post('description', '')),
            'is_published' => (bool) $request->post('is_published', false),
            'is_featured' => (bool) $request->post('is_featured', false),
            'sort_order' => (int) $request->post('sort_order', 0),
            'specs' => $this->gatherSpecs($request),
        ];
    }

    /** @return list<array{label:string,value:string,unit:string}> */
    private function gatherSpecs(Request $request): array
    {
        $rows = $request->post('specs', []);
        if (!is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'label' => trim((string) ($row['label'] ?? '')),
                'value' => trim((string) ($row['value'] ?? '')),
                'unit' => trim((string) ($row['unit'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * @param array<string,mixed> $data
     * @return list<string>
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
                'model_code' => '型番',
                'name' => '製品名',
                'slug' => 'スラッグ',
                'motor_type' => 'モータ種類',
                'rated_voltage' => '定格電圧',
                'body_diameter' => '外径',
                'rated_torque' => '定格トルク',
                'rated_speed' => '定格回転数',
                'noise_level' => '騒音',
                'life_hours' => '寿命',
                'description' => '説明',
            ]
        );

        $errors = $validator->flatErrors();

        $slugSource = $data['slug'] !== '' ? $data['slug'] : ($data['model_code'] ?? '');
        $slug = str_slug((string) $slugSource);
        if ($slug === '') {
            $errors[] = '型番またはスラッグから URL を生成できません。';
        } elseif ($this->products->slugExists($slug, $exceptId)) {
            $errors[] = "スラッグ「{$slug}」は既に使われています。";
        }

        if (($data['category_id'] ?? null) !== null && !$this->categories->exists((int) $data['category_id'])) {
            $errors[] = 'カテゴリの選択が不正です。';
        }

        return $errors;
    }

    private function primaryIndex(Request $request): ?int
    {
        $value = $request->post('primary_image_index');
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (int) $value);
    }

    /**
     * @param list<string> $errors
     * @param array<string,mixed> $data
     */
    private function back(array $errors, array $data, string $to): Response
    {
        foreach ($errors as $message) {
            flash_set('error', $message);
        }

        $old = $data;
        unset($old['specs']);
        $old['specs_json'] = json_encode($data['specs'] ?? [], JSON_UNESCAPED_UNICODE);
        set_old($old);

        return $this->redirect($to);
    }
}
