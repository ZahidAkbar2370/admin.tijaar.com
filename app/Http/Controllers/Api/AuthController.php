<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\RecaptchaService;
use App\Support\PhoneHelper;
use App\Support\RegistrationSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const OTP_EXPIRY_MINUTES = 15;

    public static function isEmailVerificationRequired(): bool
    {
        return (string) Setting::get('email_verification_required', '1') === '1';
    }

    private function issueEmailVerificationOtp(User $user): bool
    {
        DB::table('email_verification_otps')->where('email', $user->email)->delete();

        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        DB::table('email_verification_otps')->insert([
            'email' => $user->email,
            'otp_code' => $otp,
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $user->notify(new \App\Notifications\EmailVerificationOtpNotification($otp, self::OTP_EXPIRY_MINUTES));
            return true;
        } catch (\Throwable $e) {
            \Log::warning('Send verification OTP failed: ' . $e->getMessage());
            return false;
        }
    }

    public function register(Request $request): JsonResponse
    {
        if (RecaptchaService::requiredForRegister()) {
            $captcha = RecaptchaService::verify($request->input('recaptcha_token'), $request->ip());
            if (!$captcha['ok']) {
                return response()->json([
                    'success' => false,
                    'message' => $captcha['message'] ?? 'reCAPTCHA verification failed.',
                    'errors' => ['recaptcha_token' => [$captcha['message'] ?? 'Please complete the reCAPTCHA challenge.']],
                    'error_code' => 'recaptcha_failed',
                ], 422);
            }
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            // Public signup is customer-only. Private sellers convert via Profile KYC.
            'role' => 'nullable|in:customer',
            'phone' => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = 'customer';
        $phone = PhoneHelper::normalize($request->phone);
        if ($request->filled('phone') && $phone === null) {
            return response()->json([
                'success' => false,
                'message' => 'Phone must be a valid Pakistani mobile (03XXXXXXXXX).',
                'errors' => ['phone' => ['Invalid phone format']],
            ], 422);
        }
        if ($phone !== null && User::where('phone', $phone)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This mobile number is already registered to another account.',
                'errors' => ['phone' => ['Mobile number already in use']],
            ], 422);
        }

        $requireVerification = self::isEmailVerificationRequired();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $phone,
            'role' => $role,
            'registration_source' => RegistrationSource::fromRequest($request),
            'email_verified_at' => $requireVerification ? null : now(),
        ]);

        if ($requireVerification) {
            $sent = $this->issueEmailVerificationOtp($user);
            ActivityLogger::log([
                'action_type' => 'register',
                'action_by' => $user->id,
                'target_table' => 'users',
                'action_on' => $user->id,
                'description' => "User registered (email verification required): {$user->email}",
            ], $request);
            if (!$sent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account created, but we could not send the verification email. Please use Resend on the verify page, or contact support.',
                    'requires_verification' => true,
                    'email' => $user->email,
                    'error_code' => 'otp_send_failed',
                ], 201);
            }

            return response()->json([
                'success' => true,
                'message' => 'Account created. Please verify your email with the code we sent you.',
                'requires_verification' => true,
                'email' => $user->email,
            ], 201);
        }

        try {
            $user->notify(new \App\Notifications\WelcomeNotification($user->name, $user->role ?? 'customer'));
        } catch (\Throwable $e) {
            \Log::warning('Welcome email failed: ' . $e->getMessage());
        }

        $token = $user->createToken($this->deviceTokenName($request))->plainTextToken;
        $user->load(['addresses']);
        if (method_exists($user, 'seller') && $user->seller) {
            $user->load(['seller.store']);
        }

        ActivityLogger::log([
            'action_type' => 'register',
            'action_by' => $user->id,
            'target_table' => 'users',
            'action_on' => $user->id,
            'description' => "User registered: {$user->email}",
        ], $request);

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully. You can complete your order.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->makeHidden(['password']),
            'requires_verification' => false,
        ], 201);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        try {
            $email = $request->input('email');
            $rawOtp = $request->input('otp') ?? $request->input('code') ?? '';
            $otpCode = preg_replace('/\D/', '', (string) $rawOtp);

            $validator = Validator::make([
                'email' => $email,
                'otp' => $otpCode,
            ], [
                'email' => 'required|email',
                'otp' => 'required|string|size:6',
            ], [
                'otp.size' => 'The verification code must be 6 digits.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $now = \Carbon\Carbon::now();
            $row = DB::table('email_verification_otps')
                ->where('email', $email)
                ->where('otp_code', $otpCode)
                ->where('expires_at', '>', $now)
                ->first();

            if (!$row) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired verification code.',
                ], 422);
            }

            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User not found.'], 404);
            }

            $user->update(['email_verified_at' => now()]);
            DB::table('email_verification_otps')->where('email', $email)->delete();

            try {
                $user->notify(new \App\Notifications\WelcomeNotification($user->name, $user->role ?? 'customer'));
            } catch (\Throwable $e) {
                \Log::warning('Welcome email failed: ' . $e->getMessage());
            }

            $token = $user->createToken($this->deviceTokenName($request))->plainTextToken;

            ActivityLogger::log([
                'action_type' => 'verify_email',
                'action_by' => $user->id,
                'target_table' => 'users',
                'action_on' => $user->id,
                'description' => "Email verified: {$user->email}",
            ], $request);

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully! Welcome to Tijaar.',
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->makeHidden(['password']),
            ]);
        } catch (\Throwable $e) {
            \Log::error('verifyOtp failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'email' => $email ?? null,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Verification failed. Please try again or contact support.',
            ], 500);
        }
    }

    public function resendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email is already verified. You can login.',
            ], 400);
        }

        $sent = $this->issueEmailVerificationOtp($user);
        if (!$sent) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'A new verification code has been sent to your email.',
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        if (RecaptchaService::requiredForLogin()) {
            $captcha = RecaptchaService::verify($request->input('recaptcha_token'), $request->ip());
            if (!$captcha['ok']) {
                return response()->json([
                    'success' => false,
                    'message' => $captcha['message'] ?? 'reCAPTCHA verification failed.',
                    'errors' => ['recaptcha_token' => [$captcha['message'] ?? 'Please complete the reCAPTCHA challenge.']],
                    'error_code' => 'recaptcha_failed',
                ], 422);
            }
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email address doesn\'t exist',
                'error_code' => 'email_not_found',
            ], 401);
        }
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is wrong',
                'error_code' => 'invalid_password',
            ], 401);
        }

        if (in_array($user->role, ['admin', 'sub_admin'])) {
            Auth::logout();
            $adminUrl = rtrim(config('app.url'), '/') . '/admin/login';
            return response()->json([
                'success' => false,
                'message' => "Admins must login at the admin panel: {$adminUrl}",
            ], 403);
        }

        if (!$user->isActive()) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Account is suspended or banned',
            ], 403);
        }

        if (self::isEmailVerificationRequired() && !$user->email_verified_at) {
            Auth::logout();
            // Soft-resend so the customer can continue on the verify page.
            $this->issueEmailVerificationOtp($user);
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email before logging in. Check your inbox for the verification code.',
                'requires_verification' => true,
                'email' => $user->email,
            ], 403);
        }

        $user->update(['last_login_at' => now()]);

        // Do NOT delete other tokens — keeps concurrent sessions (web + app) alive.
        $token = $user->createToken($this->deviceTokenName($request))->plainTextToken;

        ActivityLogger::log([
            'action_type' => 'login',
            'action_by' => $user->id,
            'target_table' => 'users',
            'action_on' => $user->id,
            'description' => "User logged in: {$user->email}",
        ], $request);

        $user->load(['addresses', 'seller.store']);
        $data = $user->makeHidden(['password'])->toArray();
        if ($user->seller) {
            $data['is_seller_verified'] = $user->seller->kyc_status === 'verified';
            $data['kyc_status'] = $user->seller->kyc_status;
            $data['seller_status'] = $user->seller->status;
        }
        $data['private_seller_verification'] = \App\Services\PrivateSellerVerificationService::statusFor($user);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $data,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        ActivityLogger::log([
            'action_type' => 'logout',
            'action_by' => $user?->id,
            'target_table' => 'users',
            'action_on' => $user?->id,
            'description' => 'User logged out: '.($user?->email ?? 'unknown'),
        ], $request);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Human-readable token name derived from the request User-Agent so the
     * Sessions screen can distinguish concurrent devices (Web vs Mobile app).
     */
    private function deviceTokenName(Request $request): string
    {
        $ua = strtolower((string) $request->userAgent());
        if ($ua === '' || str_contains($ua, 'dart') || str_contains($ua, 'okhttp') || str_contains($ua, 'flutter')) {
            return 'Mobile App';
        }
        if (str_contains($ua, 'edg')) {
            return 'Web (Edge)';
        }
        if (str_contains($ua, 'chrome')) {
            return 'Web (Chrome)';
        }
        if (str_contains($ua, 'firefox')) {
            return 'Web (Firefox)';
        }
        if (str_contains($ua, 'safari')) {
            return 'Web (Safari)';
        }
        return 'Web Browser';
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['addresses', 'seller.store']);
        $data = $user->makeHidden(['password'])->toArray();
        if ($user->seller) {
            $data['is_seller_verified'] = $user->seller->kyc_status === 'verified';
            $data['kyc_status'] = $user->seller->kyc_status;
            $data['seller_status'] = $user->seller->status;
        }
        $data['private_seller_verification'] = \App\Services\PrivateSellerVerificationService::statusFor($user);
        return response()->json([
            'success' => true,
            'user' => $data,
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset link has been sent to your email.',
            ]);
        }

        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait a minute before requesting another reset link.',
            ], 429);
        }

        return response()->json([
            'success' => false,
            'message' => 'We could not find a user with that email address.',
        ], 400);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password has been reset. You can now login.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired reset token.',
        ], 400);
    }
}
