<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SavedCardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cards = $request->user()->savedCards()->orderBy('is_default', 'desc')->get();
        return response()->json(['success' => true, 'cards' => $cards]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'brand' => 'nullable|string|max:50',
            'last4' => 'required|string|size:4',
            'stripe_pm_id' => 'nullable|string|max:255',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $data = $validator->validated();
        $data['user_id'] = $user->id;

        if (!empty($data['is_default'])) {
            SavedCard::where('user_id', $user->id)->update(['is_default' => false]);
        }

        $card = SavedCard::create($data);
        return response()->json(['success' => true, 'message' => 'Card saved', 'card' => $card], 201);
    }

    public function destroy(Request $request, SavedCard $savedCard): JsonResponse
    {
        if ($savedCard->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $savedCard->delete();
        return response()->json(['success' => true, 'message' => 'Card removed']);
    }

    public function setDefault(Request $request, SavedCard $savedCard): JsonResponse
    {
        if ($savedCard->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        SavedCard::where('user_id', $request->user()->id)->update(['is_default' => false]);
        $savedCard->update(['is_default' => true]);
        return response()->json(['success' => true, 'message' => 'Default card updated', 'card' => $savedCard->fresh()]);
    }
}
