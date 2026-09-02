@extends('admin.layouts.app')
@section('title', 'Transactions — Customer #' . $user->id)
@section('admin-content')
@include('admin.users.partials.customer-nav')
<section class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h1 class="text-lg font-bold text-gray-900 mb-4">Transactions</h1>
    @if ($walletTransactions->isEmpty())
        <p class="text-sm text-gray-500">No wallet transactions yet.</p>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Amount</th><th class="px-4 py-3 text-left">Balance</th><th class="px-4 py-3 text-left">Description</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($walletTransactions as $txn)
                    <tr>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $txn->created_at?->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-3">{{ str_replace('_', ' ', ucfirst($txn->type)) }}</td>
                        <td class="px-4 py-3 font-medium {{ (float) $txn->amount >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ (float) $txn->amount >= 0 ? '+' : '' }}{{ number_format((float) $txn->amount, 2) }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $txn->balance_after, 2) }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ \Illuminate\Support\Str::limit($txn->description ?? '—', 60) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($walletTransactions->hasPages())<div class="mt-4">{{ $walletTransactions->links() }}</div>@endif
    @endif
</section>
@endsection
