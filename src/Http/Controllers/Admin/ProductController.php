<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\App;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\ImageUploadService;
use App\Service\ProductService;
use App\Service\UploadException;
use App\Validation\Validator;

/**
 * Admin product CRUD. Create/update take multipart form data:
 * text fields, `labels` (comma-separated), an optional `image` file and
 * `remove_image=1` to clear the current image.
 */
final class ProductController extends Controller
{
    private const RULES = [
        'model_code' => 'required|string|max:60',
        'name' => 'required|string|max:200',
        'slug' => 'string|max:220',
        'description' => 'string|max:5000',
        'stock' => 'integer',
        'labels' => 'string|max:400',
    ];
    private const LABELS = [
        'model_code' => '型番', 'name' => '製品名', 'slug' => 'スラッグ',
        'description' => '説明', 'stock' => '在庫', 'labels' => 'ラベル',
    ];

    private ProductRepository $products;
    private ProductService $service;

    public function __construct()
    {
        $db = App::db();
        $this->products = new ProductRepository($db);
        $this->service = new ProductService(
            $this->products,
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
        $product = $this->products->find((int) $params['id']);

        return $product === null ? $this->notFound() : $this->save($request, $product);
    }

    /** DELETE /api/admin/products/{id} */
    public function destroy(Request $request, array $params): Response
    {
        $product = $this->products->find((int) $params['id']);
        if ($product === null) {
            return $this->notFound();
        }
        $this->service->delete($product);

        return Response::noContent();
    }

    /* ------------------------------------------------------------------ */

    private function save(Request $request, ?Product $existing): Response
    {
        $data = [];
        foreach (array_keys(self::RULES) as $field) {
            $data[$field] = trim((string) $request->post($field, ''));
        }
        $data['is_published'] = filter_var($request->post('is_published', false), FILTER_VALIDATE_BOOL);
        $data['is_featured'] = filter_var($request->post('is_featured', false), FILTER_VALIDATE_BOOL);
        $data['sort_order'] = (int) $request->post('sort_order', 0);

        if ($errors = $this->validate($data, $existing?->id)) {
            return $this->error('入力内容を確認してください。', 422, $errors);
        }

        $image = normalize_files($request->file('image'))[0] ?? null;
        if ($image !== null && $image['error'] === UPLOAD_ERR_NO_FILE) {
            $image = null;
        }
        $removeImage = filter_var($request->post('remove_image', false), FILTER_VALIDATE_BOOL);

        try {
            $product = $existing === null
                ? $this->service->create($data, $image)
                : $this->service->update($existing, $data, $image, $removeImage);
        } catch (UploadException $e) {
            return $this->error($e->getMessage(), 422, ['image' => [$e->getMessage()]]);
        }

        return $this->json(['product' => $product->toArray()], $existing === null ? 201 : 200);
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,list<string>>
     */
    private function validate(array $data, ?int $exceptId): array
    {
        $validator = new Validator($data);
        $validator->validate(self::RULES, self::LABELS);
        $errors = $validator->errors();

        $slug = str_slug($data['slug'] !== '' ? $data['slug'] : $data['model_code']);
        if ($slug === '') {
            $errors['slug'][] = '型番またはスラッグから URL を生成できません。';
        } elseif ($this->products->slugExists($slug, $exceptId)) {
            $errors['slug'][] = "スラッグ「{$slug}」は既に使われています。";
        }

        return $errors;
    }
}
