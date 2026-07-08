@php
    $initial = ['data' => [
        'talent_type_ids' => $brand->creativeNeed?->talentTypes->pluck('id')->all() ?? [],
        'project_types' => $brand->creativeNeed?->projectTypes->pluck('project_type')->all() ?? [],
        'project_frequency' => $brand->creativeNeed?->project_frequency,
        'budget_tier' => $brand->creativeNeed?->budget_tier,
    ]];
@endphp

<x-brand-layout :title="__('Creative needs')">
    <div x-data="brandCreativeNeeds(@js($initial))" class="mx-auto max-w-2xl space-y-6">
        <p class="text-sm text-muted">{{ __('These preferences shape your discovery feed.') }}</p>

        <section class="rounded-xl border border-line bg-surface p-6">
            <x-input-label :value="__('Talent types')" />
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($talentTypes as $type)
                    <button type="button" @click="toggle('talent_type_ids', {{ $type->id }})"
                            :class="data.talent_type_ids.includes({{ $type->id }}) ? 'border-accent bg-accent-weak text-accent-ink' : 'border-line text-muted'"
                            class="rounded-pill border px-3 py-1.5 text-xs">{{ $type->getTranslation('name', app()->getLocale()) }}</button>
                @endforeach
            </div>

            <div class="mt-5"><x-input-label :value="__('Project types')" /></div>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($projectTypes as $pt)
                    <button type="button" @click="toggle('project_types', '{{ $pt }}')"
                            :class="data.project_types.includes('{{ $pt }}') ? 'border-accent bg-accent-weak text-accent-ink' : 'border-line text-muted'"
                            class="rounded-pill border px-3 py-1.5 text-xs">{{ __(ucfirst(str_replace('_',' ',$pt))) }}</button>
                @endforeach
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label :value="__('Frequency')" />
                    <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="data.project_frequency">
                        <option :value="null">—</option>
                        @foreach (['occasional','monthly','weekly','ongoing'] as $opt)<option value="{{ $opt }}">{{ __(ucfirst($opt)) }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <x-input-label :value="__('Budget tier')" />
                    <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="data.budget_tier">
                        <option :value="null">—</option>
                        @foreach (['under_500'=>'Under $500','500_2000'=>'$500–2k','2000_10000'=>'$2k–10k','10000_plus'=>'$10k+'] as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                    </select>
                </div>
            </div>

            <div class="mt-5 flex items-center gap-3">
                <button @click="save()" :disabled="saving" class="rounded-pill bg-primary px-5 py-2 text-sm text-on-primary disabled:opacity-50">{{ __('Save preferences') }}</button>
                <span x-show="saved" x-cloak class="text-xs text-ok">{{ __('Saved') }}</span>
            </div>
        </section>
    </div>
</x-brand-layout>
