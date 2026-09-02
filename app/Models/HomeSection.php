<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    protected $fillable = ['key', 'title', 'config', 'is_active', 'sort_order'];

    protected $casts = ['config' => 'array', 'is_active' => 'boolean'];
}
