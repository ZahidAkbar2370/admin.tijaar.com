<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotificationRead;
use App\Models\Seller;
use App\Models\Store;
use App\Models\User;
use App\Services\Admin\UserSegmentService;
use App\Services\ActivityLogger;
use App\Services\StoreProfileSync;
use App\Support\KycDocumentRules;
use App\Support\PhoneHelper;
use App\Support\UploadHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SellerController extends Controller
{
    public function index(Request $request)
    {
        AdminNotificationRead::markRead(auth()->id(), 'new_sellers');

        $query = UserSegmentService::businessSellersQuery()->orderByDesc('created_at');

        UserSegmentService::applySearch($query, $request->input('search'));

        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->whereHas('seller', fn ($s) => $s->where('status', 'pending'));
            } elseif ($request->status === 'active') {
                $query->whereHas('seller', fn ($s) => $s->where('status', 'approved'))
                    ->where('is_banned', false)->where('is_suspended', false);
            } elseif ($request->status === 'suspended') {
                $query->where('is_suspended', true);
            } elseif ($request->status === 'banned') {
                $query->where('is_banned', true);
            } elseif ($request->status === 'rejected') {
                $query->whereHas('seller', fn ($s) => $s->where('status', 'rejected'));
            }
        }

        $sellers = $query->paginate(15)->withQueryString();

        return view('admin.sellers.index', compact('sellers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:30',
            'store_name' => ['required', 'string', 'max:255', Rule::unique('stores', 'name')],
            'store_description' => 'nullable|string|max:5000',
            'store_address' => 'nullable|string|max:500',
            'store_city' => 'nullable|string|max:100',
            'store_state' => 'nullable|string|max:100',
            'store_country' => 'nullable|string|max:100',
            'store_zip_code' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'tax_id' => 'nullable|string|max:64',
            'bank_account_holder' => 'required|string|max:120',
            'bank_account_number' => 'required|string|max:64',
            'bank_name' => 'required|string|max:120',
            'bank_swift_code' => 'nullable|string|max:64',
            'document_type' => KycDocumentRules::documentTypeRule(),
            'cnic' => 'nullable|string|max:20',
            'licence_number' => 'nullable|string|max:64',
            'id_front' => 'required|file|mimes:jpeg,png,jpg|max:5120',
            'id_back' => 'required|file|mimes:jpeg,png,jpg|max:5120',
            'auto_approve' => 'nullable|boolean',
            'verify_email' => 'nullable|boolean',
        ]);

        $kycValidator = KycDocumentRules::makeValidator($request);
        if ($kycValidator->fails()) {
            return back()->withInput()->withErrors($kycValidator)->with('error', 'Check KYC document fields.');
        }

        $phone = PhoneHelper::normalize($request->phone);
        if ($phone === null) {
            return back()->withInput()->with('error', 'Phone must be a valid Pakistani mobile (03XXXXXXXXX).');
        }
        if (User::where('phone', $phone)->exists()) {
            return back()->withInput()->with('error', 'This mobile number is already registered.');
        }

        try {
            $user = DB::transaction(function () use ($request, $phone) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => $request->password,
                    'phone' => $phone,
                    'role' => 'seller',
                    'email_verified_at' => $request->boolean('verify_email') ? now() : null,
                ]);

                $kycDocs = KycDocumentRules::persistSellerDocuments($request);

                $seller = Seller::create([
                    'user_id' => $user->id,
                    'status' => 'pending',
                    'kyc_status' => 'pending',
                    'kyc_document_type' => $kycDocs['document_type'],
                    'kyc_id_number' => $kycDocs['id_number'],
                    'kyc_id_front_path' => $kycDocs['id_front_path'],
                    'kyc_id_back_path' => $kycDocs['id_back_path'],
                    'kyc_document_path' => $kycDocs['id_front_path'],
                    'tax_id' => $request->input('tax_id'),
                    'bank_account_holder' => $request->input('bank_account_holder'),
                    'bank_account_number' => $request->input('bank_account_number'),
                    'bank_name' => $request->input('bank_name'),
                    'bank_swift_code' => $request->input('bank_swift_code'),
                ]);

                $slug = Str::slug($request->store_name) ?: 'store';
                $baseSlug = $slug;
                $i = 1;
                while (Store::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $i++;
                }

                $logoPath = null;
                if ($request->hasFile('logo')) {
                    $logoPath = UploadHelper::storePublic($request->file('logo'), 'stores/logos');
                }

                $store = Store::create([
                    'seller_id' => $seller->id,
                    'name' => $request->store_name,
                    'slug' => $slug,
                    'description' => $request->input('store_description'),
                    'logo' => $logoPath,
                    'address' => $request->input('store_address'),
                    'city' => $user->city,
                    'state' => $user->state,
                    'country' => \App\Services\LocationService::defaultCountryName(),
                    'zip_code' => $request->input('store_zip_code'),
                    'phone' => $phone,
                    'email' => $user->email,
                    'is_active' => false,
                ]);

                if ($request->boolean('auto_approve')) {
                    $seller->update([
                        'status' => 'approved',
                        'kyc_status' => 'verified',
                        'approved_at' => now(),
                        'approved_by' => auth()->id(),
                    ]);
                    $store->update(['is_active' => true]);
                }

                return $user;
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not register business seller. Please try again.');
        }

        ActivityLogger::log([
            'action_type' => 'seller_register',
            'action_by' => auth()->id(),
            'target_table' => 'users',
            'action_on' => $user->id,
            'description' => "Admin registered business seller: {$user->email}",
        ], $request);

        return redirect()->route('admin.sellers.show', $user)->with('success', 'Business seller and store created.');
    }

    public function update(Request $request, User $user)
    {
        if ($user->role !== 'seller') {
            return back()->with('error', 'Not a seller.');
        }

        $return = $request->input('_return', 'profile');

        $result = match ($return) {
            'storefront' => $this->updateSellerStorefront($request, $user),
            'kyc' => $this->updateSellerKyc($request, $user),
            default => $this->updateSellerProfile($request, $user),
        };

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        $returnRoute = match ($return) {
            'kyc' => 'admin.sellers.kyc',
            'storefront' => 'admin.sellers.storefront',
            default => 'admin.sellers.profile',
        };

        $message = match ($return) {
            'kyc' => 'KYC & bank details updated.',
            'storefront' => 'Store updated.',
            default => 'Profile updated.',
        };

        return redirect()->route($returnRoute, $user)->with('success', $message);
    }

    private function updateSellerProfile(Request $request, User $user): ?RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
            'whatsapp_number' => 'nullable|string|max:30',
            'city' => 'nullable|string|max:120',
            'state' => 'nullable|string|max:120',
            'email_verified' => 'nullable|boolean',
            'phone_verified' => 'nullable|boolean',
            'whatsapp_verified' => 'nullable|boolean',
        ]);

        $phone = null;
        if ($request->filled('phone')) {
            $phone = PhoneHelper::normalize($request->phone);
            if ($phone === null) {
                return back()->withInput()->with('error', 'Phone must be a valid Pakistani mobile (03XXXXXXXXX).');
            }
            if ($this->mobileNumberTaken($user->id, $phone)) {
                return back()->withInput()->with('error', 'This mobile number is already used by another account.');
            }
        }

        $whatsapp = null;
        if ($request->filled('whatsapp_number')) {
            $whatsapp = PhoneHelper::normalize($request->whatsapp_number);
            if ($whatsapp === null) {
                return back()->withInput()->with('error', 'WhatsApp number must be a valid Pakistani mobile (03XXXXXXXXX).');
            }
            if ($this->mobileNumberTaken($user->id, $whatsapp)) {
                return back()->withInput()->with('error', 'This WhatsApp number is already used by another account.');
            }
        }

        $emailChanged = $user->email !== $request->email;
        $phoneChanged = $user->phone !== $phone;
        $whatsappChanged = $user->whatsapp_number !== $whatsapp;

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $phone,
            'whatsapp_number' => $whatsapp,
            'city' => $request->input('city'),
            'state' => $request->input('state'),
        ];

        if ($emailChanged) {
            $userData['email_verified_at'] = $request->boolean('email_verified') ? now() : null;
        } elseif ($request->boolean('email_verified')) {
            $userData['email_verified_at'] = $user->email_verified_at ?? now();
        } else {
            $userData['email_verified_at'] = null;
        }

        if ($phoneChanged) {
            $userData['phone_verified_at'] = $request->boolean('phone_verified') ? now() : null;
        } elseif ($request->boolean('phone_verified')) {
            $userData['phone_verified_at'] = $user->phone_verified_at ?? now();
        } else {
            $userData['phone_verified_at'] = null;
        }

        if ($whatsappChanged) {
            $userData['whatsapp_verified_at'] = ($whatsapp && $request->boolean('whatsapp_verified')) ? now() : null;
        } elseif ($whatsapp && $request->boolean('whatsapp_verified')) {
            $userData['whatsapp_verified_at'] = $user->whatsapp_verified_at ?? now();
        } else {
            $userData['whatsapp_verified_at'] = null;
        }

        $user->update($userData);
        StoreProfileSync::syncFromUser($user->fresh());

        return null;
    }

    private function updateSellerStorefront(Request $request, User $user): ?RedirectResponse
    {
        $seller = $user->seller;
        if (! $seller?->store) {
            return back()->with('error', 'No store found for this seller.');
        }

        $request->validate([
            'store_name' => 'required|string|max:255',
            'store_address' => 'nullable|string|max:500',
            'store_description' => 'nullable|string|max:2000',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'logo_alt' => 'nullable|string|max:255',
            'cover_image_alt' => 'nullable|string|max:255',
        ]);

        $store = $seller->store;
        $storeData = [
            'name' => $request->input('store_name'),
            'address' => $request->input('store_address'),
            'description' => $request->input('store_description'),
            'logo_alt' => $request->input('logo_alt'),
            'cover_image_alt' => $request->input('cover_image_alt'),
        ];

        if ($request->hasFile('logo')) {
            if ($store->logo) {
                UploadHelper::deleteAny($store->logo);
            }
            $storeData['logo'] = UploadHelper::storePublic($request->file('logo'), 'stores/logos');
        }

        if ($request->hasFile('cover_image')) {
            if ($store->cover_image) {
                UploadHelper::deleteAny($store->cover_image);
            }
            $storeData['cover_image'] = UploadHelper::storePublic($request->file('cover_image'), 'stores/cover');
        }

        $store->update($storeData);
        StoreProfileSync::syncFromUser($user->fresh());

        return null;
    }

    private function updateSellerKyc(Request $request, User $user): ?RedirectResponse
    {
        $request->validate([
            'tax_id' => 'nullable|string|max:64',
            'bank_name' => 'nullable|string|max:120',
            'bank_account_holder' => 'nullable|string|max:120',
            'bank_account_number' => 'nullable|string|max:64',
            'bank_swift_code' => 'nullable|string|max:64',
            'kyc_status' => 'nullable|in:none,pending,verified,rejected',
            'kyc_rejection_reason' => 'nullable|string|max:1000',
            'document_type' => KycDocumentRules::documentTypeRule(false),
            'cnic' => 'nullable|string|max:20',
            'licence_number' => 'nullable|string|max:64',
            'id_front' => 'nullable|file|mimes:jpeg,png,jpg|max:5120',
            'id_back' => 'nullable|file|mimes:jpeg,png,jpg|max:5120',
        ]);

        $seller = $user->seller;
        if (! $seller) {
            return back()->with('error', 'No seller record found.');
        }

        $documentType = (string) $request->input('document_type', $seller->kyc_document_type ?? KycDocumentRules::DOCUMENT_GOVT_ID);
        if ($documentType === KycDocumentRules::DOCUMENT_GOVT_ID && $request->filled('cnic')) {
            $normalized = KycDocumentRules::normalizeCnic($request->input('cnic'));
            if ($normalized === null) {
                return back()->withInput()->with('error', 'Enter a valid 13-digit CNIC.');
            }
        }
        if ($documentType === KycDocumentRules::DOCUMENT_LICENCE && $request->filled('licence_number') && trim((string) $request->licence_number) === '') {
            return back()->withInput()->with('error', 'Licence number is required.');
        }

        $sellerData = [
            'tax_id' => $request->input('tax_id'),
            'bank_name' => $request->input('bank_name'),
            'bank_account_holder' => $request->input('bank_account_holder'),
            'bank_account_number' => $request->input('bank_account_number'),
            'bank_swift_code' => $request->input('bank_swift_code'),
        ];

        if ($request->filled('kyc_status')) {
            $sellerData['kyc_status'] = $request->kyc_status;
            if ($request->kyc_status === 'rejected') {
                $sellerData['rejection_reason'] = $request->input('kyc_rejection_reason');
            } elseif ($request->kyc_status === 'verified') {
                $sellerData['rejection_reason'] = null;
            }
        }

        if ($request->filled('document_type')) {
            $sellerData['kyc_document_type'] = $documentType;
            if ($documentType === KycDocumentRules::DOCUMENT_GOVT_ID) {
                $sellerData['kyc_id_number'] = $request->filled('cnic')
                    ? KycDocumentRules::normalizeCnic($request->input('cnic'))
                    : $seller->kyc_id_number;
            } else {
                $sellerData['kyc_id_number'] = $request->filled('licence_number')
                    ? trim((string) $request->licence_number)
                    : $seller->kyc_id_number;
            }
        }

        if ($request->hasFile('id_front')) {
            $sellerData['kyc_id_front_path'] = UploadHelper::storePublic($request->file('id_front'), 'kyc/sellers');
            $sellerData['kyc_document_path'] = $sellerData['kyc_id_front_path'];
            if (! $request->filled('kyc_status') || $request->kyc_status === 'none') {
                $sellerData['kyc_status'] = 'pending';
            }
        }
        if ($request->hasFile('id_back')) {
            $sellerData['kyc_id_back_path'] = UploadHelper::storePublic($request->file('id_back'), 'kyc/sellers');
        }

        $seller->update($sellerData);

        return null;
    }

    private function mobileNumberTaken(int $userId, string $normalized): bool
    {
        return User::where('id', '!=', $userId)
            ->where(function ($q) use ($normalized) {
                $q->where('phone', $normalized)->orWhere('whatsapp_number', $normalized);
            })
            ->exists();
    }

    public function storeAddress(Request $request, User $user)
    {
        if ($user->role !== 'seller') {
            return back()->with('error', 'Not a seller.');
        }

        $request->validate([
            'type' => 'required|in:billing,shipping',
            'label' => 'nullable|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:120',
            'state' => 'nullable|string|max:120',
            'country' => 'required|string|max:120',
            'zip_code' => 'nullable|string|max:30',
            'is_default' => 'nullable|boolean',
        ]);

        $phone = PhoneHelper::normalize($request->phone) ?? preg_replace('/\D+/', '', (string) $request->phone) ?: null;
        if ($request->boolean('is_default')) {
            $user->addresses()->update(['is_default' => false]);
        }

        $user->addresses()->create(\App\Services\LocationService::ensureCountry([
            'type' => $request->type,
            'label' => $request->input('label'),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $phone,
            'address_line_1' => $request->address_line_1,
            'address_line_2' => $request->input('address_line_2'),
            'city' => $request->city,
            'state' => $request->input('state'),
            'country' => $request->country,
            'zip_code' => $request->input('zip_code'),
            'is_default' => $request->boolean('is_default'),
        ]));

        return redirect()->route('admin.sellers.addresses', $user)->with('success', 'Address added.');
    }

    public function updateAddress(Request $request, User $user, \App\Models\Address $address)
    {
        if ($user->role !== 'seller' || (int) $address->user_id !== (int) $user->id) {
            return back()->with('error', 'Address not found.');
        }

        $request->validate([
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:120',
            'state' => 'nullable|string|max:120',
            'country' => 'nullable|string|max:120',
            'zip_code' => 'nullable|string|max:30',
        ]);

        $phone = $request->filled('phone') ? (PhoneHelper::normalize($request->phone) ?? preg_replace('/\D+/', '', (string) $request->phone)) : $address->phone;

        $address->update(\App\Services\LocationService::withDefaultCountry([
            'first_name' => $request->input('first_name', $address->first_name),
            'last_name' => $request->input('last_name', $address->last_name),
            'phone' => $phone,
            'address_line_1' => $request->address_line_1,
            'address_line_2' => $request->input('address_line_2'),
            'city' => $request->city,
            'state' => $request->input('state'),
            'country' => $request->input('country', $address->country),
            'zip_code' => $request->input('zip_code'),
        ]));

        return redirect()->route('admin.sellers.addresses', $user)->with('success', 'Address updated.');
    }

    public function updateNotifications(Request $request, User $user)
    {
        if ($user->role !== 'seller') {
            return back()->with('error', 'Not a seller.');
        }

        $prefs = $request->input('prefs', []);
        if (! is_array($prefs)) {
            return back()->with('error', 'Invalid preferences.');
        }

        $whatsappChannelOn = (string) \App\Models\Setting::get('notification_whatsapp_enabled', '1') === '1';

        foreach ($prefs as $key => $enabled) {
            [$channel, $type] = array_pad(explode('|', (string) $key, 2), 2, null);
            if (! in_array($channel, ['email', 'whatsapp', 'push', 'push_web', 'push_app', 'sms'], true)) {
                continue;
            }
            if ($channel === 'push') {
                $channel = 'push_web';
            }
            if (! in_array($type, ['order', 'listing', 'message', 'promotion'], true)) {
                continue;
            }
            if ($channel === 'whatsapp' && ! $whatsappChannelOn) {
                continue;
            }
            \App\Models\NotificationPreference::updateOrCreate(
                ['user_id' => $user->id, 'channel' => $channel, 'type' => $type],
                ['enabled' => (string) $enabled === '1' || $enabled === 1 || $enabled === true]
            );
        }

        return redirect()->route('admin.sellers.alerts', $user)->with('success', 'Notification preferences updated.');
    }

    public function suspend(User $user)
    {
        if ($user->role !== 'seller') {
            return back()->with('error', 'Not a seller.');
        }
        $user->update(['is_suspended' => true]);
        return redirect()->route('admin.sellers.account-actions', $user)->with('success', 'Seller suspended.');
    }

    public function unsuspend(User $user)
    {
        $user->update(['is_suspended' => false]);
        return redirect()->route('admin.sellers.account-actions', $user)->with('success', 'Seller unsuspended.');
    }

    public function ban(User $user)
    {
        if ($user->role !== 'seller') {
            return back()->with('error', 'Not a seller.');
        }
        $user->update(['is_banned' => true]);
        $user->tokens()->delete();
        return redirect()->route('admin.sellers.account-actions', $user)->with('success', 'Seller banned.');
    }

    public function unban(User $user)
    {
        $user->update(['is_banned' => false]);
        return redirect()->route('admin.sellers.account-actions', $user)->with('success', 'Seller unbanned.');
    }

    public function approve(User $user)
    {
        if ($user->role !== 'seller' || !$user->seller) {
            return back()->with('error', 'Not a seller.');
        }
        $user->seller->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'rejection_reason' => null,
        ]);
        if ($user->seller->store) {
            $user->seller->store->update(['is_active' => true]);
        }
        return redirect()->route('admin.sellers.kyc', $user)->with('success', 'Seller approved.');
    }

    public function reject(Request $request, User $user)
    {
        $request->validate(['rejection_reason' => 'required|string|max:1000']);
        if ($user->role !== 'seller' || !$user->seller) {
            return back()->with('error', 'Not a seller.');
        }
        $user->seller->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_at' => null,
            'approved_by' => null,
        ]);
        return redirect()->route('admin.sellers.kyc', $user)->with('success', 'Seller rejected.');
    }

    public function verifyKyc(User $user)
    {
        if ($user->role !== 'seller' || !$user->seller) {
            return back()->with('error', 'Not a seller.');
        }
        $user->seller->update(['kyc_status' => 'verified']);
        return redirect()->route('admin.sellers.kyc', $user)->with('success', 'KYC approved / verified.');
    }

    public function rejectKyc(Request $request, User $user)
    {
        $request->validate(['rejection_reason' => 'nullable|string|max:1000']);
        if ($user->role !== 'seller' || !$user->seller) {
            return back()->with('error', 'Not a seller.');
        }
        $user->seller->update([
            'kyc_status' => 'rejected',
            'rejection_reason' => $request->input('rejection_reason'),
        ]);
        return redirect()->route('admin.sellers.kyc', $user)->with('success', 'KYC rejected.');
    }

    public function updateKycStatus(Request $request, User $user)
    {
        $request->validate([
            'kyc_status' => 'required|in:none,pending,verified,rejected',
            'rejection_reason' => 'nullable|string|max:1000',
        ]);
        if ($user->role !== 'seller' || !$user->seller) {
            return back()->with('error', 'Not a seller.');
        }
        $data = ['kyc_status' => $request->kyc_status];
        if ($request->kyc_status === 'rejected') {
            $data['rejection_reason'] = $request->input('rejection_reason');
        } elseif ($request->kyc_status === 'verified') {
            $data['rejection_reason'] = null;
        }
        $user->seller->update($data);
        return redirect()->route('admin.sellers.kyc', $user)->with('success', 'KYC status updated to ' . $request->kyc_status . '.');
    }

    public function export(Request $request)
    {
        $query = User::where('role', 'seller')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_banned', false)->where('is_suspended', false);
            } elseif ($request->status === 'suspended') {
                $query->where('is_suspended', true);
            } elseif ($request->status === 'banned') {
                $query->where('is_banned', true);
            }
        }

        $sellers = $query->get();

        $headers = ['ID', 'Name', 'Email', 'Phone', 'Status', 'Joined'];
        $rows = [implode(',', $headers)];

        foreach ($sellers as $s) {
            $status = $s->is_banned ? 'Banned' : ($s->is_suspended ? 'Suspended' : 'Active');
            $joined = $s->created_at ? $s->created_at->copy()->setTimezone(config('app.timezone', 'UTC'))->format('Y-m-d g:i A') : '';
            $rows[] = implode(',', [
                $s->id,
                '"' . str_replace('"', '""', $s->name) . '"',
                '"' . str_replace('"', '""', $s->email) . '"',
                '"' . str_replace('"', '""', $s->phone ?? '') . '"',
                $status,
                '"' . str_replace('"', '""', $joined) . '"',
            ]);
        }

        $csv = "\xEF\xBB\xBF" . implode("\n", $rows);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sellers-' . date('Y-m-d') . '.csv"',
        ]);
    }
}
