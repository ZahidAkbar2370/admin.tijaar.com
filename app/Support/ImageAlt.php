<?php

namespace App\Support;

class ImageAlt
{
    public static function resolve(?string $alt, string $fallback): string
    {
        $alt = trim((string) $alt);

        return $alt !== '' ? $alt : $fallback;
    }
}
