@extends('admin.layouts.app')

@section('title', 'Sellers')

@section('admin-content')
<div x-data="{
        columns: {
        id: { label: 'ID', visible: true },
        name: { label: 'Name', visible: true },
        email: { label: 'Email', visible: true },
        phone: { label: 'Phone', visible: true },
        status: { label: 'Status', visible: true },
        kyc: { label: 'KYC', visible: true },
        joined: { label: 'Joined', visible: true },
        store: { label: 'Store', visible: true },
        actions: { label: 'Actions', visible: true }
    },
    showColumnMenu: false,
    showRegisterBusiness: {{ $errors->any() && old('_form') === 'register_business' ? 'true' : 'false' }}
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
        <h1 class="text-2xl font-bold text-gray-900">Private Sellers</h1>
        <p class="text-sm text-gray-500 mt-1">Business sellers with stores — shops and business accounts</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <button type="button" @click="showRegisterBusiness = true" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl transition shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Register Business
        </button>
        <a href="{{ route('admin.sellers.export', request()->query()) }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl transition shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export CSV
        </a>
    </div>
</div>

{{-- Register business modal --}}
<div x-show="showRegisterBusiness" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
    <div class="absolute inset-0 bg-black/40" @click="showRegisterBusiness = false"></div>
    <div class="relative bg-white rounded-2xl shadow-xl border border-gray-100 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
            <h2 class="text-lg font-bold text-gray-900">Register Business Seller</h2>
            <button type="button" @click="showRegisterBusiness = false" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.sellers.store') }}" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            <input type="hidden" name="_form" value="register_business">

            <div>
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Account</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Owner name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" required placeholder="03XXXXXXXXX" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Password</label>
                        <input type="password" name="password" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Confirm password</label>
                        <input type="password" name="password_confirmation" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Store</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Store name</label>
                        <input type="text" name="store_name" value="{{ old('store_name') }}" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Description</label>
                        <textarea name="store_description" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">{{ old('store_description') }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Address</label>
                        <input type="text" name="store_address" value="{{ old('store_address') }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Logo</label>
                        <input type="file" name="logo" accept="image/*" class="w-full text-sm">
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Bank & KYC</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Bank name</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name') }}" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Account holder</label>
                        <input type="text" name="bank_account_holder" value="{{ old('bank_account_holder') }}" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Account number</label>
                        <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Tax ID (optional)</label>
                        <input type="text" name="tax_id" value="{{ old('tax_id') }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                    </div>
                    <div class="sm:col-span-2" x-data="{ documentType: 'govt_id' }">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Document type</label>
                        <select name="document_type" x-model="documentType" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm mb-4">
                            <option value="govt_id">Govt ID (CNIC)</option>
                            <option value="licence">Licence</option>
                        </select>
                        <div x-show="documentType === 'govt_id'" class="mb-4">
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">CNIC number</label>
                            <input type="text" name="cnic" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" placeholder="35202-1234567-1">
                        </div>
                        <div x-show="documentType === 'licence'" x-cloak class="mb-4">
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Licence number</label>
                            <input type="text" name="licence_number" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Front image</label>
                                <input type="file" name="id_front" accept=".jpg,.jpeg,.png" required class="w-full text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Back image</label>
                                <input type="file" name="id_back" accept=".jpg,.jpeg,.png" required class="w-full text-sm">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-4 text-sm">
                <label class="flex items-center gap-2">
                    <input type="hidden" name="verify_email" value="0">
                    <input type="checkbox" name="verify_email" value="1" {{ old('verify_email') ? 'checked' : '' }} class="rounded border-gray-300 text-primary">
                    Mark email verified
                </label>
                <label class="flex items-center gap-2">
                    <input type="hidden" name="auto_approve" value="0">
                    <input type="checkbox" name="auto_approve" value="1" {{ old('auto_approve') ? 'checked' : '' }} class="rounded border-gray-300 text-primary">
                    Approve seller & activate store immediately
                </label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-xl text-sm font-medium hover:bg-primary-dark">Register</button>
                <button type="button" @click="showRegisterBusiness = false" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.sellers.index') }}" class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
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
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending approval</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="banned" {{ request('status') === 'banned' ? 'selected' : '' }}>Banned</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition shadow-sm">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
            </button>
            @if (request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.sellers.index') }}" class="px-4 py-2.5 text-gray-500 hover:text-gray-700 font-medium text-sm hover:bg-gray-100 rounded-xl transition">Clear</a>
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
                    <th x-show="columns.status.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                    <th x-show="columns.kyc.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">KYC</th>
                    <th x-show="columns.joined.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Joined</th>
                    <th x-show="columns.store.visible" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Store</th>
                    <th x-show="columns.actions.visible" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($sellers as $s)
                <tr class="table-row-hover transition">
                    <td x-show="columns.id.visible" class="px-6 py-4 text-sm text-gray-400 font-mono">#{{ $s->id }}</td>
                    <td x-show="columns.name.visible" class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center text-amber-700 font-bold text-sm">
                                {{ strtoupper(substr($s->name, 0, 1)) }}
                            </div>
                            <span class="font-semibold text-gray-900">{{ $s->name }}</span>
                        </div>
                    </td>
                    <td x-show="columns.email.visible" class="px-6 py-4 text-sm text-gray-600">{{ $s->email }}</td>
                    <td x-show="columns.phone.visible" class="px-6 py-4 text-sm text-gray-500">{{ $s->phone ?? '—' }}</td>
                    <td x-show="columns.status.visible" class="px-6 py-4">
                        @if ($s->is_banned)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Banned
                            </span>
                        @elseif ($s->is_suspended)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Suspended
                            </span>
                        @elseif ($s->seller && $s->seller->status === 'pending')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Pending
                            </span>
                        @elseif ($s->seller && $s->seller->status === 'rejected')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Rejected
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                            </span>
                        @endif
                    </td>
                    <td x-show="columns.kyc.visible" class="px-6 py-4 text-sm">
                        @if ($s->seller)
                            @php $k = $s->seller->kyc_status ?? 'none'; @endphp
                            @if ($k === 'verified')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Verified</span>
                            @elseif ($k === 'pending' || $s->seller->kyc_document_path)
                                <a href="{{ route('admin.sellers.show', $s) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 hover:bg-amber-200">Pending</a>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        @else
                            <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                    <td x-show="columns.joined.visible" class="px-6 py-4 text-sm text-gray-500">{{ $s->created_at->format('M d, Y') }}</td>
                    <td x-show="columns.store.visible" class="px-6 py-4">
                        @if($s->seller && $s->seller->store)
                            <a href="{{ route('admin.sellers.show', $s) }}" class="inline-flex items-center gap-1.5 text-primary hover:underline text-sm font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                View Store
                            </a>
                        @else
                            <span class="text-gray-400 text-sm">—</span>
                        @endif
                    </td>
                    <td x-show="columns.actions.visible" class="px-6 py-4">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('admin.sellers.show', $s) }}" class="p-2 rounded-lg text-gray-400 hover:bg-amber-50 hover:text-amber-600 transition" title="View">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            @if ($s->is_suspended)
                                <form method="POST" action="{{ route('admin.sellers.unsuspend', $s) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-emerald-600 hover:bg-emerald-50 rounded-lg transition">Unsuspend</button>
                                </form>
                            @elseif (!$s->is_banned)
                                <form method="POST" action="{{ route('admin.sellers.suspend', $s) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-amber-600 hover:bg-amber-50 rounded-lg transition">Suspend</button>
                                </form>
                            @endif
                            @if ($s->is_banned)
                                <form method="POST" action="{{ route('admin.sellers.unban', $s) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-emerald-600 hover:bg-emerald-50 rounded-lg transition">Unban</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.sellers.ban', $s) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 rounded-lg transition">Ban</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-16 text-center">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <p class="text-gray-500 font-medium">No sellers found</p>
                        <p class="text-gray-400 text-sm mt-1">Try adjusting your search or filter</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($sellers->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
        {{ $sellers->links() }}
    </div>
    @endif
</div>

</div>
@endsection
