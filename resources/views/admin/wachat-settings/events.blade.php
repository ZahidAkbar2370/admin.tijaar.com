@extends('admin.layouts.app')

@section('title', 'WaChat Events')

@section('admin-content')
<div class="w-full min-w-0">
    @include('admin.wachat-settings.partials.nav')

    <h1 class="text-xl font-bold text-gray-900 mb-1">WhatsApp Events</h1>
    <p class="text-sm text-gray-500 mb-6">
        Choose which automated WhatsApp messages are sent. Edit message text under
        <a href="{{ route('admin.whatsapp-templates.index') }}" class="text-primary hover:underline">WhatsApp Templates</a>.
    </p>

    @include('admin.partials.settings-flash')

    <form method="POST" action="{{ route('admin.wachat-settings.events.update') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-2">
        @csrf

        @include('admin.partials.setting-toggle', [
            'name' => 'wachat_msg_order_placed_customer',
            'label' => 'Order placed → customer',
            'help' => 'Confirmation to the customer when an order is placed.',
            'value' => $settings['wachat_msg_order_placed_customer'],
        ])
        @include('admin.partials.setting-toggle', [
            'name' => 'wachat_msg_order_placed_seller',
            'label' => 'Order placed → seller / private seller',
            'help' => 'Notify verified sellers when they receive a new order.',
            'value' => $settings['wachat_msg_order_placed_seller'],
        ])
        @include('admin.partials.setting-toggle', [
            'name' => 'wachat_msg_payment_success',
            'label' => 'Payment success → customer',
            'help' => 'Confirm successful payment to the customer.',
            'value' => $settings['wachat_msg_payment_success'],
        ])
        @include('admin.partials.setting-toggle', [
            'name' => 'wachat_msg_order_approved',
            'label' => 'Seller accepts order → customer',
            'help' => 'Notify customer when the seller approves the order.',
            'value' => $settings['wachat_msg_order_approved'],
        ])
        @include('admin.partials.setting-toggle', [
            'name' => 'wachat_msg_order_shipped',
            'label' => 'Tracking / shipped → customer',
            'help' => 'Notify customer when seller adds a tracking ID.',
            'value' => $settings['wachat_msg_order_shipped'],
        ])
        @include('admin.partials.setting-toggle', [
            'name' => 'wachat_msg_order_delivered_seller',
            'label' => 'Order delivered → seller',
            'help' => 'Notify seller when the order is marked delivered / completed.',
            'value' => $settings['wachat_msg_order_delivered_seller'],
        ])

        <div class="pt-4 border-t border-gray-100">
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl text-sm transition">
                Save Events
            </button>
            <a href="{{ route('admin.whatsapp-templates.index') }}" class="ml-3 text-sm text-gray-500 hover:text-primary">WhatsApp templates →</a>
        </div>
    </form>
</div>
@endsection
