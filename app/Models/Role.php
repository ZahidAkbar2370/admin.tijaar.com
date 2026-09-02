<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    /** System roles that cannot be edited or deleted from admin. */
    public const PROTECTED_SLUGS = [
        'admin',
        'sub_admin',
        'customer',
        'seller',
        'private_seller',
    ];

    protected $fillable = ['name', 'slug', 'description'];

    public static function isProtected(self|string $role): bool
    {
        $slug = $role instanceof self ? $role->slug : $role;

        return in_array($slug, self::PROTECTED_SLUGS, true);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role');
    }
}
