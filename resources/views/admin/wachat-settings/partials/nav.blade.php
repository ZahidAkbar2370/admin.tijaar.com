@php
    $tabs = [
        ['label' => 'API', 'route' => 'admin.wachat-settings.index', 'match' => 'admin.wachat-settings.index'],
        ['label' => 'Events', 'route' => 'admin.wachat-settings.events', 'match' => 'admin.wachat-settings.events*'],
        ['label' => 'WhatsApp Templates', 'route' => 'admin.whatsapp-templates.index', 'match' => 'admin.whatsapp-templates.*'],
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
