<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Services\LocationService;
use App\Support\PhoneHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->orderBy('is_default', 'desc')->get();
        return response()->json([
            'success' => true,
            'addresses' => $addresses,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'country' => $request->input('country') ?: LocationService::defaultCountryName(),
        ]);

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:billing,shipping',
            'label' => 'nullable|string|max:50',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = LocationService::ensureCountry($validator->validated());
        $phone = PhoneHelper::normalize($data['phone'] ?? null);
        if ($phone === null) {
            return response()->json([
                'success' => false,
                'message' => 'Phone must be a valid Pakistani mobile (03XXXXXXXXX (also accepts 923�)).',
                'errors' => ['phone' => ['Invalid phone format']],
            ], 422);
        }
        $data['phone'] = $phone;
        $data['user_id'] = $request->user()->id;

        if (!empty($data['is_default'])) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address = Address::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Address created',
            'address' => $address,
        ], 201);
    }

    public function show(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        return response()->json(['success' => true, 'address' => $address]);
    }

    public function update(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:billing,shipping',
            'label' => 'nullable|string|max:50',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'address_line_1' => 'sometimes|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'sometimes|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'sometimes|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = LocationService::withDefaultCountry($validator->validated());
        if (array_key_exists('phone', $data)) {
            $phone = PhoneHelper::normalize($data['phone']);
            if ($phone === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone must be a valid Pakistani mobile (03XXXXXXXXX (also accepts 923�)).',
                    'errors' => ['phone' => ['Invalid phone format']],
                ], 422);
            }
            $data['phone'] = $phone;
        }

        if (!empty($data['is_default'])) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Address updated',
            'address' => $address->fresh(),
        ]);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $address->delete();
        return response()->json(['success' => true, 'message' => 'Address deleted']);
    }

    public function setDefault(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $request->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);
        return response()->json(['success' => true, 'message' => 'Default address updated', 'address' => $address->fresh()]);
    }
}
