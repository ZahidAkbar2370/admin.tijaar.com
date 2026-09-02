<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $query = Store::with('seller.user')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhereHas('seller.user', fn ($uq) => $uq->where('email', 'like', '%' . $request->search . '%'));
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $stores = $query->paginate(20)->withQueryString();

        return view('admin.stores.index', compact('stores'));
    }

    public function show(Store $store)
    {
        $store->load('seller.user');
        return view('admin.stores.show', compact('store'));
    }

    public function export(Request $request)
    {
        $stores = Store::with('seller.user')->orderBy('created_at', 'desc')->get();

        $headers = ['ID', 'Store', 'Slug', 'Seller', 'Email', 'Country', 'Active'];
        $rows = [implode(',', $headers)];

        foreach ($stores as $s) {
            $rows[] = implode(',', [
                $s->id,
                '"' . str_replace('"', '""', $s->name) . '"',
                $s->slug,
                '"' . str_replace('"', '""', $s->seller?->user?->name ?? '—') . '"',
                $s->seller?->user?->email ?? '—',
                $s->country ?? '—',
                $s->is_active ? 'Yes' : 'No',
            ]);
        }

        $csv = "\xEF\xBB\xBF" . implode("\n", $rows);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="stores-' . date('Y-m-d') . '.csv"',
        ]);
    }
}
