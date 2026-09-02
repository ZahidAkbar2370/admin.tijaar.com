@php
    $tabs = [
        ['label' => 'Overview', 'route' => 'admin.email-settings.index', 'match' => 'admin.email-settings.index'],
        ['label' => 'SMTP', 'route' => 'admin.email-settings.smtp', 'match' => 'admin.email-settings.smtp*'],
        ['label' => 'Email Events', 'route' => 'admin.email-settings.events', 'match' => 'admin.email-settings.events*'],
        ['label' => 'Email Templates', 'route' => 'admin.email-templates.index', 'match' => 'admin.email-templates.*'],
    ];
@endphp
<div class="flex flex-wrap gap-2 mb-6">
    @foreach ($tabs as $tab)
        @php $active = request()->routeIs($tab['match']); @endphp
        <a href="{{ route($tab['route']) }}"
           class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold border transition {{ $active ? 'bg-primary text-white border-primary' : 'bg-white text-gray-700 border-gray-200 hover:border-primary/40 hover:text-primary' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
