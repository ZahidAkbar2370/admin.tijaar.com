<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImageDeliveryService
{
    private const MAX_WIDTH = 2400;

    private const MIN_QUALITY = 40;

    private const MAX_QUALITY = 95;

    /** @var list<string> */
    private const ALLOWED_PREFIXES = ['upload/', 'storage/'];

    /**
     * @return array{path: string, mime: string}
     */
    public function deliver(string $relativePath, int $width, int $quality, string $format): array
    {
        $relativePath = $this->normalizePath($relativePath);
        $sourcePath = $this->resolveSourcePath($relativePath);

        if (! $sourcePath || ! is_file($sourcePath)) {
            throw new \RuntimeException('Image not found', 404);
        }

        $width = max(16, min(self::MAX_WIDTH, $width));
        $quality = max(self::MIN_QUALITY, min(self::MAX_QUALITY, $quality));
        $format = in_array($format, ['webp', 'jpeg', 'png'], true) ? $format : 'webp';

        $cacheKey = md5($relativePath.'|'.$width.'|'.$quality.'|'.$format);
        $cacheDir = storage_path('app/image-delivery');
        $ext = $format === 'jpeg' ? 'jpg' : $format;
        $cacheFile = $cacheDir.'/'.$cacheKey.'.'.$ext;

        if (is_file($cacheFile) && filemtime($cacheFile) >= filemtime($sourcePath)) {
            return ['path' => $cacheFile, 'mime' => $this->mimeForFormat($format)];
        }

        if (! File::isDirectory($cacheDir)) {
            File::makeDirectory($cacheDir, 0755, true);
        }

        $this->generateResized($sourcePath, $cacheFile, $width, $quality, $format);

        return ['path' => $cacheFile, 'mime' => $this->mimeForFormat($format)];
    }

    private function normalizePath(string $path): string
    {
        $path = urldecode(trim($path));
        $path = ltrim($path, '/');

        if (preg_match('#^https?://#i', $path)) {
            $parsed = parse_url($path);
            $path = ltrim($parsed['path'] ?? '', '/');
        }

        if (str_contains($path, '..')) {
            throw new \RuntimeException('Forbidden path', 403);
        }

        $allowed = false;
        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (Str::startsWith($path, $prefix)) {
                $allowed = true;
                break;
            }
        }

        if (! $allowed) {
            throw new \RuntimeException('Forbidden path', 403);
        }

        return $path;
    }

    private function resolveSourcePath(string $path): ?string
    {
        if (Str::startsWith($path, 'upload/')) {
            return public_path($path);
        }

        if (Str::startsWith($path, 'storage/')) {
            return storage_path('app/public/'.substr($path, strlen('storage/')));
        }

        return null;
    }

    private function generateResized(string $source, string $dest, int $maxWidth, int $quality, string $format): void
    {
        if (! extension_loaded('gd')) {
            copy($source, $dest);

            return;
        }

        $info = @getimagesize($source);
        if (! $info) {
            copy($source, $dest);

            return;
        }

        [$origW, $origH, $type] = $info;
        $newW = min($maxWidth, $origW);
        $newH = (int) round($origH * ($newW / max(1, $origW)));

        $srcImg = $this->loadImage($source, $type);
        if (! $srcImg) {
            copy($source, $dest);

            return;
        }

        $dstImg = imagecreatetruecolor($newW, $newH);
        if (in_array($format, ['png', 'webp'], true)) {
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
            $transparent = imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
            imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $transparent);
        }

        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($srcImg);

        switch ($format) {
            case 'webp':
                imagewebp($dstImg, $dest, $quality);
                break;
            case 'png':
                imagepng($dstImg, $dest, (int) round((100 - $quality) / 10));
                break;
            default:
                imagejpeg($dstImg, $dest, $quality);
        }

        imagedestroy($dstImg);
    }

    /**
     * @param  resource|\GdImage  $img
     */
    private function loadImage(string $source, int $type): \GdImage|false
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($source),
            IMAGETYPE_PNG => imagecreatefrompng($source),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($source) : false,
            IMAGETYPE_GIF => imagecreatefromgif($source),
            default => false,
        };
    }

    private function mimeForFormat(string $format): string
    {
        return match ($format) {
            'png' => 'image/png',
            'jpeg' => 'image/jpeg',
            default => 'image/webp',
        };
    }
}
