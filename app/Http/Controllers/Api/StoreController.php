<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $activeScope = function ($q) {
            $q->where('is_active', true)
                ->has('seller')
                ->whereHas('seller.user', fn ($uq) => $uq->where('is_banned', false)->where('is_suspended', false));
        };

        $query = Store::query()->tap($activeScope)->with(['seller.user']);

        if ($request->filled('search')) {
            $q = \Illuminate\Support\Str::limit($request->search, 255, '');
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%");
            });
        }

        if ($request->filled('city')) {
            $city = \Illuminate\Support\Str::limit($request->city, 100, '');
            $query->where('city', $city);
        }

        $cities = Store::query()
            ->tap($activeScope)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->values()
            ->all();

        $stores = $query->orderBy('name')->paginate(12);

        $items = $stores->getCollection()->map(function ($s) {
            $reviews = $s->reviews()->approved();
            $reviewCount = $reviews->count();
            $ratingAvg = $reviewCount > 0 ? round($reviews->avg('rating'), 1) : null;
            $contactVerified = $this->sellerContactVerification($s);

            return [
                'id' => $s->id,
                'seller_id' => $s->seller?->user_id,
                'slug' => $s->slug,
                'name' => $s->name,
                'storeName' => $s->name,
                'description' => $s->description ?? '',
                'logo' => $s->logo ? \App\Support\UploadHelper::url($s->logo) : null,
                'logo_alt' => $s->logo_alt,
                'banner' => $s->banner ? \App\Support\UploadHelper::url($s->banner) : null,
                'banner_alt' => $s->banner_alt,
                'cover_image' => $s->cover_image ? \App\Support\UploadHelper::url($s->cover_image) : null,
                'cover_image_alt' => $s->cover_image_alt,
                'city' => $s->city,
                'products_count' => $s->products()->where('status', 'published')->count(),
                'products' => $s->products()->where('status', 'published')->count(),
                'verified' => $s->seller?->status === 'approved',
                'kyc_verified' => $s->seller && ($s->seller->kyc_status ?? '') === 'verified',
                'phone_verified' => $contactVerified['phone_verified'],
                'whatsapp_verified' => $contactVerified['whatsapp_verified'],
                'rating' => $ratingAvg,
                'reviews' => $reviewCount,
                'on_time_delivery' => '98%',
                'response_rate' => '95%',
            ];
        });

        return response()->json([
            'success' => true,
            'vendors' => $items,
            'cities' => $cities,
            'pagination' => [
                'current_page' => $stores->currentPage(),
                'last_page' => $stores->lastPage(),
                'total' => $stores->total(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $store = Store::where('slug', $slug)
            ->where('is_active', true)
            ->with(['seller.user'])
            ->first();

        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Store not found'], 404);
        }

        $reviews = $store->reviews()->approved();
        $reviewCount = $reviews->count();
        $ratingAvg = $reviewCount > 0 ? round($reviews->avg('rating'), 1) : null;
        $contactVerified = $this->sellerContactVerification($store);

        return response()->json([
            'success' => true,
            'vendor' => [
                'id' => $store->id,
                'slug' => $store->slug,
                'name' => $store->name,
                'storeName' => $store->name,
                'description' => $store->description ?? '',
                'meta_title' => $store->meta_title,
                'meta_description' => $store->meta_description,
                'logo' => $store->logo ? \App\Support\UploadHelper::url($store->logo) : null,
                'logo_alt' => $store->logo_alt,
                'banner' => $store->banner ? \App\Support\UploadHelper::url($store->banner) : null,
                'banner_alt' => $store->banner_alt,
                'cover_image' => $store->cover_image ? \App\Support\UploadHelper::url($store->cover_image) : null,
                'cover_image_alt' => $store->cover_image_alt,
                'shipping_policy' => $store->shipping_policy ?? '',
                'return_policy' => $store->return_policy ?? '',
                'products_count' => $store->products()->where('status', 'published')->count(),
                'products' => $store->products()->where('status', 'published')->count(),
                'verified' => $store->seller?->status === 'approved',
                'kyc_verified' => $store->seller && ($store->seller->kyc_status ?? '') === 'verified',
                'phone_verified' => $contactVerified['phone_verified'],
                'whatsapp_verified' => $contactVerified['whatsapp_verified'],
                'rating' => $ratingAvg,
                'reviews' => $reviewCount,
                'on_time_delivery' => '98%',
                'response_rate' => '95%',
                'city' => $store->city,
                'country' => $store->country,
                'created_at' => $store->created_at?->toDateString(),
            ],
        ]);
    }

    /** @return array{phone_verified: bool, whatsapp_verified: bool} */
    private function sellerContactVerification(?Store $store): array
    {
        $user = $store?->seller?->user;

        return [
            'phone_verified' => $user && $user->phone_verified_at !== null,
            'whatsapp_verified' => $user && $user->whatsapp_verified_at !== null,
        ];
    }
}
