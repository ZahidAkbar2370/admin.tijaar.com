@extends('admin.layouts.app')

@section('title', 'Edit: Contact Us')

@section('admin-content')
<div class="w-full min-w-0">
    <div class="mb-8">
        <a href="{{ route('admin.pages.index') }}" class="inline-flex items-center gap-2 text-primary text-sm font-semibold hover:underline mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Pages
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Edit: Contact Us</h1>
        <p class="text-gray-500 mt-1">Update the Contact page: hero, contact cards, map, form title, support block, and social links. All content is dynamic.</p>
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

        {{-- Page title & banner --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Page identity &amp; hero</h2>
                <p class="text-sm text-gray-500 mt-1">Title and the blue hero section at the top.</p>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Page title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $page->title) }}" required placeholder="Contact Us" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Hero title</label>
                    @php $hero = $sections['hero'] ?? []; @endphp
                <input type="text" name="banner_title" value="{{ old('banner_title', $page->banner_title ?? ($hero['title'] ?? 'Get in Touch')) }}" placeholder="Get in Touch" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Hero subtitle</label>
                    <textarea name="banner_subtitle" rows="2" placeholder="Have a question or need help? We're here for you 24/7." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('banner_subtitle', $page->banner_subtitle ?? ($hero['subtitle'] ?? '')) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Contact cards (3) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Contact info cards (3 columns)</h2>
                <p class="text-sm text-gray-500 mt-1">Phone, Email, Address. Type controls the icon colour (green, blue, pink).</p>
            </div>
            <div class="p-6 space-y-4">
                @php
                    $cards = $sections['contact_cards'] ?? [];
                    while (count($cards) < 3) { $cards[] = ['type' => 'phone', 'label' => '', 'value' => '', 'subtext' => '']; }
                    $cards = array_slice($cards, 0, 3);
                @endphp
                @foreach ($cards as $i => $c)
                <div class="p-4 bg-gray-50 rounded-xl space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                            <select name="contact_cards[{{ $i }}][type]" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                <option value="phone" {{ ($c['type'] ?? '') === 'phone' ? 'selected' : '' }}>Phone (green icon)</option>
                                <option value="email" {{ ($c['type'] ?? '') === 'email' ? 'selected' : '' }}>Email (blue icon)</option>
                                <option value="address" {{ ($c['type'] ?? '') === 'address' ? 'selected' : '' }}>Address (pink icon)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Label</label>
                            <input type="text" name="contact_cards[{{ $i }}][label]" value="{{ old("contact_cards.$i.label", $c['label'] ?? '') }}" placeholder="Phone" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Value</label>
                            <input type="text" name="contact_cards[{{ $i }}][value]" value="{{ old("contact_cards.$i.value", $c['value'] ?? '') }}" placeholder="+971 50 123 4567" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Subtext</label>
                            <input type="text" name="contact_cards[{{ $i }}][subtext]" value="{{ old("contact_cards.$i.subtext", $c['subtext'] ?? '') }}" placeholder="Call us anytime" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Map --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Map / Our Location</h2>
                <p class="text-sm text-gray-500 mt-1">Heading, address text, and optional Google Map embed URL (iframe src).</p>
            </div>
            <div class="p-6 space-y-4">
                @php $map = $sections['map'] ?? []; @endphp
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Map section heading</label>
                    <input type="text" name="map_heading" value="{{ old('map_heading', $map['heading'] ?? 'Our Location') }}" placeholder="Our Location" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address (shown under heading)</label>
                    <input type="text" name="map_address" value="{{ old('map_address', $map['address'] ?? '') }}" placeholder="Dubai, United Arab Emirates" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Map embed URL (optional)</label>
                    <input type="url" name="map_embed_url" value="{{ old('map_embed_url', $map['embed_url'] ?? '') }}" placeholder="https://www.google.com/maps/embed?pb=..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    <p class="text-xs text-gray-500 mt-1">Paste the iframe <strong>src</strong> from Google Maps "Share → Embed map". Leave empty for a grey placeholder.</p>
                </div>
            </div>
        </div>

        {{-- Form title --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Contact form</h2>
                <p class="text-sm text-gray-500 mt-1">Title above the "Send us a Message" form. Form fields (name, email, subject, message) are fixed.</p>
            </div>
            <div class="p-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Form section title</label>
                <input type="text" name="form_title" value="{{ old('form_title', $sections['form_title'] ?? 'Send us a Message') }}" placeholder="Send us a Message" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
            </div>
        </div>

        {{-- Support card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Need Immediate Help? card</h2>
                <p class="text-sm text-gray-500 mt-1">Right column: title, description, and quick contact (phone + email).</p>
            </div>
            <div class="p-6 space-y-4">
                @php $support = $sections['support'] ?? []; @endphp
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Card title</label>
                    <input type="text" name="support_title" value="{{ old('support_title', $support['title'] ?? 'Need Immediate Help?') }}" placeholder="Need Immediate Help?" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="support_description" rows="3" placeholder="Our support team is here to assist you..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm">{{ old('support_description', $support['description'] ?? '') }}</textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone label</label>
                        <input type="text" name="support_phone_label" value="{{ old('support_phone_label', $support['phone_label'] ?? 'Call Us') }}" placeholder="Call Us" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone number</label>
                        <input type="text" name="support_phone_value" value="{{ old('support_phone_value', $support['phone_value'] ?? '') }}" placeholder="+971 50 123 4567" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email label</label>
                        <input type="text" name="support_email_label" value="{{ old('support_email_label', $support['email_label'] ?? 'Email Us') }}" placeholder="Email Us" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email address</label>
                        <input type="text" name="support_email_value" value="{{ old('support_email_value', $support['email_value'] ?? '') }}" placeholder="support@tijaar.com" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Social --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Follow Us (social links)</h2>
                <p class="text-sm text-gray-500 mt-1">Title, subtext, and platform + URL for each social (Facebook, Twitter, Instagram, YouTube, TikTok).</p>
            </div>
            <div class="p-6 space-y-4">
                @php $social = $sections['social'] ?? []; $social = is_array($social) ? $social : []; @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Social block title</label>
                        <input type="text" name="social_title" value="{{ old('social_title', $social['title'] ?? 'Follow Us') }}" placeholder="Follow Us" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subtext</label>
                        <input type="text" name="social_subtext" value="{{ old('social_subtext', $social['subtext'] ?? 'Stay connected with us on social media') }}" placeholder="Stay connected with us on social media" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    </div>
                </div>
                @php
                    $links = $social['links'] ?? [];
                    $platforms = ['facebook', 'twitter', 'instagram', 'youtube', 'tiktok'];
                    foreach ($platforms as $p) {
                        $found = collect($links)->firstWhere('platform', $p);
                        if (!$found) $links[] = ['platform' => $p, 'url' => ''];
                    }
                    $links = array_slice($links, 0, 8);
                @endphp
                @foreach ($links as $i => $link)
                <div class="flex flex-wrap gap-4 items-center p-3 bg-gray-50 rounded-xl">
                    <input type="text" name="social_links[{{ $i }}][platform]" value="{{ old("social_links.$i.platform", $link['platform'] ?? '') }}" placeholder="facebook" class="w-28 px-3 py-2 rounded-lg border border-gray-200 text-sm font-mono" />
                    <input type="url" name="social_links[{{ $i }}][url]" value="{{ old("social_links.$i.url", $link['url'] ?? '') }}" placeholder="https://facebook.com/..." class="flex-1 min-w-[200px] px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                </div>
                @endforeach
            </div>
        </div>

        {{-- SEO & visibility --}}
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
            <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-xl shadow-sm transition">
                Save changes
            </button>
            <a href="{{ route('admin.pages.index') }}" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition">Cancel</a>
            <a href="{{ url('contact') }}" target="_blank" rel="noopener" class="ml-auto text-sm text-primary hover:underline">View Contact page on site →</a>
        </div>
    </form>
</div>
@endsection
