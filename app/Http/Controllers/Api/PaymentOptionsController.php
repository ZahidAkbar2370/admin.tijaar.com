<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PartialPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentOptionsController extends Controller
{
    /**
     * Preview online/COD split for checkout (public), including marketplace fees.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'subtotal' => 'required|numeric|min:0',
            'shipping' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
        ]);

        $subtotal = (float) $request->subtotal;
        $shipping = (float) ($request->shipping ?? 0);
        $discount = (float) ($request->discount ?? 0);
        $paymentMethod = $request->input('payment_method');

        return response()->json([
            'success' => true,
            'payment' => PartialPaymentService::preview($subtotal, $shipping, $discount, $paymentMethod),
        ]);
    }
}
