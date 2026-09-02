@extends('admin.layouts.app')

@section('title', 'Private Sellers Overview')

@section('admin-content')
{{-- Page header --}}
<div class="mb-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Private Sellers Overview</h1>
            <p class="text-gray-500 mt-1.5 text-sm sm:text-base max-w-xl">Monitor private seller accounts, listing usage, and manage limits from one place.</p>
        </div>
    </div>
</div>

{{-- Stats grid --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5 mb-8">
    <div class="group relative bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-primary/10 transition-all duration-200 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
        <div class="relative p-5 sm:p-6 flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Private Sellers</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1 tabular-nums">{{ number_format($overview['total_private_sellers']) }}</p>
            </div>
        </div>
    </div>
    <div class="group relative bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-gray-200 transition-all duration-200 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-gray-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
        <div class="relative p-5 sm:p-6 flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Listings</p>
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1 tabular-nums">{{ number_format($overview['total_listings']) }}</p>
            </div>
        </div>
    </div>
    <div class="group relative bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-emerald-200 transition-all duration-200 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
        <div class="relative p-5 sm:p-6 flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Listings</p>
                <p class="text-2xl sm:text-3xl font-bold text-emerald-600 mt-1 tabular-nums">{{ number_format($overview['active_listings']) }}</p>
            </div>
        </div>
    </div>
    <div class="group relative bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-amber-200 transition-all duration-200 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
        <div class="relative p-5 sm:p-6 flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Expired Listings</p>
                <p class="text-2xl sm:text-3xl font-bold text-amber-600 mt-1 tabular-nums">{{ number_format($overview['expired_listings']) }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Pending KYC --}}
@if(isset($pendingKyc) && $pendingKyc->count())
<div class="bg-white rounded-2xl border border-amber-100 shadow-sm overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-amber-100 bg-amber-50/50 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">Pending Private Seller KYC</h3>
                <p class="text-xs text-gray-500">{{ $overview['pending_kyc'] ?? $pendingKyc->count() }} application(s) waiting for review</p>
            </div>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[640px]">
            <thead>
                <tr class="bg-gray-50/80 border-b border-gray-100">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Phone / CNIC</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Submitted</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($pendingKyc as $applicant)
                    @php $kyc = ($applicant->preferences['private_seller_kyc'] ?? []); @endphp
                    <tr class="hover:bg-gray-50/80">
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ $applicant->name }}</p>
                            <p class="text-xs text-gray-500">{{ $applicant->email }}</p>
                            @if(!empty($kyc['address']))
                                <p class="text-xs text-gray-400 mt-1 max-w-xs truncate" title="{{ $kyc['address'] }}">{{ $kyc['address'] }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            <p>{{ $kyc['phone'] ?? $applicant->phone ?? '—' }}</p>
                            <p class="text-xs text-gray-500">
                                {{ \App\Support\KycDocumentRules::documentTypeLabel($kyc['document_type'] ?? 'govt_id') }}:
                                {{ $kyc['document_type'] === 'licence' ? ($kyc['licence_number'] ?? '—') : ($kyc['cnic'] ?? '—') }}
                            </p>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ !empty($kyc['submitted_at']) ? \Carbon\Carbon::parse($kyc['submitted_at'])->diffForHumans() : $applicant->updated_at?->diffForHumans() }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2 flex-wrap">
                                <a href="{{ route('admin.users.show', $applicant) }}" class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium">View customer</a>
                                <form method="POST" action="{{ route('admin.private-sellers.approve-kyc', $applicant) }}">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.private-sellers.reject-kyc', $applicant) }}" onsubmit="var r=prompt('Rejection reason:'); if(r===null){return false;} this.querySelector('[name=rejection_reason]').value=r;">
                                    @csrf
                                    <input type="hidden" name="rejection_reason" value="">
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 text-xs font-medium border border-red-200">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Two columns: Top sellers + Configure --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    {{-- Top Private Sellers --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                </div>
                <h3 class="font-semibold text-gray-900">Top Private Sellers</h3>
            </div>
        </div>
        <div class="p-6">
            <ul class="space-y-0 divide-y divide-gray-100">
                @forelse ($topPrivateSellers as $index => $u)
                <li class="flex items-center justify-between py-4 first:pt-0 last:pb-0 first:border-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-gray-100 text-gray-500 font-semibold text-sm flex items-center justify-center">{{ $index + 1 }}</span>
                        <span class="font-medium text-gray-900 truncate">{{ $u->name }}</span>
                    </div>
                    <span class="flex-shrink-0 ml-3 px-3 py-1 rounded-lg bg-primary/10 text-primary text-sm font-medium">{{ $u->products_count ?? 0 }} listings</span>
                </li>
                @empty
                <li class="py-8 text-center">
                    <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <p class="text-gray-500 text-sm">No private sellers yet</p>
                </li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Configure card --}}
    <div class="bg-gradient-to-br from-primary/5 via-white to-primary/5 rounded-2xl border border-primary/10 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-primary/10 bg-white/80">
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-lg bg-primary/15 flex items-center justify-center">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <h3 class="font-semibold text-gray-900">Settings</h3>
            </div>
        </div>
        <div class="p-6">
            <p class="text-gray-600 text-sm mb-5">Set listing limits, eligibility rules, and other options for private sellers.</p>
            <a href="{{ route('admin.private-seller-settings.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm shadow-sm hover:shadow transition-all duration-200">
                <span>Private Seller Settings</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
    </div>
</div>

{{-- Listings usage table --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
        <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h3 class="font-semibold text-gray-900">Listings Usage Report</h3>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[500px]">
            <thead>
                <tr class="bg-gray-50/80 border-b border-gray-100">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Listing count</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($listingsUsage as $row)
                <tr class="table-row-hover transition-colors">
                    <td class="px-6 py-4">
                        <span class="font-medium text-gray-900">{{ $row->name }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $row->email }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-gray-800 font-semibold text-sm tabular-nums">{{ $row->listing_count ?? 0 }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-6 py-16 text-center">
                        <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <p class="text-gray-500 text-sm">No data yet</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($listingsUsage->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30">{{ $listingsUsage->links() }}</div>
    @endif
</div>
@endsection
