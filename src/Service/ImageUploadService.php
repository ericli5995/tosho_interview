<?php

declare(strict_types=1);

namespace App\Service;

use GdImage;

/**
 * Validates an uploaded image, re-encodes it with GD (dropping metadata and
 * any embedded payload) and writes three variants into
 * storage/uploads/products/{id}/: {hash}.ext, {hash}_medium.ext, {hash}_thumb.ext.
 */
final class ImageUploadService
{
    private const ALLOWED = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const WIDTHS = ['' => 2000, '_medium' => 1000, '_thumb' => 320];

    public function __construct(private string $uploadRoot, private int $maxBytes = 5_242_880)
    {
        $this->uploadRoot = rtrim($uploadRoot, '/');
    }

    /**
     * @param array{tmp_name?:string,error?:int,size?:int} $file a normalized $_FILES row
     * @return list<string> relative paths: [original, medium, thumb]
     */
    public function store(array $file, int $productId): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || (int) ($file['size'] ?? 0) > $this->maxBytes) {
            throw new UploadException(sprintf('画像サイズが上限（約%d MB）を超えています。', (int) round($this->maxBytes / 1048576)));
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($error !== UPLOAD_ERR_OK || $tmp === '' || !is_file($tmp) || (PHP_SAPI !== 'cli' && !is_uploaded_file($tmp))) {
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

        $base = bin2hex(random_bytes(12));
        $ext = self::ALLOWED[$mime];
        $paths = [];
        try {
            foreach (self::WIDTHS as $suffix => $maxWidth) {
                $relative = "products/{$productId}/{$base}{$suffix}.{$ext}";
                $this->write($source, $mime, "{$this->uploadRoot}/{$relative}", min(imagesx($source), $maxWidth));
                $paths[] = $relative;
            }
        } catch (\Throwable $e) {
            $this->deleteFiles($paths);
            throw new UploadException('画像の保存に失敗しました。');
        } finally {
            imagedestroy($source);
        }

        return $paths;
    }

    /** @param list<string|null> $relativePaths */
    public function deleteFiles(array $relativePaths): void
    {
        foreach ($relativePaths as $relative) {
            if (is_string($relative) && $relative !== '' && is_file("{$this->uploadRoot}/{$relative}")) {
                @unlink("{$this->uploadRoot}/{$relative}");
            }
        }
        // drop the product directory once it is empty
        foreach (array_unique(array_map('dirname', array_filter($relativePaths, 'is_string'))) as $dir) {
            @rmdir("{$this->uploadRoot}/{$dir}");
        }
    }

    private function write(GdImage $source, string $mime, string $destination, int $width): void
    {
        $scaled = imagescale($source, max(1, $width));
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
