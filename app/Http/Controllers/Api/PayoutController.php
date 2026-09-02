<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function earnings(Request $request): JsonResponse
    {
        $user = $request->user();
        $sellerType = $user->role === 'seller' ? 'business' : 'private';

        if ($user->role === 'seller') {
            if (!$user->seller?->store) {
                return response()->json([
                    'success' => true,
                    'earnings' => [
                        'total' => 0,
                        'commission' => 0,
                        'net' => 0,
                        'items' => [],
                        'already_paid' => 0,
                        'min_threshold' => PayoutService::getMinPayoutThreshold('business'),
                    ],
                ]);
            }
        } elseif ($user->role === 'customer') {
            // Customer acting as seller — show earnings from private listing sales
            $earnings = PayoutService::getEarningsForUser($user, 'private');
            $earnings['min_threshold'] = PayoutService::getMinPayoutThreshold('private');

            return response()->json(['success' => true, 'earnings' => $earnings]);
        }

        $earnings = PayoutService::getEarningsForUser($user, $sellerType);
        $earnings['min_threshold'] = PayoutService::getMinPayoutThreshold($sellerType);

        return response()->json(['success' => true, 'earnings' => $earnings]);
    }

    public function request(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->validate([
            'method' => 'nullable|in:bank,wallet',
            'amount' => 'nullable|numeric|min:0',
        ]);

        $sellerType = $user->role === 'seller' ? 'business' : 'private';
        $method = $request->input('method', 'bank');
        $amount = $request->has('amount') && $request->input('amount') !== '' && $request->input('amount') !== null
            ? (float) $request->input('amount')
            : null;

        if ($sellerType === 'business' && !$user->seller?->store) {
            return response()->json(['success' => false, 'message' => 'No store found.'], 422);
        }

        if ($sellerType === 'private' && $user->role !== 'customer') {
            return response()->json(['success' => false, 'message' => 'No private listings.'], 422);
        }

        // Allow payout for any customer with private listing earnings (not only KYC private sellers)
        if ($sellerType === 'private') {
            $hasListings = \App\Models\Product::withTrashed()
                ->where('seller_type', 'private')
                ->where('seller_id', $user->id)
                ->exists();
            if (!$hasListings && !($user->is_private_seller ?? false)) {
                return response()->json(['success' => false, 'message' => 'No private listings.'], 422);
            }
        }

        try {
            $payout = PayoutService::createPayoutRequest($user, $sellerType, $method, $amount);
            try {
                $user->notify(new \App\Notifications\PayoutRequestedNotification($payout));
            } catch (\Throwable $e) {
                \Log::warning('Payout requested notification failed: ' . $e->getMessage());
            }
            return response()->json([
                'success' => true,
                'message' => 'Payout request submitted.',
                'payout' => $payout->load('items'),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $payouts = \App\Models\Payout::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'payouts' => $payouts->items(),
            'pagination' => [
                'current_page' => $payouts->currentPage(),
                'last_page' => $payouts->lastPage(),
                'total' => $payouts->total(),
            ],
        ]);
    }
}
