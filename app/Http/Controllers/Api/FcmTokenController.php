<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FcmTokenController extends Controller
{
    /**
     * Register or update FCM token for the current user.
     * POST /notifications/fcm-token
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => ['required', 'string', 'max:512'],
            'device_type' => ['required', 'string', 'in:web,android,ios'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $fcmToken = $validator->validated()['fcm_token'];
        $deviceType = $validator->validated()['device_type'];
        $deviceName = $validator->validated()['device_name'] ?? null;

        $token = FcmToken::where('fcm_token', $fcmToken)->first();

        if ($token) {
            $token->update([
                'user_id' => $user->id,
                'device_type' => $deviceType,
                'device_name' => $deviceName,
                'last_used_at' => now(),
            ]);
        } else {
            FcmToken::create([
                'user_id' => $user->id,
                'fcm_token' => $fcmToken,
                'device_type' => $deviceType,
                'device_name' => $deviceName,
                'last_used_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'FCM token registered',
        ]);
    }
}
