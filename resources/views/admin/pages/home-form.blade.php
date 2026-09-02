@extends('admin.layouts.app')

@section('title', 'Edit Home Page')

@section('admin-content')
<div class="w-full min-w-0">
    {{-- Back & intro --}}
    <div class="mb-8">
        <a href="{{ route('admin.pages.index') }}" class="inline-flex items-center gap-2 text-primary text-sm font-semibold hover:underline mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Pages
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Edit Home Page</h1>
        <p class="text-gray-500 mt-1">Change the main landing page content and hero section. Updates appear on the public site immediately after saving.</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
            <p class="font-semibold mb-2">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">{{ $errors->first() }}</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.pages.update', $page) }}" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')

        {{-- Hero Section --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                    </span>
                    Hero Section
                </h2>
                <p class="text-sm text-gray-500 mt-1">The main banner at the top of your home page. Edit headline, short text, buttons, and the background image.</p>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Badge text</label>
                        <input type="text" name="hero_badge" value="{{ old('hero_badge', $heroConfig['badge'] ?? '') }}" placeholder="e.g. #1 Multi-Seller Marketplace" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        <p class="text-xs text-gray-500 mt-1">Small label above the main headline.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Headline (first line)</label>
                        <input type="text" name="hero_title" value="{{ old('hero_title', $heroConfig['title'] ?? '') }}" placeholder="e.g. Buy & Sell" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Headline (second line)</label>
                        <input type="text" name="hero_title_line2" value="{{ old('hero_title_line2', $heroConfig['title_line2'] ?? '') }}" placeholder="e.g. Anything, Anywhere" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subtitle</label>
                        <textarea name="hero_subtitle" rows="3" placeholder="Tijaar is Pakistan's multi-seller marketplace..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('hero_subtitle', $heroConfig['subtitle'] ?? '') }}</textarea>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100">
                    <p class="text-sm font-medium text-gray-700 mb-3">Primary button (e.g. Start Shopping)</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Button text</label>
                            <input type="text" name="hero_cta_primary_text" value="{{ old('hero_cta_primary_text', $heroConfig['cta_primary_text'] ?? '') }}" placeholder="Start Shopping" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Button link</label>
                            <input type="text" name="hero_cta_primary_url" value="{{ old('hero_cta_primary_url', $heroConfig['cta_primary_url'] ?? '') }}" placeholder="/shop" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        </div>
                    </div>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-3">Secondary button (e.g. Become a Verified Seller)</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Button text</label>
                            <input type="text" name="hero_cta_secondary_text" value="{{ old('hero_cta_secondary_text', $heroConfig['cta_secondary_text'] ?? '') }}" placeholder="Become a Verified Seller" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Button link</label>
                            <input type="text" name="hero_cta_secondary_url" value="{{ old('hero_cta_secondary_url', $heroConfig['cta_secondary_url'] ?? '') }}" placeholder="/sellers" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100">
                    <p class="text-sm font-medium text-gray-700 mb-3">Feature boxes (three short lines under the buttons)</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                            <label class="block text-xs font-medium text-gray-600">Box 1</label>
                            <input type="text" name="hero_feature1_title" value="{{ old('hero_feature1_title', $heroConfig['feature1_title'] ?? '') }}" placeholder="Secure Payments" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                            <input type="text" name="hero_feature1_subtitle" value="{{ old('hero_feature1_subtitle', $heroConfig['feature1_subtitle'] ?? '') }}" placeholder="JazzCash, Bank Card, Easypaisa" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                            <label class="block text-xs font-medium text-gray-600">Box 2</label>
                            <input type="text" name="hero_feature2_title" value="{{ old('hero_feature2_title', $heroConfig['feature2_title'] ?? '') }}" placeholder="Verified Sellers" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                            <input type="text" name="hero_feature2_subtitle" value="{{ old('hero_feature2_subtitle', $heroConfig['feature2_subtitle'] ?? '') }}" placeholder="KYC, Email, Phone," class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl space-y-2">
                            <label class="block text-xs font-medium text-gray-600">Box 3</label>
                            <input type="text" name="hero_feature3_title" value="{{ old('hero_feature3_title', $heroConfig['feature3_title'] ?? '') }}" placeholder="Fast Shipping" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                            <input type="text" name="hero_feature3_subtitle" value="{{ old('hero_feature3_subtitle', $heroConfig['feature3_subtitle'] ?? '') }}" placeholder="TCS, Leopards, PostEx" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Hero background image</label>
                    @if ($heroBanner && $heroBanner->image_path)
                        <div class="mb-3">
                            <p class="text-xs text-gray-500 mb-2">Current image:</p>
                            <img src="{{ \App\Support\UploadHelper::url($heroBanner->image_path) }}" alt="Hero" class="max-h-40 rounded-xl border border-gray-200 object-cover" />
                        </div>
                    @endif
                    <input type="file" name="hero_image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-medium" />
                    @include('admin.partials.image-alt-field', [
                        'name' => 'hero_image_alt',
                        'value' => $heroBanner->image_alt ?? '',
                    ])
                    <p class="text-xs text-gray-500 mt-1">Upload a new image to replace the hero background. Leave empty to keep the current one. Recommended: wide image (e.g. 1920×800).</p>
                </div>
            </div>
        </div>

        {{-- Get the App section --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </span>
                    Get the App
                </h2>
                <p class="text-sm text-gray-500 mt-1">Headline and description for the mobile app download block on the home page.</p>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Headline (first part)</label>
                    <input type="text" name="app_headline" value="{{ old('app_headline', $appConfig['headline'] ?? '') }}" placeholder="e.g. Get the App for a" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    <p class="text-xs text-gray-500 mt-1">Shown before the highlighted phrase (e.g. &quot;Get the App for a <strong>Better Experience</strong>&quot;).</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Headline (highlighted part)</label>
                    <input type="text" name="app_highlight" value="{{ old('app_highlight', $appConfig['highlight'] ?? '') }}" placeholder="e.g. Better Experience" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Short description</label>
                    <textarea name="app_description" rows="2" placeholder="e.g. Download our mobile app to browse listings on the go, get instant notifications." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('app_description', $appConfig['description'] ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rating / downloads text</label>
                    <input type="text" name="app_rating_text" value="{{ old('app_rating_text', $appConfig['rating_text'] ?? '') }}" placeholder="e.g. 4.9 Rating • 100K+ Downloads" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">App Store URL</label>
                        <input type="url" name="app_store_url" value="{{ old('app_store_url', $appConfig['app_store_url'] ?? '') }}" placeholder="https://apps.apple.com/..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Google Play URL</label>
                        <input type="url" name="play_store_url" value="{{ old('play_store_url', $appConfig['play_store_url'] ?? '') }}" placeholder="https://play.google.com/..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Never Miss a Deal (Newsletter) section --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </span>
                    Never Miss a Deal! (Newsletter)
                </h2>
                <p class="text-sm text-gray-500 mt-1">Heading and subtitle for the newsletter signup block on the home page.</p>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Heading</label>
                    <input type="text" name="newsletter_heading" value="{{ old('newsletter_heading', $newsletterConfig['heading'] ?? '') }}" placeholder="e.g. Never Miss a Deal!" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Subtitle</label>
                    <textarea name="newsletter_subtitle" rows="2" placeholder="e.g. Subscribe to our newsletter and be the first to know about new listings and exclusive deals." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('newsletter_subtitle', $newsletterConfig['subtitle'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- SEO --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    SEO (search engines)
                </h2>
                <p class="text-sm text-gray-500 mt-1">Optional. Helps search engines show your home page correctly in results.</p>
            </div>
            <div class="p-6 space-y-4">
                @include('admin.pages.partials.seo-fields', ['page' => $page])
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-semibold rounded-xl shadow-sm transition">
                Save changes
            </button>
            <a href="{{ route('admin.pages.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">Cancel</a>
            <a href="{{ url('/') }}" target="_blank" rel="noopener" class="ml-auto text-sm text-primary hover:underline">View home page →</a>
        </div>
    </form>
</div>
@endsection
