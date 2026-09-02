@extends('admin.layouts.app')
@section('title', 'Free Listing — Customer #' . $user->id)
@section('admin-content')
@php
    $inputClass = 'w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary';
    $effectiveListingLimit = $user->private_listing_limit !== null ? min(max(1, (int) $user->private_listing_limit), $globalMaxListingLimit) : $globalFreeListingLimit;
@endphp
@include('admin.users.partials.customer-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
    <h1 class="text-lg font-bold text-gray-900 mb-4">Offer Free Listing</h1>
    <div class="rounded-xl bg-gray-50 border border-gray-100 p-4 text-sm text-gray-600 space-y-1 mb-4">
        <p>Global free limit: <strong>{{ $globalFreeListingLimit }}</strong></p>
        <p>Global max limit: <strong>{{ $globalMaxListingLimit }}</strong></p>
        <p>Current listings: <strong>{{ $user->private_listings_count ?? 0 }}</strong></p>
        <p>Effective limit: <strong>{{ $effectiveListingLimit }}</strong></p>
    </div>
    <form method="POST" action="{{ route('admin.users.listing-limit', $user) }}" class="space-y-3">
        @csrf
        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Custom listing limit</label><input type="number" name="private_listing_limit" min="1" max="100" value="{{ old('private_listing_limit', $user->private_listing_limit) }}" class="{{ $inputClass }}"></div>
        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Payout hold days</label><input type="number" name="payout_hold_days" min="0" max="90" value="{{ old('payout_hold_days', $user->payout_hold_days) }}" class="{{ $inputClass }}"></div>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="use_global_limit" value="1" class="rounded border-gray-300 text-primary"> Reset to global free limit</label>
        <label class="inline-flex items-center gap-2 text-sm ml-4"><input type="checkbox" name="clear_payout_hold_days" value="1" class="rounded border-gray-300 text-primary"> Clear payout hold</label>
        <div><button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-xl text-sm font-medium">Save</button></div>
    </form>
</section>
@endsection
