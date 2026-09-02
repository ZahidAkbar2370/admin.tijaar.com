<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->two_factor_enabled) {
            return response()->json(['success' => false, 'message' => '2FA is already enabled.'], 400);
        }

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $user->update([
            'two_factor_secret' => encrypt($secret),
        ]);

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            'Tijaar',
            $user->email,
            $secret
        );

        $writer = new Writer(
            new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd())
        );
        $qrCodeSvg = $writer->writeString($qrCodeUrl);

        return response()->json([
            'success' => true,
            'secret' => $secret,
            'qr_code_svg' => $qrCodeSvg,
            'message' => 'Scan the QR code with your authenticator app, then verify with a code.',
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $secret = decrypt($user->two_factor_secret);
        $google2fa = new Google2FA();

        if (!$google2fa->verifyKey($secret, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code.',
            ], 422);
        }

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt($secret),
        ]);

        $recoveryCodes = $this->generateRecoveryCodes($user);
        foreach ($recoveryCodes as $code) {
            \App\Models\TwoFactorRecoveryCode::create([
                'user_id' => $user->id,
                'code' => Hash::make($code),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => '2FA enabled successfully.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is incorrect.',
            ], 422);
        }

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
        ]);
        \App\Models\TwoFactorRecoveryCode::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => '2FA disabled successfully.',
        ]);
    }

    private function generateRecoveryCodes($user): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }
}
