@extends('admin.layouts.app')

@section('title', 'Edit: About Us')

@section('admin-content')
<div class="w-full min-w-0">
    <div class="mb-8">
        <a href="{{ route('admin.pages.index') }}" class="inline-flex items-center gap-2 text-primary text-sm font-semibold hover:underline mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Pages
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Edit: About Us</h1>
        <p class="text-gray-500 mt-1">Update the About Us page sections. Hero banner, mission, stats, values, journey, team, and CTA. Changes appear on the public site after saving.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-800 text-sm">
            <p class="font-semibold mb-2">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">{{ $errors->first() }}</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.pages.update', $page) }}" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')

        {{-- Page title & banner --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Page identity &amp; banner</h2>
                <p class="text-sm text-gray-500 mt-1">Title and the blue hero section at the top of the About page.</p>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Page title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $page->title) }}" required placeholder="About Us" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Banner title</label>
                    <input type="text" name="banner_title" value="{{ old('banner_title', $page->banner_title ?? 'About Us') }}" placeholder="About Us" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Banner subtitle</label>
                    <input type="text" name="banner_subtitle" value="{{ old('banner_subtitle', $page->banner_subtitle ?? "We're building the future of e-commerce by connecting customers with trusted sellers worldwide.") }}" placeholder="Short tagline under the main title" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>
            </div>
        </div>

        {{-- Mission --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Our Mission</h2>
                <p class="text-sm text-gray-500 mt-1">The main mission card. Use multiple paragraphs (one per line) for best display.</p>
            </div>
            <div class="p-6 space-y-4">
                @php $mission = $sections['mission'] ?? []; @endphp
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mission heading</label>
                    <input type="text" name="mission_title" value="{{ old('mission_title', $mission['title'] ?? 'Our Mission') }}" placeholder="Our Mission" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mission description</label>
                    <textarea name="mission_description" rows="5" placeholder="One or more paragraphs (separate by new line)." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('mission_description', $mission['description'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Statistics (4 cards) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Statistics</h2>
                <p class="text-sm text-gray-500 mt-1">Four stat cards (e.g. 100K+ Active Products). Icon: package, users, store, cart.</p>
            </div>
            <div class="p-6 space-y-4">
                @php $stats = $sections['stats'] ?? []; while (count($stats) < 4) { $stats[] = ['icon' => 'package', 'number' => '', 'label' => '']; } $stats = array_slice($stats, 0, 4); @endphp
                @foreach ($stats as $i => $s)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-xl">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Icon</label>
                        <select name="stats[{{ $i }}][icon]" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                            <option value="package" {{ ($s['icon'] ?? '') === 'package' ? 'selected' : '' }}>Package (box)</option>
                            <option value="users" {{ ($s['icon'] ?? '') === 'users' ? 'selected' : '' }}>Users (people)</option>
                            <option value="store" {{ ($s['icon'] ?? '') === 'store' ? 'selected' : '' }}>Store</option>
                            <option value="cart" {{ ($s['icon'] ?? '') === 'cart' ? 'selected' : '' }}>Shopping cart</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Number</label>
                        <input type="text" name="stats[{{ $i }}][number]" value="{{ old("stats.$i.number", $s['number'] ?? '') }}" placeholder="100K+" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Label</label>
                        <input type="text" name="stats[{{ $i }}][label]" value="{{ old("stats.$i.label", $s['label'] ?? '') }}" placeholder="Active Products" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Values (4 cards) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Our Values</h2>
                <p class="text-sm text-gray-500 mt-1">Four value cards (e.g. Trust &amp; Security, Customer First).</p>
            </div>
            <div class="p-6 space-y-4">
                @php $values = $sections['values'] ?? []; while (count($values) < 4) { $values[] = ['title' => '', 'description' => '']; } $values = array_slice($values, 0, 4); @endphp
                @foreach ($values as $i => $v)
                <div class="p-4 bg-gray-50 rounded-xl space-y-3">
                    <input type="text" name="values[{{ $i }}][title]" value="{{ old("values.$i.title", $v['title'] ?? '') }}" placeholder="Value title (e.g. Trust &amp; Security)" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                    <textarea name="values[{{ $i }}][description]" rows="2" placeholder="Short description" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">{{ old("values.$i.description", $v['description'] ?? '') }}</textarea>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Journey (timeline) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Our Journey</h2>
                <p class="text-sm text-gray-500 mt-1">Timeline milestones. Add up to 5 or more; year, title, and short description.</p>
            </div>
            <div class="p-6 space-y-4">
                @php $journey = $sections['journey'] ?? []; if (count($journey) < 5) { $defaults = [['year'=>'2020','title'=>'Platform Launch','description'=>''],['year'=>'2021','title'=>'10K Customers','description'=>''],['year'=>'2022','title'=>'Expansion','description'=>''],['year'=>'2023','title'=>'Market Leader','description'=>''],['year'=>'2026','title'=>'Today','description'=>'']]; $journey = array_merge($journey, array_slice($defaults, count($journey))); } $journey = array_slice($journey, 0, 8); @endphp
                @foreach ($journey as $i => $j)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-xl">
                    <input type="text" name="journey[{{ $i }}][year]" value="{{ old("journey.$i.year", $j['year'] ?? '') }}" placeholder="Year (e.g. 2020)" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                    <input type="text" name="journey[{{ $i }}][title]" value="{{ old("journey.$i.title", $j['title'] ?? '') }}" placeholder="Milestone title" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                    <input type="text" name="journey[{{ $i }}][description]" value="{{ old("journey.$i.description", $j['description'] ?? '') }}" placeholder="Short description" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                </div>
                @endforeach
            </div>
        </div>

        {{-- Team --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Our Team</h2>
                <p class="text-sm text-gray-500 mt-1">Team member cards. Name, role, and optional profile image (upload to replace).</p>
            </div>
            <div class="p-6 space-y-4">
                @php $team = $sections['team'] ?? []; while (count($team) < 4) { $team[] = ['name' => '', 'role' => '', 'image_path' => null]; } $team = array_slice($team, 0, 4); @endphp
                @foreach ($team as $i => $t)
                <div class="p-4 bg-gray-50 rounded-xl space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="text" name="team[{{ $i }}][name]" value="{{ old("team.$i.name", $t['name'] ?? '') }}" placeholder="Full name" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                        <input type="text" name="team[{{ $i }}][role]" value="{{ old("team.$i.role", $t['role'] ?? '') }}" placeholder="Role (e.g. Co-Founder &amp; CEO)" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm" />
                    </div>
                    @if (!empty($t['image_path']))
                        <div class="flex items-center gap-3">
                            <img src="{{ \App\Support\UploadHelper::url($t['image_path']) }}" alt="" class="w-16 h-16 rounded-full object-cover border border-gray-200" />
                            <span class="text-xs text-gray-500">Current photo. Upload a new file below to replace.</span>
                        </div>
                    @endif
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Profile image (optional)</label>
                        <input type="file" name="team_{{ $i }}_image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:bg-primary/10 file:text-primary file:text-sm" />
                    </div>
                    @include('admin.partials.image-alt-field', [
                        'name' => 'team['.$i.'][alt]',
                        'value' => $t['alt'] ?? '',
                    ])
                </div>
                @endforeach
            </div>
        </div>

        {{-- CTA --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary/5 to-transparent">
                <h2 class="text-lg font-semibold text-gray-900">Call to action (bottom block)</h2>
                <p class="text-sm text-gray-500 mt-1">Heading, text, and two buttons (e.g. Become a Seller, Contact Us).</p>
            </div>
            <div class="p-6 space-y-4">
                @php $cta = $sections['cta'] ?? []; $cta = is_array($cta) ? $cta : []; @endphp
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">CTA heading</label>
                    <input type="text" name="cta_heading" value="{{ old('cta_heading', $cta['heading'] ?? 'Join Us on Our Journey') }}" placeholder="Join Us on Our Journey" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">CTA text</label>
                    <textarea name="cta_text" rows="3" placeholder="Short paragraph encouraging visitors to join." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('cta_text', $cta['text'] ?? '') }}</textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Primary button text</label>
                        <input type="text" name="cta_primary_text" value="{{ old('cta_primary_text', $cta['primary_text'] ?? 'Become a Seller') }}" placeholder="Become a Seller" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Primary button URL</label>
                        <input type="text" name="cta_primary_url" value="{{ old('cta_primary_url', $cta['primary_url'] ?? '/sellers') }}" placeholder="/sellers" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Secondary button text</label>
                        <input type="text" name="cta_secondary_text" value="{{ old('cta_secondary_text', $cta['secondary_text'] ?? 'Contact Us') }}" placeholder="Contact Us" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Secondary button URL</label>
                        <input type="text" name="cta_secondary_url" value="{{ old('cta_secondary_url', $cta['secondary_url'] ?? '/contact') }}" placeholder="/contact" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm" />
                    </div>
                </div>
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
            <a href="{{ url('about') }}" target="_blank" rel="noopener" class="ml-auto text-sm text-primary hover:underline">View About page on site →</a>
        </div>
    </form>
</div>
@endsection
