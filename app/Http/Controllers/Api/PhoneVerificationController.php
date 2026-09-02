<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsappTemplate;
use App\Services\WachatService;
use App\Support\PhoneHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PhoneVerificationController extends Controller
{
    private const OTP_EXPIRY_MINUTES = 10;

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'verification_available' => WachatService::isEnabled(),
            'phone_verified' => (bool) $user->phone_verified_at,
            'phone_verified_at' => $user->phone_verified_at,
            'phone' => $user->phone,
        ]);
    }

    public function sendOtp(Request $request): JsonResponse
    {
        if (! WachatService::isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Mobile verification is not available right now.',
            ], 503);
        }

        $user = $request->user();
        $phone = PhoneHelper::normalize($request->input('phone', $user->phone));

        if (! $phone) {
            return response()->json([
                'success' => false,
                'message' => 'Enter a valid Pakistani mobile number (03XXXXXXXXX).',
            ], 422);
        }

        if ($this->numberTaken($user, $phone)) {
            return response()->json([
                'success' => false,
                'message' => 'This mobile number is already registered to another account.',
                'errors' => ['phone' => ['Mobile number already in use']],
            ], 422);
        }

        if ($user->phone !== $phone) {
            $user->update([
                'phone' => $phone,
                'phone_verified_at' => null,
            ]);
        }

        DB::table('phone_verification_otps')->where('user_id', $user->id)->delete();

        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        DB::table('phone_verification_otps')->insert([
            'user_id' => $user->id,
            'phone' => $phone,
            'otp_code' => $otp,
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $message = WhatsappTemplate::renderSlug('whatsapp_otp', [
            'otp' => $otp,
            'expiry_minutes' => (string) self::OTP_EXPIRY_MINUTES,
            'name' => $user->name ?? '',
            'app_name' => config('app.name', 'Tijaar'),
        ], 'Tijaar: Your mobile verification code is '.$otp.'. It expires in '.self::OTP_EXPIRY_MINUTES.' minutes. Do not share this code.');

        $result = WachatService::send($phone, $message);

        if (! ($result['ok'] ?? false)) {
            Log::warning('Phone OTP send failed', ['user_id' => $user->id, 'result' => $result]);

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to send verification code. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to '.$phone,
            'phone' => $phone,
            'expires_in_minutes' => self::OTP_EXPIRY_MINUTES,
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Enter the 6-digit verification code.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $otpCode = preg_replace('/\D/', '', (string) $request->input('otp'));

        if (strlen($otpCode) !== 6) {
            return response()->json([
                'success' => false,
                'message' => 'The verification code must be 6 digits.',
            ], 422);
        }

        $row = DB::table('phone_verification_otps')
            ->where('user_id', $user->id)
            ->where('otp_code', $otpCode)
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if (! $row) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        $user->update([
            'phone' => $row->phone,
            'phone_verified_at' => now(),
        ]);

        DB::table('phone_verification_otps')->where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mobile number verified successfully.',
            'user' => $user->fresh()->makeHidden(['password']),
            'phone_verified' => true,
        ]);
    }

    private function numberTaken(User $user, string $normalized): bool
    {
        return User::where('id', '!=', $user->id)
            ->where(function ($q) use ($normalized) {
                $q->where('phone', $normalized)->orWhere('whatsapp_number', $normalized);
            })
            ->exists();
    }
}
