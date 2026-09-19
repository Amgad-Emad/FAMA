@php
    $skills = $talent->talentTypes
        ->sortBy(fn ($t) => [$t->pivot->is_primary ? 0 : 1, (int) $t->pivot->position])
        ->values();
    $primaryType = $skills->firstWhere(fn ($type) => (bool) $type->pivot->is_primary) ?? $skills->first();
    $avatar = $talent->avatar_url;

    // Header stats (from already eager-loaded relations — no N+1).
    // Counts what the page SHOWS: own projects plus accepted credits on other
    // people's work (ADR-AA). A project both owned and credited counts once.
    $projectsCount = $talent->projects->pluck('id')
        ->merge($talent->creditedProjects->pluck('id'))
        ->unique()->count();
    $reviewsCount = $talent->reviews->count();            // reviews eager-loaded with the approved scope
    $avgRating = $reviewsCount ? round((float) $talent->reviews->avg('rating'), 1) : null;

    // Secondary line: the primary skill, else the headline.
    $secondary = $primaryType?->name ?: $talent->headline;

    // Pricing rate (ADR-N) — render only when the whole rate is set AND the
    // pricing feature is on (hidden in V1).
    $hasRate = \App\Support\Features::enabled('pricing')
        && filled($talent->rate_amount) && filled($talent->rate_unit) && filled($talent->rate_currency);
    $rateUnitLabels = ['project' => __('project'), 'day' => __('day'), 'hour' => __('hour')];
    $rateAmount = null;
    if ($hasRate) {
        $amount = (float) $talent->rate_amount;
        $rateAmount = number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2);
    }

    // Optional external link — an external portfolio/booking URL, if the talent has one.
    $externalUrl = ($talent->booking_type === 'external' && filled($talent->booking_value)) ? $talent->booking_value : null;
    $externalLabel = $externalUrl ? preg_replace('#^https?://(www\.)?#i', '', rtrim($externalUrl, '/')) : null;

    // Blocks (ADR-Q/R): universal / profile-level blocks (talent_type_id = NULL),
    // then one tab per skill that has visible blocks. Hero is the header, so skip it.
    $blocksFor = fn ($typeId) => $talent->profileBlocks
        ->where('talent_type_id', $typeId)
        ->filter(fn ($b) => $b->blockType->key !== 'hero')
        ->sortBy('position')->values();
    $universalBlocks = $blocksFor(null);
    $tabSkills = $skills->filter(fn ($s) => $blocksFor($s->id)->isNotEmpty())->values();

    // Active tab: `?skill=` when it names a skill with visible blocks, else the primary.
    $activeSkill = $tabSkills->firstWhere('slug', request('skill')) ?? $tabSkills->first();
    $tabUrls = $tabSkills->mapWithKeys(fn ($s) => [$s->slug => route('talent.tab', ['slug' => $talent->slug, 'skill' => $s->slug])]);

    // Social platform display labels (ADR-Z). Kept here rather than in the
    // enum so the copy is translatable and the column stays a stable key.
    $socialLabels = [
        'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'x' => 'X',
        'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'behance' => 'Behance',
        'vimeo' => 'Vimeo', 'website' => __('Website'), 'other' => __('Link'),
    ];

    // Section order (editor pill bar): universal renders above the skill tabs
    // by default; any position > 0 moves it below them.
    $universalAfterTabs = ((int) ($talent->meta['universal_position'] ?? 0)) > 0;
    $universalAbove = $universalBlocks->isNotEmpty() && ! $universalAfterTabs;
@endphp

<x-public-layout :title="$talent->display_name">
    {{-- Thin brand accent strip --}}
    <div class="h-1 w-full bg-accent"></div>

    <div x-data="profileTabs({ active: @js($activeSkill?->slug), tabs: @js($tabSkills->pluck('slug')), labels: @js($tabSkills->pluck('name', 'slug')), urls: @js($tabUrls) })">

        {{-- ═══ REGION 1 — Identity (always visible) ═══ --}}
        {{-- Full-width single-row header: avatar · name/handle/skill/location ·
             stats · CTAs, with the bio on its own row below. --}}
        <header class="mx-auto max-w-6xl px-4 pt-10 sm:px-6">
            <div class="flex flex-col items-center gap-6 text-center lg:flex-row lg:items-center lg:gap-8 lg:text-start">
                <x-ui.avatar :src="$avatar" :name="$talent->display_name" size="2xl" class="shrink-0 shadow-e2" />

                <div class="min-w-0">
                    <div class="flex flex-col items-center gap-x-3 gap-y-1 lg:flex-row lg:items-baseline">
                        <h1 class="font-display text-3xl leading-tight text-ink sm:text-4xl">{{ $talent->display_name }}</h1>
                        <span class="font-mono text-sm text-subtle">{{ '@'.$talent->slug }}</span>
                    </div>
                    {{-- Secondary line: primary skill / headline --}}
                    @if ($secondary)
                        <p class="mt-1 text-sm font-medium text-accent-ink">{{ $secondary }}</p>
                    @endif
                    @if ($talent->base_city)
                        <span class="mt-1.5 inline-flex items-center gap-1.5 text-sm text-muted">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 1 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ $talent->base_city }}{{ $talent->base_country ? ', '.$talent->base_country : '' }}
                        </span>
                    @endif
                </div>

                {{-- Stats: Projects · Views · Rating (rating hidden when there are no reviews) --}}
                <div class="flex shrink-0 items-start gap-8 lg:ms-auto">
                    <div class="text-center lg:text-start">
                        <div class="font-display text-xl text-ink">{{ number_format($projectsCount) }}</div>
                        <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Projects') }}</div>
                    </div>
                    <div class="text-center lg:text-start">
                        <div class="font-display text-xl text-ink">{{ number_format($talent->view_count) }}</div>
                        <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Views') }}</div>
                    </div>
                    @if ($avgRating !== null)
                        <div class="text-center lg:text-start">
                            <div class="font-display text-xl text-ink">{{ number_format($avgRating, 1) }}<span class="ms-1 text-gold">★</span></div>
                            <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Rating') }}</div>
                        </div>
                    @endif
                </div>

                {{-- CTAs: Follow · Message (flagged) · Leave a review · Share profile --}}
                <div class="flex shrink-0 flex-wrap items-center justify-center gap-2">
                    @if ($canFollow ?? true)
                        <div x-data="talentFollow({
                                id: {{ $talent->id }},
                                following: {{ ($following ?? false) ? 'true' : 'false' }},
                                confirm: {
                                    title: @js(__('Unfollow :name?', ['name' => $talent->display_name])),
                                    message: @js(__('You will stop seeing their new posts in your feed.')),
                                    confirmLabel: @js(__('Unfollow')),
                                },
                             })">
                            <button type="button" @click="toggle()" :disabled="busy" :aria-pressed="following ? 'true' : 'false'"
                                    class="inline-flex select-none items-center justify-center gap-2 rounded-pill px-4 py-2.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-bg disabled:opacity-60"
                                    :class="following ? 'border border-line-strong text-ink hover:bg-surface' : 'bg-accent text-on-accent hover:opacity-90'">
                                {{-- following: checkmark · not following: plus --}}
                                <svg x-show="following" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                <svg x-show="!following" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                                <span x-text="following ? '{{ __('Following') }}' : '{{ __('Follow') }}'">{{ __('Follow') }}</span>
                            </button>
                        </div>
                    @endif
                    @feature('messaging')
                        <x-ui.button :href="route('brand.talents.message', ['talent' => $talent->slug])" variant="outline">{{ __('Message') }}</x-ui.button>
                    @endfeature
                    <x-ui.button :href="route('talent.review.create', ['slug' => $talent->slug])" variant="outline">{{ __('Leave a review') }}</x-ui.button>
                    {{-- Peer recognition (ADR-Z) — offered only to a signed-in
                         talent who is not this talent. A brand sees "Leave a
                         review"; a peer sees both, and they mean different things. --}}
                    @if (auth('talent')->check() && ! auth('talent')->user()->is($talent))
                        <x-ui.button :href="route('talent.recognition.create', ['slug' => $talent->slug])" variant="outline">{{ __('Recognise') }}</x-ui.button>
                    @endif
                    {{-- Share profile: copies the canonical profile link. --}}
                    <div x-data="{ copied: false }">
                        <button type="button"
                                @click="navigator.clipboard?.writeText(@js(url($talent->slug))); copied = true; setTimeout(() => copied = false, 2000)"
                                class="inline-flex select-none items-center justify-center gap-2 rounded-pill border border-line-strong px-4 py-2.5 text-sm font-medium text-ink transition hover:bg-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-bg">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/></svg>
                            <span x-show="!copied">{{ __('Share profile') }}</span>
                            <span x-show="copied" x-cloak>{{ __('Link copied!') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ═══ NOW (ADR-Z) ═══ Everything time-sensitive, in one strip, because
                 this is what a brand checks before anything else. Availability
                 ALWAYS carries its last-confirmed date: without it a stale toggle
                 looks current forever, which is worse than showing nothing. --}}
            <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 border-y border-line py-3">
                <span class="font-mono text-[10px] uppercase tracking-[0.14em] text-subtle">{{ __('Now') }}</span>

                <span class="inline-flex items-center gap-2 text-sm">
                    <span @class([
                        'h-1.5 w-1.5 rounded-full',
                        'bg-success' => $talent->is_available,
                        'bg-subtle' => ! $talent->is_available,
                    ])></span>
                    <span class="font-medium text-ink">
                        {{ $talent->is_available ? __('Taking work') : __('Not taking work') }}
                    </span>
                    @if ($talent->availability_changed_at)
                        <span class="font-mono text-[11px] text-subtle"
                              title="{{ $talent->availability_changed_at->toDayDateTimeString() }}">
                            {{ __('as of :date', ['date' => $talent->availability_changed_at->diffForHumans()]) }}
                        </span>
                    @endif
                </span>

                @if ($talent->will_travel)
                    {{-- A yes/no, never a list of cities: which city and on whose
                         budget is the conversation this is meant to start. --}}
                    <span class="inline-flex items-center gap-1.5 text-sm text-muted">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/><circle cx="12" cy="12" r="10"/></svg>
                        {{ __('Will travel') }}
                    </span>
                @endif
            </div>

            {{-- Bio row (below the identity row) + rate chip + external link --}}
            @if ($talent->bio)
                <p class="mt-6 text-sm leading-relaxed text-ink">{{ $talent->bio }}</p>
            @endif

            {{-- Worldview (ADR-Z, About) — taste and point of view, kept visually
                 apart from the bio so it does not read as a second one. --}}
            @if ($talent->worldview)
                <div class="mt-4 border-s-2 border-accent ps-4">
                    <div class="font-mono text-[10px] uppercase tracking-[0.14em] text-subtle">{{ __('Point of view') }}</div>
                    <p class="mt-1.5 text-sm leading-relaxed text-ink">{{ $talent->worldview }}</p>
                </div>
            @endif
            @if ($hasRate || $externalUrl)
                <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2">
                    @if ($hasRate)
                        <span class="inline-flex items-baseline gap-1.5 rounded-pill bg-accent-weak px-3.5 py-1.5 text-accent-ink">
                            <span class="font-mono text-[10px] uppercase tracking-wider">{{ __('Rate') }}</span>
                            <span class="font-display text-sm">{{ __('From') }} {{ $talent->rate_currency }} {{ $rateAmount }} / {{ $rateUnitLabels[$talent->rate_unit] ?? $talent->rate_unit }}</span>
                        </span>
                    @endif
                    @if ($externalUrl)
                        <a href="{{ $externalUrl }}" target="_blank" rel="noopener nofollow"
                           class="inline-flex items-center gap-1.5 text-sm font-medium text-accent-ink hover:underline">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M10 13a5 5 0 0 0 7.07 0l3-3A5 5 0 0 0 12.99 3l-1.5 1.5"/><path d="M14 11a5 5 0 0 0-7.07 0l-3 3A5 5 0 0 0 11 21.01l1.5-1.5"/></svg>
                            {{ $externalLabel }}
                        </a>
                    @endif
                </div>
            @endif

            {{-- Social (ADR-Z, About) — Instagram and anywhere else the work lives. --}}
            @if ($talent->socialHandles->isNotEmpty())
                <ul class="mt-3 flex flex-wrap items-center gap-2">
                    @foreach ($talent->socialHandles as $social)
                        <li>
                            <a href="{{ $social->url }}" target="_blank" rel="noopener nofollow"
                               class="inline-flex items-center gap-1.5 rounded-pill border border-line-strong px-3 py-1.5 font-mono text-[11px] text-muted transition hover:border-accent hover:text-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                                <span class="uppercase tracking-wider">{{ $socialLabels[$social->platform] ?? $social->platform }}</span>
                                <bdi>{{ $social->handle }}</bdi>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </header>

        {{-- Universal / profile-level blocks — above the tabs by default, or
             below them when the talent reordered the sections in the editor. --}}
        <div class="mx-auto mt-12 max-w-6xl px-4 pb-4 sm:px-6">
            @if ($universalAbove)
                <div id="universal-blocks" class="space-y-16">
                    @include('talent.partials.universal-blocks', ['talent' => $talent, 'blocks' => $universalBlocks])
                </div>
            @endif

            {{-- ═══ REGION 2 — Skill tabs (the profile's primary navigation) ═══ --}}
            @if ($tabSkills->isNotEmpty())
                @if ($tabSkills->count() > 1)
                    <div @class(['mt-12' => $universalAbove])>
                        {{-- Sticky segmented tab bar — sits under the site header (z-30) so it --}}
                        {{-- reads as primary nav; a top divider separates it from the identity region. --}}
                        <div class="sticky top-16 z-20 -mx-4 border-y border-line bg-bg/90 px-4 backdrop-blur sm:-mx-6 sm:px-6">
                            <div class="relative">
                                {{-- Mobile edge fades hint the horizontal scroll --}}
                                <div aria-hidden="true" class="pointer-events-none absolute inset-y-0 start-0 z-10 w-6 bg-gradient-to-r from-bg to-transparent sm:hidden"></div>
                                <div aria-hidden="true" class="pointer-events-none absolute inset-y-0 end-0 z-10 w-6 bg-gradient-to-l from-bg to-transparent sm:hidden"></div>

                                <div role="tablist" aria-label="{{ __('Skills') }}" x-ref="tablist"
                                     class="flex snap-x gap-2 overflow-x-auto py-3 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                    @foreach ($tabSkills as $skill)
                                        @php $count = $blocksFor($skill->id)->count(); @endphp
                                        <button type="button" role="tab"
                                                id="skill-tab-{{ $skill->slug }}"
                                                aria-controls="skill-tabpanel"
                                                @click="show('{{ $skill->slug }}')"
                                                @keydown="onTabKey($event, {{ $loop->index }})"
                                                :aria-selected="active === '{{ $skill->slug }}' ? 'true' : 'false'"
                                                :tabindex="active === '{{ $skill->slug }}' ? 0 : -1"
                                                class="flex shrink-0 snap-start items-center gap-2 rounded-pill border px-4 py-2.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-bg"
                                                :class="active === '{{ $skill->slug }}'
                                                    ? 'border-transparent bg-accent font-semibold text-on-accent shadow-e1'
                                                    : 'border-line bg-surface text-muted hover:border-line-strong hover:text-ink'">
                                            <x-skill-icon :icon="$skill->icon" class="h-4 w-4 shrink-0" />
                                            <span>{{ $skill->name }}</span>
                                            @if ($count > 0)
                                                <span class="grid h-5 min-w-[1.25rem] place-items-center rounded-pill px-1 text-[10px] font-semibold tabular-nums"
                                                      :class="active === '{{ $skill->slug }}' ? 'bg-on-accent/25 text-on-accent' : 'bg-accent-weak text-accent-ink'">{{ $count }}</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Panel: the active skill's name as a heading (context when the bar --}}
                        {{-- scrolls out on mobile), then its blocks. Active renders server-side; --}}
                        {{-- other tabs inject lazily with a reduced-motion-aware fade. --}}
                        <div class="mt-8">
                            <h2 class="mb-6 font-display text-2xl text-ink" x-text="labels[active]">{{ $activeSkill->name }}</h2>
                            <div id="skill-tabpanel" role="tabpanel" tabindex="0"
                                 x-ref="panel" :aria-labelledby="'skill-tab-' + active" :aria-busy="loading"
                                 class="min-h-[4rem] transition-opacity duration-200 ease-out motion-reduce:transition-none focus:outline-none">
                                @include('talent.partials.skill-blocks', ['talent' => $talent, 'skill' => $activeSkill, 'blocks' => $blocksFor($activeSkill->id)])
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Single skill: no tab bar (renders directly, per Prompt H). --}}
                    <div x-ref="panel" @class(['mt-12' => $universalAbove])>
                        @include('talent.partials.skill-blocks', ['talent' => $talent, 'skill' => $activeSkill, 'blocks' => $blocksFor($activeSkill->id)])
                    </div>
                @endif
            @endif

            {{-- Universal region moved BELOW the skill tabs (section order). --}}
            @if ($universalBlocks->isNotEmpty() && $universalAfterTabs)
                <div id="universal-blocks" @class(['space-y-16', 'mt-16' => $tabSkills->isNotEmpty()])>
                    @include('talent.partials.universal-blocks', ['talent' => $talent, 'blocks' => $universalBlocks])
                </div>
            @endif

            @if ($universalBlocks->isEmpty() && $tabSkills->isEmpty())
                <x-ui.card class="text-center text-subtle">{{ __('This profile is still being set up.') }}</x-ui.card>
            @endif
        </div>
    </div>
</x-public-layout>
