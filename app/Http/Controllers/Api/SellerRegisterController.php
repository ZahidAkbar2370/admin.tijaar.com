<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\RecaptchaService;
use App\Support\KycDocumentRules;
use App\Support\PhoneHelper;
use App\Support\UploadHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Public business-seller onboarding: account + store + KYC in one step.
 * Admin approves seller status and KYC separately.
 */
class SellerRegisterController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        if (RecaptchaService::requiredForRegister()) {
            $captcha = RecaptchaService::verify($request->input('recaptcha_token'), $request->ip());
            if (! $captcha['ok']) {
                return response()->json([
                    'success' => false,
                    'message' => $captcha['message'] ?? 'reCAPTCHA verification failed.',
                    'errors' => ['recaptcha_token' => [$captcha['message'] ?? 'Please complete the reCAPTCHA challenge.']],
                    'error_code' => 'recaptcha_failed',
                ], 422);
            }
        }

        $validator = Validator::make($request->all(), [
            // Account
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:30',
            // Store
            'store_name' => ['required', 'string', 'max:255', Rule::unique('stores', 'name')],
            'store_description' => 'nullable|string|max:5000',
            'store_address' => 'nullable|string|max:500',
            'store_city' => 'nullable|string|max:100',
            'store_state' => 'nullable|string|max:100',
            'store_country' => 'nullable|string|max:100',
            'store_zip_code' => 'nullable|string|max:20',
            'store_phone' => 'nullable|string|max:30',
            'store_email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            // KYC
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
        ], [
            'store_name.unique' => 'This store name is already taken. Please choose another.',
            'id_front.required' => 'Upload the front of your ID document.',
            'id_back.required' => 'Upload the back of your ID document.',
        ]);

        $kycValidator = KycDocumentRules::makeValidator($request);
        if ($kycValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $kycValidator->errors(),
            ], 422);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = PhoneHelper::normalize($request->phone);
        if ($phone === null) {
            return response()->json([
                'success' => false,
                'message' => 'Phone must be a valid Pakistani mobile (03XXXXXXXXX).',
                'errors' => ['phone' => ['Invalid phone format']],
            ], 422);
        }
        if (User::where('phone', $phone)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This mobile number is already registered to another account.',
                'errors' => ['phone' => ['Mobile number already in use']],
            ], 422);
        }

        $requireEmailVerification = (string) Setting::get('email_verification_required', '1') === '1';

        try {
            $result = DB::transaction(function () use ($request, $phone, $requireEmailVerification) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => $request->password, // User model `hashed` cast
                    'phone' => $phone,
                    'role' => 'seller',
                    'email_verified_at' => $requireEmailVerification ? null : now(),
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

                $slug = Str::slug($request->store_name);
                $baseSlug = $slug ?: 'store';
                $slug = $baseSlug;
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
                    'city' => $request->input('store_city'),
                    'state' => $request->input('store_state'),
                    'country' => $request->input('store_country') ?: 'Pakistan',
                    'zip_code' => $request->input('store_zip_code'),
                    'phone' => $request->input('store_phone') ?: $phone,
                    'email' => $request->input('store_email') ?: $user->email,
                    'is_active' => false, // live after admin approves seller
                ]);

                return compact('user', 'seller', 'store');
            });
        } catch (\Throwable $e) {
            \Log::error('Seller register failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Registration failed. Please try again.',
            ], 500);
        }

        $user = $result['user'];

        ActivityLogger::log([
            'action_type' => 'seller_register',
            'action_by' => $user->id,
            'target_table' => 'users',
            'action_on' => $user->id,
            'description' => "Seller registered (pending approval): {$user->email}",
        ], $request);

        if ($requireEmailVerification) {
            $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            DB::table('email_verification_otps')->where('email', $user->email)->delete();
            DB::table('email_verification_otps')->insert([
                'email' => $user->email,
                'otp_code' => $otp,
                'expires_at' => now()->addMinutes(15),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            try {
                $user->notify(new \App\Notifications\EmailVerificationOtpNotification($otp, 15));
            } catch (\Throwable $e) {
                \Log::warning('Seller register OTP email failed: '.$e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Seller account created. Verify your email, then wait for admin approval of your store and KYC.',
                'requires_verification' => true,
                'email' => $user->email,
                'seller_pending' => true,
            ], 201);
        }

        $token = $user->createToken('seller-register')->plainTextToken;
        $user->load(['seller.store']);

        return response()->json([
            'success' => true,
            'message' => 'Seller account created. Your store and KYC are pending admin approval.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->makeHidden(['password']),
            'requires_verification' => false,
            'seller_pending' => true,
        ], 201);
    }
}
