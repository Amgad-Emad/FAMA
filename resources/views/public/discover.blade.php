<x-public-layout :title="__('Discover talent')">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6"
         x-data="talentSearch({
            types: @js($types),
            equipmentCategories: @js($equipmentCategories),
            softwareOptions: @js($softwareOptions),
            lookOptions: @js($lookOptions),
            scopeLabels: { model: '{{ __('Modeling') }}', crew: '{{ __('Crew') }}', creative: '{{ __('Creative') }}' },
         })">

        <header class="mb-6">
            <x-ui.eyebrow>{{ __('Discovery') }}</x-ui.eyebrow>
            <h1 class="mt-1 font-display text-4xl text-ink">{{ __('Find creative talent') }}</h1>
        </header>

        {{-- PRIMARY control: Skills — sticky under the site header while results scroll. --}}
        <section class="sticky top-16 z-20 mb-6 rounded-lg border border-line bg-surface shadow-e1">
            <div class="p-4 sm:p-5">
                {{-- Header row: heading + selected count · secondary search + advanced trigger --}}
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2.5">
                        <h2 class="font-display text-lg text-ink">{{ __('Skills') }}</h2>
                        <span x-show="selectedSkillCount" x-cloak
                              class="grid h-5 min-w-[1.25rem] place-items-center rounded-pill bg-accent-weak px-1.5 text-[11px] font-semibold text-accent-ink"
                              x-text="selectedSkillCount"></span>
                    </div>

                    <div class="flex items-center gap-2">
                        {{-- SECONDARY free-text search (de-emphasised) --}}
                        <div class="relative w-full sm:w-52">
                            <label for="discover-q" class="sr-only">{{ __('Search by name') }}</label>
                            <svg class="pointer-events-none absolute inset-y-0 start-2.5 my-auto h-3.5 w-3.5 text-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                            <input id="discover-q" x-model="filters.q" @input.debounce.400ms="search(1, { replace: true })" type="text" placeholder="{{ __('Search by name…') }}"
                                   class="w-full rounded-pill border-line bg-elevated py-1.5 ps-8 pe-3 text-xs text-ink shadow-sm focus:border-accent focus:ring-accent">
                        </div>

                        {{-- Advanced filters trigger (opens the modal) --}}
                        <button type="button" @click="openFilters()"
                                class="relative inline-flex shrink-0 items-center gap-2 rounded-pill border border-line-strong px-3 py-1.5 text-xs font-medium text-ink transition hover:bg-elevated focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-surface">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                            <span class="hidden sm:inline">{{ __('Advanced filters') }}</span>
                            <span class="sm:hidden">{{ __('Filters') }}</span>
                            <span x-show="activeFilterCount > 0" x-cloak
                                  class="grid h-5 min-w-[1.25rem] place-items-center rounded-pill bg-accent px-1 text-[10px] font-semibold text-on-accent"
                                  x-text="activeFilterCount"></span>
                        </button>
                    </div>
                </div>

                {{-- Skill chips: "All" beside the scope groups, on one line (shared partial). --}}
                <div class="mt-4">
                    @include('public.partials.skill-filter-chips', ['nowrap' => true])
                </div>

                {{-- Active-filter summary row (removable chips + clear all) --}}
                <div x-show="activeSummary.length" x-cloak class="mt-4 flex flex-wrap items-center gap-2 border-t border-line pt-3">
                    <span class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Active') }}</span>
                    <template x-for="item in activeSummary" :key="item.kind + ':' + item.value">
                        <button type="button" @click="removeFilter(item)"
                                class="inline-flex items-center gap-1.5 rounded-pill border border-line-strong bg-elevated px-2.5 py-1 text-xs text-ink transition hover:border-danger hover:text-danger focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                            <span x-text="item.label"></span>
                            <span aria-hidden="true">✕</span>
                            <span class="sr-only">{{ __('Remove filter') }}</span>
                        </button>
                    </template>
                    <button type="button" @click="clearAll()"
                            class="ms-1 text-xs text-accent-ink underline hover:no-underline focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">{{ __('Clear all') }}</button>
                </div>
            </div>
        </section>

        {{-- Results --}}
        <div>
            <div class="mb-4">
                <p class="text-sm text-muted" aria-live="polite">
                    <span x-show="loading">{{ __('Searching…') }}</span>
                    <span x-show="!loading" x-cloak>
                        <span class="font-semibold text-ink" x-text="resultTotal"></span>
                        <span x-show="resultTotal === 1">{{ __('talent') }}</span>
                        <span x-show="resultTotal !== 1">{{ __('talents') }}</span>
                    </span>
                </p>
            </div>

            {{-- Skeleton loaders (mirror the card shape) --}}
            <div x-show="loading" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <template x-for="n in skeletons" :key="n">
                    <div class="overflow-hidden rounded-lg border border-line bg-surface">
                        <div class="aspect-[4/3] animate-pulse bg-elevated"></div>
                        <div class="space-y-2 p-4">
                            <div class="h-2.5 w-16 animate-pulse rounded bg-elevated"></div>
                            <div class="h-4 w-32 animate-pulse rounded bg-elevated"></div>
                            <div class="h-3 w-40 animate-pulse rounded bg-elevated"></div>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="!loading" x-cloak class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <template x-for="talent in results" :key="talent.slug">
                    <a :href="'/' + talent.slug" class="group flex flex-col overflow-hidden rounded-lg border border-line bg-surface transition hover:shadow-e2">
                        <div class="relative aspect-[4/3]">
                            <template x-if="talent.avatar_url"><img :src="talent.avatar_url" class="h-full w-full object-cover" alt=""></template>
                            <template x-if="!talent.avatar_url">
                                <div class="flex h-full w-full items-center justify-center font-display text-4xl text-accent-ink"
                                     style="background:linear-gradient(135deg, var(--accent-weak), var(--gold-weak));"
                                     x-text="(talent.display_name || '?').slice(0,1)"></div>
                            </template>
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                            <div class="font-mono text-[10px] uppercase tracking-wider text-accent-ink" x-text="talent.primary_type?.name || ''"></div>
                            <div class="mt-0.5 font-display text-xl text-ink group-hover:text-accent-ink" x-text="talent.display_name"></div>
                            <div class="mt-1 text-sm text-muted" x-text="talent.headline"></div>
                            <div class="mt-auto pt-3 text-xs text-subtle" x-text="[talent.city, talent.country].filter(Boolean).join(', ')"></div>
                        </div>
                    </a>
                </template>
            </div>

            {{-- Empty state with a clear-filters escape hatch --}}
            <div x-show="!loading && results.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-12 text-center">
                <p class="text-sm text-subtle">{{ __('No talent matches those filters.') }}</p>
                <button type="button" x-show="activeSummary.length" @click="clearAll()"
                        class="mt-4 inline-flex items-center gap-2 rounded-pill bg-accent px-4 py-2 text-sm font-medium text-on-accent transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-surface">{{ __('Clear filters') }}</button>
            </div>

            {{-- Pager (x-if so children aren't evaluated before the first search resolves) --}}
            <template x-if="!loading && meta?.pagination && meta.pagination.last_page > 1">
                <div class="mt-6 flex items-center justify-center gap-2 text-sm">
                    <button @click="search(meta.pagination.current_page - 1)" :disabled="meta.pagination.current_page <= 1" class="rounded-md border border-line px-3 py-1 disabled:opacity-40 rtl:rotate-180">‹</button>
                    <span class="text-muted" x-text="meta.pagination.current_page + ' / ' + meta.pagination.last_page"></span>
                    <button @click="search(meta.pagination.current_page + 1)" :disabled="meta.pagination.current_page >= meta.pagination.last_page" class="rounded-md border border-line px-3 py-1 disabled:opacity-40 rtl:rotate-180">›</button>
                </div>
            </template>
        </div>

        {{-- ADVANCED FILTERS MODAL — teleported to <body> so no transformed/overflow --}}
        {{-- ancestor can trap it; always opens centred in the viewport. --}}
        {{-- x-show plainly toggles display (reliable); enter/leave animate via :class + --}}
        {{-- CSS transitions, NOT Alpine x-transition (its leave never completes for a --}}
        {{-- teleported node, leaving a click-trapping overlay). --}}
        <template x-teleport="body">
            <div x-show="modalOpen" x-cloak
                 class="fixed inset-0 z-[60] flex items-end justify-center transition-opacity duration-200 ease-out motion-reduce:transition-none sm:items-center sm:p-6"
                 :class="modalActive ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                 role="dialog" aria-modal="true" aria-labelledby="advfilters-title"
                 @keydown.escape.window="closeFilters()">
                {{-- Backdrop scrim --}}
                <div @click="closeFilters()" class="absolute inset-0" style="background: var(--scrim);"></div>

                {{-- Dialog panel: bottom sheet on mobile, large centred card on desktop (slide/scale in) --}}
                <div x-ref="dialog" tabindex="-1" @keydown="trapFocus($event)"
                     class="relative flex max-h-[92vh] w-full max-w-none flex-col rounded-t-2xl border border-line bg-surface shadow-e4 outline-none transition duration-200 ease-out motion-reduce:transition-none sm:max-h-[88vh] sm:max-w-3xl sm:rounded-2xl"
                     :class="modalActive ? 'translate-y-0 opacity-100 sm:scale-100' : 'translate-y-6 opacity-0 sm:translate-y-2 sm:scale-95'">
                    {{-- Header --}}
                    <div class="flex items-start justify-between gap-4 border-b border-line px-7 py-5">
                        <div class="min-w-0">
                            <h2 id="advfilters-title" class="font-display text-2xl leading-tight text-ink">{{ __('Advanced filters') }}</h2>
                            <p class="mt-1 text-sm text-muted">{{ __('Refine by skill, location, and skill-specific criteria.') }}</p>
                        </div>
                        <button type="button" @click="closeFilters()" aria-label="{{ __('Close') }}"
                                class="grid h-9 w-9 shrink-0 place-items-center rounded-pill text-lg text-subtle transition hover:bg-elevated hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">✕</button>
                    </div>

                    {{-- Scrollable body (the body scrolls, never the page). Everything here --}}
                    {{-- edits the STAGED draft; nothing applies until "Apply filters". --}}
                    <div class="flex-1 space-y-7 overflow-y-auto px-7 py-6">
                        {{-- Skills — picking skills here reveals the scoped filters below. --}}
                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Skills') }}</span>
                                <span x-show="draftSelectedCount" x-cloak
                                      class="grid h-4 min-w-[1rem] place-items-center rounded-pill bg-accent-weak px-1 text-[10px] font-semibold text-accent-ink"
                                      x-text="draftSelectedCount"></span>
                            </div>
                            @include('public.partials.skill-filter-chips', ['staged' => true])
                        </section>

                        <div class="border-t border-line"></div>

                        {{-- Universal (always): Location --}}
                        <section>
                            <div class="mb-3 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Location') }}</div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <input x-model="draft.city" type="text" placeholder="{{ __('City…') }}"
                                       class="w-full rounded-lg border-line bg-elevated px-3.5 py-2.5 text-ink shadow-sm focus:border-accent focus:ring-accent">
                                <input x-model="draft.country" type="text" placeholder="{{ __('Country…') }}"
                                       class="w-full rounded-lg border-line bg-elevated px-3.5 py-2.5 text-ink shadow-sm focus:border-accent focus:ring-accent">
                            </div>
                        </section>

                        {{-- Skill-specific (scoped) filters — shown ONLY for the selected (draft) --}}
                        {{-- skills' categories: pick a skill to reveal the filters that narrow it --}}
                        {{-- further (crew → Equipment, creative → Software, modeling → Looks). --}}
                        <section>
                            <div class="mb-3 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Skill-specific') }}</div>

                            <div x-show="hasScopedFilters" x-cloak class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {{-- Crew scope → Equipment --}}
                                <div x-show="showEquipment" x-cloak>
                                    <label class="mb-1.5 block text-xs font-medium text-muted">{{ __('Equipment') }} <span class="text-subtle">· {{ __('Crew') }}</span></label>
                                    <select x-model="draft.equipment" class="w-full rounded-lg border-line bg-elevated px-3.5 py-2.5 text-ink shadow-sm focus:border-accent focus:ring-accent">
                                        <option value="">{{ __('Any') }}</option>
                                        <template x-for="cat in equipmentCategories" :key="cat"><option :value="cat" x-text="cat"></option></template>
                                    </select>
                                </div>

                                {{-- Creative scope → Software --}}
                                <div x-show="showSoftware" x-cloak>
                                    <label class="mb-1.5 block text-xs font-medium text-muted">{{ __('Software') }} <span class="text-subtle">· {{ __('Creative') }}</span></label>
                                    <select x-model="draft.software" class="w-full rounded-lg border-line bg-elevated px-3.5 py-2.5 text-ink shadow-sm focus:border-accent focus:ring-accent">
                                        <option value="">{{ __('Any') }}</option>
                                        <template x-for="s in softwareOptions" :key="s"><option :value="s" x-text="s"></option></template>
                                    </select>
                                </div>

                                {{-- Modeling scope → Looks --}}
                                <div x-show="showLooks" x-cloak>
                                    <label class="mb-1.5 block text-xs font-medium text-muted">{{ __('Looks') }} <span class="text-subtle">· {{ __('Modeling') }}</span></label>
                                    <select x-model="draft.looks" class="w-full rounded-lg border-line bg-elevated px-3.5 py-2.5 text-ink shadow-sm focus:border-accent focus:ring-accent">
                                        <option value="">{{ __('Any') }}</option>
                                        <template x-for="look in lookOptions" :key="look"><option :value="look" x-text="look"></option></template>
                                    </select>
                                </div>
                            </div>

                            {{-- No skill selected yet → prompt to pick one. --}}
                            <p x-show="!hasScopedFilters" x-cloak
                               class="rounded-lg border border-dashed border-line-strong bg-elevated/50 px-4 py-3 text-xs leading-relaxed text-subtle">
                                {{ __('Select a skill to reveal its filters.') }}
                            </p>
                        </section>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between gap-3 border-t border-line bg-surface px-7 py-5">
                        <button type="button" @click="clearModalFilters()"
                                class="rounded-pill px-2 py-1 text-sm font-medium text-muted underline-offset-2 transition hover:text-ink hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">{{ __('Clear filters') }}</button>
                        <x-ui.button variant="accent" size="lg" x-on:click="applyFilters()">{{ __('Apply filters') }}</x-ui.button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-public-layout>
