@extends('admin.layouts.app')

@section('title', 'Customers')

@section('admin-content')
<div x-data="{
    columns: {
        id: { label: 'ID', visible: true },
        name: { label: 'Name', visible: true },
        email: { label: 'Email', visible: true },
        phone: { label: 'Phone', visible: true },
        source: { label: 'Registered Via', visible: true },
        buyer_orders: { label: 'Orders (Buyer)', visible: true },
        seller_listings: { label: 'Listings (Seller)', visible: true },
        status: { label: 'Status', visible: true },
        joined: { label: 'Joined', visible: true },
        actions: { label: 'Actions', visible: true }
    },
    showColumnMenu: false,
    showCreateCustomer: {{ $errors->any() && old('_form') === 'create_customer' ? 'true' : 'false' }}
}">

@if (session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if (session('error'))
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
        <p class="text-sm text-gray-500 mt-1">All accounts with the customer role — buyer orders and seller listings</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <button type="button" @click="showCreateCustomer = true" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl transition shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Customer
        </button>
        <a href="{{ route('admin.users.export', request()->query()) }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl transition shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export CSV
        </a>
    </div>
</div>

{{-- Create customer modal --}}
<div x-show="showCreateCustomer" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
    <div class="absolute inset-0 bg-black/40" @click="showCreateCustomer = false"></div>
    <div class="relative bg-white rounded-2xl shadow-xl border border-gray-100 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900">Create Customer</h2>
            <button type="button" @click="showCreateCustomer = false" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.users.store') }}" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="_form" value="create_customer">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Phone (optional)</label>
                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="03XXXXXXXXX" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Confirm</label>
                    <input type="password" name="password_confirmation" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="hidden" name="email_verified" value="0">
                <input type="checkbox" name="email_verified" value="1" {{ old('email_verified') ? 'checked' : '' }} class="rounded border-gray-300 text-primary">
                Mark email as verified
            </label>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-xl text-sm font-medium hover:bg-primary-dark">Create</button>
                <button type="button" @click="showCreateCustomer = false" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
        <div class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Search</label>
                <div class="relative">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, email, phone..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white" />
                </div>
            </div>
            <div class="w-44">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="banned" {{ request('status') === 'banned' ? 'selected' : '' }}>Banned</option>
                </select>
            </div>
            <div class="w-44">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Registered Via</label>
                <select name="source" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white">
                    <option value="">All Sources</option>
                    <option value="web" {{ request('source') === 'web' ? 'selected' : '' }}>Web</option>
                    <option value="app" {{ request('source') === 'app' ? 'selected' : '' }}>App</option>
                    <option value="api" {{ request('source') === 'api' ? 'selected' : '' }}>API</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition shadow-sm">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
            </button>
            @if (request()->hasAny(['search', 'status', 'source']))
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2.5 text-gray-500 hover:text-gray-700 font-medium text-sm hover:bg-gray-100 rounded-xl transition">Clear</a>
            @endif

            {{-- Column Toggle --}}
            <div class="relative ml-auto">
                <button type="button" @click="showColumnMenu = !showColumnMenu" class="px-4 py-2.5 text-gray-600 hover:text-gray-900 font-medium text-sm border border-gray-200 rounded-xl hover:bg-gray-50 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    Columns
                </button>
                <div x-show="showColumnMenu" @click.away="showColumnMenu = false" x-cloak x-transition class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-2" style="z-index: 9999;">
                    <p class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">Toggle Columns</p>
                    <template x-for="(col, key) in columns" :key="key">
                        <label class="column-toggle-item flex items-center gap-3 px-4 py-2 cursor-pointer transition">
                            <input type="checkbox" x-model="col.visible" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/20" />
                            <span class="text-sm text-gray-700" x-text="col.label"></span>
                        </label>
                    </template>
                </div>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50/80">
                <tr>
                    <th x-show="columns.id.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                    <th x-show="columns.name.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Name</th>
                    <th x-show="columns.email.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Email</th>
                    <th x-show="columns.phone.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Phone</th>
                    <th x-show="columns.source.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Registered Via</th>
                    <th x-show="columns.buyer_orders.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Orders (Buyer)</th>
                    <th x-show="columns.seller_listings.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Listings (Seller)</th>
                    <th x-show="columns.status.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                    <th x-show="columns.joined.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Joined</th>
                    <th x-show="columns.actions.visible" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $u)
                <tr class="table-row-hover transition">
                    <td x-show="columns.id.visible" class="px-6 py-4 text-sm text-gray-400 font-mono">#{{ $u->id }}</td>
                    <td x-show="columns.name.visible" class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-sm">
                                {{ strtoupper(substr($u->name, 0, 1)) }}
                            </div>
                            <span class="font-semibold text-gray-900">{{ $u->name }}</span>
                        </div>
                    </td>
                    <td x-show="columns.email.visible" class="px-6 py-4 text-sm text-gray-600">{{ $u->email }}</td>
                    <td x-show="columns.phone.visible" class="px-6 py-4 text-sm text-gray-500">{{ $u->phone ?? '—' }}</td>
                    <td x-show="columns.source.visible" class="px-6 py-4">
                        @php $sourceLabel = \App\Support\RegistrationSource::label($u->registration_source); @endphp
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold
                            @if ($u->registration_source === 'app') bg-violet-100 text-violet-700
                            @elseif ($u->registration_source === 'api') bg-sky-100 text-sky-700
                            @else bg-gray-100 text-gray-700 @endif">
                            {{ $sourceLabel }}
                        </span>
                    </td>
                    <td x-show="columns.buyer_orders.visible" class="px-6 py-4 text-sm text-gray-600">{{ $u->orders_count ?? 0 }}</td>
                    <td x-show="columns.seller_listings.visible" class="px-6 py-4 text-sm text-gray-600">{{ $u->private_listings_count ?? 0 }}</td>
                    <td x-show="columns.status.visible" class="px-6 py-4">
                        @if ($u->is_banned)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Banned
                            </span>
                        @elseif ($u->is_suspended)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Suspended
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                            </span>
                        @endif
                    </td>
                    <td x-show="columns.joined.visible" class="px-6 py-4 text-sm text-gray-500">{{ $u->created_at->format('M d, Y') }}</td>
                    <td x-show="columns.actions.visible" class="px-6 py-4">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('admin.users.show', $u) }}" class="p-2 rounded-lg text-gray-400 hover:bg-primary/10 hover:text-primary transition" title="View">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            @if ($u->is_suspended)
                                <form method="POST" action="{{ route('admin.users.unsuspend', $u) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-emerald-600 hover:bg-emerald-50 rounded-lg transition">Unsuspend</button>
                                </form>
                            @elseif (!$u->is_banned)
                                <form method="POST" action="{{ route('admin.users.suspend', $u) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-amber-600 hover:bg-amber-50 rounded-lg transition">Suspend</button>
                                </form>
                            @endif
                            @if ($u->is_banned)
                                <form method="POST" action="{{ route('admin.users.unban', $u) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-emerald-600 hover:bg-emerald-50 rounded-lg transition">Unban</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.users.ban', $u) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 rounded-lg transition">Ban</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="px-6 py-16 text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p class="text-gray-500 font-medium">No customers found</p>
                        <p class="text-gray-400 text-sm mt-1">Try adjusting your search or filter</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
        {{ $users->links() }}
    </div>
    @endif
</div>

</div>
@endsection
