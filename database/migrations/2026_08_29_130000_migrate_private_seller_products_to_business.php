<?php

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()
            ->where('is_private_seller', true)
            ->with('seller.store')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $storeId = $user->seller?->store?->id;
                    if (! $storeId) {
                        continue;
                    }

                    Product::query()
                        ->where('seller_id', $user->id)
                        ->where('seller_type', 'private')
                        ->update([
                            'seller_type' => 'business',
                            'store_id' => $storeId,
                        ]);

                    OrderItem::query()
                        ->where('seller_id', $user->id)
                        ->where('seller_type', 'private')
                        ->update([
                            'seller_type' => 'business',
                            'store_id' => $storeId,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Irreversible: private sellers may have mixed business listings after migration.
    }
};
