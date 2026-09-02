<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'body',
        'event_key',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function getBySlug(string $slug): ?self
    {
        return static::query()->where('slug', $slug)->where('is_active', true)->first();
    }

    public function render(array $data): string
    {
        return self::replaceInString($this->body ?? '', $data);
    }

    public static function replaceInString(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{{'.$key.'}}', (string) $value, $text);
        }

        return $text;
    }

    /**
     * Render template by slug; fall back to $fallback if missing/empty.
     */
    public static function renderSlug(string $slug, array $data, string $fallback = ''): string
    {
        $tpl = self::getBySlug($slug);
        if (! $tpl) {
            return self::replaceInString($fallback, $data);
        }
        $out = trim($tpl->render($data));

        return $out !== '' ? $out : self::replaceInString($fallback, $data);
    }
}
