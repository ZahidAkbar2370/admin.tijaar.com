<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockHistory;
use App\Services\StockAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function updateStock(Request $request, int $productId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
            'reason' => 'nullable|string|in:adjustment,restock,sale,return',
            'notes' => 'nullable|string|max:500',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        $product = Product::whereHas('store', fn ($q) => $q->where('seller_id', $request->user()->seller?->id))
            ->orWhere('seller_id', $request->user()->id)
            ->findOrFail($productId);

        $beforeQty = (int) $product->quantity;
        $beforeAvailable = (int) $product->getAvailableQuantity();
        $after = max(0, (int) $request->quantity);
        $change = $after - $beforeQty;

        $updateData = ['quantity' => $after];
        if ($request->has('low_stock_threshold')) {
            $updateData['low_stock_threshold'] = $request->low_stock_threshold ?: null;
        }
        $product->update($updateData);

        StockHistory::create([
            'product_id' => $product->id,
            'quantity_before' => $beforeQty,
            'quantity_after' => $after,
            'change' => $change,
            'reason' => $request->reason ?? 'adjustment',
            'user_id' => $request->user()->id,
            'notes' => $request->notes,
        ]);

        $product->refresh();
        StockAlertService::syncForProduct($product, $beforeAvailable);

        return response()->json([
            'success' => true,
            'product' => $product,
            'message' => 'Stock updated',
        ]);
    }

    public function updateLowStockThreshold(Request $request, int $productId): JsonResponse
    {
        $request->validate([
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        $product = Product::whereHas('store', fn ($q) => $q->where('seller_id', $request->user()->seller?->id))
            ->orWhere('seller_id', $request->user()->id)
            ->findOrFail($productId);

        $product->update([
            'low_stock_threshold' => $request->low_stock_threshold ?: null,
        ]);

        return response()->json([
            'success' => true,
            'product' => $product->fresh(),
            'message' => 'Low stock alert updated',
        ]);
    }

    public function history(Request $request, int $productId): JsonResponse
    {
        $product = Product::whereHas('store', fn ($q) => $q->where('seller_id', $request->user()->seller?->id))
            ->orWhere('seller_id', $request->user()->id)
            ->findOrFail($productId);

        $history = StockHistory::where('product_id', $product->id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'history' => $history->items(),
            'pagination' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
            ],
        ]);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $user = $request->user();
        $sellerId = $user->seller?->id;
        $query = Product::where(function ($q) use ($user, $sellerId) {
            if ($sellerId) {
                $q->whereHas('store', fn ($s) => $s->where('seller_id', $sellerId));
            }
            $q->orWhere('seller_id', $user->id);
        })
            ->where('track_inventory', true)
            ->whereNotNull('low_stock_threshold')
            ->whereColumn('quantity', '<=', 'low_stock_threshold')
            ->where('quantity', '>', 0);

        $products = $query->with(['media', 'category'])->paginate(20);
        $items = $products->getCollection()->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'quantity' => (int) $p->quantity,
            'low_stock_threshold' => $p->low_stock_threshold,
            'image' => $p->getMainImageUrl(),
            'media' => $p->media->map(fn ($m) => ['path' => $m->path, 'url' => \App\Support\UploadHelper::url($m->path)])->toArray(),
            'category' => $p->category?->name,
            'alert_type' => 'low_stock',
        ]);

        return response()->json([
            'success' => true,
            'products' => $items->values()->toArray(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function outOfStock(Request $request): JsonResponse
    {
        $user = $request->user();
        $sellerId = $user->seller?->id;
        $query = Product::where(function ($q) use ($user, $sellerId) {
            if ($sellerId) {
                $q->whereHas('store', fn ($s) => $s->where('seller_id', $sellerId));
            }
            $q->orWhere('seller_id', $user->id);
        })
            ->where(function ($q) {
                $q->where('track_inventory', true)->orWhereNull('track_inventory');
            })
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where(function ($q3) {
                        $q3->whereNull('product_type')->orWhere('product_type', '!=', 'variable');
                    })->where('quantity', '<=', 0);
                })->orWhere(function ($q2) {
                    $q2->where('product_type', 'variable')
                        ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM product_variants WHERE product_id = products.id) <= 0');
                });
            });

        $products = $query->with(['media', 'category'])->paginate(20);
        $items = $products->getCollection()->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'quantity' => $p->getEffectiveQuantity(),
            'image' => $p->getMainImageUrl(),
            'category' => $p->category?->name,
            'alert_type' => 'out_of_stock',
            'message' => 'Out of stock — hidden from the public shop until you add stock.',
        ]);

        return response()->json([
            'success' => true,
            'products' => $items->values()->toArray(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ]);
    }
}
