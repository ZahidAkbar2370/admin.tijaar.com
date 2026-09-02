<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function validate(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|max:32']);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Login required'], 401);
        }

        $cart = Cart::getOrCreate($user->id);
        $cart->load('items.product');

        if ($cart->items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Cart is empty'], 422);
        }

        $result = CouponService::validate($request->code, $cart);

        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'valid' => true,
            'coupon' => [
                'id' => $result['coupon']->id,
                'code' => $result['coupon']->code,
                'type' => $result['coupon']->type,
                'value' => (float) $result['coupon']->value,
            ],
            'discount' => $result['discount'],
            'applicable_total' => $result['applicable_total'],
        ]);
    }
}
