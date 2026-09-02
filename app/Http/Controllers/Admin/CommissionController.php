<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Commission;
use App\Models\User;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function index()
    {
        $commissions = Commission::with('category')->ordered()->get();
        return view('admin.commissions.index', compact('commissions'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $sellers = User::where('role', 'seller')->orderBy('name')->get();
        return view('admin.commissions.create', compact('categories', 'sellers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'scope_type' => 'required|in:global,category,seller_type,seller',
            'scope_id' => 'nullable|integer',
            'seller_type' => 'nullable|in:business,private',
            'commission_type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'priority' => 'nullable|integer|min:0',
        ]);

        if ($request->scope_type === 'category' && !$request->scope_id) {
            return back()->withInput()->withErrors(['scope_id' => 'Select a category.']);
        }
        if ($request->scope_type === 'seller' && !$request->scope_id) {
            return back()->withInput()->withErrors(['scope_id' => 'Select a seller.']);
        }
        if ($request->scope_type === 'seller_type' && !$request->seller_type) {
            return back()->withInput()->withErrors(['seller_type' => 'Select seller type.']);
        }

        Commission::create([
            'scope_type' => $request->scope_type,
            'scope_id' => $request->scope_id ?: null,
            'seller_type' => $request->seller_type ?: null,
            'commission_type' => $request->commission_type,
            'value' => $request->value,
            'priority' => $request->priority ?? 0,
            'is_active' => true,
        ]);

        return redirect()->route('admin.commissions.index')->with('success', 'Commission rule added.');
    }

    public function destroy(Commission $commission)
    {
        $commission->delete();
        return redirect()->route('admin.commissions.index')->with('success', 'Commission rule removed.');
    }
}
