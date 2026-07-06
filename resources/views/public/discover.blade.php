<x-public-layout :title="__('Discover talent')">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6"
         x-data="talentSearch({ types: @js($types), equipmentCategories: @js($equipmentCategories), softwareOptions: @js($softwareOptions) })">

        <header class="mb-8">
            <x-ui.eyebrow>{{ __('Discovery') }}</x-ui.eyebrow>
            <h1 class="mt-1 font-display text-4xl text-ink">{{ __('Find creative talent') }}</h1>
        </header>

        <div class="grid gap-8 lg:grid-cols-4">
            {{-- Filters --}}
            <aside class="space-y-6 lg:col-span-1">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Search') }}</label>
                    <input x-model="filters.q" @input.debounce.400ms="search()" type="text" placeholder="{{ __('Name…') }}"
                           class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                </div>

                <div>
                    <div class="mb-2 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Profession') }}</div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="type in types" :key="type.id">
                            <button type="button" @click="toggleType(type.slug)"
                                    class="rounded-pill border px-3 py-1 text-xs font-medium transition"
                                    :class="filters.type.includes(type.slug) ? 'border-transparent bg-accent text-on-accent' : 'border-line-strong text-muted hover:text-ink'"
                                    x-text="t(type.name)"></button>
                        </template>
                    </div>
                </div>

                <div>
                    <div class="mb-2 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Availability') }}</div>
                    <select x-model="filters.availability" @change="search()" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                        <option value="">{{ __('Any') }}</option>
                        <option value="available">{{ __('Available') }}</option>
                        <option value="booked">{{ __('Booked') }}</option>
                        <option value="unavailable">{{ __('Unavailable') }}</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Location') }}</label>
                    <input x-model="filters.city" @input.debounce.400ms="search()" type="text" placeholder="{{ __('City…') }}"
                           class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Equipment') }}</label>
                    <select x-model="filters.equipment" @change="search()" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                        <option value="">{{ __('Any') }}</option>
                        <template x-for="cat in equipmentCategories" :key="cat"><option :value="cat" x-text="cat"></option></template>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Software') }}</label>
                    <select x-model="filters.software" @change="search()" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                        <option value="">{{ __('Any') }}</option>
                        <template x-for="s in softwareOptions" :key="s"><option :value="s" x-text="s"></option></template>
                    </select>
                </div>

                <button @click="reset()" class="text-sm text-accent-ink underline">{{ __('Reset filters') }}</button>
            </aside>

            {{-- Results --}}
            <div class="lg:col-span-3">
                <div class="mb-4 flex items-center justify-between">
                    <span class="text-sm text-muted" x-show="meta?.pagination" x-cloak>
                        <span x-text="meta?.pagination?.total"></span> {{ __('talents') }}
                    </span>
                </div>

                <div x-show="loading" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <div class="h-64 animate-pulse rounded-lg bg-surface"></div>
                    <div class="h-64 animate-pulse rounded-lg bg-surface"></div>
                    <div class="h-64 animate-pulse rounded-lg bg-surface"></div>
                </div>

                <div x-show="!loading" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <template x-for="talent in results" :key="talent.slug">
                        <a :href="'/' + talent.slug" class="group flex flex-col overflow-hidden rounded-lg border border-line bg-surface transition hover:shadow-e2">
                            <div class="relative aspect-[4/3]">
                                <template x-if="talent.avatar_url"><img :src="talent.avatar_url" class="h-full w-full object-cover" alt=""></template>
                                <template x-if="!talent.avatar_url">
                                    <div class="flex h-full w-full items-center justify-center font-display text-4xl text-accent-ink"
                                         style="background:linear-gradient(135deg, var(--accent-weak), var(--gold-weak));"
                                         x-text="(talent.display_name || '?').slice(0,1)"></div>
                                </template>
                                <span class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-pill bg-bg/85 px-2 py-1 text-[10px] font-medium backdrop-blur"
                                      :class="{ 'text-success': talent.availability === 'available', 'text-warn': talent.availability === 'booked', 'text-muted': talent.availability === 'unavailable' }">
                                    <span class="h-1.5 w-1.5 rounded-pill" :class="{ 'bg-success': talent.availability === 'available', 'bg-warn': talent.availability === 'booked', 'bg-subtle': talent.availability === 'unavailable' }"></span>
                                    <span x-text="talent.availability"></span>
                                </span>
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

                <p x-show="!loading && results.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-10 text-center text-sm text-subtle">{{ __('No talent matches those filters.') }}</p>

                {{-- Pager --}}
                <div x-show="meta?.pagination && meta.pagination.last_page > 1" x-cloak class="mt-6 flex items-center justify-center gap-2 text-sm">
                    <button @click="search(meta.pagination.current_page - 1)" :disabled="meta.pagination.current_page <= 1" class="rounded-md border border-line px-3 py-1 disabled:opacity-40">‹</button>
                    <span class="text-muted" x-text="meta.pagination.current_page + ' / ' + meta.pagination.last_page"></span>
                    <button @click="search(meta.pagination.current_page + 1)" :disabled="meta.pagination.current_page >= meta.pagination.last_page" class="rounded-md border border-line px-3 py-1 disabled:opacity-40">›</button>
                </div>
            </div>
        </div>
    </div>
</x-public-layout>
