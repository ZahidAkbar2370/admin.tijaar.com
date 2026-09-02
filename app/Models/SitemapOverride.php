<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SitemapOverride extends Model
{
    protected $fillable = [
        'file_key',
        'mode',
        'manual_xml',
    ];

    public function isManual(): bool
    {
        return $this->mode === 'manual' && trim((string) $this->manual_xml) !== '';
    }
}
