<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ListingPendingApprovalMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\WalletDeposit;
use App\Models\WalletTransaction;
use App\Services\JazzCashService;
use App\Services\OrderWorkflowService;
use App\Support\ProductSeoHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PrivateListingController extends Controller
{
    /** Block approved private sellers who still owe required verification. */
    private function verificationGate(Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (! \App\Services\PrivateSellerVerificationService::isBlocked($user)) {
            return null;
        }
        $status = \App\Services\PrivateSellerVerificationService::statusFor($user);

        return response()->json([
            'success' => false,
            'message' => 'Complete required verification before using seller tools.',
            'error_code' => 'private_seller_verification_required',
            'private_seller_verification' => $status,
        ], 403);
    }

    public function config(): JsonResponse
    {
        $enabled = (bool) (int) Setting::get('private_sellers_enabled', '1');
        $approvalRequired = (bool) (int) Setting::get('private_listing_approval', '0');
        $expiryDays = (int) Setting::get('private_listing_expiry_days', '30');

        $user = auth()->user();
        $limits = self::listingLimitsFor($user);
        $used = $user ? self::listingCount($user) : 0;
        $liveUsed = $user ? self::liveListingCount($user) : 0;
        $remaining = max(0, $limits['effective_limit'] - $used);
        $listingFee = (float) Setting::get('private_listing_fee', '50');
        $maxImages = max(1, min(12, (int) Setting::get('private_listing_max_images', '6')));
        $videoEnabled = (bool) (int) Setting::get('private_listing_video_enabled', '0');
        $freeRemaining = max(0, $limits['free_limit'] - $liveUsed);

        return response()->json([
            'success' => true,
            'config' => [
                'enabled' => $enabled,
                'limit' => $limits['effective_limit'],
                'free_limit' => $limits['free_limit'],
                'max_limit' => $limits['max_limit'],
                'has_plan' => $limits['has_plan'],
                'approval_required' => $approvalRequired,
                'expiry_days' => $expiryDays,
                'used' => $used,
                'live_used' => $liveUsed,
                'remaining' => $remaining,
                'free_remaining' => $freeRemaining,
                'listing_fee' => $listingFee,
                'min_images' => 1,
                'max_images' => $maxImages,
                'max_image_updates' => max(0, (int) Setting::get('private_listing_max_image_updates', '0')),
                'video_enabled' => $videoEnabled,
                'global_limit' => $limits['max_limit'],
                'custom_limit' => $user?->private_listing_limit,
                'plan_required' => !$limits['has_plan'] && $liveUsed >= $limits['free_limit'] && $listingFee > 0,
                'payment_methods' => array_map(
                    static fn (string $v) => match ($v) {
                        'wallet' => ['value' => 'wallet', 'label' => 'Wallet', 'desc' => 'Pay from wallet balance'],
                        'stripe' => ['value' => 'stripe', 'label' => 'Credit/Debit Card', 'desc' => 'Pay with Stripe'],
                        'jazzcash' => ['value' => 'jazzcash', 'label' => 'JazzCash', 'desc' => 'JazzCash / Mobicash'],
                        'easypaisa' => ['value' => 'easypaisa', 'label' => 'Easypaisa', 'desc' => 'Easypaisa wallet'],
                        default => ['value' => $v, 'label' => ucfirst($v), 'desc' => ''],
                    },
                    \App\Services\ListingFeeService::allowedGateways()
                ),
            ],
        ]);
    }

    public static function listingCount(\App\Models\User $user): int
    {
        // Include soft-deleted listings so free limit is not restored on delete
        return self::listingsQuery($user, true)->count();
    }

    /** Whether this private listing has any sold order lines. */
    public static function listingHasSales(Product $product): bool
    {
        return \App\Models\OrderItem::where('product_id', $product->id)->exists();
    }

    /**
     * After a sale, only approved private sellers (or business sellers) may edit / restock / delete.
     */
    public static function canManageAfterSale(\App\Models\User $user, Product $product): bool
    {
        if (! self::listingHasSales($product)) {
            return true;
        }

        return (bool) ($user->is_private_seller ?? false) || $user->role === 'seller';
    }

    /** Published + pending (live/awaiting) � drafts do not consume free live slots. */
    public static function liveListingCount(\App\Models\User $user): int
    {
        return self::listingsQuery($user)
            ->whereIn('status', ['published', 'pending'])
            ->count();
    }

    /** Listings owned by this user (private sellers use seller_type business + store). */
    public static function listingsQuery(\App\Models\User $user, bool $withTrashed = false)
    {
        $query = $withTrashed ? Product::withTrashed() : Product::query();

        return $query->where('seller_id', $user->id);
    }

    private static function ownsListing(\App\Models\User $user, Product $product): bool
    {
        return (int) $product->seller_id === (int) $user->id;
    }

    private static function resolveStoreForUser(\App\Models\User $user): ?\App\Models\Store
    {
        $user->loadMissing('seller.store');

        return $user->seller?->store;
    }

    /** Customers with free listings, or approved private sellers (role seller). */
    private static function canAccessPrivateListings(\App\Models\User $user): bool
    {
        if ($user->role === 'customer') {
            return true;
        }

        return $user->role === 'seller' && (bool) ($user->is_private_seller ?? false);
    }

    /**
     * Free tier (default 3) for customers without a plan override.
     * Max manage limit (default 15) for users with a plan (per-user private_listing_limit set by admin).
     */
    public static function listingLimitsFor(?\App\Models\User $user): array
    {
        $freeLimit = max(0, (int) Setting::get('private_listing_free_limit', '3'));
        $maxLimit = max(max(1, $freeLimit), (int) Setting::get('private_listing_limit', '15'));

        $hasPlan = $user && $user->private_listing_limit !== null && (int) $user->private_listing_limit > 0;
        if ($hasPlan) {
            $effective = min(max(1, (int) $user->private_listing_limit), $maxLimit);
        } else {
            // Without a plan: free slots for live; drafts can still go up to max_limit.
            $effective = $freeLimit > 0 ? min($freeLimit, $maxLimit) : $maxLimit;
        }

        return [
            'free_limit' => $freeLimit,
            'max_limit' => $maxLimit,
            'has_plan' => (bool) $hasPlan,
            'effective_limit' => $effective,
        ];
    }

    /** @deprecated use listingLimitsFor */
    public static function effectiveListingLimit(?\App\Models\User $user): int
    {
        return self::listingLimitsFor($user)['effective_limit'];
    }

    public function store(Request $request): JsonResponse
    {
        if ($blocked = $this->verificationGate($request)) {
            return $blocked;
        }
        $user = $request->user();
        if (! self::canAccessPrivateListings($user)) {
            return response()->json(['success' => false, 'message' => 'Only customers can create private listings.'], 403);
        }

        $enabled = (bool) (int) Setting::get('private_sellers_enabled', '1');
        if (!$enabled) {
            return response()->json(['success' => false, 'message' => 'Private sellers are disabled.'], 403);
        }

        $limits = self::listingLimitsFor($user);
        $used = self::listingCount($user);
        $liveUsed = self::liveListingCount($user);
        $listingFee = (float) Setting::get('private_listing_fee', '50');
        $overFreeLive = !$limits['has_plan'] && $liveUsed >= $limits['free_limit'];
        $overMax = $used >= $limits['max_limit'];

        if ($overMax) {
            return response()->json([
                'success' => false,
                'message' => "Listing limit reached ({$limits['max_limit']}). You cannot add more products.",
                'max_limit' => $limits['max_limit'],
            ], 422);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0|max:' . (($user->is_private_seller ?? false) ? 9999 : 1),
            'condition' => 'required|in:new,used,refurbished',
            'status' => 'nullable|in:draft,published',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'image_alts' => 'nullable|array',
            'image_alts.*' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'thumbnail_alt' => 'nullable|string|max:255',
            'video_url' => 'nullable|url|max:500',
            'documents.*' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'document_labels.*' => 'nullable|string|max:100',
            'weight_kg' => 'nullable|numeric|min:0.01|max:500',
            'shipping_mode' => 'nullable|string|in:free_shipping,customer_pays',
            'shipping_cost_cached' => 'nullable|numeric|min:0',
            'length_cm' => 'nullable|numeric|min:0|max:500',
            'width_cm' => 'nullable|numeric|min:0|max:500',
            'height_cm' => 'nullable|numeric|min:0|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ]);

        if (!($user->is_private_seller ?? false)) {
            $request->merge(['quantity' => 1]);
        }

        $maxImages = max(1, min(12, (int) Setting::get('private_listing_max_images', '6')));
        $videoEnabled = (bool) (int) Setting::get('private_listing_video_enabled', '0');
        $imageFiles = array_values(array_filter($request->file('images', []) ?: [], fn ($f) => $f && $f->isValid()));
        $hasThumb = $request->hasFile('thumbnail') && $request->file('thumbnail')?->isValid();
        $imageCount = count($imageFiles) + ($hasThumb ? 1 : 0);
        if ($imageCount < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Add at least 1 product photo.',
                'errors' => ['images' => ['At least 1 image is required.']],
            ], 422);
        }
        if ($imageCount > $maxImages) {
            return response()->json([
                'success' => false,
                'message' => "You can upload at most {$maxImages} images.",
                'errors' => ['images' => ["Maximum {$maxImages} images allowed."]],
            ], 422);
        }
        if (!$videoEnabled && $request->filled('video_url')) {
            return response()->json([
                'success' => false,
                'message' => 'Video URLs are not allowed for customer listings.',
                'errors' => ['video_url' => ['Video is disabled.']],
            ], 422);
        }

        $shippingMode = $request->input('shipping_mode', 'customer_pays');
        if ($shippingMode === 'customer_pays' && !$request->filled('shipping_cost_cached')) {
            return response()->json([
                'success' => false,
                'message' => 'shipping_cost_cached is required when shipping_mode is customer_pays.',
            ], 422);
        }

        // Over free live limit: force draft; activate requires listing fee payment.
        $wantDraft = $request->input('status') === 'draft' || $overFreeLive;
        if (!$wantDraft && (int) $request->quantity < 1) {
            return response()->json(['success' => false, 'message' => 'Quantity must be at least 1 to publish.'], 422);
        }

        $approvalRequired = (bool) (int) Setting::get('private_listing_approval', '0');
        $expiryDays = (int) Setting::get('private_listing_expiry_days', '30');

        if ($wantDraft) {
            $status = 'draft';
        } else {
            $status = $approvalRequired ? 'pending' : 'published';
        }

        $payload = array_merge($request->all(), ['_auto_seo' => true]);
        $meta = ProductSeoHelper::resolve($payload);
        $store = self::resolveStoreForUser($user);
        $useBusinessListing = (bool) ($user->is_private_seller ?? false) && $store;
        $product = Product::create([
            'seller_type' => $useBusinessListing ? 'business' : 'private',
            'seller_id' => $user->id,
            'store_id' => $useBusinessListing ? $store->id : null,
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id ?: null,
            'sku' => $this->uniquePrivateSku(),
            'name' => $request->name,
            'slug' => ProductSeoHelper::uniqueSlug((string) $request->name),
            'description' => $request->description,
            'short_description' => $request->short_description,
            'price' => $request->price,
            'compare_at_price' => $request->compare_at_price ?: null,
            'quantity' => $request->quantity,
            'condition' => $request->condition,
            'status' => $status,
            'expires_at' => ($expiryDays > 0 && !$wantDraft) ? now()->addDays($expiryDays) : null,
            'product_type' => 'simple',
            'video_url' => ($videoEnabled ? ($request->video_url ?: null) : null),
            'weight_kg' => $request->input('weight_kg', 0.5),
            'shipping_mode' => $shippingMode,
            'shipping_cost_cached' => $shippingMode === 'customer_pays'
                ? (float) $request->input('shipping_cost_cached', 0)
                : 0,
            'length_cm' => null,
            'width_cm' => null,
            'height_cm' => null,
            'meta_title' => $meta['meta_title'],
            'meta_description' => $meta['meta_description'],
            'meta_keywords' => $meta['meta_keywords'],
        ]);

        $thumbnailPath = null;
        $thumbnailFile = $request->file('thumbnail');
        if ($thumbnailFile && $thumbnailFile->isValid()) {
            $thumbnailPath = \App\Support\UploadHelper::storePublic($thumbnailFile, 'products/' . $product->id);
            $product->update([
                'thumbnail_path' => $thumbnailPath,
                'thumbnail_alt' => $request->input('thumbnail_alt'),
            ]);
        } elseif ($request->filled('thumbnail_alt')) {
            $product->update(['thumbnail_alt' => $request->input('thumbnail_alt')]);
        }

        if ($request->hasFile('images')) {
            $imageAlts = $request->input('image_alts', []);
            foreach ($request->file('images') as $i => $file) {
                $path = \App\Support\UploadHelper::storePublic($file, 'products/' . $product->id);
                ProductMedia::create([
                    'product_id' => $product->id,
                    'type' => 'image',
                    'path' => $path,
                    'alt_text' => $imageAlts[$i] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }

        if (!$thumbnailPath) {
            $firstMedia = $product->media()->orderBy('sort_order')->first();
            if ($firstMedia) {
                $product->update(['thumbnail_path' => $firstMedia->path]);
            }
        }

        if ($request->hasFile('documents')) {
            $labels = $request->input('document_labels', []);
            foreach ($request->file('documents') as $idx => $file) {
                $path = \App\Support\UploadHelper::storePublic($file, 'products/' . $product->id . '/docs');
                ProductDocument::create([
                    'product_id' => $product->id,
                    'type' => 'manual',
                    'label' => $labels[$idx] ?? $file->getClientOriginalName(),
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'sort_order' => $idx,
                ]);
            }
        }

        // Do NOT auto-set is_private_seller on first listing � KYC approval does that.

        if (!$wantDraft && $approvalRequired) {
            try {
                Mail::to($user->email)->send(new ListingPendingApprovalMail(
                    $user->name ?: 'Customer',
                    $product->name,
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        \App\Services\StockAlertService::syncForProduct($product->fresh(['variants']));

        $message = $wantDraft
            ? ($overFreeLive
                ? 'Free listing limit reached. Listing saved as draft. Pay the listing fee to go live.'
                : 'Listing saved as draft. You can activate it later from My Listings.')
            : ($approvalRequired ? 'Listing submitted for approval.' : 'Listing published.');

        return response()->json([
            'success' => true,
            'message' => $message,
            'listing_fee_required' => $overFreeLive && $listingFee > 0,
            'listing_fee' => $listingFee,
            'product' => $product->fresh(['category', 'media']),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $listings = self::listingsQuery($user, true)
            ->with(['category', 'media'])
            ->withCount('wishlists')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Product $p) use ($user) {
                $hasSales = self::listingHasSales($p);
                $arr = $p->toArray();
                $arr['has_sales'] = $hasSales;
                $arr['locked_after_sale'] = $hasSales && ! self::canManageAfterSale($user, $p);
                $arr['wishlist_count'] = (int) ($p->wishlists_count ?? 0);
                $arr['impressions_count'] = (int) ($p->impressions_count ?? 0);
                $arr['clicks_count'] = (int) ($p->clicks_count ?? 0);
                $arr['shares_count'] = (int) ($p->shares_count ?? 0);
                $arr['display_status'] = self::displayStatusForListing($p, $hasSales);
                $arr['is_removed'] = $p->trashed() || $p->status === 'removed';

                return $arr;
            });

        return response()->json(['success' => true, 'listings' => $listings]);
    }

    /** Buyer-facing status for My Listings: published | draft | sold | expired | removed */
    public static function displayStatusForListing(Product $p, ?bool $hasSales = null): string
    {
        if ($p->trashed() || $p->status === 'removed') {
            return 'removed';
        }
        $hasSales = $hasSales ?? self::listingHasSales($p);
        if ($hasSales && (int) $p->quantity <= 0) {
            return 'sold';
        }
        if ($p->expires_at && $p->expires_at->isPast() && in_array($p->status, ['published', 'pending', 'unpublished'], true)) {
            return 'expired';
        }
        if (in_array($p->status, ['draft', 'unpublished', 'pending'], true)) {
            return $p->status === 'pending' ? 'draft' : ($p->status === 'unpublished' ? 'draft' : 'draft');
        }

        return $p->status === 'published' ? 'published' : 'draft';
    }

    /** Activate a draft (or unpublished) private listing. */
    public function activate(Request $request, Product $product): JsonResponse
    {
        if ($blocked = $this->verificationGate($request)) {
            return $blocked;
        }
        $user = $request->user();
        if (! self::canAccessPrivateListings($user)) {
            return response()->json(['success' => false, 'message' => 'Only customers can activate private listings.'], 403);
        }
        if (! self::ownsListing($user, $product)) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }
        if (!in_array($product->status, ['draft', 'unpublished'], true)) {
            return response()->json(['success' => false, 'message' => 'Only draft or inactive listings can be activated.'], 422);
        }

        if ((int) $product->quantity < 1 && $product->track_inventory !== false) {
            return response()->json(['success' => false, 'message' => 'Add stock quantity before activating this listing.'], 422);
        }

        $limits = self::listingLimitsFor($user);
        $liveUsed = self::liveListingCount($user);
        $listingFee = (float) Setting::get('private_listing_fee', '50');
        $needsFee = !$limits['has_plan'] && $liveUsed >= $limits['free_limit'] && $listingFee > 0;

        if ($needsFee) {
            return response()->json([
                'success' => false,
                'message' => 'Listing fee payment required to go live. Use pay-activate.',
                'listing_fee_required' => true,
                'listing_fee' => $listingFee,
            ], 422);
        }

        return $this->publishListing($user, $product);
    }

    /**
     * Pay listing fee via enabled gateway (wallet / JazzCash / Stripe / Easypaisa), then activate.
     * Online gateways follow the same redirect flow as order Pay Now.
     */
    public function payToActivate(Request $request, Product $product): JsonResponse
    {
        if ($blocked = $this->verificationGate($request)) {
            return $blocked;
        }
        $user = $request->user();
        if (! self::canAccessPrivateListings($user)) {
            return response()->json(['success' => false, 'message' => 'Only customers can activate private listings.'], 403);
        }
        if (! self::ownsListing($user, $product)) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }
        if (!in_array($product->status, ['draft', 'unpublished'], true)) {
            return response()->json(['success' => false, 'message' => 'Only draft or inactive listings can be activated.'], 422);
        }

        $limits = self::listingLimitsFor($user);
        $liveUsed = self::liveListingCount($user);
        $listingFee = (float) Setting::get('private_listing_fee', '50');
        $needsFee = !$limits['has_plan'] && $liveUsed >= $limits['free_limit'] && $listingFee > 0;

        if (!$needsFee) {
            return $this->publishListing($user, $product);
        }

        $allowed = implode(',', \App\Services\ListingFeeService::allowedGateways());
        $request->validate([
            'payment_method' => 'required|string|in:' . $allowed,
            'payment_phone' => 'nullable|string|max:20',
            'payment_cnic' => 'nullable|string|max:20',
        ]);

        $method = $request->input('payment_method');
        if (!\App\Services\ListingFeeService::isGatewayAllowed($method)) {
            return response()->json(['success' => false, 'message' => 'Selected payment method is not available.'], 422);
        }

        if ($method === 'wallet') {
            if (!\App\Services\ListingFeeService::chargeWalletAndPublish($user, $product, $listingFee)) {
                return response()->json(['success' => false, 'message' => 'Insufficient wallet balance'], 422);
            }
            $product->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Listing fee paid via wallet. Listing activated.',
                'payment_ok' => true,
                'product' => $product->fresh(['category', 'media']),
            ]);
        }

        $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:3001'), '/');
        $successUrl = $frontendUrl . '/customer/listings?paid=1&product_id=' . $product->id;
        $cancelUrl = $frontendUrl . '/customer/listings?paid=0&product_id=' . $product->id;

        $deposit = WalletDeposit::create([
            'user_id' => $user->id,
            'amount' => $listingFee,
            'currency' => 'PKR',
            'gateway' => $method,
            'status' => 'pending',
            'gateway_response' => [
                'purpose' => 'listing_fee',
                'product_id' => $product->id,
            ],
        ]);

        if ($method === 'stripe') {
            $checkoutUrl = (new \App\Services\StripeService())->createWalletDepositSession($deposit, $successUrl, $cancelUrl);
            if (!$checkoutUrl) {
                return response()->json(['success' => false, 'message' => 'Stripe is not configured.'], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Redirect to Stripe payment',
                'payment_ok' => false,
                'listing_fee' => $listingFee,
                'deposit_id' => $deposit->id,
                'product_id' => $product->id,
                'checkout_url' => $checkoutUrl,
            ]);
        }

        if ($method === 'easypaisa') {
            $data = (new \App\Services\EasypaisaService())->getWalletDepositCheckoutData(
                $deposit,
                $request->input('payment_phone') ?: $user->phone,
                $user->email
            );
            if (!$data) {
                return response()->json(['success' => false, 'message' => 'Easypaisa is not configured.'], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Redirect to Easypaisa payment',
                'payment_ok' => false,
                'listing_fee' => $listingFee,
                'deposit_id' => $deposit->id,
                'product_id' => $product->id,
                'checkout_url' => $data['url'],
                'checkout_method' => $data['method'] ?? 'POST',
                'checkout_params' => $data['params'] ?? [],
            ]);
        }

        // JazzCash � same validation as order Pay Now
        $phone = JazzCashService::normalizeMobile(
            $request->input('payment_phone') ?: $user->phone
        );
        if ($phone === null) {
            return response()->json([
                'success' => false,
                'message' => 'JazzCash mobile number is required (03XXXXXXXXX).',
            ], 422);
        }
        if (JazzCashService::normalizeCnic($request->input('payment_cnic')) === null) {
            return response()->json([
                'success' => false,
                'message' => 'CNIC is required for JazzCash (last 6 digits or full CNIC).',
            ], 422);
        }

        $pay = (new JazzCashService())->processWalletDeposit($deposit, $phone, $request->input('payment_cnic'));
        $deposit->refresh();

        if (!empty($pay['payment_ok']) || $deposit->status === 'completed') {
            // completeWalletDeposit may already have applied the fee; safe to call again.
            \App\Services\ListingFeeService::applyAfterDeposit($deposit->fresh());
            $product->refresh();

            return response()->json([
                'success' => true,
                'message' => $pay['message'] ?? 'Listing fee paid via JazzCash. Listing activated.',
                'payment_ok' => true,
                'payment_status' => 'paid',
                'product' => $product->fresh(['category', 'media']),
            ]);
        }

        if (($pay['payment_status'] ?? '') === 'pending') {
            return response()->json([
                'success' => true,
                'message' => $pay['message'] ?? 'Payment pending JazzCash confirmation. Approve in the JazzCash app, then try Activate again.',
                'payment_ok' => false,
                'payment_status' => 'pending',
                'listing_fee' => $listingFee,
                'deposit_id' => $deposit->id,
                'product_id' => $product->id,
                'jazzcash_mode' => $pay['mode'] ?? 'mwallet_v2.0',
                'response_code' => $pay['response_code'] ?? null,
            ]);
        }

        // Do not redirect to Payment Portal � same as order Pay Now (MWallet v2 only).
        return response()->json([
            'success' => false,
            'message' => $pay['message'] ?? 'JazzCash payment failed.',
            'payment_ok' => false,
            'payment_status' => $pay['payment_status'] ?? 'failed',
            'listing_fee' => $listingFee,
            'deposit_id' => $deposit->id,
            'product_id' => $product->id,
            'jazzcash_mode' => $pay['mode'] ?? 'mwallet_v2.0',
            'response_code' => $pay['response_code'] ?? null,
        ], 422);
    }

    protected function publishListing($user, Product $product, ?string $customMessage = null): JsonResponse
    {
        $approvalRequired = (bool) (int) Setting::get('private_listing_approval', '0');
        $expiryDays = (int) Setting::get('private_listing_expiry_days', '30');

        $product->status = $approvalRequired ? 'pending' : 'published';
        $product->oos_auto_inactive = false;
        if ($expiryDays > 0 && !$product->expires_at) {
            $product->expires_at = now()->addDays($expiryDays);
        }
        $product->save();

        if ($approvalRequired) {
            try {
                Mail::to($user->email)->send(new ListingPendingApprovalMail(
                    $user->name ?: 'Customer',
                    $product->name,
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $customMessage ?: ($approvalRequired ? 'Listing submitted for approval.' : 'Listing activated.'),
            'product' => $product->fresh(['category', 'media']),
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        if ($blocked = $this->verificationGate($request)) {
            return $blocked;
        }
        $user = $request->user();
        if (! self::canAccessPrivateListings($user)) {
            return response()->json(['success' => false, 'message' => 'Only customers can update private listings.'], 403);
        }
        if (! self::ownsListing($user, $product)) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        if (! self::canManageAfterSale($user, $product)) {
            return response()->json([
                'success' => false,
                'message' => 'This item has sales. Become an approved private seller to edit or restock it.',
                'error_code' => 'listing_locked_after_sale',
                'has_sales' => true,
            ], 422);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'category_id' => 'sometimes|required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'price' => 'sometimes|required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'quantity' => 'sometimes|required|integer|min:0|max:' . (($user->is_private_seller ?? false) ? 9999 : 1),
            'condition' => 'sometimes|required|in:new,used,refurbished',
            'status' => 'nullable|in:draft,published',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'image_alts' => 'nullable|array',
            'image_alts.*' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'thumbnail_alt' => 'nullable|string|max:255',
            'video_url' => 'nullable|url|max:500',
            'weight_kg' => 'nullable|numeric|min:0.01|max:500',
            'shipping_mode' => 'nullable|string|in:free_shipping,customer_pays',
            'shipping_cost_cached' => 'nullable|numeric|min:0',
            'is_featured' => 'nullable|boolean',
            'is_hot' => 'nullable|boolean',
            'length_cm' => 'nullable|numeric|min:0|max:500',
            'width_cm' => 'nullable|numeric|min:0|max:500',
            'height_cm' => 'nullable|numeric|min:0|max:500',
        ]);

        if ($request->input('shipping_mode') === 'customer_pays' && !$request->filled('shipping_cost_cached') && $product->shipping_cost_cached === null) {
            return response()->json([
                'success' => false,
                'message' => 'shipping_cost_cached is required when shipping_mode is customer_pays.',
            ], 422);
        }

        $isFeatured = $request->has('is_featured') ? $request->boolean('is_featured') : (bool) $product->is_featured;
        $isHot = $request->has('is_hot') ? $request->boolean('is_hot') : (bool) $product->is_hot;
        if ($isFeatured) {
            $ok = \App\Models\Promotion::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->whereHas('package', fn ($q) => $q->where('type', 'featured_product'))
                ->exists();
            if (!$ok) {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase a Featured promotion package to use this badge. Visit Promote page.',
                ], 422);
            }
        }
        if ($isHot) {
            $ok = \App\Models\Promotion::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->whereHas('package', fn ($q) => $q->where('type', 'hot_sale'))
                ->exists();
            if (!$ok) {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase a Hot promotion package to use this badge. Visit Promote page.',
                ], 422);
            }
        }

        if ($request->has('video_url')) {
            $videoEnabled = (bool) (int) Setting::get('private_listing_video_enabled', '0');
            if (!$videoEnabled) {
                $product->video_url = null;
            } elseif ($request->filled('video_url')) {
                $product->video_url = $request->video_url;
            } else {
                $product->video_url = null;
            }
        }

        $product->fill($request->only([
            'name', 'description', 'short_description', 'category_id', 'brand_id',
            'price', 'compare_at_price', 'quantity', 'condition',
            'shipping_mode', 'shipping_cost_cached',
        ]));
        if (!($user->is_private_seller ?? false)) {
            $product->quantity = 1;
        }
        if ($request->has('is_featured')) {
            $product->is_featured = $isFeatured;
        }
        if ($request->has('is_hot')) {
            $product->is_hot = $isHot;
        }
        // SKU is auto-generated on create and not editable by customers
        if (!$product->sku) {
            $product->sku = $this->uniquePrivateSku();
        }
        if ($product->shipping_mode === 'free_shipping') {
            $product->shipping_cost_cached = 0;
        }

        // Always refresh SEO from product fields
        $meta = ProductSeoHelper::resolve(array_merge($product->toArray(), [
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            '_auto_seo' => true,
        ]));
        $product->meta_title = $meta['meta_title'];
        $product->meta_description = $meta['meta_description'];
        $product->meta_keywords = $meta['meta_keywords'];

        $thumbnailFile = $request->file('thumbnail');
        $imagesChanging = ($thumbnailFile && $thumbnailFile->isValid()) || $request->hasFile('images');
        if ($imagesChanging) {
            $maxUpdates = max(0, (int) Setting::get('private_listing_max_image_updates', '0'));
            $used = (int) ($product->image_update_count ?? 0);
            if ($maxUpdates > 0 && $used >= $maxUpdates) {
                return response()->json([
                    'success' => false,
                    'message' => "Image update limit reached ({$maxUpdates}). Contact support if you need to change photos again.",
                    'error_code' => 'image_update_limit',
                    'image_update_count' => $used,
                    'max_image_updates' => $maxUpdates,
                ], 422);
            }
        }

        if ($thumbnailFile && $thumbnailFile->isValid()) {
            $product->thumbnail_path = \App\Support\UploadHelper::storePublic($thumbnailFile, 'products/' . $product->id);
        }
        if ($request->filled('thumbnail_alt')) {
            $product->thumbnail_alt = $request->input('thumbnail_alt');
        }
        if ($request->filled('name')) {
            $product->slug = ProductSeoHelper::uniqueSlug((string) $product->name, (int) $product->id);
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'draft') {
                $product->status = 'draft';
                $product->oos_auto_inactive = false;
            } elseif ($request->input('status') === 'published' && in_array($product->status, ['draft', 'unpublished'], true)) {
                $approvalRequired = (bool) (int) Setting::get('private_listing_approval', '0');
                $product->status = $approvalRequired ? 'pending' : 'published';
                $product->oos_auto_inactive = false;
            }
        }

        if ($imagesChanging) {
            $product->image_update_count = (int) ($product->image_update_count ?? 0) + 1;
        }

        $product->save();

        if ($request->hasFile('images')) {
            $product->media()->delete();
            $imageAlts = $request->input('image_alts', []);
            foreach ($request->file('images') as $i => $file) {
                $path = \App\Support\UploadHelper::storePublic($file, 'products/' . $product->id);
                \App\Models\ProductMedia::create([
                    'product_id' => $product->id,
                    'type' => 'image',
                    'path' => $path,
                    'alt_text' => $imageAlts[$i] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }

        \App\Services\StockAlertService::syncForProduct($product->fresh(['variants']));

        return response()->json(['success' => true, 'product' => $product->fresh(['category', 'media'])]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        if ($blocked = $this->verificationGate($request)) {
            return $blocked;
        }
        $user = $request->user();
        if (! self::canAccessPrivateListings($user)) {
            return response()->json(['success' => false, 'message' => 'Only customers can delete private listings.'], 403);
        }
        if (! self::ownsListing($user, $product)) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }
        if (! self::canManageAfterSale($user, $product)) {
            return response()->json([
                'success' => false,
                'message' => 'This item has sales and cannot be deleted. Become an approved private seller to manage sold listings.',
                'error_code' => 'listing_locked_after_sale',
            ], 422);
        }
        // Soft-remove: hide from public, keep in My Listings as "removed" (still counts toward free limit).
        $product->status = 'removed';
        $product->save();
        $product->delete();

        return response()->json(['success' => true, 'message' => 'Listing hidden from public. You can recover it anytime from My Listings. Past orders keep product details.']);
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! self::canAccessPrivateListings($user)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $product = self::listingsQuery($user, true)->find($id);

        if (! $product || ! $product->trashed()) {
            return response()->json(['success' => false, 'message' => 'Removed listing not found.'], 404);
        }
        if (! self::canManageAfterSale($user, $product)) {
            return response()->json([
                'success' => false,
                'message' => 'This item has sales and cannot be restored without private seller approval.',
                'error_code' => 'listing_locked_after_sale',
            ], 422);
        }

        $product->restore();
        $approvalRequired = (bool) (int) Setting::get('private_listing_approval', '0');
        $product->status = $approvalRequired ? 'pending' : 'draft';
        $product->save();

        return response()->json([
            'success' => true,
            'message' => $approvalRequired
                ? 'Listing recovered as pending approval. Publish when ready.'
                : 'Listing recovered as draft. Publish when ready.',
            'product' => $product->fresh(['category', 'media']),
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $user = $request->user();
        // Any customer with private listings can manage selling orders (not only KYC private sellers).
        if (! self::canAccessPrivateListings($user)) {
            return response()->json(['success' => true, 'orders' => [], 'pagination' => ['total' => 0]]);
        }

        $orderIds = \App\Models\OrderItem::where('seller_id', $user->id)
            ->distinct('order_id')
            ->pluck('order_id');

        $ordersQuery = Order::whereIn('id', $orderIds);
        OrderWorkflowService::applySellerVisibleScope($ordersQuery);
        $orders = $ordersQuery
            ->with(['items' => fn ($q) => $q->where('seller_id', $user->id), 'shippingAddress', 'shipments', 'coupon'])
            ->orderByDesc('created_at')
            ->paginate(20);

        foreach ($orders as $order) {
            $sellerItems = $order->items;
            $order->seller_subtotal = round($sellerItems->sum(fn ($i) => (float) $i->price * (int) $i->quantity), 2);
            $order->seller_discount_allocated = round($sellerItems->sum(fn ($i) => (float) ($i->discount_allocated ?? 0)), 2);
            \App\Services\OrderFeeBreakdown::attachSellerView($order, $sellerItems);
            $order->coupon_code = $order->coupon?->code;
        }

        foreach ($orders as $order) {
            $sellerItems = $order->items->filter(fn ($i) => (float) $i->price > 0)->values();
            $shipment = $order->shipments->where('seller_id', $user->id)->first();
            $portion = \App\Services\SellerFulfillmentService::portionStatus($sellerItems, $shipment);
            $order->seller_display_status = $order->status === 'cancellation_requested' && in_array($portion, ['processing', 'approved'], true)
                ? 'cancellation_requested'
                : $portion;
            $order->seller_fulfillment_status = $portion;
        }

        return response()->json([
            'success' => true,
            'orders' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function showOrder(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if (! self::canAccessPrivateListings($user)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }
        $hasItem = $order->items()->where('seller_id', $user->id)->exists();
        if (!$hasItem) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        $visible = Order::query()->where('id', $order->id);
        OrderWorkflowService::applySellerVisibleScope($visible);
        if (!$visible->exists()) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        $order->load(['items' => fn ($q) => $q->where('seller_id', $user->id), 'items.product.media', 'shippingAddress', 'timeline', 'shipments', 'coupon']);
        $sellerShipments = $order->shipments->where('seller_id', $user->id)->values();
        $order->setRelation('shipments', $sellerShipments);

        $order->seller_subtotal = round($order->items->sum(fn ($i) => (float) $i->price * (int) $i->quantity), 2);
        $order->seller_discount_allocated = round($order->items->sum(fn ($i) => (float) ($i->discount_allocated ?? 0)), 2);
        \App\Services\OrderFeeBreakdown::attachSellerView($order, $order->items);
        $order->coupon_code = $order->coupon?->code;

        $sellerItems = $order->items->filter(fn ($i) => (float) $i->price > 0)->values();
        $shipment = $sellerShipments->first();
        $portion = \App\Services\SellerFulfillmentService::portionStatus($sellerItems, $shipment);
        $order->seller_display_status = $order->status === 'cancellation_requested' && in_array($portion, ['processing', 'approved'], true)
            ? 'cancellation_requested'
            : $portion;
        $order->seller_fulfillment_status = $portion;
        $order->can_approve = $portion === 'processing' && ! in_array($order->status, ['cancelled', 'refunded'], true);
        $order->can_reject = in_array($portion, ['processing', 'approved'], true)
            && ! in_array($order->status, ['cancelled', 'refunded'], true);
        $order->can_add_tracking = $portion === 'approved' && ! $order->hasOpenReturnOrDispute();

        $variantIds = $order->items->map(fn ($i) => $i->options['variant_id'] ?? null)->filter()->unique()->values()->all();
        $variants = !empty($variantIds) ? ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id') : collect();
        foreach ($order->items as $item) {
            $variantId = isset($item->options['variant_id']) ? (int) $item->options['variant_id'] : 0;
            $variant = $variantId > 0 ? $variants->get($variantId) : null;
            $variantImagePath = $variant ? ($variant->image_path ?? (is_array($variant->image_paths ?? null) && !empty($variant->image_paths) ? $variant->image_paths[0] : null)) : null;
            $item->image_url = $item->resolveImageUrl($variantImagePath);
            $item->product_available = $item->isProductAvailable();
            if ($item->product && $item->product->trashed()) {
                $item->product->setAttribute('slug', null);
            }
        }

        $order->setRelation('items', $order->items->filter(fn ($i) => (float) $i->price > 0)->values());

        $order->makeHidden([
            'marketplace_fee',
            'online_transaction_fee',
            'platform_revenue',
            'marketplace_fee_type',
            'marketplace_fee_rate',
            'online_transaction_fee_type',
            'online_transaction_fee_rate',
            'seller_commission_total',
            'seller_marketplace_fee_total',
            'seller_online_transaction_fee_total',
            'seller_marketplace_fee_type',
            'seller_marketplace_fee_rate',
            'seller_online_transaction_fee_type',
            'seller_online_transaction_fee_rate',
            'seller_commission_type',
            'seller_commission_rate',
            'total',
        ]);
        foreach ($order->items as $item) {
            $item->makeHidden(['commission_amount', 'marketplace_fee_allocated', 'online_transaction_fee_allocated']);
        }

        return response()->json(['success' => true, 'order' => $order]);
    }

    /** Generate a unique SKU for private listings (products.sku is unique). */
    private function uniquePrivateSku(): string
    {
        do {
            $sku = 'PVT-' . strtoupper(Str::random(10));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }
}
