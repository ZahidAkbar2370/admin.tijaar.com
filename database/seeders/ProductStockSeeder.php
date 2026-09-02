<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductStockSeeder extends Seeder
{
    /**
     * Set random inventory quantity on published products (and variants) that are out of stock.
     * Ensures Flash Deals, Featured, New Arrival and other home-page products show In Stock.
     */
    public function run(): void
    {
        $updated = Product::where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('quantity')->orWhere('quantity', '<=', 0);
            })
            ->get();

        foreach ($updated as $product) {
            $product->update(['quantity' => rand(20, 200)]);
        }

        $updatedVariants = ProductVariant::whereHas('product', fn ($q) => $q->where('status', 'published'))
            ->where(function ($q) {
                $q->whereNull('quantity')->orWhere('quantity', '<=', 0);
            })
            ->get();

        foreach ($updatedVariants as $variant) {
            $variant->update(['quantity' => rand(10, 80)]);
        }

        $this->command?->info('ProductStockSeeder: Set random stock on ' . $updated->count() . ' products and ' . $updatedVariants->count() . ' variants.');
    }
}
