<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StoreProfileSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Support\UploadHelper;

class UserController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load('addresses');
        return response()->json([
            'success' => true,
            'user' => $user->makeHidden(['password']),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:120',
            'state' => 'nullable|string|max:120',
            'permanent_address' => 'nullable|string|max:1000',
            'delivery_address' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only(['name', 'phone', 'whatsapp_number', 'city', 'state', 'permanent_address', 'delivery_address']);

        if (array_key_exists('phone', $data)) {
            if ($data['phone'] === null || $data['phone'] === '') {
                $data['phone'] = null;
                $data['phone_verified_at'] = null;
            } else {
                $phone = \App\Support\PhoneHelper::normalize($data['phone']);
                if ($phone === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Phone must be a valid Pakistani mobile (03XXXXXXXXX / 923XXXXXXXXX).',
                        'errors' => ['phone' => ['Invalid phone format']],
                    ], 422);
                }
                if ($this->mobileNumberTaken($user->id, $phone)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This mobile number is already registered to another account.',
                        'errors' => ['phone' => ['Mobile number already in use']],
                    ], 422);
                }
                $data['phone'] = $phone;
                if ($user->phone !== $phone) {
                    $data['phone_verified_at'] = null;
                }
            }
        }

        if (array_key_exists('whatsapp_number', $data)) {
            if ($data['whatsapp_number'] === null || $data['whatsapp_number'] === '') {
                $data['whatsapp_number'] = null;
                $data['whatsapp_verified_at'] = null;
            } else {
                $wa = \App\Support\PhoneHelper::normalize($data['whatsapp_number']);
                if ($wa === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'WhatsApp number must be a valid Pakistani mobile (03XXXXXXXXX / 923XXXXXXXXX).',
                        'errors' => ['whatsapp_number' => ['Invalid WhatsApp number format']],
                    ], 422);
                }
                if ($this->mobileNumberTaken($user->id, $wa)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This WhatsApp number is already registered to another account.',
                        'errors' => ['whatsapp_number' => ['WhatsApp number already in use']],
                    ], 422);
                }
                $data['whatsapp_number'] = $wa;
                if ($user->whatsapp_number !== $wa) {
                    $data['whatsapp_verified_at'] = null;
                }
            }
        }

        $user->update($data);

        if ($user->role === 'seller') {
            StoreProfileSync::syncFromUser($user->fresh());
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated',
            'user' => $user->fresh()->makeHidden(['password'])->load('addresses'),
        ]);
    }

    /** Phone and WhatsApp must be unique across both columns for other users. */
    private function mobileNumberTaken(int $userId, string $normalized): bool
    {
        return \App\Models\User::where('id', '!=', $userId)
            ->where(function ($q) use ($normalized) {
                $q->where('phone', $normalized)->orWhere('whatsapp_number', $normalized);
            })
            ->exists();
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'avatar_alt' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if ($user->avatar) {
            UploadHelper::deleteAny($user->avatar);
        }

        $path = UploadHelper::storePublic($request->file('avatar'), 'avatars');
        $user->update([
            'avatar' => $path,
            'avatar_alt' => $request->input('avatar_alt'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar updated',
            'user' => $user->fresh()->makeHidden(['password']),
            'avatar_url' => UploadHelper::url($path),
        ]);
    }
}
