@php
    $initial = [
        'settings' => $settings,
        'flows' => $flows->map(fn ($f) => ['id' => $f->id, 'name' => $f->name])->values(),
    ];
@endphp

<x-admin-layout :title="__('Settings')">
    <div x-data="adminSettings(@js($initial))" class="mx-auto max-w-2xl space-y-6">
        <x-ui.card class="space-y-4">
            <h3 class="font-display text-lg">{{ __('Platform globals') }}</h3>
            <div>
                <x-input-label :value="__('Default currency')" />
                <x-text-input class="mt-1 block w-full" x-model="form.default_currency" maxlength="3" />
            </div>
            <div>
                <x-input-label :value="__('Default deal flow')" />
                <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model.number="form.default_deal_flow_id">
                    <option :value="null">—</option>
                    <template x-for="flow in flows" :key="flow.id"><option :value="flow.id" x-text="flow.name"></option></template>
                </select>
            </div>
        </x-ui.card>

        <x-ui.card class="space-y-3">
            <h3 class="font-display text-lg">{{ __('Feature flags') }}</h3>
            @foreach (['brand_initiated_deals' => 'Brand-initiated deals', 'public_brand_pages' => 'Public brand pages', 'talent_discovery' => 'Talent discovery'] as $flag => $label)
                <label class="flex items-center justify-between text-sm">
                    <span>{{ __($label) }}</span>
                    <input type="checkbox" x-model="form.feature_flags['{{ $flag }}']" class="rounded border-line">
                </label>
            @endforeach
        </x-ui.card>

        <div class="flex items-center gap-3">
            <x-ui.button variant="primary" x-on:click="save()" x-bind:disabled="saving">{{ __('Save settings') }}</x-ui.button>
            <span x-show="saved" x-cloak class="text-xs text-ok">{{ __('Saved') }}</span>
        </div>
    </div>
</x-admin-layout>
