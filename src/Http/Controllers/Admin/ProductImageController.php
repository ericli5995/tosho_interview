<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\App;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Entity\ProductImage;
use App\Repository\ProductImageRepository;
use App\Repository\ProductRepository;
use App\Service\ImageUploadService;
use App\Service\UploadException;

/** Add / remove / set-primary images on an existing product. */
final class ProductImageController extends Controller
{
    private ProductRepository $products;
    private ProductImageRepository $images;
    private ImageUploadService $uploads;

    public function __construct()
    {
        $db = App::db();
        $this->products = new ProductRepository($db);
        $this->images = new ProductImageRepository($db);
        $this->uploads = new ImageUploadService((string) config('app.paths.uploads'), (int) config('app.upload_max_bytes'));
    }

    /** POST /api/admin/products/{id}/images  (multipart images[]) -> {images} */
    public function store(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        if ($this->products->find($id) === null) {
            return $this->notFound();
        }

        $order = $this->images->maxSortOrder($id) + 1;
        try {
            foreach (normalize_files($request->file('images')) as $file) {
                if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $meta = $this->uploads->store($file, $id);
                $imageId = $this->images->insert(new ProductImage(
                    productId: $id,
                    path: $meta['path'],
                    thumbPath: $meta['thumb_path'],
                    mediumPath: $meta['medium_path'],
                    sortOrder: $order++,
                    width: $meta['width'],
                    height: $meta['height'],
                    sizeBytes: $meta['size_bytes'],
                    mime: $meta['mime'],
                ));
                if (!$this->images->hasPrimary($id)) {
                    $this->images->setPrimary($imageId, $id);
                }
            }
        } catch (UploadException $e) {
            return $this->error($e->getMessage(), 422, ['images' => [$e->getMessage()]]);
        }

        return $this->json(['images' => $this->list($id)]);
    }

    /** DELETE /api/admin/products/{id}/images/{imageId} */
    public function destroy(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $image = $this->owned($id, (int) $params['imageId']);
        if ($image === null) {
            return $this->notFound();
        }

        $this->images->delete($image->id);
        $this->uploads->deleteFiles([$image->path, $image->thumbPath, $image->mediumPath]);
        if ($image->isPrimary && ($remaining = $this->images->forProduct($id)) !== []) {
            $this->images->setPrimary($remaining[0]->id, $id);
        }

        return Response::noContent();
    }

    /** PUT /api/admin/products/{id}/images/{imageId}/primary */
    public function setPrimary(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $image = $this->owned($id, (int) $params['imageId']);
        if ($image === null) {
            return $this->notFound();
        }
        $this->images->setPrimary($image->id, $id);

        return Response::noContent();
    }

    /* ------------------------------------------------------------------ */

    /** The image only if it belongs to the product (prevents cross-product tampering). */
    private function owned(int $productId, int $imageId): ?ProductImage
    {
        $image = $this->images->find($imageId);

        return $image !== null && $image->productId === $productId ? $image : null;
    }

    /** @return list<array<string,mixed>> */
    private function list(int $productId): array
    {
        return array_map(static fn (ProductImage $i): array => $i->toArray(), $this->images->forProduct($productId));
    }
}
