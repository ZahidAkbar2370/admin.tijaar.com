@extends('admin.layouts.app')

@section('title', 'Sellers (Buy & Sell)')

@section('admin-content')
<div x-data="{
    columns: {
        id: { label: 'ID', visible: true },
        name: { label: 'Name', visible: true },
        email: { label: 'Email', visible: true },
        phone: { label: 'Phone', visible: true },
        listings: { label: 'Listings', visible: true },
        kyc: { label: 'KYC', visible: true },
        status: { label: 'Status', visible: true },
        joined: { label: 'Joined', visible: true },
        actions: { label: 'Actions', visible: true }
    },
    showColumnMenu: false
}">

@include('admin.partials.settings-flash')

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Sellers</h1>
        <p class="text-sm text-gray-500 mt-1">Customers who buy and also sell (private listings / C2C sales)</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <form method="GET" action="{{ route('admin.customer-sellers.index') }}" class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, email, phone..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white" />
            </div>
            <div class="w-44">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
                    <option value="banned" @selected(request('status') === 'banned')>Banned</option>
                </select>
            </div>
            <div class="w-44">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">KYC</label>
                <select name="kyc" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white">
                    <option value="">All</option>
                    <option value="approved" @selected(request('kyc') === 'approved')>Approved</option>
                    <option value="pending" @selected(request('kyc') === 'pending')>Pending</option>
                    <option value="casual" @selected(request('kyc') === 'casual')>Casual (no KYC)</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">Filter</button>
            @if (request()->hasAny(['search', 'status', 'kyc']))
                <a href="{{ route('admin.customer-sellers.index') }}" class="px-4 py-2.5 text-gray-500 hover:text-gray-700 text-sm rounded-xl">Clear</a>
            @endif
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Listings</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">KYC</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $u)
                @php
                    $kycStatus = $u->private_seller_kyc_status ?: ($u->is_private_seller ? 'approved' : 'casual');
                @endphp
                <tr class="table-row-hover transition">
                    <td class="px-6 py-4 text-sm text-gray-400 font-mono">#{{ $u->id }}</td>
                    <td class="px-6 py-4">
                        <span class="font-semibold text-gray-900">{{ $u->name }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $u->email }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $u->private_listings_count ?? 0 }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-700">{{ ucfirst($kycStatus) }}</span>
                    </td>
                    <td class="px-6 py-4">
                        @if ($u->is_banned)
                            <span class="text-xs font-semibold text-red-700">Banned</span>
                        @elseif ($u->is_suspended)
                            <span class="text-xs font-semibold text-amber-700">Suspended</span>
                        @else
                            <span class="text-xs font-semibold text-emerald-700">Active</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.users.show', $u) }}" class="inline-flex px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 rounded-lg">Manage</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center text-gray-500">No customer-sellers found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $users->links() }}</div>
    @endif
</div>
</div>
@endsection
