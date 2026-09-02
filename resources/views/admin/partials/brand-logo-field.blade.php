@props([
    'method',
    'logoUrl',
    'hasCustomLogo' => false,
])

<div
    class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-5"
    x-data="{
        logoUrl: @js($logoUrl),
        hasCustom: @js($hasCustomLogo),
        busy: false,
        uploadUrl: @js(route('admin.payment-methods.logo', ['method' => $method])),
        removeUrl: @js(route('admin.payment-methods.logo.remove', ['method' => $method])),
        csrf: @js(csrf_token()),
        pick() {
            if (!this.busy) this.$refs.fileInput.click();
        },
        async upload(event) {
            const file = event.target.files && event.target.files[0];
            event.target.value = '';
            if (!file) return;

            this.busy = true;
            const body = new FormData();
            body.append('logo', file);

            try {
                const res = await fetch(this.uploadUrl, {
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
                this.logoUrl = data.logo_url + (data.logo_url.includes('?') ? '&' : '?') + 't=' + Date.now();
                this.hasCustom = !!data.has_custom_logo;
            } catch (err) {
                alert(err.message || 'Upload failed.');
            } finally {
                this.busy = false;
            }
        },
        async reset() {
            if (!this.hasCustom || this.busy) return;
            if (!confirm('Reset this logo to the default?')) return;

            this.busy = true;
            try {
                const res = await fetch(this.removeUrl, {
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
                this.logoUrl = data.logo_url + (data.logo_url.includes('?') ? '&' : '?') + 't=' + Date.now();
                this.hasCustom = false;
            } catch (err) {
                alert(err.message || 'Reset failed.');
            } finally {
                this.busy = false;
            }
        },
    }"
>
    <div class="shrink-0 flex flex-col items-center">
        <button
            type="button"
            class="group relative w-24 h-24 rounded-full border-4 border-white bg-gray-50 shadow-md flex items-center justify-center overflow-hidden hover:ring-2 hover:ring-primary/25 transition focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:opacity-60"
            title="Click to change logo"
            :disabled="busy"
            @click="pick()"
        >
            <img
                :src="logoUrl"
                alt="Payment method logo"
                class="w-full h-full object-contain p-2"
            />
            <span class="absolute inset-0 flex flex-col items-center justify-center gap-0.5 bg-black/50 text-white text-[11px] font-semibold opacity-0 group-hover:opacity-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Change
            </span>
            <span
                x-show="busy"
                x-cloak
                class="absolute inset-0 flex items-center justify-center bg-white/80 text-primary text-xs font-semibold"
            >…</span>
        </button>
        <input
            type="file"
            x-ref="fileInput"
            class="hidden"
            accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml,.svg"
            @change="upload($event)"
        />
        <p class="text-[10px] text-gray-400 mt-2 text-center">Click to change</p>
        <button
            type="button"
            x-show="hasCustom"
            x-cloak
            class="text-[10px] text-gray-500 hover:text-primary mt-0.5"
            :disabled="busy"
            @click="reset()"
        >Use default</button>
    </div>

    <div class="min-w-0">
        <p class="text-sm font-semibold text-gray-900">Logo</p>
        <p class="text-xs text-gray-500 mt-1">PNG, JPG, WEBP or SVG · max 2 MB</p>
    </div>
</div>
