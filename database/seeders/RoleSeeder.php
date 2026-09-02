<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Platform administrator'],
            ['name' => 'Sub Admin', 'slug' => 'sub_admin', 'description' => 'Limited admin'],
            ['name' => 'Private Seller', 'slug' => 'seller', 'description' => 'Business seller with store'],
            ['name' => 'Customer', 'slug' => 'customer', 'description' => 'Buyer'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }

        $permissions = [
            ['name' => 'Manage Users', 'slug' => 'users.manage', 'module' => 'users'],
            ['name' => 'Manage Sellers', 'slug' => 'sellers.manage', 'module' => 'sellers'],
            ['name' => 'Manage Products', 'slug' => 'products.manage', 'module' => 'products'],
            ['name' => 'Manage Orders', 'slug' => 'orders.manage', 'module' => 'orders'],
            ['name' => 'Manage CMS', 'slug' => 'cms.manage', 'module' => 'cms'],
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'module' => 'settings'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
        }

        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $admin->permissions()->sync(Permission::pluck('id'));
        }
    }
}
