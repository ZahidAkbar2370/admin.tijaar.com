<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlaggedItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AbuseSafetyController extends Controller
{
    public function index(): View
    {
        $settings = [
            'auto_ban_threshold' => \App\Models\Setting::get('abuse_auto_ban_threshold', '100'),
            'max_price_private' => \App\Models\Setting::get('private_max_price_threshold', '500000'),
            'blocked_categories_private' => \App\Models\Setting::get('private_blocked_categories', '') ?: '',
            'duplicate_image_detection' => \App\Models\Setting::get('abuse_duplicate_image_detection', '0'),
            'listing_expiry_days' => \App\Models\Setting::get('private_listing_expiry_days', '30'),
        ];
        return view('admin.abuse-safety.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'auto_ban_threshold' => 'nullable|integer|min:0|max:1000',
            'max_price_private' => 'nullable|numeric|min:0',
            'blocked_categories_private' => 'nullable|string|max:1000',
            'duplicate_image_detection' => 'nullable|in:0,1',
        ]);

        \App\Models\Setting::set('abuse_auto_ban_threshold', (string) ($request->auto_ban_threshold ?? '100'));
        \App\Models\Setting::set('private_max_price_threshold', (string) ($request->max_price_private ?? '500000'));
        \App\Models\Setting::set('private_blocked_categories', $request->blocked_categories_private ?? '');
        \App\Models\Setting::set('abuse_duplicate_image_detection', (string) ($request->duplicate_image_detection ?? '0'));

        return back()->with('success', 'Abuse & safety settings updated.');
    }

    public function flaggedItems(Request $request): View
    {
        $query = FlaggedItem::with(['reporter:id,name,email', 'flaggable'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $flagged = $query->paginate(20)->withQueryString();
        return view('admin.abuse-safety.flagged', compact('flagged'));
    }
}
