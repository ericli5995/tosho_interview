<?php

declare(strict_types=1);

namespace App\Service;

use GdImage;

/**
 * Validates an uploaded image, re-encodes it with GD (stripping any metadata or
 * embedded payload), and writes three size variants into
 * storage/uploads/products/{id}/. Returns the relative paths + metadata for the
 * product_images row. It never touches the database.
 */
final class ImageUploadService
{
    /** mime => file extension */
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private string $uploadRoot,
        private int $maxBytes = 5_242_880,
        private int $originalMaxWidth = 2000,
        private int $mediumWidth = 1000,
        private int $thumbWidth = 320,
    ) {
        $this->uploadRoot = rtrim($uploadRoot, '/');
    }

    /**
     * @param array{name?:string,tmp_name?:string,error?:int,size?:int} $file
     * @return array{path:string,medium_path:string,thumb_path:string,width:int,height:int,size_bytes:int,mime:string}
     */
    public function store(array $file, int $productId): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new UploadException('画像のサイズが大きすぎます。');
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new UploadException('画像のアップロードに失敗しました。');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp)) {
            throw new UploadException('アップロードファイルが見つかりません。');
        }
        // is_uploaded_file() is the real check under a web request; skip it in CLI (tests).
        if (PHP_SAPI !== 'cli' && !is_uploaded_file($tmp)) {
            throw new UploadException('不正なアップロードです。');
        }

        $size = (int) ($file['size'] ?? filesize($tmp) ?: 0);
        if ($size > $this->maxBytes) {
            throw new UploadException(
                sprintf('画像サイズが上限（約%d MB）を超えています。', (int) round($this->maxBytes / 1048576))
            );
        }

        $mime = $this->detectMime($tmp);
        if (!isset(self::ALLOWED[$mime])) {
            throw new UploadException('対応形式は JPEG / PNG / WebP です。');
        }

        $source = $this->createImage($tmp, $mime);
        if (!$source instanceof GdImage) {
            throw new UploadException('画像を読み込めませんでした。');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $extension = self::ALLOWED[$mime];
        $directory = $this->uploadRoot . '/products/' . $productId;
        $this->ensureDirectory($directory);

        $base = bin2hex(random_bytes(16));
        $relative = [
            'path' => "products/{$productId}/{$base}.{$extension}",
            'medium_path' => "products/{$productId}/{$base}_medium.{$extension}",
            'thumb_path' => "products/{$productId}/{$base}_thumb.{$extension}",
        ];

        try {
            $this->writeVariant($source, $mime, "{$directory}/{$base}.{$extension}", min($width, $this->originalMaxWidth));
            $this->writeVariant($source, $mime, "{$directory}/{$base}_medium.{$extension}", min($width, $this->mediumWidth));
            $this->writeVariant($source, $mime, "{$directory}/{$base}_thumb.{$extension}", min($width, $this->thumbWidth));
        } catch (\Throwable $e) {
            imagedestroy($source);
            $this->deleteFiles(array_values($relative));
            throw new UploadException('画像の保存に失敗しました。');
        }

        imagedestroy($source);

        return $relative + [
            'width' => $width,
            'height' => $height,
            'size_bytes' => $size,
            'mime' => $mime,
        ];
    }

    /** @param list<string|null> $relativePaths */
    public function deleteFiles(array $relativePaths): void
    {
        foreach ($relativePaths as $relative) {
            if (!is_string($relative) || $relative === '') {
                continue;
            }
            $absolute = $this->uploadRoot . '/' . ltrim($relative, '/');
            if (is_file($absolute)) {
                @unlink($absolute);
            }
        }
    }

    public function removeProductDirectory(int $productId): void
    {
        $directory = $this->uploadRoot . '/products/' . $productId;
        if (is_dir($directory) && (glob($directory . '/*') ?: []) === []) {
            @rmdir($directory);
        }
    }

    /* ------------------------------------------------------------------ */

    private function detectMime(string $path): string
    {
        $info = @getimagesize($path);
        if (is_array($info) && isset($info['mime'])) {
            return (string) $info['mime'];
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = (string) finfo_file($finfo, $path);
                finfo_close($finfo);

                return $mime;
            }
        }

        return '';
    }

    private function createImage(string $path, string $mime): GdImage|false
    {
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };
    }

    private function writeVariant(GdImage $source, string $mime, string $destination, int $targetWidth): void
    {
        $targetWidth = max(1, $targetWidth);
        $scaled = imagescale($source, $targetWidth);
        if (!$scaled instanceof GdImage) {
            throw new \RuntimeException('imagescale() failed');
        }

        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($scaled, false);
            imagesavealpha($scaled, true);
        }

        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($scaled, $destination, 82),
            'image/png' => imagepng($scaled, $destination, 6),
            'image/webp' => imagewebp($scaled, $destination, 82),
            default => false,
        };

        imagedestroy($scaled);

        if ($ok === false) {
            throw new \RuntimeException('image write failed');
        }

        @chmod($destination, 0644);
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new UploadException('アップロード先ディレクトリを作成できません。');
        }
    }
}
