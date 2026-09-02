@php
    $labels = ['order' => 'Order updates', 'listing' => 'Listing updates', 'message' => 'Messages', 'promotion' => 'Promotions'];
    $channels = \App\Models\NotificationPreference::uiChannels($whatsappChannelOn ?? true);
@endphp
@foreach ($channels as $channel)
    @php $channelPrefs = ($notificationPrefs ?? collect())->where('channel', $channel); @endphp
    @if ($channelPrefs->isNotEmpty())
        <div class="rounded-xl border border-gray-100 p-4">
            <p class="text-sm font-semibold mb-1">{{ \App\Models\NotificationPreference::channelLabel($channel) }}</p>
            @if (in_array($channel, ['push_web', 'push_app'], true))
                <p class="text-xs text-gray-500 mb-3">
                    @if ($channel === 'push_web')
                        Firebase browser notifications when enabled on the website.
                    @else
                        Firebase push on the mobile app (Flutter).
                    @endif
                </p>
            @endif
            @foreach ($channelPrefs as $pref)
                <label class="flex items-center justify-between gap-3 text-sm bg-gray-50 rounded-lg px-3 py-2 mb-2">
                    <span>{{ $labels[$pref->type] ?? $pref->type }}</span>
                    <input type="hidden" name="prefs[{{ $pref->channel }}|{{ $pref->type }}]" value="0">
                    <input type="checkbox" name="prefs[{{ $pref->channel }}|{{ $pref->type }}]" value="1" {{ $pref->enabled ? 'checked' : '' }} class="rounded border-gray-300 text-primary">
                </label>
            @endforeach
        </div>
    @endif
@endforeach
