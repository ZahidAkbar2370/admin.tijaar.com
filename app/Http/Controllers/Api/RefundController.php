<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function request(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        $order = Order::where('user_id', $request->user()->id)->findOrFail($request->order_id);

        if ($order->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Cannot request refund: order is cancelled.'], 422);
        }

        // Return/refund only after order is delivered (and payment completed for COD)
        if (!in_array($order->status, ['delivered', 'completed'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Return and refund requests are only allowed after your order has been delivered. Until then, use "Cancel order" if you no longer want it.',
            ], 422);
        }

        $payment = $order->payments()->whereIn('status', ['completed', 'pending'])->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'No payment found for this order'], 422);
        }

        $amount = (float) $request->amount;
        if ($amount > (float) $payment->amount) {
            return response()->json(['success' => false, 'message' => 'Refund amount exceeds payment'], 422);
        }

        $existingRefunds = $payment->refunds()->whereIn('status', ['pending', 'completed'])->sum('amount');
        if ($amount > (float) $payment->amount - $existingRefunds) {
            return response()->json(['success' => false, 'message' => 'Refund amount exceeds available balance'], 422);
        }

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'amount' => $amount,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Refund request submitted',
            'refund' => [
                'id' => $refund->id,
                'amount' => (float) $refund->amount,
                'status' => $refund->status,
            ],
        ], 201);
    }
}
