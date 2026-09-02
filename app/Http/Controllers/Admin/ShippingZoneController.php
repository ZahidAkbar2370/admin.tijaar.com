<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingRule;
use App\Models\ShippingZone;
use Illuminate\Http\Request;

class ShippingZoneController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $zones = ShippingZone::withCount('rules')->orderBy('market')->orderBy('sort_order')->paginate(20);
        return view('admin.shipping-zones.index', compact('zones'));
    }

    public function create(): \Illuminate\View\View
    {
        return view('admin.shipping-zones.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'market' => 'required|in:PK,AE',
            'country' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        ShippingZone::create([
            'name' => $request->name,
            'market' => $request->market,
            'country' => $request->country,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => true,
        ]);

        return redirect()->route('admin.shipping-zones.index')->with('success', 'Zone created.');
    }

    public function edit(ShippingZone $shippingZone): \Illuminate\View\View
    {
        $shippingZone->load('allRules');
        return view('admin.shipping-zones.edit', compact('shippingZone'));
    }

    public function update(Request $request, ShippingZone $shippingZone)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'market' => 'required|in:PK,AE',
            'country' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $shippingZone->update([
            'name' => $request->name,
            'market' => $request->market,
            'country' => $request->country,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.shipping-zones.index')->with('success', 'Zone updated.');
    }

    public function storeRule(Request $request, ShippingZone $shippingZone)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'type' => 'required|in:flat,weight_based,price_based',
            'rate' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'free_threshold' => 'nullable|numeric|min:0',
            'min_weight_kg' => 'nullable|numeric|min:0',
            'max_weight_kg' => 'nullable|numeric|min:0',
        ]);

        ShippingRule::create([
            'shipping_zone_id' => $shippingZone->id,
            'name' => $request->name,
            'type' => $request->type,
            'rate' => $request->rate,
            'min_order_amount' => $request->min_order_amount,
            'free_threshold' => $request->free_threshold,
            'min_weight_kg' => $request->min_weight_kg,
            'max_weight_kg' => $request->max_weight_kg,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => true,
        ]);

        return redirect()->route('admin.shipping-zones.edit', $shippingZone)->with('success', 'Rule added.');
    }

    public function destroyRule(ShippingZone $shippingZone, ShippingRule $rule)
    {
        if ($rule->shipping_zone_id !== $shippingZone->id) {
            abort(404);
        }
        $rule->delete();
        return redirect()->route('admin.shipping-zones.edit', $shippingZone)->with('success', 'Rule removed.');
    }
}
