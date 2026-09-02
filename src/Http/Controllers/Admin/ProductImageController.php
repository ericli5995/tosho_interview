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

/**
 * Add / delete / re-assign the primary flag for a product's images.
 * These endpoints back the edit-form image panel (they also work without JS).
 */
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
        $this->uploads = new ImageUploadService(
            (string) config('app.paths.uploads'),
            (int) config('app.upload_max_bytes', 5_242_880)
        );
    }

    public function store(Request $request, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->products->find($id) === null) {
            return $this->notFound();
        }

        $files = normalize_files($request->file('images'));
        $order = $this->images->maxSortOrder($id) + 1;
        $stored = 0;

        try {
            foreach ($files as $file) {
                if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $meta = $this->uploads->store($file, $id);
                $imageId = $this->images->insert(new ProductImage(
                    productId: $id,
                    path: $meta['path'],
                    thumbPath: $meta['thumb_path'],
                    mediumPath: $meta['medium_path'],
                    alt: '',
                    sortOrder: $order++,
                    width: $meta['width'],
                    height: $meta['height'],
                    sizeBytes: $meta['size_bytes'],
                    mime: $meta['mime'],
                ));

                if (!$this->images->hasPrimary($id)) {
                    $this->images->setPrimary($imageId, $id);
                }

                $stored++;
            }
        } catch (UploadException $e) {
            flash_set('error', $e->getMessage());

            return $this->redirect('/admin/products/' . $id . '/edit');
        }

        flash_set($stored > 0 ? 'success' : 'info', "{$stored} 件の画像を追加しました。");

        return $this->redirect('/admin/products/' . $id . '/edit');
    }

    public function setPrimary(Request $request, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);
        $imageId = (int) $request->post('image_id', 0);

        $image = $this->images->find($imageId);
        if ($image !== null && $image->productId === $id) {
            $this->images->setPrimary($imageId, $id);
            flash_set('success', '主画像を設定しました。');
        }

        return $this->redirect('/admin/products/' . $id . '/edit');
    }

    public function destroy(Request $request, array $params): Response
    {
        $id = (int) ($params['id'] ?? 0);
        $imageId = (int) ($params['imageId'] ?? 0);

        $image = $this->images->find($imageId);
        if ($image !== null && $image->productId === $id) {
            $this->images->delete($imageId);
            $this->uploads->deleteFiles([$image->path, $image->thumbPath, $image->mediumPath]);

            if ($image->isPrimary) {
                $remaining = $this->images->forProduct($id);
                if ($remaining !== []) {
                    $this->images->setPrimary($remaining[0]->id, $id);
                }
            }

            flash_set('success', '画像を削除しました。');
        }

        return $this->redirect('/admin/products/' . $id . '/edit');
    }
}
