<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UploadHelper
{
    /**
     * Store uploaded file under public/upload/{subdir}/ and
     * return DB path like "upload/{subdir}/filename.ext".
     */
    public static function storePublic(UploadedFile $file, string $subdir): string
    {
        $subdir = trim($subdir, '/');
        $dir = public_path('upload/' . $subdir);

        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $name = Str::random(20) . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $name);

        return 'upload/' . $subdir . '/' . $name;
    }

    /**
     * Delete a file previously stored under public/upload/.
     */
    public static function deletePublic(?string $path): void
    {
        if (empty($path)) {
            return;
        }
        $path = ltrim($path, '/');
        if (!Str::startsWith($path, 'upload/')) {
            return;
        }
        $full = public_path($path);
        if (File::isFile($full)) {
            File::delete($full);
        }
    }

    /**
     * Delete a file from either public/upload/ or storage (for backward compatibility).
     */
    public static function deleteAny(?string $path): void
    {
        if (empty($path)) {
            return;
        }
        $path = ltrim($path, '/');
        if (Str::startsWith($path, 'upload/')) {
            self::deletePublic($path);
            return;
        }
        \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
    }

    /**
     * Build a full URL for an image path that may be either:
     * - "upload/..." (public path), or
     * - "some/relative/path" stored on the public disk ("storage/...").
     */
    public static function url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        $path = ltrim($path, '/');
        $base = rtrim((string) (config('app.url') ?: ''), '/');

        if (Str::startsWith($path, 'upload/')) {
            return $base !== '' ? $base . '/' . $path : asset($path);
        }

        return $base !== '' ? $base . '/storage/' . $path : asset('storage/' . $path);
    }

    /**
     * Normalize a stored path or absolute URL into upload/... or storage/... for delivery API.
     */
    public static function deliveryPath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $path = ltrim($path, '/');

        if (Str::startsWith($path, 'http://') || Str::startsWith($path, 'https://')) {
            $parsed = parse_url($path);
            $path = ltrim($parsed['path'] ?? '', '/');
        }

        if (Str::contains($path, '..')) {
            return null;
        }

        if (Str::startsWith($path, 'upload/') || Str::startsWith($path, 'storage/')) {
            return $path;
        }

        return 'storage/' . $path;
    }

    /**
     * Build an on-demand resized image URL (WebP by default) with long-lived cache headers.
     */
    public static function deliveryUrl(?string $path, int $width = 800, int $quality = 82, string $format = 'webp'): ?string
    {
        $deliveryPath = self::deliveryPath($path);
        if (! $deliveryPath) {
            return self::url($path);
        }

        $base = rtrim((string) (config('app.url') ?: ''), '/');
        if ($base === '') {
            return self::url($path);
        }

        $query = http_build_query([
            'path' => $deliveryPath,
            'w' => max(16, min(2400, $width)),
            'q' => max(40, min(95, $quality)),
            'fmt' => in_array($format, ['webp', 'jpeg', 'png'], true) ? $format : 'webp',
        ]);

        return $base . '/api/v1/media/delivery?' . $query;
    }
}

