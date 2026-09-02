@extends('admin.layouts.app')

@section('title', 'Courier')

@section('admin-content')
<div
    class="w-full min-w-0"
    x-data="courierAdminPanel({
        csrf: @js(csrf_token()),
        routes: {
            enabled: @js(route('admin.courier.enabled', ['provider' => '__PROVIDER__'])),
            logo: @js(route('admin.courier.logo', ['provider' => '__PROVIDER__'])),
            logoRemove: @js(route('admin.courier.logo.remove', ['provider' => '__PROVIDER__'])),
        },
    })"
>
    <div
        x-show="toast.message"
        x-cloak
        x-transition
        class="mb-6 p-4 rounded-xl text-sm border"
        :class="toast.ok ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'"
        x-text="toast.message"
    ></div>

    <h1 class="text-xl font-bold text-gray-900 mb-1">Courier</h1>
    <p class="text-sm text-gray-500 mb-6">
        Click a courier logo to upload a new one. Toggle availability — changes save instantly.
        Disabled couriers are hidden on the website and app.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($couriers as $courier)
            @php
                $enabled = (bool) $courier['enabled'];
            @endphp
            <article
                data-courier-card="{{ $courier['value'] }}"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col h-full transition-shadow {{ $enabled ? 'ring-1 ring-emerald-200/80' : '' }}"
            >
                <div class="p-5 flex-1 flex flex-col gap-4">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0">
                            <button
                                type="button"
                                class="group relative w-[72px] h-[52px] rounded-xl border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden hover:border-primary/50 hover:ring-2 hover:ring-primary/20 transition focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:opacity-60"
                                title="Click to change logo"
                                :disabled="busyLogo === @js($courier['value'])"
                                @click="pickLogo(@js($courier['value']))"
                            >
                                <img
                                    data-courier-logo="{{ $courier['value'] }}"
                                    src="{{ $courier['logo_url'] }}"
                                    alt="{{ $courier['label'] }}"
                                    class="max-w-full max-h-full object-contain p-1.5"
                                />
                                <span class="absolute inset-0 flex items-center justify-center bg-black/45 text-white text-[10px] font-semibold uppercase tracking-wide opacity-0 group-hover:opacity-100 transition">
                                    Change
                                </span>
                                <span
                                    x-show="busyLogo === @js($courier['value'])"
                                    x-cloak
                                    class="absolute inset-0 flex items-center justify-center bg-white/80 text-primary text-xs font-semibold"
                                >…</span>
                            </button>
                            <input
                                type="file"
                                class="hidden"
                                data-courier-file="{{ $courier['value'] }}"
                                accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml,.svg"
                                @change="uploadLogo($event, @js($courier['value']))"
                            />
                            <p class="text-[10px] text-gray-400 mt-1.5 text-center">Click logo</p>
                            @if ($courier['has_custom_logo'])
                                <button
                                    type="button"
                                    data-reset-logo="{{ $courier['value'] }}"
                                    class="block w-full text-[10px] text-gray-500 hover:text-primary mt-0.5 text-center"
                                    @click="resetLogo(@js($courier['value']))"
                                >Use default</button>
                            @else
                                <button
                                    type="button"
                                    data-reset-logo="{{ $courier['value'] }}"
                                    class="hidden w-full text-[10px] text-gray-500 hover:text-primary mt-0.5 text-center"
                                    @click="resetLogo(@js($courier['value']))"
                                >Use default</button>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <h2 class="text-base font-semibold text-gray-900 leading-snug">{{ $courier['label'] }}</h2>
                                    <p class="text-sm text-gray-500 mt-0.5 line-clamp-2">{{ $courier['description'] ?? $courier['label'] }}</p>
                                </div>
                                <span
                                    data-courier-badge="{{ $courier['value'] }}"
                                    class="flex-shrink-0 inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold {{ $enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}"
                                >{{ $enabled ? 'Enabled' : 'Disabled' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 pt-3 border-t border-gray-100 mt-auto">
                        <span class="text-sm font-medium text-gray-700">Availability</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                class="sr-only peer"
                                data-courier-toggle="{{ $courier['value'] }}"
                                @checked($enabled)
                                :disabled="busyToggle === @js($courier['value'])"
                                @change="toggleEnabled(@js($courier['value']), $event.target.checked, $event.target)"
                            />
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary peer-disabled:opacity-50"></div>
                            <span
                                data-courier-state="{{ $courier['value'] }}"
                                class="ml-3 text-sm font-medium text-gray-700"
                            >{{ $enabled ? 'Enabled' : 'Disabled' }}</span>
                        </label>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('courierAdminPanel', (cfg) => ({
        csrf: cfg.csrf,
        routes: cfg.routes,
        toast: { message: '', ok: true },
        busyToggle: null,
        busyLogo: null,
        toastTimer: null,

        route(name, provider) {
            return String(this.routes[name] || '').replace('__PROVIDER__', encodeURIComponent(provider));
        },

        showToast(message, ok = true) {
            this.toast = { message, ok };
            clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => { this.toast.message = ''; }, 3200);
        },

        setCardEnabled(provider, enabled) {
            const card = document.querySelector(`[data-courier-card="${provider}"]`);
            const badge = document.querySelector(`[data-courier-badge="${provider}"]`);
            const state = document.querySelector(`[data-courier-state="${provider}"]`);
            if (badge) {
                badge.textContent = enabled ? 'Enabled' : 'Disabled';
                badge.className = 'flex-shrink-0 inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold '
                    + (enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500');
            }
            if (state) state.textContent = enabled ? 'Enabled' : 'Disabled';
            if (card) {
                card.classList.toggle('ring-1', enabled);
                card.classList.toggle('ring-emerald-200/80', enabled);
            }
        },

        setLogoUi(provider, logoUrl, hasCustom) {
            const img = document.querySelector(`[data-courier-logo="${provider}"]`);
            const resetBtn = document.querySelector(`[data-reset-logo="${provider}"]`);
            if (img && logoUrl) {
                img.src = logoUrl + (logoUrl.includes('?') ? '&' : '?') + 't=' + Date.now();
            }
            if (resetBtn) {
                resetBtn.classList.toggle('hidden', !hasCustom);
            }
        },

        async toggleEnabled(provider, enabled, input) {
            this.busyToggle = provider;
            try {
                const res = await fetch(this.route('enabled', provider), {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ enabled: enabled ? 1 : 0 }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Could not update courier.');
                }
                this.setCardEnabled(provider, !!data.enabled);
                this.showToast(data.message || 'Saved.');
            } catch (err) {
                if (input) input.checked = !enabled;
                this.setCardEnabled(provider, !enabled);
                this.showToast(err.message || 'Update failed.', false);
            } finally {
                this.busyToggle = null;
            }
        },

        pickLogo(provider) {
            const input = document.querySelector(`[data-courier-file="${provider}"]`);
            if (input) input.click();
        },

        async uploadLogo(event, provider) {
            const file = event.target.files && event.target.files[0];
            event.target.value = '';
            if (!file) return;

            this.busyLogo = provider;
            const body = new FormData();
            body.append('logo', file);

            try {
                const res = await fetch(this.route('logo', provider), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Could not upload logo.');
                }
                this.setLogoUi(provider, data.logo_url, !!data.has_custom_logo);
                this.showToast(data.message || 'Logo updated.');
            } catch (err) {
                this.showToast(err.message || 'Upload failed.', false);
            } finally {
                this.busyLogo = null;
            }
        },

        async resetLogo(provider) {
            if (!confirm('Reset this courier logo to the default?')) return;

            this.busyLogo = provider;
            try {
                const res = await fetch(this.route('logoRemove', provider), {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Could not reset logo.');
                }
                this.setLogoUi(provider, data.logo_url, false);
                this.showToast(data.message || 'Logo reset.');
            } catch (err) {
                this.showToast(err.message || 'Reset failed.', false);
            } finally {
                this.busyLogo = null;
            }
        },
    }));
});
</script>
@endsection
