<nav class="py-2 px-2 space-y-1" x-data="adminSidebar()">
    {{-- Dashboard --}}
    <a href="{{ route('admin.dashboard') }}" class="sidebar-link flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 6h6m-3-3v6"/></svg>
        <span>Dashboard</span>
    </a>

    {{-- People --}}
    @include('admin.layouts._sidebar-divider', ['label' => 'People'])
    <a href="{{ route('admin.users.index') }}" class="sidebar-link flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        <span>Customer</span>
    </a>
    <a href="{{ route('admin.sellers.index') }}" class="sidebar-link flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium {{ request()->routeIs('admin.sellers.*') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        <span>Private Seller</span>
    </a>
    <a href="{{ route('admin.people-settings.index') }}" class="sidebar-link flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium {{ request()->routeIs('admin.people-settings.*') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span>Settings</span>
    </a>

    {{-- Catalog --}}
    @include('admin.layouts._sidebar-divider', ['label' => 'Catalog'])
    <a href="{{ route('admin.categories.index') }}" class="sidebar-link flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/></svg>
        <span>Categories</span>
    </a>
    <a href="{{ route('admin.brands.index') }}" class="sidebar-link flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium {{ request()->routeIs('admin.brands.*') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
        <span>Brands</span>
    </a>
    <a href="{{ route('admin.products.index') }}" class="sidebar-link flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        <span>Products</span>
    </a>
    <a href="{{ route('admin.home-featured.index') }}" class="sidebar-link flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium {{ request()->routeIs('admin.home-featured.*') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
        <span>Home Display</span>
    </a>

    {{-- Orders --}}
    @include('admin.layouts._sidebar-divider', ['label' => 'Orders'])
    <a href="{{ route('admin.orders.index') }}" class="sidebar-link flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <span>Orders</span>
    </a>
    <a href="{{ route('admin.reviews.index') }}" class="sidebar-link flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
        <span>Reviews</span>
    </a>
    <a href="{{ route('admin.transactions.index') }}" class="sidebar-link flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
        <span>Transactions</span>
    </a>
    <a href="{{ route('admin.track-orders.index') }}" class="sidebar-link flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium {{ request()->routeIs('admin.track-orders.*') ? 'active' : '' }}">
        <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span>Track Orders</span>
    </a>

    {{-- General --}}
    @include('admin.layouts._sidebar-divider', ['label' => 'General'])

    {{-- Support --}}
    <div x-data="{ isActive: {{ request()->routeIs('admin.conversations.*') || request()->routeIs('admin.disputes.*') || request()->routeIs('admin.refunds.*') ? 'true' : 'false' }} }" x-init="if(isActive) openSection = 'support'">
        <button type="button" @click="toggleSection('support')" class="sidebar-link w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium hover:bg-gray-50/80 transition" :class="openSection === 'support' ? 'bg-primary/5 text-primary' : ''">
            <span class="flex items-center gap-2.5">
                <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <span>Support</span>
            </span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="openSection === 'support' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="openSection === 'support'" x-collapse class="ml-4 mt-1 space-y-0.5 border-l-2 border-gray-100 pl-3">
            <a href="{{ route('admin.conversations.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.conversations.*') ? 'active' : '' }}">Conversations</a>
            <a href="{{ route('admin.disputes.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.disputes.*') ? 'active' : '' }}">Disputes</a>
            <a href="{{ route('admin.refunds.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}">Refunds</a>
        </div>
    </div>

    {{-- Marketing --}}
    <div x-data="{ isActive: {{ request()->routeIs('admin.coupons.*') || request()->routeIs('admin.promotion-packages.*') ? 'true' : 'false' }} }" x-init="if(isActive) openSection = 'marketing'">
        <button type="button" @click="toggleSection('marketing')" class="sidebar-link w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium hover:bg-gray-50/80 transition" :class="openSection === 'marketing' ? 'bg-primary/5 text-primary' : ''">
            <span class="flex items-center gap-2.5">
                <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span>Marketing</span>
            </span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="openSection === 'marketing' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="openSection === 'marketing'" x-collapse class="ml-4 mt-1 space-y-0.5 border-l-2 border-gray-100 pl-3">
            <a href="{{ route('admin.coupons.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}">Coupons</a>
            <a href="{{ route('admin.promotion-packages.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.promotion-packages.*') ? 'active' : '' }}">Promotion Packages</a>
        </div>
    </div>

    {{-- CMS --}}
    <div x-data="{ isActive: {{ request()->routeIs('admin.pages.*') || request()->routeIs('admin.testimonials.*') || request()->routeIs('admin.faqs.*') || request()->routeIs('admin.blogs.*') || request()->routeIs('admin.contact-submissions.*') || request()->routeIs('admin.newsletter.index') ? 'true' : 'false' }} }" x-init="if(isActive) openSection = 'cms'">
        <button type="button" @click="toggleSection('cms')" class="sidebar-link w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium hover:bg-gray-50/80 transition" :class="openSection === 'cms' ? 'bg-primary/5 text-primary' : ''">
            <span class="flex items-center gap-2.5">
                <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>CMS</span>
            </span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="openSection === 'cms' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="openSection === 'cms'" x-collapse class="ml-4 mt-1 space-y-0.5 border-l-2 border-gray-100 pl-3">
            <a href="{{ route('admin.pages.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">Pages</a>
            <a href="{{ route('admin.testimonials.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.testimonials.*') ? 'active' : '' }}">Testimonials</a>
            <a href="{{ route('admin.faqs.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}">FAQs</a>
            <a href="{{ route('admin.blogs.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.blogs.*') ? 'active' : '' }}">Blogs</a>
            <a href="{{ route('admin.contact-submissions.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.contact-submissions.*') ? 'active' : '' }}">Contact Submissions</a>
            <a href="{{ route('admin.newsletter.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.newsletter.index') ? 'active' : '' }}">Newsletter</a>
        </div>
    </div>

    {{-- Analytics --}}
    <div x-data="{ isActive: {{ request()->routeIs('admin.analytics.*') || request()->routeIs('admin.inventory.*') ? 'true' : 'false' }} }" x-init="if(isActive) openSection = 'analytics'">
        <button type="button" @click="toggleSection('analytics')" class="sidebar-link w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium hover:bg-gray-50/80 transition" :class="openSection === 'analytics' ? 'bg-primary/5 text-primary' : ''">
            <span class="flex items-center gap-2.5">
                <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span>Analytics</span>
            </span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="openSection === 'analytics' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="openSection === 'analytics'" x-collapse class="ml-4 mt-1 space-y-0.5 border-l-2 border-gray-100 pl-3">
            <a href="{{ route('admin.analytics.sales') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.analytics.sales') ? 'active' : '' }}">Sales Report</a>
            <a href="{{ route('admin.analytics.seller-earning') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.analytics.seller-earning') ? 'active' : '' }}">Seller Earning</a>
            <a href="{{ route('admin.analytics.commission') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.analytics.commission') ? 'active' : '' }}">Commission Report</a>
            <a href="{{ route('admin.analytics.payout') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.analytics.payout') ? 'active' : '' }}">Payout Report</a>
            <a href="{{ route('admin.analytics.refund') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.analytics.refund') ? 'active' : '' }}">Refund Report</a>
            <a href="{{ route('admin.inventory.low-stock') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.inventory.low-stock') ? 'active' : '' }}">Low Stock</a>
            <a href="{{ route('admin.inventory.out-of-stock') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.inventory.out-of-stock') ? 'active' : '' }}">Out of Stock</a>
        </div>
    </div>

    {{-- Settings --}}
    <div x-data="{ isActive: {{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.sitemap.*') || request()->routeIs('admin.recaptcha-settings.*') || request()->routeIs('admin.wachat-settings.*') || request()->routeIs('admin.whatsapp-templates.*') || request()->routeIs('admin.activities.*') || request()->routeIs('admin.expenses.*') || request()->routeIs('admin.courier.*') || request()->routeIs('admin.payment-methods.*') || request()->routeIs('admin.email-settings.*') || request()->routeIs('admin.email-templates.*') || request()->routeIs('admin.commission-settings.*') || request()->routeIs('admin.commissions.*') ? 'true' : 'false' }} }" x-init="if(isActive) openSection = 'settings'">
        <button type="button" @click="toggleSection('settings')" class="sidebar-link w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium hover:bg-gray-50/80 transition" :class="openSection === 'settings' ? 'bg-primary/5 text-primary' : ''">
            <span class="flex items-center gap-2.5">
                <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Settings</span>
            </span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="openSection === 'settings' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="openSection === 'settings'" x-collapse class="ml-4 mt-1 space-y-0.5 border-l-2 border-gray-100 pl-3">
            <a href="{{ route('admin.settings.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">General Settings</a>
            <a href="{{ route('admin.email-settings.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.email-settings.*') || request()->routeIs('admin.email-templates.*') ? 'active' : '' }}">Email Setting</a>
            <a href="{{ route('admin.payment-methods.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.payment-methods.*') ? 'active' : '' }}">Payment Method</a>
            <a href="{{ route('admin.courier.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.courier.*') ? 'active' : '' }}">Courier</a>
            <a href="{{ route('admin.commissions.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.commission-settings.*') || request()->routeIs('admin.commissions.*') ? 'active' : '' }}">Commission</a>
            <a href="{{ route('admin.recaptcha-settings.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.recaptcha-settings.*') ? 'active' : '' }}">Google reCAPTCHA</a>
            <a href="{{ route('admin.wachat-settings.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.wachat-settings.*') || request()->routeIs('admin.whatsapp-templates.*') ? 'active' : '' }}">WaChat WhatsApp</a>
            <a href="{{ route('admin.activities.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.activities.*') ? 'active' : '' }}">Activity Log</a>
            <a href="{{ route('admin.expenses.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}">Expenses</a>
            <a href="{{ route('admin.sitemap.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.sitemap.*') ? 'active' : '' }}">Sitemaps</a>
        </div>
    </div>

    {{-- Finance --}}
    <div x-data="{ isActive: {{ request()->routeIs('admin.payouts.*') ? 'true' : 'false' }} }" x-init="if(isActive) openSection = 'finance'">
        <button type="button" @click="toggleSection('finance')" class="sidebar-link w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium hover:bg-gray-50/80 transition" :class="openSection === 'finance' ? 'bg-primary/5 text-primary' : ''">
            <span class="flex items-center gap-2.5">
                <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Finance</span>
            </span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="openSection === 'finance' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="openSection === 'finance'" x-collapse class="ml-4 mt-1 space-y-0.5 border-l-2 border-gray-100 pl-3">
            <a href="{{ route('admin.payouts.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.payouts.*') ? 'active' : '' }}">Payouts</a>
        </div>
    </div>

    {{-- Access Control --}}
    <div x-data="{ isActive: {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.sub-admins.*') ? 'true' : 'false' }} }" x-init="if(isActive) openSection = 'access'">
        <button type="button" @click="toggleSection('access')" class="sidebar-link w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium hover:bg-gray-50/80 transition" :class="openSection === 'access' ? 'bg-primary/5 text-primary' : ''">
            <span class="flex items-center gap-2.5">
                <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12 12 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Access Control</span>
            </span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="openSection === 'access' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="openSection === 'access'" x-collapse class="ml-4 mt-1 space-y-0.5 border-l-2 border-gray-100 pl-3">
            <a href="{{ route('admin.roles.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.roles.index') || request()->routeIs('admin.roles.edit') || request()->routeIs('admin.roles.create') ? 'active' : '' }}">Roles</a>
            <a href="{{ route('admin.roles.permissions-matrix') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.roles.permissions-matrix') ? 'active' : '' }}">Permissions Matrix</a>
            <a href="{{ route('admin.sub-admins.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.sub-admins.*') ? 'active' : '' }}">Sub-Admins</a>
        </div>
    </div>

    {{-- Safety --}}
    <div x-data="{ isActive: {{ request()->routeIs('admin.abuse-safety.*') ? 'true' : 'false' }} }" x-init="if(isActive) openSection = 'safety'">
        <button type="button" @click="toggleSection('safety')" class="sidebar-link w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-gray-700 text-[15px] font-medium hover:bg-gray-50/80 transition" :class="openSection === 'safety' ? 'bg-primary/5 text-primary' : ''">
            <span class="flex items-center gap-2.5">
                <svg class="w-5 h-5 flex-shrink-0 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span>Safety</span>
            </span>
            <svg class="w-4 h-4 transition-transform duration-200" :class="openSection === 'safety' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="openSection === 'safety'" x-collapse class="ml-4 mt-1 space-y-0.5 border-l-2 border-gray-100 pl-3">
            <a href="{{ route('admin.abuse-safety.index') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.abuse-safety.index') ? 'active' : '' }}">Abuse & Safety</a>
            <a href="{{ route('admin.abuse-safety.flagged') }}" class="sidebar-sub flex items-center gap-2 py-2 px-2 rounded-lg text-gray-600 text-[15px] {{ request()->routeIs('admin.abuse-safety.flagged') ? 'active' : '' }}">Flagged Items</a>
        </div>
    </div>
</nav>
