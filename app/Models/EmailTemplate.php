<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['slug', 'name', 'subject', 'body_html', 'body_plain', 'description'];

    public static function getBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    public function replacePlaceholders(array $data): string
    {
        $body = $this->body_html ?: $this->body_plain ?: '';
        return $this->replaceInString($body, $data);
    }

    public function replaceSubject(array $data): string
    {
        return $this->replaceInString($this->subject, $data);
    }

    public static function replaceInString(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{{' . $key . '}}', (string) $value, $text);
        }
        return $text;
    }
}
