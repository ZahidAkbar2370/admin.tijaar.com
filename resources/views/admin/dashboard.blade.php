@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('admin-content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
    <form method="GET" class="flex gap-2">
        <select name="period" onchange="this.form.submit()" class="text-sm rounded-xl border border-gray-200 px-3 py-2">
            <option value="7" {{ ($period ?? 30) == 7 ? 'selected' : '' }}>Last 7 days</option>
            <option value="30" {{ ($period ?? 30) == 30 ? 'selected' : '' }}>Last 30 days</option>
            <option value="90" {{ ($period ?? 30) == 90 ? 'selected' : '' }}>Last 90 days</option>
        </select>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Total Users</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['users'] }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Customers</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['customers'] }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Sellers</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['sellers'] }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Stores</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['stores'] }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-violet-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Products</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['products'] }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Total Orders</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['orders_total'] ?? 0 }}</p>
                <p class="text-xs text-gray-400">{{ $stats['orders_pending'] ?? 0 }} pending</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Revenue ({{ $period ?? 30 }}d)</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['revenue_period'] ?? 0, 0) }} PKR</p>
                <p class="text-xs text-gray-400">{{ $stats['orders_period'] ?? 0 }} orders</p>
            </div>
        </div>
    </div>
    <a href="{{ route('admin.inventory.low-stock') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow hover:border-amber-200">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Low Stock</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['low_stock_count'] ?? 0 }}</p>
                <p class="text-xs text-amber-600">View report →</p>
            </div>
        </div>
    </a>
    <a href="{{ route('admin.inventory.out-of-stock') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow hover:border-red-200">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-red-500/10 flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V7a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4m16 0H4"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Out of Stock</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['out_of_stock_count'] ?? 0 }}</p>
                <p class="text-xs text-red-600">Hidden from shop →</p>
            </div>
        </div>
    </a>
</div>

{{-- Charts --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Revenue ({{ $period ?? 30 }} days)</h2>
        <div class="h-64 relative">
            <canvas id="chartRevenue" height="200"></canvas>
            @if(empty($chartRevenue) || $chartRevenue->isEmpty())
            <div class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm">No revenue data for this period</div>
            @endif
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Orders ({{ $period ?? 30 }} days)</h2>
        <div class="h-64">
            <canvas id="chartOrders" height="200"></canvas>
        </div>
    </div>
</div>

{{-- Recent Orders & Customers --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-semibold text-gray-900">Recent Orders</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-primary hover:underline">View all →</a>
        </div>
        <div class="space-y-3 max-h-72 overflow-y-auto">
            @forelse($recentOrders ?? [] as $o)
            <a href="{{ route('admin.orders.show', $o) }}" class="flex items-center justify-between py-3 px-4 rounded-xl hover:bg-gray-50 transition">
                <div>
                    <p class="font-medium text-gray-900">{{ $o->order_number }}</p>
                    <p class="text-xs text-gray-500">{{ $o->user?->name ?? 'Guest' }} · {{ $o->created_at->format('M d, H:i') }}</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-gray-900">{{ number_format($o->total, 0) }} PKR</p>
                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium
                        {{ in_array($o->status, ['completed','delivered']) ? 'bg-emerald-100 text-emerald-700' : '' }}
                        {{ in_array($o->status, ['pending','processing']) ? 'bg-amber-100 text-amber-700' : '' }}
                        {{ $o->status === 'cancelled' ? 'bg-red-100 text-red-700' : '' }}
                        {{ !in_array($o->status, ['completed','delivered','pending','processing','cancelled']) ? 'bg-gray-100 text-gray-600' : '' }}
                    ">{{ $o->status }}</span>
                </div>
            </a>
            @empty
            <p class="text-gray-500 text-sm py-4">No orders yet.</p>
            @endforelse
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-semibold text-gray-900">Recent Customers</h2>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-primary hover:underline">View all →</a>
        </div>
        <div class="space-y-3 max-h-72 overflow-y-auto">
            @forelse($recentCustomers ?? [] as $u)
            <a href="{{ route('admin.users.show', $u) }}" class="flex items-center gap-3 py-3 px-4 rounded-xl hover:bg-gray-50 transition">
                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold">
                    {{ strtoupper(substr($u->name ?? 'U', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 truncate">{{ $u->name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ $u->email }}</p>
                </div>
                <p class="text-xs text-gray-400 shrink-0">{{ $u->created_at->format('M d') }}</p>
            </a>
            @empty
            <p class="text-gray-500 text-sm py-4">No customers yet.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="font-semibold text-gray-900 mb-4">Quick Actions</h2>
    <div class="flex flex-wrap gap-4">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 px-4 py-3 bg-primary/5 hover:bg-primary/10 rounded-xl text-primary font-medium transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            Manage Customers
        </a>
        <a href="{{ route('admin.sellers.index') }}" class="inline-flex items-center gap-2 px-4 py-3 bg-amber-50 hover:bg-amber-100 rounded-xl text-amber-700 font-medium transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Manage Sellers
        </a>
        <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center gap-2 px-4 py-3 bg-blue-50 hover:bg-blue-100 rounded-xl text-blue-700 font-medium transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Manage Stores
        </a>
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-2 px-4 py-3 bg-violet-50 hover:bg-violet-100 rounded-xl text-violet-700 font-medium transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            Manage Products
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const primary = 'rgb(23, 144, 215)';
    const primaryLight = 'rgba(23, 144, 215, 0.2)';

    const revData = @json($chartRevenue ?? []);
    const ordData = @json($chartOrders ?? []);

    if (document.getElementById('chartRevenue') && revData.length) {
        new Chart(document.getElementById('chartRevenue'), {
            type: 'line',
            data: {
                labels: revData.map(d => String(d.date || '')),
                datasets: [{
                    label: 'Revenue (PKR)',
                    data: revData.map(d => Number(d.total) || 0),
                    borderColor: primary,
                    backgroundColor: primaryLight,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() } }
                }
            }
        });
    }

    if (document.getElementById('chartOrders') && ordData.length) {
        new Chart(document.getElementById('chartOrders'), {
            type: 'bar',
            data: {
                labels: ordData.map(d => d.date),
                datasets: [{
                    label: 'Orders',
                    data: ordData.map(d => d.count),
                    backgroundColor: primaryLight,
                    borderColor: primary,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }
});
</script>
@endsection
