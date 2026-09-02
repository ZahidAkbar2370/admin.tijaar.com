@extends('admin.layouts.app')

@section('title', 'Edit: How It Works')

@section('admin-content')
<div class="w-full min-w-0">
    <div class="mb-8">
        <a href="{{ route('admin.pages.index') }}" class="inline-flex items-center gap-2 text-primary text-sm font-semibold hover:underline mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Pages
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Edit: How It Works</h1>
        <p class="text-gray-500 mt-1">Update the hero banner and all step cards (For Buyers, For Sellers, Trust bar). Changes appear on the public How It Works page.</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
            <p class="font-semibold mb-2">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">{{ $errors->first() }}</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="space-y-8">
        @csrf
        @method('PUT')

        {{-- Hero --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Hero banner</h2>
                <p class="text-sm text-gray-500 mt-1">Title and subtitle at the top of the page.</p>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Page title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $page->title) }}" required placeholder="How It Works" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Hero title</label>
                    <input type="text" name="banner_title" value="{{ old('banner_title', $page->banner_title ?? 'How Tijaar Works') }}" placeholder="How Tijaar Works" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Hero subtitle</label>
                    <input type="text" name="banner_subtitle" value="{{ old('banner_subtitle', $page->banner_subtitle ?? 'Buy from trusted sellers or start selling yourself.') }}" placeholder="Buy from trusted sellers or start selling yourself." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                </div>
            </div>
        </div>

        {{-- For Buyers --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">For Buyers section</h2>
                <p class="text-sm text-gray-500 mt-1">Heading, subtitle, step cards (title + description), and CTA button.</p>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Section heading</label>
                    <input type="text" name="buyer_heading" value="{{ old('buyer_heading', $sections['buyer_heading'] ?? 'For Buyers') }}" placeholder="For Buyers" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Section subtitle</label>
                    <input type="text" name="buyer_subtitle" value="{{ old('buyer_subtitle', $sections['buyer_subtitle'] ?? 'Shop with confidence from verified sellers.') }}" placeholder="Shop with confidence from verified sellers." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                </div>
                @php $buyerSteps = $sections['buyer_steps'] ?? []; $buyerSteps = array_pad($buyerSteps, 3, ['title' => '', 'description' => '']); @endphp
                @foreach ($buyerSteps as $i => $step)
                <div class="pl-4 border-l-2 border-primary/20 space-y-2">
                    <span class="text-xs font-semibold text-gray-500">Step {{ $i + 1 }}</span>
                    <input type="text" name="buyer_steps[{{ $i }}][title]" value="{{ old("buyer_steps.{$i}.title", $step['title'] ?? '') }}" placeholder="Card title" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    <textarea name="buyer_steps[{{ $i }}][description]" rows="2" placeholder="Short description" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">{{ old("buyer_steps.{$i}.description", $step['description'] ?? '') }}</textarea>
                </div>
                @endforeach
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Button text</label>
                        <input type="text" name="buyer_cta_text" value="{{ old('buyer_cta_text', $sections['buyer_cta_text'] ?? 'Start Shopping') }}" placeholder="Start Shopping" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Button URL</label>
                        <input type="text" name="buyer_cta_url" value="{{ old('buyer_cta_url', $sections['buyer_cta_url'] ?? '/shop') }}" placeholder="/shop" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    </div>
                </div>
            </div>
        </div>

        {{-- For Sellers --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">For Sellers section</h2>
            </div>
            <div class="p-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Section heading</label>
                    <input type="text" name="seller_heading" value="{{ old('seller_heading', $sections['seller_heading'] ?? 'For Sellers') }}" placeholder="For Sellers" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Section subtitle</label>
                    <input type="text" name="seller_subtitle" value="{{ old('seller_subtitle', $sections['seller_subtitle'] ?? 'Reach millions of buyers. List products, manage orders, and get paid.') }}" placeholder="Reach millions of buyers." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                </div>
                @php $sellerSteps = $sections['seller_steps'] ?? []; $sellerSteps = array_pad($sellerSteps, 3, ['title' => '', 'description' => '']); @endphp
                @foreach ($sellerSteps as $i => $step)
                <div class="pl-4 border-l-2 border-primary/20 space-y-2">
                    <span class="text-xs font-semibold text-gray-500">Step {{ $i + 1 }}</span>
                    <input type="text" name="seller_steps[{{ $i }}][title]" value="{{ old("seller_steps.{$i}.title", $step['title'] ?? '') }}" placeholder="Card title" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    <textarea name="seller_steps[{{ $i }}][description]" rows="2" placeholder="Short description" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">{{ old("seller_steps.{$i}.description", $step['description'] ?? '') }}</textarea>
                </div>
                @endforeach
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Button text</label>
                        <input type="text" name="seller_cta_text" value="{{ old('seller_cta_text', $sections['seller_cta_text'] ?? 'Become a seller') }}" placeholder="Become a seller" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Button URL</label>
                        <input type="text" name="seller_cta_url" value="{{ old('seller_cta_url', $sections['seller_cta_url'] ?? '/sellers') }}" placeholder="/sellers" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Trust bar --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Trust bar (bottom)</h2>
                <p class="text-sm text-gray-500 mt-1">Three labels (e.g. Verified Sellers, Secure Payments, Reliable Shipping), intro text, and optional links.</p>
            </div>
            <div class="p-6 space-y-4">
                @php $trustItems = $sections['trust_items'] ?? []; $trustItems = array_pad($trustItems, 3, ['label' => '']); @endphp
                @foreach ($trustItems as $i => $item)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Trust label {{ $i + 1 }}</label>
                    <input type="text" name="trust_items[{{ $i }}][label]" value="{{ old("trust_items.{$i}.label", $item['label'] ?? '') }}" placeholder="e.g. Verified Sellers" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                </div>
                @endforeach
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Trust bar text</label>
                    <textarea name="trust_text" rows="2" placeholder="Short intro text under the labels" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">{{ old('trust_text', $sections['trust_text'] ?? '') }}</textarea>
                </div>
                @php $trustLinks = $sections['trust_links'] ?? []; $trustLinks = array_pad($trustLinks, 2, ['text' => '', 'url' => '']); @endphp
                @foreach ($trustLinks as $i => $link)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <input type="text" name="trust_links[{{ $i }}][text]" value="{{ old("trust_links.{$i}.text", $link['text'] ?? '') }}" placeholder="Link text" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    <input type="text" name="trust_links[{{ $i }}][url]" value="{{ old("trust_links.{$i}.url", $link['url'] ?? '') }}" placeholder="URL (e.g. /faqs)" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                </div>
                @endforeach
            </div>
        </div>

        {{-- SEO --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">SEO &amp; visibility</h2>
            </div>
            <div class="p-6 space-y-4">
                @include('admin.pages.partials.seo-fields', ['page' => $page])
                <div class="flex flex-wrap items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0" />
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $page->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary/20" />
                        <span class="text-sm font-medium text-gray-700">Page is live</span>
                    </label>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Sort order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $page->sort_order ?? 0) }}" min="0" class="w-24 px-3 py-2 rounded-xl border border-gray-200 text-sm" />
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl shadow-sm transition">Save changes</button>
            <a href="{{ route('admin.pages.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">Cancel</a>
            <a href="{{ url('how-it-works') }}" target="_blank" rel="noopener" class="ml-auto text-sm text-primary hover:underline">View How It Works on site</a>
        </div>
    </form>
</div>
@endsection
