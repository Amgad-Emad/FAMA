{{--
    Admin dashboard (fama-ui admin language). Top: what needs a decision now
    (moderation queues). Then platform insights over a chosen range — KPI tiles
    with the change against the previous period + sparklines, and one large chart
    with a metric switch (adminInsights in resources/js/admin.js; data from
    AdminInsightsService, first range server-rendered). Then the verification
    funnel, the skills mix, marketplace cards (feature-gated), governance links
    and recent activity.
--}}
@php
    $insightStrings = [
        'metrics' => [
            'signups' => __('New talents'),
            'verified' => __('Verified'),
            'posts' => __('Posts'),
            'views' => __('Profile views'),
            'likes' => __('Likes'),
            'comments' => __('Comments'),
            'follows' => __('Follows'),
        ],
        'vsPrev' => __('vs previous period'),
        'noPrev' => __('new this period'),
        'total' => __('total in this period'),
        'failed' => __('Couldn’t load insights. Try again.'),
        'rightNow' => __('right now'),
        'info' => [
            'signups' => __('Talent accounts created in the selected period (deleted accounts excluded). The change compares with the same number of days just before it.'),
            'verified' => __('Profiles our team verified in the period, counted from the moderation audit log (each “talent verified” entry).'),
            'posts' => __('Feed posts published in the period. Hidden, removed and deleted posts are not counted.'),
            'views' => __('Public profile views in the period, added up across every talent (each talent page keeps one count per day).'),
            'buckets' => __('Each bar is one day for 7 and 30 days, one week (Monday start) for 90 days, and one month for 12 months.'),
            'completed' => __('Of the talents who signed up in this period, how many have finished the six profile steps (or are already verified).'),
            'live' => __('Of the talents who signed up in this period, how many are Live now.'),
            'per_day' => __('New talents in the period divided by its number of days.'),
            'verified_live' => __('Talents verified in this period whose profile is Live now.'),
            'awaiting' => __('Every talent not verified yet (suspended accounts excluded). A snapshot of right now, not limited to the period.'),
            'ready' => __('Unverified talents who have finished all six steps — the ones to check first. A snapshot of right now.'),
            'likes' => __('Likes given on posts and comments in the period.'),
            'comments' => __('Comments and replies written in the period (deleted ones excluded).'),
            'posters' => __('Distinct accounts that published at least one post in the period.'),
            'viewed' => __('Distinct talent profiles that got at least one view in the period.'),
            'per_talent' => __('Profile views in the period divided by the number of talents viewed.'),
            'follows' => \App\Support\Features::brands() ? __('New follows in the period, of talents and brands.') : __('New follows in the period.'),
        ],
        'details' => [
            'completed' => __('Finished the six steps'),
            'live' => __('Went Live'),
            'per_day' => __('Average per day'),
            'verified_live' => __('Verified and Live'),
            'awaiting' => __('Awaiting verification'),
            'ready' => __('Ready for review'),
            'likes' => __('Likes'),
            'comments' => __('Comments'),
            'posters' => __('Active posters'),
            'viewed' => __('Talents viewed'),
            'per_talent' => __('Views per talent'),
            'follows' => __('Follows'),
        ],
    ];
    $funnelLabels = ['signed_up' => __('Signed up'), 'completed' => __('Six steps done'), 'verified' => __('Checked by Fama'), 'live' => __('Live')];
    $funnelTop = max(1, $funnel[0]['value'] ?? 1);
    $skillTop = max(1, collect($skillsMix)->max('value') ?? 1);
@endphp
<x-admin-layout :title="__('Admin dashboard')">
    <div class="space-y-10">

        {{-- ── Needs a decision ─────────────────────────────────────────── --}}
        @can('moderate-content')
            <section>
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <h2 class="font-display text-2xl text-ink">{{ __('Needs a decision') }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ __('What is waiting on the team right now.') }}</p>
                    </div>
                    <a href="{{ route('admin.moderation.index') }}" class="hidden text-sm font-semibold text-accent-ink hover:underline sm:inline">{{ __('Open moderation') }} →</a>
                </div>
                @php
                    $moderationCards = [
                        ['info' => __('Talent profiles still in Created or Draft — built but not Live yet. Open the queue to verify them.'), 'label' => __('Pending talent profiles'), 'value' => $stats['moderation']['pending_talents'], 'queue' => 'talents', 'icon' => '<circle cx="12" cy="8" r="4.5"/><path d="M20 21a8 8 0 0 0-16 0"/>'],
                        ['info' => __('Reviews written about talents that wait for an approve or reject decision.'), 'label' => __('Pending talent reviews'), 'value' => $stats['moderation']['pending_reviews'], 'queue' => 'reviews', 'icon' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>'],
                    ];
                    if (\App\Support\Features::brands()) {
                        $moderationCards[] = ['info' => __('Reviews written about brands that wait for an approve or reject decision.'), 'label' => __('Pending brand reviews'), 'value' => $stats['moderation']['pending_brand_reviews'], 'queue' => 'brand-reviews', 'icon' => '<path d="M11.5 2.8a.5.5 0 0 1 1 0l2.3 4.7 5.2.8a.5.5 0 0 1 .3.9l-3.8 3.7.9 5.2a.5.5 0 0 1-.7.5L12 16.2l-4.7 2.4a.5.5 0 0 1-.7-.5l.9-5.2-3.8-3.7a.5.5 0 0 1 .3-.9l5.2-.8z"/>'];
                        $moderationCards[] = ['info' => __('Brand accounts our team has not verified yet. A brand cannot publish until it is verified.'), 'label' => __('Brands awaiting verification'), 'value' => $stats['moderation']['unverified_brands'], 'queue' => 'brands', 'icon' => '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/>'];
                    }
                @endphp
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($moderationCards as $card)
                        <a href="{{ route('admin.moderation.index', ['queue' => $card['queue']]) }}"
                           class="group flex items-center gap-4 rounded-[20px] border bg-surface p-5 transition hover:shadow-e2 {{ $card['value'] ? 'border-gold-weak' : 'border-line' }}">
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-[14px] {{ $card['value'] ? 'bg-gold-weak text-gold' : 'bg-elevated text-subtle' }}">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">{!! $card['icon'] !!}</svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-display text-3xl leading-none {{ $card['value'] ? 'text-ink' : 'text-subtle' }}">{{ $card['value'] }}</span>
                                <span class="mt-1.5 block text-sm font-medium text-muted">{{ $card['label'] }}</span>
                                @if (! $card['value'])<span class="mt-0.5 block text-xs text-success">{{ __('All clear.') }}</span>@endif
                            </span>
                            @if (! empty($card['info']))<x-admin.info :text="$card['info']" class="self-start" />@endif
                            <svg class="h-5 w-5 shrink-0 text-subtle transition group-hover:translate-x-0.5 group-hover:text-ink rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                        </a>
                    @endforeach
                </div>
            </section>
        @endcan

        {{-- ── Platform insights ───────────────────────────────────────── --}}
        <section x-data="adminInsights({ initial: @js($insights), strings: @js($insightStrings) })">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="font-display text-2xl text-ink">{{ __('Platform insights') }}</h2>
                    <p class="mt-1 text-sm text-muted" x-text="rangeNote"></p>
                </div>
                <div class="inline-flex rounded-pill border border-line bg-surface p-1" role="group" aria-label="{{ __('Period') }}">
                    @foreach (['7d' => __('7 days'), '30d' => __('30 days'), '90d' => __('90 days'), '12m' => __('12 months')] as $r => $label)
                        <button type="button" @click="load('{{ $r }}')" :aria-pressed="(range === '{{ $r }}').toString()"
                                :class="range === '{{ $r }}' ? 'bg-primary text-on-primary' : 'text-muted hover:text-ink'"
                                class="rounded-pill px-4 py-2 text-sm font-semibold transition">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            {{-- KPI tiles: click one to chart it --}}
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" :class="{ 'opacity-60': loading }" style="transition:opacity .2s">
                <template x-for="m in chartMetrics" :key="m">
                    <div role="button" tabindex="0" @click="metric = m" @keydown.enter.prevent="metric = m" @keydown.space.prevent="metric = m" :aria-pressed="(metric === m).toString()"
                            class="cursor-pointer rounded-[20px] border bg-surface p-5 text-start transition hover:shadow-e2 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                            :class="metric === m ? 'border-ink ring-1 ring-ink' : 'border-line'">
                        <span class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-pill" :style="'background:' + colors[m]"></span><span class="text-sm font-semibold text-muted" x-text="S.metrics[m]"></span></span>
                            <x-admin.info bind="S.info[m]" />
                        </span>
                        <span class="mt-2 block font-display text-[34px] leading-none text-ink" x-text="fmt(data.totals[m].value)"></span>
                        <span class="mt-2 flex items-center gap-1.5 text-[13px]">
                            <span class="inline-flex items-center gap-0.5 rounded-pill px-1.5 py-0.5 font-semibold"
                                  :class="deltaClass(data.totals[m].delta_pct)" x-text="deltaText(data.totals[m])"></span>
                            <span class="text-subtle" x-text="data.totals[m].delta_pct === null ? '' : S.vsPrev"></span>
                        </span>
                        <svg class="mt-3 h-10 w-full overflow-visible" viewBox="0 0 100 32" preserveAspectRatio="none" aria-hidden="true">
                            <path :d="sparkArea(data.series[m])" :fill="colors[m]" fill-opacity=".12"/>
                            <path :d="sparkLine(data.series[m])" fill="none" :stroke="colors[m]" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round"/>
                        </svg>
                    </div>
                </template>
            </div>

            {{-- The big chart --}}
            <div class="mt-4 rounded-[20px] border border-line bg-surface p-5 sm:p-7">
                <div class="mb-5 flex flex-wrap items-baseline justify-between gap-3">
                    <h3 class="flex items-center gap-2 font-display text-xl text-ink"><span x-text="S.metrics[metric]"></span>
                        <x-admin.info bind="S.info[metric] + ' ' + S.info.buckets" align="start" /></h3>
                    <p class="text-sm text-muted"><b class="font-semibold text-ink" x-text="fmt(data.totals[metric].value)"></b> <span x-text="S.total"></span></p>
                </div>
                <div class="grid grid-cols-[auto_1fr] gap-x-3">
                    <div class="flex h-64 flex-col justify-between text-end text-xs text-subtle" aria-hidden="true">
                        <template x-for="(t, i) in yTicks" :key="i"><span x-text="fmt(t)"></span></template>
                    </div>
                    <div class="relative h-64" x-ref="area">
                        <div class="pointer-events-none absolute inset-0 flex flex-col justify-between">
                            <template x-for="i in 4" :key="i"><i class="block border-t" :class="i === 4 ? 'border-line-strong' : 'border-line'"></i></template>
                        </div>
                        <div class="absolute inset-0 flex items-end gap-[3px]">
                            <template x-for="(v, i) in data.series[metric]" :key="range + metric + i">
                                <button type="button" class="group relative flex h-full flex-1 items-end focus:outline-none" @mouseenter="hover = i" @mouseleave="hover = null" @focus="hover = i" @blur="hover = null"
                                        :aria-label="data.labels[i] + ': ' + fmt(v)">
                                    <span class="block w-full rounded-t-[6px] transition-[height] duration-700 ease-out"
                                          :style="'height:' + (grown ? pct(v) : 0) + '%;min-height:' + (v ? 3 : 2) + 'px;background:' + colors[metric] + ';opacity:' + (hover === null || hover === i ? 1 : .45) + ';transition-delay:' + delay(i) + 'ms'"></span>
                                    <span x-show="hover === i" x-cloak class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 whitespace-nowrap rounded-[10px] bg-primary px-2.5 py-1.5 text-xs text-on-primary">
                                        <b x-text="fmt(v)"></b> · <span x-text="data.labels[i]"></span>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <span></span>
                    <div class="mt-2 flex justify-between text-xs text-subtle" aria-hidden="true">
                        <template x-for="i in xPicks" :key="range + i"><span x-text="data.labels[i]"></span></template>
                    </div>
                </div>
                <p x-show="error" x-cloak class="mt-3 text-sm text-danger" x-text="S.failed"></p>

                {{-- Detail figures for the picked KPI --}}
                <div class="mt-6 grid gap-3 border-t border-line pt-5 sm:grid-cols-3">
                    <template x-for="(d, key) in data.details[metric]" :key="metric + key">
                        <div class="flex items-center justify-between gap-3 rounded-[14px] bg-bg px-4 py-3">
                            <span class="min-w-0">
                                <span class="flex items-center gap-1.5 text-sm font-semibold text-muted"><span x-text="S.details[key]"></span><x-admin.info bind="S.info[key]" align="start" /></span>
                                <span class="block text-xs text-subtle" x-show="d.previous === null" x-text="S.rightNow"></span>
                            </span>
                            <span class="flex shrink-0 items-center gap-2">
                                <b class="font-display text-xl font-semibold text-ink" x-text="fmt(d.value)"></b>
                                <span x-show="d.previous !== null" class="rounded-pill px-1.5 py-0.5 text-xs font-semibold" :class="deltaClass(d.delta_pct)" x-text="deltaText(d)"></span>
                            </span>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        {{-- ── Funnel + skills ──────────────────────────────────────────── --}}
        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-[20px] border border-line bg-surface p-6 sm:p-7">
                <h2 class="flex items-center justify-between gap-3 font-display text-xl text-ink">{{ __('Road to Live') }}
                    <x-admin.info :text="__('Every talent account right now, not limited to the period. Six steps done = finished the profile checklist; Checked by Fama = verified by our team; Live = published. Each percentage compares a step with the one before it.')" /></h2>
                <p class="mt-1 text-sm text-muted">{{ __('Where talents are right now, from sign-up to a Live profile.') }}</p>
                <ol class="mt-6 space-y-4">
                    @foreach ($funnel as $i => $step)
                        @php
                            $pct = round($step['value'] / $funnelTop * 100);
                            $prev = $i > 0 ? $funnel[$i - 1]['value'] : null;
                            $conv = $prev ? round($step['value'] / $prev * 100) : null;
                        @endphp
                        <li>
                            <div class="mb-1.5 flex items-baseline justify-between gap-3 text-sm">
                                <span class="font-semibold text-ink">{{ $funnelLabels[$step['key']] }}</span>
                                <span class="text-muted"><b class="font-display text-lg font-semibold text-ink">{{ number_format($step['value']) }}</b>
                                    @if ($conv !== null)<span class="ms-1 text-xs">· {{ $conv }}% {{ __('of previous') }}</span>@endif</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-pill bg-elevated">
                                <div class="h-full rounded-pill" style="width:{{ max($pct, $step['value'] ? 2 : 0) }}%;background:{{ ['#14110F', '#8A2FA8', '#AD50D3', '#1B6B52'][$i] }}"></div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            <section class="rounded-[20px] border border-line bg-surface p-6 sm:p-7">
                <h2 class="flex items-center justify-between gap-3 font-display text-xl text-ink">{{ __('Skills mix') }}
                    <x-admin.info :text="__('How many talents hold each skill right now. A talent with several skills counts once per skill, so the numbers can add up to more than the number of talents.')" /></h2>
                <p class="mt-1 text-sm text-muted">{{ __('How many talents hold each skill (a talent can hold several).') }}</p>
                <ul class="mt-6 space-y-3">
                    @foreach ($skillsMix as $skill)
                        <li class="grid grid-cols-[minmax(0,9rem)_1fr_auto] items-center gap-3 text-sm">
                            <span class="truncate font-medium text-ink">{{ $skill['name'] }}</span>
                            <span class="h-2.5 overflow-hidden rounded-pill bg-elevated"><span class="block h-full rounded-pill bg-accent" style="width:{{ max(round($skill['value'] / $skillTop * 100), $skill['value'] ? 3 : 0) }}%"></span></span>
                            <span class="w-8 text-end font-semibold text-ink">{{ $skill['value'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        {{-- ── Marketplace (brand/contract features) — not rendered at all when both
             are off, or the empty row would add a whole section gap ── --}}
        @php
            $admin = auth('admin')->user();
            $showContracts = \App\Support\Features::contracts() && $admin?->can('intervene-contracts');
            $showProjects = \App\Support\Features::brands() && $admin?->can('moderate-content');
        @endphp
        @if ($showContracts || $showProjects)
        <div class="grid gap-4 lg:grid-cols-2">
            @feature('contracts')
            @can('intervene-contracts')
                <a href="{{ route('admin.contracts') }}" class="block rounded-[20px] border border-line bg-surface p-6 transition hover:shadow-e2">
                    <h2 class="font-display text-xl text-ink">{{ __('Contracts') }}</h2>
                    <div class="mt-3 flex items-baseline gap-3">
                        <span class="font-display text-3xl leading-none text-ink">{{ $stats['contracts']['active'] }}</span>
                        <span class="text-sm text-muted">{{ __('Active contracts') }}</span>
                    </div>
                    @if ($stats['contracts']['active'])
                        <div class="mt-3 flex flex-wrap gap-2 text-xs text-muted">
                            <span class="rounded-pill bg-elevated px-2.5 py-1">{{ __('Awaiting brand') }}: {{ $stats['contracts']['awaiting_brand'] }}</span>
                            <span class="rounded-pill bg-elevated px-2.5 py-1">{{ __('Awaiting talent') }}: {{ $stats['contracts']['awaiting_talent'] }}</span>
                            <span class="rounded-pill bg-elevated px-2.5 py-1">{{ __('Awaiting admin') }}: {{ $stats['contracts']['awaiting_admin'] }}</span>
                        </div>
                    @else
                        <p class="mt-3 text-sm text-muted">{{ __('No contracts in flight.') }}</p>
                    @endif
                </a>
            @endcan
            @endfeature

            @feature('brands')
            @can('moderate-content')
                <a href="{{ route('admin.moderation.index', ['queue' => 'projects']) }}" class="block rounded-[20px] border border-line bg-surface p-6 transition hover:shadow-e2">
                    <h2 class="font-display text-xl text-ink">{{ __('Projects') }}</h2>
                    <div class="mt-3 grid grid-cols-3 gap-3">
                        @foreach (['open' => __('Open'), 'in_progress' => __('In progress'), 'completed' => __('Completed')] as $key => $label)
                            <div>
                                <div class="font-display text-3xl leading-none {{ $stats['projects'][$key] ? 'text-ink' : 'text-subtle' }}">{{ $stats['projects'][$key] }}</div>
                                <div class="mt-1 text-sm text-muted">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                </a>
            @endcan
            @endfeature
        </div>
        @endif

        {{-- ── Governance quick links ──────────────────────────────────── --}}
        @php
            $governance = array_filter([
                ['route' => 'admin.flows', 'label' => __('Contract flows'), 'hint' => __('Author the step sequences contracts run on.'), 'can' => 'manage-flows', 'feature' => 'contracts'],
                ['route' => 'admin.skills', 'label' => __('Skills templates'), 'hint' => __('Per-skill preselected blocks and their order.'), 'can' => 'manage-flows'],
                ['route' => 'admin.blocks', 'label' => __('Block catalog'), 'hint' => __('Which blocks exist and who may use them.'), 'can' => 'manage-blocks'],
                ['route' => 'admin.settings', 'label' => __('Settings'), 'hint' => __('Platform globals and feature flags.'), 'can' => 'manage-settings'],
                ['route' => 'admin.users', 'label' => __('Accounts'), 'hint' => \App\Support\Features::brands() ? __('Create admins, brands, talents; manage staff roles.') : __('Create admins and talents; manage staff roles.'), 'can' => 'manage-users'],
            ], fn ($link) => auth('admin')->user()?->can($link['can'])
                && (empty($link['feature']) || \App\Support\Features::enabled($link['feature'])));
        @endphp

        <div class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
            @if ($governance)
                <section>
                    <h2 class="mb-4 font-display text-xl text-ink">{{ __('Governance') }}</h2>
                    <div class="space-y-2">
                        @foreach ($governance as $link)
                            <a href="{{ route($link['route']) }}" class="group flex items-center justify-between gap-4 rounded-[16px] border border-line bg-surface px-5 py-4 transition hover:border-ink">
                                <span>
                                    <span class="block text-[15px] font-semibold text-ink">{{ $link['label'] }}</span>
                                    <span class="mt-0.5 block text-sm text-muted">{{ $link['hint'] }}</span>
                                </span>
                                <svg class="h-5 w-5 shrink-0 text-subtle transition group-hover:translate-x-0.5 group-hover:text-ink rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @can('manage-settings')
                <section>
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="font-display text-xl text-ink">{{ __('Recent activity') }}</h2>
                        <a href="{{ route('admin.activity') }}" class="text-sm font-semibold text-accent-ink hover:underline">{{ __('View activity log') }}</a>
                    </div>
                    <div class="rounded-[20px] border border-line bg-surface p-2">
                        @forelse ($stats['recent_activity'] as $entry)
                            <div class="flex items-center gap-3 rounded-[14px] px-3 py-3 {{ ! $loop->last ? 'border-b border-line' : '' }}">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-pill bg-accent-weak text-xs font-semibold text-accent-ink">{{ \Illuminate\Support\Str::of($entry->causer?->name ?? 'S')->explode(' ')->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm text-ink"><b class="font-semibold">{{ $entry->causer?->name ?? __('System') }}</b> · {{ str_replace(['.', '_'], ' ', $entry->description) }}</span>
                                    <span class="block text-xs text-subtle">{{ str_replace('_', ' ', $entry->log_name) }}</span>
                                </span>
                                <span class="shrink-0 text-xs text-subtle">{{ $entry->created_at->diffForHumans() }}</span>
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-muted">{{ __('No activity yet.') }}</p>
                        @endforelse
                    </div>
                </section>
            @endcan
        </div>
    </div>
</x-admin-layout>
