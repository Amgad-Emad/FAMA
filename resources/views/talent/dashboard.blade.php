<x-talent-layout :title="__('Dashboard')">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-ui.eyebrow>{{ __('Welcome back') }}</x-ui.eyebrow>
            <h2 class="mt-1 font-display text-3xl text-ink">{{ $talent->display_name ?: $talent->email }}</h2>
        </div>
    </div>

    @php
        $weekly = $stats['weekly'];
        $deltaTone = $weekly['delta'] > 0 ? 'text-success' : ($weekly['delta'] < 0 ? 'text-danger' : 'text-subtle');
        $deltaArrow = $weekly['delta'] > 0 ? '▲' : ($weekly['delta'] < 0 ? '▼' : '—');
        $deltaFigure = $weekly['delta_pct'] !== null
            ? number_format(abs($weekly['delta_pct']), 1).'%'
            : ($weekly['delta'] !== 0 ? number_format(abs($weekly['delta'])) : '');
    @endphp

    <div x-data="dashboardHome({ publish: @js(['is_published' => $stats['is_published'], 'is_verified' => $stats['is_verified']]) })">
        <div class="grid gap-4 sm:grid-cols-2">
            {{-- Profile visibility: live status + the public/private switch. --}}
            <x-ui.card>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Profile visibility') }}</div>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-pill" :class="isPublished ? 'bg-success' : 'bg-warn'"></span>
                            <span class="font-display text-xl text-ink" x-text="isPublished ? '{{ __('Public') }}' : '{{ __('Private') }}'">{{ $stats['is_published'] ? __('Public') : __('Private') }}</span>
                        </div>
                    </div>
                    <button type="button" role="switch" :aria-checked="isPublished ? 'true' : 'false'"
                            aria-label="{{ __('Toggle profile visibility') }}"
                            x-on:click="togglePublish()"
                            x-bind:disabled="publishing || (!isVerified && !isPublished)"
                            x-bind:title="(!isVerified && !isPublished) ? '{{ __('Your account must be verified before you can publish.') }}' : ''"
                            class="relative mt-1 h-6 w-11 shrink-0 rounded-pill border transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-bg disabled:cursor-not-allowed disabled:opacity-50"
                            :class="isPublished ? 'border-accent bg-accent' : 'border-line-strong bg-bg'">
                        <span class="absolute start-0.5 top-0.5 h-[18px] w-[18px] rounded-pill shadow-e1 transition-transform"
                              :class="isPublished ? 'translate-x-5 rtl:-translate-x-5 bg-on-accent' : 'bg-elevated border border-line'"></span>
                    </button>
                </div>
                <p x-show="publishError" x-cloak class="mt-2 text-xs text-danger" x-text="publishError"></p>
                <p x-show="!isVerified && !isPublished" x-cloak class="mt-2 text-xs text-muted">{{ __('Awaiting verification — the switch unlocks once our team approves your profile.') }}</p>
                <a href="{{ route('talent.profile.edit') }}" class="mt-3 inline-block text-xs text-accent-ink underline">{{ __('Manage publishing') }}</a>
            </x-ui.card>

            {{-- Views KPI: lifetime total + week-over-week delta; expands into the chart. --}}
            <x-ui.card :pad="false">
                <button type="button" x-on:click="toggleChart()" :aria-expanded="chartOpen ? 'true' : 'false'" aria-controls="views-chart"
                        class="w-full p-6 text-start transition hover:bg-bg/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Profile views') }}</div>
                            <div class="mt-2 font-display text-2xl leading-none text-ink">{{ number_format($stats['view_count']) }}</div>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                @if ($weekly['current_week'] > 0 || $weekly['previous_week'] > 0)
                                    <span class="font-medium {{ $deltaTone }}">{{ $deltaArrow }} {{ $deltaFigure }}</span>
                                    <span class="text-subtle">{{ __(':current this week · :previous last week', ['current' => number_format($weekly['current_week']), 'previous' => number_format($weekly['previous_week'])]) }}</span>
                                @else
                                    <span class="text-subtle">{{ __('No views in the last two weeks.') }}</span>
                                @endif
                            </div>
                        </div>
                        <svg class="mt-1 h-4 w-4 shrink-0 text-subtle transition-transform" :class="chartOpen ? 'rotate-180' : ''" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </button>
            </x-ui.card>
        </div>

        {{-- Expandable views chart: range presets back to the profile's creation date. --}}
        <div x-show="chartOpen" x-cloak id="views-chart" class="mt-4">
            <x-ui.card>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Views over time') }}</div>
                        <p class="mt-1 text-xs text-muted" x-show="chart" x-cloak>
                            <span x-text="(chart?.total ?? 0).toLocaleString()"></span>
                            <span>{{ __('views in this period') }}</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-1.5" role="group" aria-label="{{ __('Date range') }}">
                        @foreach (['7d' => __('Last 7 days'), '30d' => __('Last 30 days'), '90d' => __('Last 90 days'), 'all' => __('Since created')] as $rangeKey => $rangeLabel)
                            <button type="button" x-on:click="setRange('{{ $rangeKey }}')" :aria-pressed="range === '{{ $rangeKey }}' ? 'true' : 'false'"
                                    class="rounded-pill border px-3 py-1 text-xs font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                    :class="range === '{{ $rangeKey }}' ? 'border-accent bg-accent text-on-accent' : 'border-line text-muted hover:border-line-strong hover:text-ink'">
                                {{ $rangeLabel }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Column chart: hairline gridlines, thin accent bars (4px rounded data-end),
                     per-bar hover/focus tooltip; a refetch dims the previous render in place. --}}
                <div x-show="chart" x-cloak class="transition-opacity" :class="loading ? 'opacity-60' : ''">
                    <div class="relative mt-6 h-48" dir="ltr">
                        <template x-for="tick in yTicks" :key="'tick-'+tick">
                            <div class="absolute inset-x-0 flex translate-y-1/2 items-center gap-2" :style="`bottom: ${yMax ? (tick / yMax) * 100 : 0}%`">
                                <span class="w-8 shrink-0 text-end font-mono text-[10px] tabular-nums text-subtle" x-text="tick.toLocaleString()"></span>
                                <div class="h-px flex-1 bg-line"></div>
                            </div>
                        </template>
                        <div class="absolute inset-x-0 bottom-0 ms-10 h-px bg-line-strong"></div>
                        <div class="absolute inset-y-0 end-0 start-10 flex items-end gap-0.5">
                            <template x-for="(point, i) in (chart?.series ?? [])" :key="point.date">
                                <div class="group relative flex h-full flex-1 items-end justify-center focus:outline-none"
                                     tabindex="0" :aria-label="`${point.label}: ${point.views}`">
                                    <div class="w-full max-w-[24px] rounded-t bg-accent transition group-hover:opacity-80 group-focus-visible:opacity-80"
                                         :style="`height: ${barHeight(point.views)}%`"></div>
                                    <div class="pointer-events-none absolute bottom-full start-1/2 z-10 mb-1.5 hidden -translate-x-1/2 whitespace-nowrap rounded-md border border-line bg-elevated px-2 py-1 text-xs shadow-e2 group-hover:block group-focus-within:block">
                                        <span class="font-medium text-ink" x-text="point.views.toLocaleString()"></span>
                                        <span class="text-subtle" x-text="point.label"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <p x-show="chart && !hasViews" x-cloak class="absolute inset-0 ms-10 flex items-center justify-center text-sm text-muted">
                            {{ __('No views in this period yet.') }}
                        </p>
                    </div>
                    <div class="ms-10 mt-1.5 flex gap-0.5" dir="ltr">
                        <template x-for="(point, i) in (chart?.series ?? [])" :key="'x-'+point.date">
                            <div class="min-w-0 flex-1 truncate text-center font-mono text-[10px] text-subtle" x-text="xLabel(i) ? point.label : ''"></div>
                        </template>
                    </div>

                    {{-- Non-visual table view of the same series (tooltips never gate). --}}
                    <table class="sr-only">
                        <caption>{{ __('Profile views') }}</caption>
                        <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Views') }}</th></tr></thead>
                        <tbody>
                            <template x-for="point in (chart?.series ?? [])" :key="'row-'+point.date">
                                <tr><td x-text="point.label"></td><td x-text="point.views"></td></tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p x-show="!chart && loading" x-cloak class="mt-6 text-sm text-muted">{{ __('Loading…') }}</p>
            </x-ui.card>
        </div>
    </div>

    @feature('contracts')
    <x-ui.section class="mt-10" :title="__('Active contracts')" :eyebrow="__('Whose turn')">
        @if ($activeContracts->isEmpty())
            <x-ui.card class="flex items-center justify-between gap-4">
                <p class="text-muted">{{ __('No active contracts yet — enquiries from brands will open here.') }}</p>
                <a href="{{ route('talent.contracts') }}" class="shrink-0 text-xs text-accent-ink underline">{{ __('All contracts') }}</a>
            </x-ui.card>
        @else
            <div class="space-y-2">
                @foreach ($activeContracts as $contract)
                    <a href="{{ route('talent.contracts.show', $contract) }}"
                       class="flex items-center justify-between gap-3 rounded-lg border border-line bg-surface p-4 transition hover:border-line-strong {{ $contract->status->getValue() === 'awaiting_talent' ? 'ring-1 ring-accent' : '' }}">
                        <div class="min-w-0">
                            <div class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ $contract->reference }}</div>
                            <div class="font-display text-lg text-ink">{{ $contract->title }}</div>
                            <div class="text-sm text-muted">{{ $contract->brand?->name }}</div>
                        </div>
                        <div class="shrink-0 text-end">
                            <span class="rounded-pill px-2 py-1 text-[10px] font-medium {{ $contract->status->getValue() === 'awaiting_talent' ? 'bg-accent text-on-accent' : 'border border-line bg-surface text-muted' }}">
                                {{ $contract->status->getValue() === 'awaiting_talent' ? __('Your turn') : str_replace('_', ' ', $contract->status->getValue()) }}
                            </span>
                            @if ($contract->currentStep)
                                <div class="mt-1 text-xs text-subtle">{{ $contract->currentStep->name }}</div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-ui.section>
    @endfeature

    {{-- Every page in the talent nav, reachable straight from the dashboard.
         Driven by App\Support\Navigation::talentItems() (minus this page), so a
         new nav item appears here automatically — feature flags included. --}}
    @php
        $quickLinks = array_values(array_filter(
            \App\Support\Navigation::talentItems(),
            fn ($item) => $item['route'] !== 'talent.dashboard',
        ));
    @endphp
    <x-ui.section class="mt-10" :title="__('Where to next')" :eyebrow="__('All your pages')">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($quickLinks as $item)
                <a href="{{ route($item['route'], $item['params'] ?? []) }}"
                   class="group flex items-start gap-3 rounded-lg border border-line bg-surface p-5 transition hover:border-line-strong hover:shadow-e1 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-pill bg-accent-weak text-accent-ink transition group-hover:bg-accent group-hover:text-on-accent">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block font-medium text-ink group-hover:text-accent-ink">{{ $item['label'] }}</span>
                        <span class="mt-1 block text-sm text-muted">{{ $item['description'] }}</span>
                    </span>
                </a>
            @endforeach

            {{-- Not a nav item, but the one link that leaves the dashboard for
                 the public side. --}}
            @if ($talent->slug)
                <a href="{{ url($talent->slug) }}" target="_blank" rel="noopener"
                   class="group flex items-start gap-3 rounded-lg border border-line bg-surface p-5 transition hover:border-line-strong hover:shadow-e1 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-pill bg-accent-weak text-accent-ink transition group-hover:bg-accent group-hover:text-on-accent">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block font-medium text-ink group-hover:text-accent-ink">{{ __('Preview profile') }}</span>
                        <span class="mt-1 block text-sm text-muted">{{ __('See your public profile the way brands see it') }}</span>
                    </span>
                </a>
            @endif
        </div>
    </x-ui.section>
</x-talent-layout>
