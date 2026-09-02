@extends('admin.layout')

@section('content')
@php
        $adminSearchItems = [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'icon' => 'layout-dashboard'],
        ['label' => 'Customers', 'url' => route('admin.users.index'), 'icon' => 'users'],
        ['label' => 'Sellers (Buy & Sell)', 'url' => route('admin.customer-sellers.index'), 'icon' => 'users'],
        ['label' => 'Private Sellers', 'url' => route('admin.sellers.index'), 'icon' => 'shopping-cart'],
        ['label' => 'People Settings', 'url' => route('admin.people-settings.index'), 'icon' => 'settings'],
        ['label' => 'Customer Settings', 'url' => route('admin.people-settings.index', ['tab' => 'customer']), 'icon' => 'users'],
        ['label' => 'Seller Settings', 'url' => route('admin.people-settings.index', ['tab' => 'seller']), 'icon' => 'users'],
        ['label' => 'Private Seller Settings', 'url' => route('admin.people-settings.index', ['tab' => 'private_seller']), 'icon' => 'users'],
        ['label' => 'Categories', 'url' => route('admin.categories.index'), 'icon' => 'folder'],
        ['label' => 'Brands', 'url' => route('admin.brands.index'), 'icon' => 'tag'],
        ['label' => 'Products', 'url' => route('admin.products.index'), 'icon' => 'package'],
        ['label' => 'Orders', 'url' => route('admin.orders.index'), 'icon' => 'shopping-bag'],
        ['label' => 'Transactions', 'url' => route('admin.transactions.index'), 'icon' => 'credit-card'],
        ['label' => 'Track Orders', 'url' => route('admin.track-orders.index'), 'icon' => 'truck'],
        ['label' => 'Reviews', 'url' => route('admin.reviews.index'), 'icon' => 'star'],
        ['label' => 'Conversations', 'url' => route('admin.conversations.index'), 'icon' => 'message-square'],
        ['label' => 'Disputes', 'url' => route('admin.disputes.index'), 'icon' => 'alert-circle'],
        ['label' => 'Shipping Zones', 'url' => route('admin.shipping-zones.index'), 'icon' => 'truck'],
        ['label' => 'Coupons', 'url' => route('admin.coupons.index'), 'icon' => 'tag'],
        ['label' => 'Promotion Packages', 'url' => route('admin.promotion-packages.index'), 'icon' => 'star'],
        ['label' => 'Pages', 'url' => route('admin.pages.index'), 'icon' => 'file-text'],
        ['label' => 'Testimonials', 'url' => route('admin.testimonials.index'), 'icon' => 'message-circle'],
        ['label' => 'FAQs', 'url' => route('admin.faqs.index'), 'icon' => 'help-circle'],
        ['label' => 'Blogs', 'url' => route('admin.blogs.index'), 'icon' => 'book'],
        ['label' => 'Contact Submissions', 'url' => route('admin.contact-submissions.index'), 'icon' => 'mail'],
        ['label' => 'Newsletter', 'url' => route('admin.newsletter.index'), 'icon' => 'mail'],
        ['label' => 'Email Templates', 'url' => route('admin.email-templates.index'), 'icon' => 'mail'],
        ['label' => 'WhatsApp Templates', 'url' => route('admin.whatsapp-templates.index'), 'icon' => 'message-circle'],
        ['label' => 'Sales Report', 'url' => route('admin.analytics.sales'), 'icon' => 'trending-up'],
        ['label' => 'Low Stock', 'url' => route('admin.inventory.low-stock'), 'icon' => 'alert-triangle'],
        ['label' => 'Out of Stock', 'url' => route('admin.inventory.out-of-stock'), 'icon' => 'package-x'],
        ['label' => 'Settings', 'url' => route('admin.settings.index'), 'icon' => 'settings'],
        ['label' => 'Email Setting', 'url' => route('admin.email-settings.index'), 'icon' => 'mail'],
        ['label' => 'SMTP', 'url' => route('admin.email-settings.smtp'), 'icon' => 'mail'],
        ['label' => 'Email Events', 'url' => route('admin.email-settings.events'), 'icon' => 'mail'],
        ['label' => 'Customer Setting', 'url' => route('admin.people-settings.index', ['tab' => 'customer']), 'icon' => 'users'],
        ['label' => 'Customer Settings', 'url' => route('admin.people-settings.index', ['tab' => 'customer']), 'icon' => 'users'],
        ['label' => 'Seller Setting', 'url' => route('admin.people-settings.index', ['tab' => 'seller']), 'icon' => 'users'],
        ['label' => 'Seller Settings', 'url' => route('admin.people-settings.index', ['tab' => 'seller']), 'icon' => 'users'],
        ['label' => 'Private Seller Setting', 'url' => route('admin.people-settings.index', ['tab' => 'private_seller']), 'icon' => 'users'],
        ['label' => 'Google reCAPTCHA', 'url' => route('admin.recaptcha-settings.index'), 'icon' => 'shield'],
        ['label' => 'WaChat WhatsApp', 'url' => route('admin.wachat-settings.index'), 'icon' => 'message-circle'],
        ['label' => 'WaChat API', 'url' => route('admin.wachat-settings.index'), 'icon' => 'message-circle'],
        ['label' => 'WaChat Events', 'url' => route('admin.wachat-settings.events'), 'icon' => 'message-circle'],
        ['label' => 'Activity Log', 'url' => route('admin.activities.index'), 'icon' => 'activity'],
        ['label' => 'Expenses', 'url' => route('admin.expenses.index'), 'icon' => 'credit-card'],
        ['label' => 'Payment Method', 'url' => route('admin.payment-methods.index'), 'icon' => 'credit-card'],
        ['label' => 'Cash on Delivery', 'url' => route('admin.payment-methods.cod'), 'icon' => 'credit-card'],
        ['label' => 'JazzCash', 'url' => route('admin.payment-methods.jazzcash'), 'icon' => 'credit-card'],
        ['label' => 'Stripe', 'url' => route('admin.payment-methods.stripe'), 'icon' => 'credit-card'],
        ['label' => 'Paypal', 'url' => route('admin.payment-methods.paypal'), 'icon' => 'credit-card'],
        ['label' => 'Easypaisa', 'url' => route('admin.payment-methods.easypaisa'), 'icon' => 'credit-card'],
        ['label' => 'Courier', 'url' => route('admin.courier.index'), 'icon' => 'truck'],
        ['label' => 'Commission', 'url' => route('admin.commissions.index'), 'icon' => 'dollar-sign'],
        ['label' => 'Customer Commission', 'url' => route('admin.people-settings.index', ['tab' => 'customer']), 'icon' => 'dollar-sign'],
        ['label' => 'Seller Commission', 'url' => route('admin.people-settings.index', ['tab' => 'seller']), 'icon' => 'dollar-sign'],
        ['label' => 'Private Seller Commission', 'url' => route('admin.people-settings.index', ['tab' => 'private_seller']), 'icon' => 'dollar-sign'],
        ['label' => 'Marketplace Fee', 'url' => route('admin.people-settings.index', ['tab' => 'customer']), 'icon' => 'dollar-sign'],
        ['label' => 'Online Transaction Fee', 'url' => route('admin.people-settings.index', ['tab' => 'customer']), 'icon' => 'dollar-sign'],
        ['label' => 'Commission Rules', 'url' => route('admin.commissions.index'), 'icon' => 'dollar-sign'],
        ['label' => 'Payouts', 'url' => route('admin.payouts.index'), 'icon' => 'credit-card'],
        ['label' => 'Refunds', 'url' => route('admin.refunds.index'), 'icon' => 'refresh-cw'],
        ['label' => 'Roles', 'url' => route('admin.roles.index'), 'icon' => 'shield'],
        ['label' => 'Permissions Matrix', 'url' => route('admin.roles.permissions-matrix'), 'icon' => 'grid'],
        ['label' => 'Sub-Admins', 'url' => route('admin.sub-admins.index'), 'icon' => 'user-plus'],
        ['label' => 'Private Sellers Overview', 'url' => route('admin.customer-sellers.index'), 'icon' => 'users'],
        ['label' => 'Abuse & Safety', 'url' => route('admin.abuse-safety.index'), 'icon' => 'shield-off'],
        ['label' => 'Flagged Items', 'url' => route('admin.abuse-safety.flagged'), 'icon' => 'flag'],
    ];
@endphp
<style>
    /* Thin visible scrollbar in admin sidebar (overrides .admin-sidebar-nav hide rules in admin/layout) */
    aside[aria-label="Admin navigation"] .admin-sidebar-nav.admin-sidebar-scroll {
        -ms-overflow-style: auto;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }
    aside[aria-label="Admin navigation"] .admin-sidebar-nav.admin-sidebar-scroll::-webkit-scrollbar {
        display: block;
        width: 8px;
    }
    aside[aria-label="Admin navigation"] .admin-sidebar-nav.admin-sidebar-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
    aside[aria-label="Admin navigation"] .admin-sidebar-nav.admin-sidebar-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    aside[aria-label="Admin navigation"] .admin-sidebar-nav.admin-sidebar-scroll::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
<div class="flex min-h-screen md:h-screen md:min-h-0" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    {{-- Mobile overlay when sidebar open --}}
    <div x-show="sidebarOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-black/50 z-[9997] md:hidden"
         aria-hidden="true"></div>

    {{-- Left Sidebar: drawer on mobile, static on md+ --}}
    <aside class="fixed inset-y-0 left-0 z-[9998] w-72 md:w-64 bg-white border-r border-gray-200 flex flex-col flex-shrink-0 shadow-lg md:shadow-sm overflow-hidden transform transition-transform duration-300 ease-out -translate-x-full md:translate-x-0"
         :class="{ 'translate-x-0': sidebarOpen }"
         aria-label="Admin navigation">
        <div class="px-3 py-3 border-b border-gray-100 flex-shrink-0 flex items-center justify-between gap-2">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 min-w-0" @click="sidebarOpen = false">
                <img src="{{ asset('images/tijaar-logo.png') }}" alt="Tijaar" class="h-9 flex-shrink-0" />
                <span class="text-base font-medium text-gray-500 hidden sm:inline">Admin</span>
            </a>
            <button type="button" @click="sidebarOpen = false" class="md:hidden p-2 -mr-1 rounded-lg text-gray-500 hover:bg-gray-100 flex-shrink-0" aria-label="Close menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-contain py-2 admin-sidebar-nav admin-sidebar-scroll" @click="if ($event.target.closest('a')) sidebarOpen = false">
            @include('admin.layouts._sidebar')
        </div>
    </aside>

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col min-w-0 min-h-screen md:min-h-0 md:ml-64 bg-gradient-to-br from-slate-50 to-gray-50/50 overflow-visible">
        {{-- Top Bar: Hamburger + Search + Notifications + User --}}
        <header class="sticky top-0 z-[9980] h-14 min-h-[3.5rem] bg-white/95 backdrop-blur-md border-b border-gray-200/80 flex items-center justify-between gap-2 sm:gap-4 px-3 sm:px-5 overflow-visible flex-shrink-0">
            {{-- Hamburger (mobile only) --}}
            <button type="button" @click="sidebarOpen = true" class="md:hidden p-2.5 -ml-1 rounded-xl text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition" aria-label="Open menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            {{-- Global Search --}}
            <div class="flex-1 min-w-0 max-w-xl relative" x-data="adminSearch()" @click.away="open = false">
                <div class="relative overflow-visible">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-400 absolute left-2.5 sm:left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-ref="searchInput" x-model="query" @focus="open = true" @keydown.escape.window="open = false" placeholder="Search..."
                           class="w-full pl-8 sm:pl-10 pr-3 sm:pr-4 py-2 sm:py-2.5 bg-gray-50 border border-gray-200/80 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary focus:bg-white transition" />
                    <template x-teleport="body">
                    <div x-show="open" x-cloak x-transition
                         class="fixed bg-white rounded-xl shadow-xl border border-gray-100 py-2 max-h-[70vh] overflow-y-auto"
                         style="z-index: 99999; top: 56px; left: 12px; right: 12px; width: auto; max-width: 420px; margin-left: auto; margin-right: auto;"
                         x-ref="searchDropdown">
                        <template x-for="item in filtered" :key="item.url">
                            <a :href="item.url" class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 text-gray-700 text-sm">
                                <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </span>
                                <span x-text="item.label"></span>
                            </a>
                        </template>
                        <p x-show="filtered.length === 0 && query.length > 0" class="px-4 py-3 text-gray-400 text-sm">No matches</p>
                    </div>
                    </template>
                </div>
            </div>

            {{-- Notification Icons + User Menu --}}
            <div class="flex items-center flex-wrap gap-0.5 sm:gap-1 md:gap-2 flex-shrink-0 justify-end" x-data="adminNotifications()" x-init="fetchCounts()">
                <a href="{{ route('admin.orders.index') }}" title="Orders" class="relative p-2.5 rounded-xl text-gray-500 hover:bg-cyan-50 hover:text-cyan-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span x-show="orders_pending > 0" x-text="orders_pending > 99 ? '99+' : orders_pending"
                          class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center bg-cyan-500 text-white text-xs font-bold rounded-full"></span>
                </a>
                <a href="{{ route('admin.conversations.index') }}" title="Conversations" class="relative p-2.5 rounded-xl text-gray-500 hover:bg-violet-50 hover:text-violet-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <span x-show="conversations > 0" x-text="conversations > 99 ? '99+' : conversations"
                          class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center bg-violet-500 text-white text-xs font-bold rounded-full"></span>
                </a>
                <a href="{{ route('admin.disputes.index') }}" title="Disputes" class="relative p-2.5 rounded-xl text-gray-500 hover:bg-rose-50 hover:text-rose-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span x-show="disputes_pending > 0" x-text="disputes_pending > 99 ? '99+' : disputes_pending"
                          class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center bg-rose-500 text-white text-xs font-bold rounded-full"></span>
                </a>
                <a href="{{ route('admin.payouts.index') }}" title="Payouts" class="relative p-2.5 rounded-xl text-gray-500 hover:bg-emerald-50 hover:text-emerald-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    <span x-show="payouts_pending > 0" x-text="payouts_pending > 99 ? '99+' : payouts_pending"
                          class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center bg-emerald-500 text-white text-xs font-bold rounded-full"></span>
                </a>
                <div class="w-px h-6 bg-gray-200 hidden sm:block"></div>
                <a href="{{ route('admin.users.index') }}" @click="markRead('new_customers')" title="Customers" class="relative p-2.5 rounded-xl text-gray-500 hover:bg-primary/10 hover:text-primary transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span x-show="customers > 0" x-text="customers > 99 ? '99+' : customers"
                          class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center bg-red-500 text-white text-xs font-bold rounded-full"></span>
                </a>
                <a href="{{ route('admin.sellers.index') }}" @click="markRead('new_sellers')" title="Sellers" class="relative p-2.5 rounded-xl text-gray-500 hover:bg-amber-50 hover:text-amber-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span x-show="sellers > 0" x-text="sellers > 99 ? '99+' : sellers"
                          class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center bg-amber-500 text-white text-xs font-bold rounded-full"></span>
                </a>
                <div class="w-px h-6 bg-gray-200 hidden sm:block"></div>
                <div class="relative" x-data="{ userMenuOpen: false }" @click.away="userMenuOpen = false">
                    <button type="button" @click="userMenuOpen = !userMenuOpen" class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center text-white shadow-lg shadow-primary/25 hover:shadow-primary/30 transition flex-shrink-0" aria-haspopup="true" :aria-expanded="userMenuOpen">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </button>
                    <div x-show="userMenuOpen" x-cloak x-transition
                         class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-[9999]">
                        <p class="px-4 py-2 text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="px-4 pb-2 text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                        <form method="POST" action="{{ route('admin.logout') }}" class="border-t border-gray-100">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg mx-2">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 min-h-0 p-4 sm:p-5 md:p-6 overflow-auto overflow-x-hidden admin-scrollbar">
            @yield('admin-content')
        </main>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('adminSidebar', () => ({
        openSection: null,
        toggleSection(key) {
            this.openSection = this.openSection === key ? null : key;
        }
    }));
    Alpine.data('adminLocationFields', (cfg) => ({
        tree: cfg.tree || [],
        countryName: cfg.countryName || 'country',
        stateName: cfg.stateName || 'state',
        cityName: cfg.cityName || 'city',
        inputClass: cfg.inputClass || 'w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm',
        required: cfg.required !== false,
        showCountry: cfg.showCountry === true,
        countryValue: cfg.country || 'Pakistan',
        stateValue: cfg.state || '',
        cityValue: cfg.city || '',
        get pakistan() {
            return this.tree.find(c => (c.name || '').toLowerCase() === 'pakistan') || this.tree[0] || null;
        },
        get provinces() {
            const match = this.tree.find(c =>
                (c.name || '').toLowerCase() === (this.countryValue || '').trim().toLowerCase()
            );
            return (match || this.pakistan)?.provinces || [];
        },
        get cities() {
            if (!this.stateValue) return [];
            const prov = this.provinces.find(p =>
                (p.name || '').toLowerCase() === this.stateValue.trim().toLowerCase()
            );
            return prov?.cities || [];
        },
        init() {
            if (!this.countryValue) {
                this.countryValue = this.pakistan?.name || 'Pakistan';
            }
        },
        onProvinceChange() {
            this.cityValue = '';
        },
        onCountryChange() {
            this.stateValue = '';
            this.cityValue = '';
        },
    }));
    Alpine.data('adminSearch', () => ({
        query: '',
        open: false,
        items: @json($adminSearchItems),
        get filtered() {
            if (!this.query.trim()) return this.items;
            const q = this.query.toLowerCase();
            return this.items.filter(i => i.label.toLowerCase().includes(q));
        }
    }));
    Alpine.data('adminNotifications', () => ({
        customers: 0,
        sellers: 0,
        orders_pending: 0,
        disputes_pending: 0,
        payouts_pending: 0,
        conversations: 0,
        async fetchCounts() {
            try {
                const r = await fetch('{{ route('admin.notifications.counts') }}', { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                this.customers = d.customers || 0;
                this.sellers = d.sellers || 0;
                this.orders_pending = d.orders_pending || 0;
                this.disputes_pending = d.disputes_pending || 0;
                this.payouts_pending = d.payouts_pending || 0;
                this.conversations = d.conversations || 0;
            } catch (_) {}
        },
        init() {
            this.fetchCounts();
            setInterval(() => this.fetchCounts(), 60000);
        },
        async markRead(type) {
            try {
                await fetch('{{ route('admin.notifications.mark-read') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ type })
                });
                if (type === 'new_customers') this.customers = 0;
                else this.sellers = 0;
            } catch (_) {}
        }
    }));
});
</script>
@endsection
