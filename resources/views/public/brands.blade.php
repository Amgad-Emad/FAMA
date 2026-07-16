@php
    // Translated labels for every filter value (used by the active-summary chips).
    $labels = ['verified' => __('Verified')];
    foreach ($industries as $v) { $labels[$v] = __(ucfirst(str_replace('_', ' ', $v))); }
    foreach ($stages as $v) { $labels[$v] = __(ucfirst($v)); }
    foreach ($reaches as $v) { $labels[$v] = __(ucfirst(str_replace('_', ' ', $v))); }
@endphp

<x-public-layout :title="__('Discover brands')">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6"
         x-data="brandsDiscover({ industries: @js($industries), stages: @js($stages), reaches: @js($reaches), labels: @js($labels) })">

        <header class="mb-6">
            <x-ui.eyebrow>{{ __('Discovery') }}</x-ui.eyebrow>
            <h1 class="mt-1 font-display text-4xl text-ink">{{ __('Discover brands') }}</h1>
        </header>

        {{-- PRIMARY control: Industries — sticky under the site header while results scroll. --}}
        <section class="sticky top-16 z-20 mb-6 rounded-lg border border-line bg-surface shadow-e1">
            <div class="p-4 sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2.5">
                        <h2 class="font-display text-lg text-ink">{{ __('Industries') }}</h2>
                        <span x-show="selectedIndustryCount" x-cloak
                              class="grid h-5 min-w-[1.25rem] place-items-center rounded-pill bg-accent-weak px-1.5 text-[11px] font-semibold text-accent-ink"
                              x-text="selectedIndustryCount"></span>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="relative w-full sm:w-52">
                            <label for="brands-q" class="sr-only">{{ __('Search brands…') }}</label>
                            <svg class="pointer-events-none absolute inset-y-0 start-2.5 my-auto h-3.5 w-3.5 text-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                            <input id="brands-q" x-model="filters.q" @input.debounce.400ms="load(1)" type="text" placeholder="{{ __('Search brands…') }}"
                                   class="w-full rounded-pill border-line bg-elevated py-1.5 ps-8 pe-3 text-xs text-ink shadow-sm focus:border-accent focus:ring-accent">
                        </div>

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

                {{-- Industry chips: "All" reset beside the chips (one line, scrolls on overflow). --}}
                <div class="mt-4 flex items-center gap-2 overflow-x-auto [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <button type="button" @click="clearIndustry()" :disabled="selectedIndustryCount === 0"
                            class="inline-flex shrink-0 items-center rounded-pill border border-line-strong px-3 py-1.5 text-sm font-medium text-muted transition hover:border-accent hover:text-ink disabled:cursor-default disabled:opacity-45 disabled:hover:border-line-strong disabled:hover:text-muted">
                        {{ __('All') }}
                    </button>
                    @foreach ($industries as $ind)
                        <button type="button" @click="toggleIndustry('{{ $ind }}')" :aria-pressed="filters.industry === '{{ $ind }}'"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-pill border px-3 py-1.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                                :class="filters.industry === '{{ $ind }}' ? 'border-transparent bg-accent text-on-accent shadow-e1' : 'border-line-strong text-muted hover:border-accent hover:text-ink'">
                            {{ __(ucfirst(str_replace('_', ' ', $ind))) }}
                            <svg x-show="filters.industry === '{{ $ind }}'" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        </button>
                    @endforeach
                </div>

                {{-- Active-filter summary --}}
                <div x-show="activeSummary.length" x-cloak class="mt-4 flex flex-wrap items-center gap-2 border-t border-line pt-3">
                    <span class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Active') }}</span>
                    <template x-for="item in activeSummary" :key="item.kind + ':' + item.value">
                        <button type="button" @click="removeFilter(item)"
                                class="inline-flex items-center gap-1.5 rounded-pill border border-line-strong bg-elevated px-2.5 py-1 text-xs text-ink transition hover:border-danger hover:text-danger">
                            <span x-text="item.label"></span><span aria-hidden="true">✕</span>
                        </button>
                    </template>
                    <button type="button" @click="clearAll()" class="ms-1 text-xs text-accent-ink underline hover:no-underline">{{ __('Clear all') }}</button>
                </div>
            </div>
        </section>

        {{-- Results --}}
        <div class="mb-4">
            <p class="text-sm text-muted" aria-live="polite">
                <span x-show="loading">{{ __('Searching…') }}</span>
                <span x-show="!loading" x-cloak><span class="font-semibold text-ink" x-text="resultTotal"></span> {{ __('brands hiring on Fama') }}</span>
            </p>
        </div>

        {{-- Skeletons --}}
        <div x-show="loading" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="n in skeletons" :key="n"><div class="h-44 animate-pulse rounded-xl bg-surface"></div></template>
        </div>

        <div x-show="!loading" x-cloak class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="brand in brands" :key="brand.id">
                <div class="group flex flex-col rounded-xl border border-line bg-surface p-5 shadow-e1 transition hover:-translate-y-0.5 hover:shadow-e3">
                    <a :href="`/brands/${brand.slug}`" class="flex items-center gap-4">
                        <span class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-lg bg-primary font-display text-lg text-on-primary"
                              :style="brand.logo_url ? `background-image:url(${brand.logo_url});background-size:cover;background-position:center` : ''">
                            <span x-show="!brand.logo_url" x-text="brand.name.slice(0, 1)"></span>
                        </span>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="truncate font-display text-lg text-ink transition group-hover:text-accent-ink" x-text="brand.name"></span>
                                <svg x-show="brand.is_verified" x-cloak class="h-4 w-4 shrink-0 text-accent" viewBox="0 0 24 24" fill="currentColor" :aria-label="'{{ __('Verified business') }}'"><path d="M12 2l2.4 1.8 3 .1 1 2.8 2.4 1.7-1 2.8 1 2.8-2.4 1.7-1 2.8-3 .1L12 22l-2.4-1.8-3-.1-1-2.8L3.2 15l1-2.8-1-2.8 2.4-1.7 1-2.8 3-.1L12 2z"/></svg>
                            </div>
                            <div class="text-xs text-muted" x-text="brand.industry || ''"></div>
                        </div>
                    </a>
                    <p class="mt-3 line-clamp-2 text-sm text-muted" x-show="brand.tagline" x-cloak x-text="brand.tagline"></p>
                    <div class="mt-auto flex items-center justify-between gap-2 pt-4">
                        <span class="min-w-0 truncate text-xs text-subtle" x-show="brand.location" x-cloak x-text="brand.location"></span>
                        <div class="flex shrink-0 items-center gap-2">
                            <a :href="`/brands/${brand.slug}`" class="rounded-pill border border-line-strong px-3 py-1.5 text-xs font-medium text-muted transition hover:border-accent hover:text-ink">{{ __('View') }}</a>
                            <a :href="`/brands/${brand.slug}/message`" class="rounded-pill bg-accent px-3 py-1.5 text-xs font-medium text-on-accent transition hover:opacity-90">{{ __('Message') }}</a>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Empty state --}}
        <div x-show="!loading && brands.length === 0" x-cloak class="rounded-xl border border-dashed border-line py-16 text-center">
            <p class="text-sm text-subtle">{{ __('No brands found.') }}</p>
            <button type="button" x-show="activeSummary.length" @click="clearAll()" class="mt-4 inline-flex rounded-pill bg-accent px-4 py-2 text-sm font-medium text-on-accent transition hover:opacity-90">{{ __('Clear filters') }}</button>
        </div>

        {{-- Pager --}}
        <template x-if="!loading && meta?.pagination && meta.pagination.last_page > 1">
            <div class="mt-8 flex items-center justify-center gap-2 text-sm">
                <button @click="load(meta.pagination.current_page - 1)" :disabled="meta.pagination.current_page <= 1" class="rounded-md border border-line px-3 py-1 disabled:opacity-40 rtl:rotate-180">‹</button>
                <span class="text-muted" x-text="meta.pagination.current_page + ' / ' + meta.pagination.last_page"></span>
                <button @click="load(meta.pagination.current_page + 1)" :disabled="meta.pagination.current_page >= meta.pagination.last_page" class="rounded-md border border-line px-3 py-1 disabled:opacity-40 rtl:rotate-180">›</button>
            </div>
        </template>

        {{-- ADVANCED FILTERS MODAL (teleported; enter/leave via :class + CSS, not x-transition) --}}
        <template x-teleport="body">
            <div x-show="modalOpen" x-cloak
                 class="fixed inset-0 z-[60] flex items-end justify-center transition-opacity duration-200 ease-out motion-reduce:transition-none sm:items-center sm:p-6"
                 :class="modalActive ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                 role="dialog" aria-modal="true" aria-labelledby="brandfilters-title" @keydown.escape.window="closeFilters()">
                <div @click="closeFilters()" class="absolute inset-0" style="background: var(--scrim);"></div>
                <div x-ref="dialog" tabindex="-1" @keydown="trapFocus($event)"
                     class="relative flex max-h-[92vh] w-full flex-col rounded-t-2xl border border-line bg-surface shadow-e4 outline-none transition duration-200 ease-out motion-reduce:transition-none sm:max-h-[88vh] sm:max-w-xl sm:rounded-2xl"
                     :class="modalActive ? 'translate-y-0 opacity-100 sm:scale-100' : 'translate-y-6 opacity-0 sm:translate-y-2 sm:scale-95'">
                    <div class="flex items-start justify-between gap-4 border-b border-line px-7 py-5">
                        <div class="min-w-0">
                            <h2 id="brandfilters-title" class="font-display text-2xl leading-tight text-ink">{{ __('Advanced filters') }}</h2>
                            <p class="mt-1 text-sm text-muted">{{ __('Refine by stage, reach, and verification.') }}</p>
                        </div>
                        <button type="button" @click="closeFilters()" aria-label="{{ __('Close') }}" class="grid h-9 w-9 shrink-0 place-items-center rounded-pill text-lg text-subtle transition hover:bg-elevated hover:text-ink">✕</button>
                    </div>

                    <div class="flex-1 space-y-7 overflow-y-auto px-7 py-6">
                        {{-- Stage --}}
                        <section>
                            <div class="mb-3 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Brand stage') }}</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($stages as $stage)
                                    <button type="button" @click="toggleDraft('brand_stage', '{{ $stage }}')" :aria-pressed="draft.brand_stage === '{{ $stage }}'"
                                            class="rounded-pill border px-3 py-1.5 text-sm font-medium transition"
                                            :class="draft.brand_stage === '{{ $stage }}' ? 'border-transparent bg-accent text-on-accent' : 'border-line-strong text-muted hover:border-accent hover:text-ink'">{{ __(ucfirst($stage)) }}</button>
                                @endforeach
                            </div>
                        </section>

                        <div class="border-t border-line"></div>

                        {{-- Reach --}}
                        <section>
                            <div class="mb-3 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Geographic reach') }}</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($reaches as $reach)
                                    <button type="button" @click="toggleDraft('geographic_reach', '{{ $reach }}')" :aria-pressed="draft.geographic_reach === '{{ $reach }}'"
                                            class="rounded-pill border px-3 py-1.5 text-sm font-medium transition"
                                            :class="draft.geographic_reach === '{{ $reach }}' ? 'border-transparent bg-accent text-on-accent' : 'border-line-strong text-muted hover:border-accent hover:text-ink'">{{ __(ucfirst(str_replace('_', ' ', $reach))) }}</button>
                                @endforeach
                            </div>
                        </section>

                        <div class="border-t border-line"></div>

                        {{-- Verified --}}
                        <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-line bg-elevated px-4 py-3">
                            <span>
                                <span class="block text-sm font-medium text-ink">{{ __('Verified only') }}</span>
                                <span class="block text-xs text-muted">{{ __('Show only verified businesses.') }}</span>
                            </span>
                            <input type="checkbox" x-model="draft.verified" class="h-4 w-4 rounded border-line text-accent focus:ring-accent">
                        </label>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-line bg-surface px-7 py-5">
                        <button type="button" @click="clearModalFilters()" class="rounded-pill px-2 py-1 text-sm font-medium text-muted underline-offset-2 transition hover:text-ink hover:underline">{{ __('Clear filters') }}</button>
                        <x-ui.button variant="accent" size="lg" x-on:click="applyFilters()">{{ __('Apply filters') }}</x-ui.button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-public-layout>
