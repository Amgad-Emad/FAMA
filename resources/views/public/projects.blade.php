<x-public-layout :title="__('Opportunities')">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6"
         x-data="projectBrowse({
            types: @js($types),
            scopeLabels: { model: '{{ __('Modeling') }}', crew: '{{ __('Crew') }}', creative: '{{ __('Creative') }}' },
            typeLabels: { campaign: '{{ __('Campaign') }}', shoot: '{{ __('Shoot') }}' },
         })">

        <header class="mb-6">
            <x-ui.eyebrow>{{ __('Opportunities') }}</x-ui.eyebrow>
            <h1 class="mt-1 font-display text-4xl text-ink">{{ __('Open projects') }}</h1>
        </header>

        {{-- PRIMARY control: Disciplines — sticky under the site header while results scroll. --}}
        <section class="sticky top-16 z-20 mb-6 rounded-lg border border-line bg-surface shadow-e1">
            <div class="p-4 sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2.5">
                        <h2 class="font-display text-lg text-ink">{{ __('Disciplines') }}</h2>
                        <span x-show="selectedSkillCount" x-cloak
                              class="grid h-5 min-w-[1.25rem] place-items-center rounded-pill bg-accent-weak px-1.5 text-[11px] font-semibold text-accent-ink"
                              x-text="selectedSkillCount"></span>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="relative w-full sm:w-52">
                            <label for="campaigns-q" class="sr-only">{{ __('Search projects…') }}</label>
                            <svg class="pointer-events-none absolute inset-y-0 start-2.5 my-auto h-3.5 w-3.5 text-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                            <input id="campaigns-q" x-model="filters.q" @input.debounce.400ms="load(1)" type="text" placeholder="{{ __('Search projects…') }}"
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

                {{-- Discipline chips (shared partial, live) --}}
                <div class="mt-4">
                    @include('public.partials.skill-filter-chips', ['nowrap' => true])
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
                <span x-show="!loading" x-cloak><span class="font-semibold text-ink" x-text="resultTotal"></span> {{ __('projects looking for talent') }}</span>
            </p>
        </div>

        {{-- Skeletons --}}
        <div x-show="loading" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="n in skeletons" :key="n"><div class="h-72 animate-pulse rounded-xl bg-surface"></div></template>
        </div>

        <div x-show="!loading" x-cloak class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="c in campaigns" :key="c.id">
                <div class="group flex flex-col overflow-hidden rounded-xl border border-line bg-surface shadow-e1 transition hover:-translate-y-0.5 hover:shadow-e3">
                    <a :href="`/brands/${c.brand?.slug}/projects/${c.slug}`" class="relative block aspect-[16/10] bg-elevated bg-cover bg-center"
                       :style="c.cover_image_url ? `background-image:url(${c.cover_image_url})` : 'background-image:repeating-linear-gradient(135deg, var(--line) 0 1px, transparent 1px 12px)'">
                        <template x-if="c.budget_min != null || c.budget_max != null">
                            <span class="absolute start-2.5 top-2.5 rounded-pill bg-surface/90 px-2.5 py-1 font-mono text-[10px] text-accent-ink backdrop-blur">
                                <span x-text="[c.budget_min, c.budget_max].filter((v) => v != null).map((v) => Number(v).toLocaleString()).join('–')"></span>
                                <span x-text="c.currency"></span>
                            </span>
                        </template>
                    </a>
                    <div class="flex min-w-0 flex-1 flex-col gap-3 p-5">
                        <div class="min-w-0">
                            <a :href="`/brands/${c.brand?.slug}`" class="flex items-center gap-2 text-xs text-muted transition hover:text-accent-ink">
                                <span class="grid h-5 w-5 shrink-0 place-items-center overflow-hidden rounded bg-primary text-[9px] text-on-primary"
                                      :style="c.brand?.logo_url ? `background-image:url(${c.brand.logo_url});background-size:cover;background-position:center` : ''">
                                    <span x-show="!c.brand?.logo_url" x-text="(c.brand?.name || '?').slice(0, 1)"></span>
                                </span>
                                <span class="truncate" x-text="c.brand?.name"></span>
                            </a>
                            <a :href="`/brands/${c.brand?.slug}/projects/${c.slug}`" class="mt-1.5 block truncate font-display text-lg text-ink transition group-hover:text-accent-ink" x-text="c.title"></a>
                        </div>
                        <div x-show="c.role" x-cloak>
                            <span class="inline-block rounded-pill bg-elevated px-2 py-0.5 text-[11px] text-muted"><span x-text="c.role?.name"></span> × 1</span>
                        </div>
                        <div class="mt-auto flex items-center justify-between gap-2 pt-2">
                            <span class="min-w-0 truncate font-mono text-[11px] text-subtle" x-show="c.location" x-cloak x-text="c.location"></span>
                            <a :href="`/brands/${c.brand?.slug}/projects/${c.slug}`" class="shrink-0 rounded-pill bg-accent px-3 py-1.5 text-xs font-medium text-on-accent transition hover:opacity-90">{{ __('Apply') }}</a>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Empty state --}}
        <div x-show="!loading && campaigns.length === 0" x-cloak class="rounded-xl border border-dashed border-line py-16 text-center">
            <p class="text-sm text-subtle">{{ __('No open projects right now.') }}</p>
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
                 role="dialog" aria-modal="true" aria-labelledby="campfilters-title" @keydown.escape.window="closeFilters()">
                <div @click="closeFilters()" class="absolute inset-0" style="background: var(--scrim);"></div>
                <div x-ref="dialog" tabindex="-1" @keydown="trapFocus($event)"
                     class="relative flex max-h-[92vh] w-full flex-col rounded-t-2xl border border-line bg-surface shadow-e4 outline-none transition duration-200 ease-out motion-reduce:transition-none sm:max-h-[88vh] sm:max-w-2xl sm:rounded-2xl"
                     :class="modalActive ? 'translate-y-0 opacity-100 sm:scale-100' : 'translate-y-6 opacity-0 sm:translate-y-2 sm:scale-95'">
                    <div class="flex items-start justify-between gap-4 border-b border-line px-7 py-5">
                        <div class="min-w-0">
                            <h2 id="campfilters-title" class="font-display text-2xl leading-tight text-ink">{{ __('Advanced filters') }}</h2>
                            <p class="mt-1 text-sm text-muted">{{ __('Refine by discipline, type, budget, and location.') }}</p>
                        </div>
                        <button type="button" @click="closeFilters()" aria-label="{{ __('Close') }}" class="grid h-9 w-9 shrink-0 place-items-center rounded-pill text-lg text-subtle transition hover:bg-elevated hover:text-ink">✕</button>
                    </div>

                    <div class="flex-1 space-y-7 overflow-y-auto px-7 py-6">
                        {{-- Disciplines (staged) --}}
                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Disciplines') }}</span>
                                <span x-show="draftSelectedCount" x-cloak class="grid h-4 min-w-[1rem] place-items-center rounded-pill bg-accent-weak px-1 text-[10px] font-semibold text-accent-ink" x-text="draftSelectedCount"></span>
                            </div>
                            @include('public.partials.skill-filter-chips', ['staged' => true])
                        </section>

                        <div class="border-t border-line"></div>

                        {{-- Type --}}
                        <section>
                            <div class="mb-3 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Type') }}</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach (['campaign' => __('Campaign'), 'shoot' => __('Shoot')] as $val => $label)
                                    <button type="button" @click="draft.campaign_type = draft.campaign_type === '{{ $val }}' ? '' : '{{ $val }}'" :aria-pressed="draft.campaign_type === '{{ $val }}'"
                                            class="rounded-pill border px-3 py-1.5 text-sm font-medium transition"
                                            :class="draft.campaign_type === '{{ $val }}' ? 'border-transparent bg-accent text-on-accent' : 'border-line-strong text-muted hover:border-accent hover:text-ink'">{{ $label }}</button>
                                @endforeach
                            </div>
                        </section>

                        <div class="border-t border-line"></div>

                        {{-- Budget --}}
                        <section>
                            <div class="mb-3 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Budget') }}</div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <input x-model="draft.budget_min" type="number" min="0" placeholder="{{ __('Budget min') }}" class="w-full rounded-lg border-line bg-elevated px-3.5 py-2.5 text-ink shadow-sm focus:border-accent focus:ring-accent">
                                <input x-model="draft.budget_max" type="number" min="0" placeholder="{{ __('Budget max') }}" class="w-full rounded-lg border-line bg-elevated px-3.5 py-2.5 text-ink shadow-sm focus:border-accent focus:ring-accent">
                            </div>
                        </section>

                        <div class="border-t border-line"></div>

                        {{-- Location --}}
                        <section>
                            <div class="mb-3 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Location') }}</div>
                            <input x-model="draft.city" type="text" placeholder="{{ __('City…') }}" class="w-full rounded-lg border-line bg-elevated px-3.5 py-2.5 text-ink shadow-sm focus:border-accent focus:ring-accent">
                        </section>
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
