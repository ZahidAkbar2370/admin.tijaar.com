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

class WhatsappVerificationController extends Controller
{
    private const OTP_EXPIRY_MINUTES = 10;

    /**
     * Whether WaChat is configured (for profile UI).
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'wachat_enabled' => WachatService::isEnabled(),
            'whatsapp_verified' => (bool) $user->whatsapp_verified_at,
            'whatsapp_verified_at' => $user->whatsapp_verified_at,
            'phone' => $user->phone,
            'whatsapp_number' => $user->whatsapp_number,
        ]);
    }

    public function sendOtp(Request $request): JsonResponse
    {
        if (! WachatService::isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp verification is not available right now.',
            ], 503);
        }

        $user = $request->user();
        $phoneInput = $request->input('phone', $request->input('whatsapp_number', $user->whatsapp_number ?: $user->phone));
        $phone = PhoneHelper::normalize($phoneInput);

        if (! $phone) {
            return response()->json([
                'success' => false,
                'message' => 'Add a valid WhatsApp number (Pakistani mobile) on your profile first.',
            ], 422);
        }

        $taken = User::where('id', '!=', $user->id)
            ->where(function ($q) use ($phone) {
                $q->where('phone', $phone)->orWhere('whatsapp_number', $phone);
            })
            ->exists();
        if ($taken) {
            return response()->json([
                'success' => false,
                'message' => 'This WhatsApp number is already registered to another account.',
                'errors' => ['whatsapp_number' => ['WhatsApp number already in use']],
            ], 422);
        }

        // Persist WhatsApp number (separate from contact phone)
        if ($user->whatsapp_number !== $phone) {
            $user->update([
                'whatsapp_number' => $phone,
                'whatsapp_verified_at' => null,
            ]);
        }

        DB::table('whatsapp_verification_otps')->where('user_id', $user->id)->delete();

        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        DB::table('whatsapp_verification_otps')->insert([
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
        ], "Tijaar: Your WhatsApp verification code is {$otp}. It expires in ".self::OTP_EXPIRY_MINUTES.' minutes. Do not share this code.');
        $result = WachatService::send($phone, $message);

        if (! ($result['ok'] ?? false)) {
            Log::warning('WhatsApp OTP send failed', ['user_id' => $user->id, 'result' => $result]);

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to send WhatsApp OTP. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to WhatsApp on '.$phone,
            'phone' => $phone,
            'whatsapp_number' => $phone,
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
                'message' => 'Enter the 6-digit code from WhatsApp.',
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

        $row = DB::table('whatsapp_verification_otps')
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
            'whatsapp_number' => $row->phone,
            'whatsapp_verified_at' => now(),
        ]);

        DB::table('whatsapp_verification_otps')->where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp number verified successfully.',
            'user' => $user->fresh()->makeHidden(['password']),
            'whatsapp_verified' => true,
        ]);
    }
}
