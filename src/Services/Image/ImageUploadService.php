<?php

declare(strict_types=1);

namespace App\Services\Image;

use GdImage;

/**
 * Validates an uploaded image, re-encodes it with GD (dropping metadata and
 * any embedded payload, capping the width) and writes it to
 * storage/uploads/products/{id}/{hash}.ext.
 */
final class ImageUploadService
{
    private const ALLOWED = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const MAX_WIDTH = 1200;

    public function __construct(private string $uploadRoot, private int $maxBytes = 5_242_880)
    {
        $this->uploadRoot = rtrim($uploadRoot, '/');
    }

    /**
     * @param array{tmp_name?:string,error?:int,size?:int} $file a normalized $_FILES row
     * @return string path relative to the upload root
     */
    public function store(array $file, int $productId): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || (int) ($file['size'] ?? 0) > $this->maxBytes) {
            throw new UploadException(sprintf('画像サイズが上限（約%d MB）を超えています。', (int) round($this->maxBytes / 1048576)));
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($error !== UPLOAD_ERR_OK || $tmp === '' || !is_file($tmp) || !is_uploaded_file($tmp)) {
            throw new UploadException('画像のアップロードに失敗しました。');
        }

        $mime = (string) (@getimagesize($tmp)['mime'] ?? '');
        if (!isset(self::ALLOWED[$mime])) {
            throw new UploadException('対応形式は JPEG / PNG / WebP です。');
        }
        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png' => @imagecreatefrompng($tmp),
            default => @imagecreatefromwebp($tmp),
        };
        if (!$source instanceof GdImage) {
            throw new UploadException('画像を読み込めませんでした。');
        }

        $dir = "{$this->uploadRoot}/products/{$productId}";
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new UploadException('アップロード先ディレクトリを作成できません。');
        }

        $relative = "products/{$productId}/" . bin2hex(random_bytes(12)) . '.' . self::ALLOWED[$mime];
        try {
            $this->write($source, $mime, "{$this->uploadRoot}/{$relative}");
        } catch (\Throwable $e) {
            $this->delete($relative);
            throw new UploadException('画像の保存に失敗しました。');
        } finally {
            imagedestroy($source);
        }

        return $relative;
    }

    /** Remove a stored image (and its product directory once empty). No-op for null. */
    public function delete(?string $relative): void
    {
        if ($relative === null || $relative === '') {
            return;
        }
        @unlink("{$this->uploadRoot}/{$relative}");
        @rmdir("{$this->uploadRoot}/" . dirname($relative));
    }

    private function write(GdImage $source, string $mime, string $destination): void
    {
        $scaled = imagescale($source, min(imagesx($source), self::MAX_WIDTH));
        if (!$scaled instanceof GdImage) {
            throw new \RuntimeException('imagescale failed');
        }
        if ($mime !== 'image/jpeg') {
            imagealphablending($scaled, false);
            imagesavealpha($scaled, true);
        }
        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($scaled, $destination, 82),
            'image/png' => imagepng($scaled, $destination, 6),
            default => imagewebp($scaled, $destination, 82),
        };
        imagedestroy($scaled);
        if (!$ok) {
            throw new \RuntimeException('image write failed');
        }
    }
}
