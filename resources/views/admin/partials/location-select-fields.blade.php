@php
    use App\Services\LocationService;

    $locationTree = LocationService::publicTree();
    $locationInputClass = $inputClass ?? 'w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500';
    $locationCountryName = $countryName ?? 'country';
    $locationStateName = $stateName ?? 'state';
    $locationCityName = $cityName ?? 'city';
    $locationCountryValue = old($locationCountryName, $countryValue ?? LocationService::defaultCountryName());
    $locationStateValue = old($locationStateName, $stateValue ?? '');
    $locationCityValue = old($locationCityName, $cityValue ?? '');
    $locationRequired = $required ?? true;
    $locationShowCountry = $showCountry ?? false;
    $locationGridClass = $gridClass ?? 'grid grid-cols-1 sm:grid-cols-2 gap-3';
@endphp

<div
    class="contents"
    x-data="adminLocationFields(@js([
        'tree' => $locationTree,
        'countryName' => $locationCountryName,
        'stateName' => $locationStateName,
        'cityName' => $locationCityName,
        'country' => $locationCountryValue,
        'state' => $locationStateValue,
        'city' => $locationCityValue,
        'inputClass' => $locationInputClass,
        'required' => $locationRequired,
        'showCountry' => $locationShowCountry,
    ]))"
>
    <template x-if="showCountry">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Country</label>
            <select
                :name="countryName"
                x-model="countryValue"
                @change="onCountryChange()"
                :class="inputClass"
                :required="required"
            >
                <template x-for="c in tree" :key="'c-' + c.id">
                    <option :value="c.name" x-text="c.name"></option>
                </template>
            </select>
        </div>
    </template>
    <template x-if="!showCountry">
        <input type="hidden" :name="countryName" :value="countryValue">
    </template>

    <div class="{{ $locationGridClass }} contents">
        <div>
            <label class="block text-xs text-gray-500 mb-1">
                Province / State @if($locationRequired)<span class="text-red-500">*</span>@endif
            </label>
            <select
                :name="stateName"
                x-model="stateValue"
                @change="onProvinceChange()"
                :class="inputClass"
                :required="required"
            >
                <option value="">Select province</option>
                <template x-for="p in provinces" :key="'p-' + p.id">
                    <option :value="p.name" x-text="p.name"></option>
                </template>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">
                City @if($locationRequired)<span class="text-red-500">*</span>@endif
            </label>
            <select
                :name="cityName"
                x-model="cityValue"
                :class="inputClass"
                :required="required"
                :disabled="!stateValue"
            >
                <option value="">Select city</option>
                <template x-for="c in cities" :key="'city-' + c.id">
                    <option :value="c.name" x-text="c.name"></option>
                </template>
            </select>
        </div>
    </div>

    <template x-if="!showCountry">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Country</label>
            <div :class="inputClass + ' bg-gray-50 text-gray-700'" x-text="countryValue"></div>
        </div>
    </template>
</div>
