@php $initial = [
    'talentTypes' => $talentTypes,
    'locale' => app()->getLocale(),
    't' => [
        'universal' => __('Every profile'),
        'byCategory' => __('Only'),
        'bySkill' => __('By skill'),
        'inline' => __('inline'),
        'table' => __('table'),
        'categories' => ['model' => __('Modeling'), 'crew' => __('Crew'), 'creative' => __('Creative')],
    ],
]; @endphp

<x-admin-layout :title="__('Block catalog')">
    <div x-data="adminBlocks(@js($initial))" class="space-y-5">
        {{-- Summary + add --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <p class="max-w-3xl text-sm text-muted">{{ __('The building blocks talent profiles are made of. This page decides which blocks exist and who may use them; which ones each skill starts with, and in what order, lives on Skills templates.') }}</p>
            <button type="button" @click="openNew()" class="inline-flex h-11 items-center gap-2 rounded-pill bg-primary px-5 text-sm font-semibold text-on-primary hover:opacity-90">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>{{ __('Add block type') }}</button>
        </div>
        <div class="grid gap-3 sm:grid-cols-3" x-show="!loading">
            <div class="rounded-[16px] border border-line bg-surface px-5 py-4"><p class="text-sm font-semibold text-muted">{{ __('Active blocks') }}</p><p class="mt-1 font-display text-3xl text-ink" x-text="rows.filter(r => r.is_active).length"></p></div>
            <div class="rounded-[16px] border border-line bg-surface px-5 py-4"><p class="text-sm font-semibold text-muted">{{ __('Retired') }}</p><p class="mt-1 font-display text-3xl text-ink" x-text="rows.filter(r => !r.is_active).length"></p></div>
            <div class="rounded-[16px] border border-line bg-surface px-5 py-4"><p class="text-sm font-semibold text-muted">{{ __('Placed on profiles') }}</p><p class="mt-1 font-display text-3xl text-ink" x-text="rows.reduce((n, r) => n + (r.in_use_count || 0), 0)"></p></div>
        </div>

        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-2 rounded-[20px] border border-line bg-surface p-2.5">
            <div class="relative min-w-[200px] flex-1">
                <svg class="pointer-events-none absolute inset-y-0 start-4 my-auto h-[18px] w-[18px] text-subtle" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                <input type="search" x-model="search" placeholder="{{ __('Search blocks…') }}" class="h-11 w-full rounded-pill border-line bg-bg ps-11 pe-4 text-[15px] text-ink" aria-label="{{ __('Search') }}">
            </div>
            <div class="inline-flex flex-wrap rounded-pill border border-line bg-bg p-1" role="group">
                @foreach (['all' => __('All'), 'universal' => __('Profile-wide'), 'scoped' => __('Skill-specific'), 'inactive' => __('Retired')] as $f => $label)
                    <button type="button" @click="filter = '{{ $f }}'" :class="filter === '{{ $f }}' ? 'bg-primary text-on-primary' : 'text-muted hover:text-ink'" class="rounded-pill px-3.5 py-1.5 text-sm font-semibold transition">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <template x-if="loading"><div><x-admin.skeleton :rows="5" /></div></template>

        {{-- Catalog cards --}}
        <div x-show="!loading" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <template x-for="row in shown" :key="row.id">
                <article class="flex flex-col rounded-[20px] border bg-surface p-5 transition hover:shadow-e2" :class="row.is_active ? 'border-line' : 'border-dashed border-line-strong bg-bg'">
                    <div class="flex items-start gap-3.5">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-[12px]" :class="row.is_active ? 'bg-accent-weak text-accent-ink' : 'bg-elevated text-subtle'">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true" x-html="icon(row.key)"></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-[17px] font-semibold text-ink" x-text="blockName(row)"></h3>
                            <p class="truncate font-mono text-xs text-subtle" x-text="row.key"></p>
                        </div>
                        <button type="button" role="switch" class="relative mt-1 inline-flex h-6 w-11 shrink-0 rounded-pill transition" :class="row.is_active ? 'bg-accent' : 'bg-line-strong'"
                                :aria-checked="row.is_active.toString()" :aria-label="(row.is_active ? '{{ __('Retire') }} ' : '{{ __('Activate') }} ') + blockName(row)" @click="toggle(row)">
                            <span class="absolute top-0.5 h-5 w-5 rounded-pill bg-white shadow transition-all" :class="row.is_active ? 'start-[22px]' : 'start-0.5'"></span>
                        </button>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
                        <div class="rounded-[12px] bg-bg px-3 py-2"><dt class="text-xs font-semibold text-subtle">{{ __('Available to') }}</dt><dd class="mt-0.5 font-medium text-ink" x-text="availabilityLabel(row)"></dd></div>
                        <div class="rounded-[12px] bg-bg px-3 py-2"><dt class="text-xs font-semibold text-subtle">{{ __('Holds') }}</dt><dd class="mt-0.5 font-medium text-ink" x-text="row.content_source === 'table' ? '{{ __('Its own content') }}' : '{{ __('Settings only') }}'"></dd></div>
                    </dl>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-pill bg-elevated px-2.5 py-1 font-semibold text-muted" x-text="(row.in_use_count || 0) + ' {{ __('profiles') }}'"></span>
                        <template x-if="row.is_repeatable"><span class="rounded-pill bg-elevated px-2.5 py-1 font-semibold text-muted">{{ __('Once per tab') }}</span></template>
                        <template x-if="!row.is_active"><span class="rounded-pill bg-danger-weak px-2.5 py-1 font-semibold text-danger">{{ __('Retired') }}</span></template>
                    </div>
                    <p x-show="!row.is_active && row.in_use_count" x-cloak class="mt-3 text-xs text-muted">{{ __('Profiles that have it keep showing it; it is no longer offered for new placements.') }}</p>
                    <div class="mt-auto flex justify-end pt-4">
                        <button type="button" @click="edit(row)" class="inline-flex h-9 items-center gap-1.5 rounded-pill border border-line px-4 text-[13px] font-semibold text-ink hover:border-ink">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4L19 9l-4-4L4 16v4Z"/></svg>{{ __('Edit') }}</button>
                    </div>
                </article>
            </template>
        </div>
        <template x-if="!loading && !shown.length"><p class="rounded-[20px] border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('No blocks match.') }}</p></template>

        <x-admin.pagination />

        {{-- Editor (create / edit) — a modal --}}
        <template x-teleport="body">
        <div x-show="editorOpen" x-cloak class="fixed inset-0 z-[70] grid place-items-center overflow-y-auto p-4" data-surface="fama-admin" role="dialog" aria-modal="true" @keydown.escape.window="editorOpen && reset()">
        <div class="absolute inset-0" style="background: var(--scrim)" @click="reset()"></div>
        <div class="relative w-full max-w-3xl">
        <x-ui.card>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-display text-lg" x-text="form.id ? '{{ __('Edit block type') }}' : '{{ __('Add block type') }}'"></h3>
                <button @click="reset()" class="grid h-9 w-9 place-items-center rounded-pill border border-line text-ink hover:border-ink" aria-label="{{ __('Close') }}">✕</button>
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
                        <span x-show="form.id && form.in_use_count > 0" x-cloak class="rounded-full border border-line px-1.5 py-px text-[10px] font-medium uppercase tracking-wide text-subtle" title="{{ __('Locked while in use') }}">{{ __('Locked') }}</span>
                    </span>
                    <x-text-input class="mt-1 block w-full font-mono {{ $lockedField }}" x-model="form.key" x-bind:disabled="form.id && form.in_use_count > 0" />
                    <template x-if="errors['key']"><p class="mt-1 text-xs text-danger" x-text="errors['key'][0]"></p></template>
                </div>
                <div><x-input-label :value="__('Name')" /><x-text-input class="mt-1 block w-full" x-model="form.name.en" />
                    <template x-if="errors['name.en']"><p class="mt-1 text-xs text-danger" x-text="errors['name.en'][0]"></p></template>
                </div>
                {{-- Arabic input hidden while the app is English-only (restore for the AR pass).
                <div><x-input-label :value="__('Name (AR)')" /><x-text-input class="mt-1 block w-full" dir="rtl" x-model="form.name.ar" /></div>
                --}}

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
                        <span x-show="form.id && form.in_use_count > 0" x-cloak class="rounded-full border border-line px-1.5 py-px text-[10px] font-medium uppercase tracking-wide text-subtle" title="{{ __('Locked while in use') }}">{{ __('Locked') }}</span>
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
        </div>
        </template>
    </div>
</x-admin-layout>
