<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\Store;
use App\Services\LocationService;
use App\Services\StoreProfileSync;
use App\Support\KycDocumentRules;
use App\Support\UploadHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SellerStoreController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json([
                'success' => true,
                'store' => null,
                'has_store' => false,
            ]);
        }

        $seller = $store->seller;
        $data = [
            'id' => $store->id,
            'name' => $store->name,
            'slug' => $store->slug,
            'description' => $store->description,
            'logo' => $store->logo ? UploadHelper::url($store->logo) : null,
            'logo_alt' => $store->logo_alt,
            'banner' => $store->banner ? UploadHelper::url($store->banner) : null,
            'banner_alt' => $store->banner_alt,
            'cover_image' => $store->cover_image ? UploadHelper::url($store->cover_image) : null,
            'cover_image_alt' => $store->cover_image_alt,
            'address' => $store->address,
            'city' => $store->city,
            'state' => $store->state,
            'country' => $store->country,
            'zip_code' => $store->zip_code,
            'phone' => $store->phone,
            'email' => $store->email,
            'shipping_policy' => $store->shipping_policy,
            'return_policy' => $store->return_policy,
            'kyc_status' => $seller?->kyc_status,
            'vacation_mode' => (bool) ($seller?->vacation_mode ?? false),
            'vacation_mode_until' => $seller?->vacation_mode_until?->toIso8601String(),
            'seller_status' => $seller?->status,
        ];

        return response()->json([
            'success' => true,
            'store' => $data,
            'has_store' => true,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('stores', 'name'),
            ],
            'description' => 'nullable|string|max:5000',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'shipping_policy' => 'nullable|string|max:2000',
            'return_policy' => 'nullable|string|max:2000',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'logo_alt' => 'nullable|string|max:255',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'banner_alt' => 'nullable|string|max:255',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'cover_image_alt' => 'nullable|string|max:255',
        ], [
            'name.unique' => 'This store name is already taken. Please choose another.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $seller = $user->seller;
        if (!$seller) {
            $seller = Seller::create([
                'user_id' => $user->id,
                'status' => 'pending',
            ]);
        }

        if ($seller->store) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a store. Use the update endpoint to modify it.',
            ], 400);
        }

        $slug = Str::slug($request->name);
        $baseSlug = $slug;
        $i = 1;
        while (Store::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }

        try {
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = UploadHelper::storePublic($request->file('logo'), 'stores/logos');
            }

            $bannerPath = null;
            if ($request->hasFile('banner')) {
                $bannerPath = UploadHelper::storePublic($request->file('banner'), 'stores/banners');
            }
            $coverPath = null;
            if ($request->hasFile('cover_image')) {
                $coverPath = UploadHelper::storePublic($request->file('cover_image'), 'stores/cover');
            }

            $store = Store::create([
                'seller_id' => $seller->id,
                'name' => $request->name,
                'slug' => $slug,
                'description' => $request->description,
                'logo' => $logoPath,
                'logo_alt' => $request->input('logo_alt'),
                'banner' => $bannerPath,
                'banner_alt' => $request->input('banner_alt'),
                'cover_image' => $coverPath,
                'cover_image_alt' => $request->input('cover_image_alt'),
                'address' => $request->address,
                'city' => $user->city,
                'state' => $user->state,
                'country' => LocationService::defaultCountryName(),
                'zip_code' => $request->zip_code,
                'phone' => $user->phone,
                'email' => $user->email,
                'shipping_policy' => $request->shipping_policy,
                'return_policy' => $request->return_policy,
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Store creation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $msg = config('app.debug') ? $e->getMessage() : 'Failed to create store. Please try again.';
            return response()->json(['success' => false, 'message' => $msg], 500);
        }

        $data = [
            'id' => $store->id,
            'name' => $store->name,
            'slug' => $store->slug,
            'description' => $store->description,
            'logo' => $store->logo ? UploadHelper::url($store->logo) : null,
            'banner' => $store->banner ? UploadHelper::url($store->banner) : null,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Store created successfully!',
            'store' => $data,
        ], 201);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $store = $user->seller?->store;
        if (!$store) {
            return response()->json(['success' => false, 'message' => 'Create a store first.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('stores', 'name')->ignore($store->id),
            ],
            'description' => 'nullable|string|max:5000',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'shipping_policy' => 'nullable|string|max:2000',
            'return_policy' => 'nullable|string|max:2000',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'logo_alt' => 'nullable|string|max:255',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'banner_alt' => 'nullable|string|max:255',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'cover_image_alt' => 'nullable|string|max:255',
        ], [
            'name.unique' => 'This store name is already taken. Please choose another.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only([
            'name', 'description', 'address', 'zip_code',
            'shipping_policy', 'return_policy',
            'logo_alt', 'banner_alt', 'cover_image_alt',
        ]);

        if ($request->hasFile('logo')) {
            if ($store->logo) {
                UploadHelper::deleteAny($store->logo);
            }
            $data['logo'] = UploadHelper::storePublic($request->file('logo'), 'stores/logos');
        }
        if ($request->hasFile('banner')) {
            if ($store->banner) {
                UploadHelper::deleteAny($store->banner);
            }
            $data['banner'] = UploadHelper::storePublic($request->file('banner'), 'stores/banners');
        }
        if ($request->hasFile('cover_image')) {
            if ($store->cover_image) {
                UploadHelper::deleteAny($store->cover_image);
            }
            $data['cover_image'] = UploadHelper::storePublic($request->file('cover_image'), 'stores/cover');
        }

        // Only remove null values; keep empty strings to clear optional fields
        $store->update(array_filter($data, fn ($v) => $v !== null));
        StoreProfileSync::syncFromUser($user->fresh());

        $data = [
            'id' => $store->id,
            'name' => $store->name,
            'slug' => $store->slug,
            'description' => $store->description,
            'logo' => $store->logo ? UploadHelper::url($store->logo) : null,
            'logo_alt' => $store->logo_alt,
            'banner' => $store->banner ? UploadHelper::url($store->banner) : null,
            'banner_alt' => $store->banner_alt,
            'cover_image' => $store->cover_image ? UploadHelper::url($store->cover_image) : null,
            'cover_image_alt' => $store->cover_image_alt,
            'address' => $store->address,
            'city' => $store->city,
            'state' => $store->state,
            'country' => $store->country,
            'zip_code' => $store->zip_code,
            'phone' => $store->phone,
            'email' => $store->email,
            'shipping_policy' => $store->shipping_policy,
            'return_policy' => $store->return_policy,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Store updated.',
            'store' => $data,
        ]);
    }

    /**
     * Seller application: upload KYC document.
     */
    public function uploadKyc(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate([
            'document_type' => KycDocumentRules::documentTypeRule(),
            'cnic' => 'nullable|string|max:20',
            'licence_number' => 'nullable|string|max:64',
            'id_front' => 'required|file|mimes:jpeg,png,jpg|max:5120',
            'id_back' => 'required|file|mimes:jpeg,png,jpg|max:5120',
            'bank_account_holder' => 'required|string|max:120',
            'bank_account_number' => 'required|string|max:64',
            'bank_name' => 'required|string|max:120',
            'bank_swift_code' => 'nullable|string|max:64',
        ]);

        $kycValidator = KycDocumentRules::makeValidator($request);
        if ($kycValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $kycValidator->errors(),
            ], 422);
        }

        $seller = $user->seller;
        if (!$seller) {
            $seller = Seller::create(['user_id' => $user->id, 'status' => 'pending']);
        }

        $kycDocs = KycDocumentRules::persistSellerDocuments($request);
        $seller->update([
            'kyc_document_type' => $kycDocs['document_type'],
            'kyc_id_number' => $kycDocs['id_number'],
            'kyc_id_front_path' => $kycDocs['id_front_path'],
            'kyc_id_back_path' => $kycDocs['id_back_path'],
            'kyc_document_path' => $kycDocs['id_front_path'],
            'bank_account_holder' => $request->input('bank_account_holder'),
            'bank_account_number' => $request->input('bank_account_number'),
            'bank_name' => $request->input('bank_name'),
            'bank_swift_code' => $request->input('bank_swift_code'),
            'kyc_status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'KYC submitted. Pending verification.',
            'kyc_status' => $seller->kyc_status,
        ]);
    }

    public function vacationMode(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'seller') {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }
        $seller = $user->seller;
        if (!$seller) {
            return response()->json(['success' => false, 'message' => 'Create a store first.'], 400);
        }
        $request->validate([
            'enabled' => 'required|boolean',
            'until' => 'nullable|date|after:today',
        ]);
        $seller->update([
            'vacation_mode' => $request->boolean('enabled'),
            'vacation_mode_until' => $request->boolean('enabled') && $request->filled('until')
                ? $request->until
                : null,
        ]);
        return response()->json([
            'success' => true,
            'vacation_mode' => $seller->vacation_mode,
            'vacation_mode_until' => $seller->vacation_mode_until?->toIso8601String(),
        ]);
    }
}
