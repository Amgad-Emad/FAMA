@php $initial = [
    'talentTypes' => $talentTypes,
    'locale' => app()->getLocale(),
    't' => [
        'universal' => __('Universal'),
        'byCategory' => __('By category'),
        'bySkill' => __('By skill'),
        'inline' => __('inline'),
        'table' => __('table'),
        'categories' => ['model' => __('Modeling'), 'crew' => __('Crew'), 'creative' => __('Creative')],
    ],
]; @endphp

<x-admin-layout :title="__('Block catalog')">
    <div x-data="adminBlocks(@js($initial))" class="space-y-5">
        <p class="text-sm text-muted">{{ __('The building blocks talent profiles are assembled from. This page owns which blocks exist and who may use them; per-skill preselection and order live on the Skills page.') }}</p>

        <template x-if="loading"><div><x-admin.skeleton :rows="5" /></div></template>

        {{-- Catalog rows --}}
        <div x-show="!loading" class="space-y-2">
            <template x-for="row in rows" :key="row.id">
                <div class="rounded-lg border border-line bg-surface">
                    <div class="flex flex-wrap items-center gap-3 px-4 py-3 text-sm">
                        <span class="font-medium text-ink" x-text="blockName(row)"></span>
                        <span class="rounded-pill bg-elevated px-2 py-0.5 font-mono text-[10px] text-muted" x-text="row.key"></span>
                        <span class="rounded-pill bg-elevated px-2 py-0.5 text-xs text-muted" x-text="availabilityLabel(row)"></span>
                        <span class="rounded-pill bg-elevated px-2 py-0.5 text-xs text-muted" x-text="sourceLabel(row.content_source)"></span>
                        <template x-if="row.is_repeatable"><span class="rounded-pill bg-elevated px-2 py-0.5 text-xs text-muted">{{ __('once per scope') }}</span></template>
                        <template x-if="row.in_use_count"><span class="rounded-pill bg-elevated px-2 py-0.5 text-xs text-muted"><span x-text="row.in_use_count"></span> {{ __('in use') }}</span></template>
                        <template x-if="!row.is_active"><span class="rounded-pill bg-danger/10 px-2 py-0.5 text-xs text-danger">{{ __('inactive') }}</span></template>

                        <div class="ms-auto flex gap-1.5">
                            <button @click="edit(row)" class="rounded-pill border border-line px-2.5 py-1 text-xs text-muted hover:text-ink">{{ __('Edit') }}</button>
                            <button @click="toggle(row)"
                                    :class="row.is_active ? 'text-muted' : 'text-accent-ink'"
                                    class="rounded-pill border border-line px-2.5 py-1 text-xs"
                                    x-text="row.is_active ? '{{ __('Deactivate') }}' : '{{ __('Activate') }}'"></button>
                        </div>
                    </div>
                    {{-- Grandfathering note when retired but in use --}}
                    <template x-if="!row.is_active && row.in_use_count">
                        <p class="border-t border-line px-4 py-2 text-xs text-muted">{{ __('Existing profiles keep rendering this block; it is no longer offered for new placements.') }}</p>
                    </template>
                </div>
            </template>
            <template x-if="!rows.length"><p class="rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('The block catalog is empty.') }}</p></template>
        </div>

        <x-admin.pagination />

        {{-- Editor (create / edit) --}}
        <x-ui.card>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-display text-lg" x-text="form.id ? '{{ __('Edit block type') }}' : '{{ __('Add block type') }}'"></h3>
                <button x-show="form.id" @click="reset()" class="text-xs text-muted hover:text-ink">{{ __('Cancel edit') }}</button>
            </div>

            {{-- Structural guard-rail notice --}}
            <template x-if="form.id && form.in_use_count > 0">
                <p class="mb-3 rounded-md border border-line bg-elevated px-3 py-2 text-xs text-muted">
                    {{ __('This block is in use — its key and content source are locked, and narrowing who can use it only affects new placements (existing profiles are grandfathered).') }}
                </p>
            </template>

            <div class="grid gap-3 sm:grid-cols-3">
                @php $lockedField = 'disabled:cursor-not-allowed disabled:opacity-60 disabled:bg-elevated'; @endphp
                <div>
                    <span class="flex items-center gap-1.5">
                        <x-input-label :value="__('Key')" />
                        <span x-show="form.id && form.in_use_count > 0" x-cloak class="text-subtle" title="{{ __('Locked while in use') }}">🔒</span>
                    </span>
                    <x-text-input class="mt-1 block w-full font-mono {{ $lockedField }}" x-model="form.key" x-bind:disabled="form.id && form.in_use_count > 0" />
                    <template x-if="errors['key']"><p class="mt-1 text-xs text-danger" x-text="errors['key'][0]"></p></template>
                </div>
                <div><x-input-label :value="__('Name (EN)')" /><x-text-input class="mt-1 block w-full" x-model="form.name.en" />
                    <template x-if="errors['name.en']"><p class="mt-1 text-xs text-danger" x-text="errors['name.en'][0]"></p></template>
                </div>
                <div><x-input-label :value="__('Name (AR)')" /><x-text-input class="mt-1 block w-full" dir="rtl" x-model="form.name.ar" /></div>

                <div>
                    <x-input-label :value="__('Availability')" />
                    <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="form.availability">
                        <option value="universal">{{ __('Universal') }}</option>
                        <option value="by_category">{{ __('By category') }}</option>
                        <option value="by_type">{{ __('By skill') }}</option>
                    </select>
                </div>
                <div>
                    <span class="flex items-center gap-1.5">
                        <x-input-label :value="__('Content source')" />
                        <span x-show="form.id && form.in_use_count > 0" x-cloak class="text-subtle" title="{{ __('Locked while in use') }}">🔒</span>
                    </span>
                    <select class="mt-1 block w-full rounded-md border-line bg-bg {{ $lockedField }}" x-model="form.content_source" x-bind:disabled="form.id && form.in_use_count > 0">
                        <option value="inline">{{ __('inline') }}</option>
                        <option value="table">{{ __('table') }}</option>
                    </select>
                    <p x-show="form.id && form.in_use_count > 0" x-cloak class="mt-1 text-xs text-muted">{{ __('Locked because talents already use this block.') }}</p>
                </div>
                <div>
                    <x-input-label :value="__('Default layout')" />
                    <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="form.default_layout">
                        <option value="">{{ __('None') }}</option>
                        @foreach (['grid', 'carousel', 'list', 'masonry'] as $layout)<option value="{{ $layout }}">{{ $layout }}</option>@endforeach
                    </select>
                </div>
            </div>

            {{-- Category gates --}}
            <div x-show="form.availability === 'by_category'" x-cloak class="mt-3">
                <x-input-label :value="__('Eligible categories')" />
                <div class="mt-1 flex gap-2">
                    @foreach (['model' => __('Modeling'), 'crew' => __('Crew'), 'creative' => __('Creative')] as $cat => $catLabel)
                        <button type="button" @click="toggleIn(form.categories, '{{ $cat }}')"
                                :class="form.categories.includes('{{ $cat }}') ? 'border-accent bg-accent-weak text-accent-ink' : 'border-line text-muted'"
                                class="rounded-pill border px-3 py-1.5 text-xs">{{ $catLabel }}</button>
                    @endforeach
                </div>
                <template x-if="errors['categories']"><p class="mt-1 text-xs text-danger" x-text="errors['categories'][0]"></p></template>
            </div>

            {{-- Skill gates --}}
            <div x-show="form.availability === 'by_type'" x-cloak class="mt-3">
                <x-input-label :value="__('Eligible skills')" />
                <div class="mt-1 flex flex-wrap gap-2">
                    <template x-for="type in talentTypes" :key="type.id">
                        <button type="button" @click="toggleIn(form.talent_type_ids, type.id)"
                                :class="form.talent_type_ids.includes(type.id) ? 'border-accent bg-accent-weak text-accent-ink' : 'border-line text-muted'"
                                class="rounded-pill border px-3 py-1.5 text-xs" x-text="type.name"></button>
                    </template>
                </div>
                <template x-if="errors['talent_type_ids']"><p class="mt-1 text-xs text-danger" x-text="errors['talent_type_ids'][0]"></p></template>
            </div>

            <div class="mt-3 flex flex-wrap gap-4 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" x-model="form.is_repeatable" class="rounded border-line"> {{ __('Repeatable (once per scope)') }}</label>
                <label class="flex items-center gap-2"><input type="checkbox" x-model="form.is_active" class="rounded border-line"> {{ __('Active') }}</label>
            </div>

            <div class="mt-3">
                <x-input-label :value="__('Settings schema (JSON)')" />
                <textarea x-model="form.settings_schema" rows="3" dir="ltr" class="mt-1 block w-full rounded-md border-line bg-bg font-mono text-xs" placeholder='{"fields": []}'></textarea>
                <template x-if="errors['settings_schema']"><p class="mt-1 text-xs text-danger" x-text="errors['settings_schema'][0]"></p></template>
            </div>

            <template x-if="errors['_']"><p class="mt-2 text-xs text-danger" x-text="errors['_'][0]"></p></template>
            <x-ui.button variant="accent" class="mt-4" x-on:click="save()" x-text="form.id ? '{{ __('Save changes') }}' : '{{ __('Create block type') }}'"></x-ui.button>
        </x-ui.card>
    </div>
</x-admin-layout>
