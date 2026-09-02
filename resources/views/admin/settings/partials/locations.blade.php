<div class="locations-admin" x-data="locationsAdminPanel({
    countriesUrl: '{{ route('admin.locations.countries.index') }}',
    countriesStore: '{{ route('admin.locations.countries.store') }}',
    provincesUrl: '{{ route('admin.locations.provinces.index') }}',
    provincesStore: '{{ route('admin.locations.provinces.store') }}',
    citiesUrl: '{{ route('admin.locations.cities.index') }}',
    citiesStore: '{{ route('admin.locations.cities.store') }}',
    importLeopardsUrl: '{{ route('admin.locations.cities.import-leopards') }}',
    syncLeopardsIdsUrl: '{{ route('admin.locations.cities.sync-leopards-ids') }}',
    csrf: '{{ csrf_token() }}'
})" x-init="init()">
    <p class="text-sm text-gray-600 mb-4">Manage countries, provinces, and cities used in checkout and seller store forms. Import Leopards cities into a province to link courier rates and avoid typos.</p>

    <div class="flex flex-wrap gap-2 mb-4">
        <button type="button" @click="section = 'countries'" :class="section === 'countries' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-2 rounded-lg text-sm font-medium">Countries</button>
        <button type="button" @click="section = 'provinces'" :class="section === 'provinces' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-2 rounded-lg text-sm font-medium">Provinces</button>
        <button type="button" @click="section = 'cities'" :class="section === 'cities' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-2 rounded-lg text-sm font-medium">Cities</button>
    </div>

    <div x-show="message" x-cloak class="mb-4 p-3 rounded-xl text-sm" :class="isSuccess ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">
        <span x-text="message"></span>
    </div>

    <div x-show="section === 'countries'" x-cloak>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4 p-4 bg-gray-50 rounded-xl">
            <input type="text" x-model="countryForm.name" placeholder="Country name *" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
            <input type="text" x-model="countryForm.code" placeholder="Code (PK)" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
            <input type="number" x-model.number="countryForm.sort_order" placeholder="Sort" min="0" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
            <button type="button" @click="saveCountry()" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium" x-text="countryForm.id ? 'Update country' : 'Add country'"></button>
        </div>
        <div class="border border-gray-200 rounded-xl overflow-hidden max-h-80 overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 sticky top-0"><tr><th class="text-left px-3 py-2">Name</th><th class="text-left px-3 py-2">Code</th><th class="text-left px-3 py-2">Active</th><th class="px-3 py-2"></th></tr></thead>
                <tbody>
                    <template x-for="row in countries" :key="row.id">
                        <tr class="border-t border-gray-100">
                            <td class="px-3 py-2" x-text="row.name"></td>
                            <td class="px-3 py-2" x-text="row.code || '—'"></td>
                            <td class="px-3 py-2" x-text="row.is_active ? 'Yes' : 'No'"></td>
                            <td class="px-3 py-2 text-right space-x-2">
                                <button type="button" @click="editCountry(row)" class="text-primary text-xs">Edit</button>
                                <button type="button" @click="deleteCountry(row)" class="text-red-600 text-xs">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="section === 'provinces'" x-cloak>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4 p-4 bg-gray-50 rounded-xl">
            <select x-model="provinceForm.country_id" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                <option value="">Country *</option>
                <template x-for="c in countries" :key="'p-c-'+c.id"><option :value="c.id" x-text="c.name"></option></template>
            </select>
            <input type="text" x-model="provinceForm.name" placeholder="Province name *" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
            <input type="number" x-model.number="provinceForm.sort_order" placeholder="Sort" min="0" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
            <button type="button" @click="saveProvince()" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium" x-text="provinceForm.id ? 'Update province' : 'Add province'"></button>
        </div>
        <div class="border border-gray-200 rounded-xl overflow-hidden max-h-80 overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 sticky top-0"><tr><th class="text-left px-3 py-2">Province</th><th class="text-left px-3 py-2">Country</th><th class="px-3 py-2"></th></tr></thead>
                <tbody>
                    <template x-for="row in provinces" :key="row.id">
                        <tr class="border-t border-gray-100">
                            <td class="px-3 py-2" x-text="row.name"></td>
                            <td class="px-3 py-2" x-text="row.country?.name || '—'"></td>
                            <td class="px-3 py-2 text-right space-x-2">
                                <button type="button" @click="editProvince(row)" class="text-primary text-xs">Edit</button>
                                <button type="button" @click="deleteProvince(row)" class="text-red-600 text-xs">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="section === 'cities'" x-cloak>
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3 mb-4 p-4 bg-gray-50 rounded-xl">
            <select x-model="cityForm.province_id" class="px-3 py-2 border border-gray-200 rounded-lg text-sm md:col-span-2">
                <option value="">Province *</option>
                <template x-for="p in provinces" :key="'c-p-'+p.id"><option :value="p.id" x-text="(p.country?.name ? p.country.name + ' — ' : '') + p.name"></option></template>
            </select>
            <input type="text" x-model="cityForm.name" placeholder="City name *" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
            <input type="text" x-model="cityForm.leopards_city_id" placeholder="Leopards city ID" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
            <button type="button" @click="saveCity()" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium" x-text="cityForm.id ? 'Update city' : 'Add city'"></button>
        </div>
        <div class="flex flex-wrap gap-3 mb-4">
            <button type="button" @click="syncLeopardsIds()" :disabled="importing" class="px-4 py-2 bg-emerald-700 text-white rounded-lg text-sm disabled:opacity-50">
                <span x-text="importing ? 'Working…' : 'Sync Leopards IDs (existing cities)'"></span>
            </button>
            <select x-model="importProvinceId" class="px-3 py-2 border border-gray-200 rounded-lg text-sm min-w-[200px]">
                <option value="">Import all 800+ into province…</option>
                <template x-for="p in provinces" :key="'imp-'+p.id"><option :value="p.id" x-text="(p.country?.name ? p.country.name + ' — ' : '') + p.name"></option></template>
            </select>
            <button type="button" @click="importLeopards()" :disabled="importing || !importProvinceId" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm disabled:opacity-50">
                <span x-text="importing ? 'Importing…' : 'Bulk import to province'"></span>
            </button>
        </div>
        <p class="text-xs text-gray-500 mb-4">Use <strong>Sync Leopards IDs</strong> first to link Lahore, Karachi, etc. without duplicating cities. Bulk import adds every Leopards city into one province (usually not needed).</p>
        <div class="border border-gray-200 rounded-xl overflow-hidden max-h-96 overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 sticky top-0"><tr><th class="text-left px-3 py-2">City</th><th class="text-left px-3 py-2">Province</th><th class="text-left px-3 py-2">Leopards ID</th><th class="px-3 py-2"></th></tr></thead>
                <tbody>
                    <template x-for="row in cities" :key="row.id">
                        <tr class="border-t border-gray-100">
                            <td class="px-3 py-2" x-text="row.name"></td>
                            <td class="px-3 py-2" x-text="row.province?.name || '—'"></td>
                            <td class="px-3 py-2" x-text="row.leopards_city_id || '—'"></td>
                            <td class="px-3 py-2 text-right space-x-2">
                                <button type="button" @click="editCity(row)" class="text-primary text-xs">Edit</button>
                                <button type="button" @click="deleteCity(row)" class="text-red-600 text-xs">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
