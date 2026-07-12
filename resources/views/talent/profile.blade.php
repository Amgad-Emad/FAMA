@php
    $skills = $talent->talentTypes
        ->sortBy(fn ($t) => [$t->pivot->is_primary ? 0 : 1, (int) $t->pivot->position])
        ->values();
    $primaryType = $skills->firstWhere(fn ($type) => (bool) $type->pivot->is_primary) ?? $skills->first();
    $avatar = $talent->avatar_url;

    // Header stats (from already eager-loaded relations — no N+1).
    $projectsCount = $talent->projects->count();
    $reviewsCount = $talent->reviews->count();            // reviews eager-loaded with the approved scope
    $avgRating = $reviewsCount ? round((float) $talent->reviews->avg('rating'), 1) : null;

    // Secondary line: the primary skill, else the headline.
    $secondary = $primaryType?->name ?: $talent->headline;

    // Pricing rate (ADR-N) — render only when the whole rate is set.
    $hasRate = filled($talent->rate_amount) && filled($talent->rate_unit) && filled($talent->rate_currency);
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
@endphp

<x-public-layout :title="$talent->display_name">
    {{-- Thin brand accent strip --}}
    <div class="h-1 w-full bg-accent"></div>

    <div x-data="profileTabs({ active: @js($activeSkill?->slug), tabs: @js($tabSkills->pluck('slug')), labels: @js($tabSkills->pluck('name', 'slug')), urls: @js($tabUrls) })">

        {{-- ═══ REGION 1 — Identity & universal (always visible) ═══ --}}
        {{-- Instagram-style identity header (no cover image) --}}
        <header class="mx-auto max-w-3xl px-4 pt-10 sm:px-6">
            <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-start sm:gap-10">
                <x-ui.avatar :src="$avatar" :name="$talent->display_name" size="2xl" class="shadow-e2" />

                <div class="min-w-0 flex-1 text-center sm:text-start">
                    {{-- Name + @username + optional … menu --}}
                    <div class="flex flex-col items-center gap-x-4 gap-y-1 sm:flex-row sm:items-center">
                        <h1 class="font-display text-3xl leading-tight text-ink sm:text-4xl">{{ $talent->display_name }}</h1>
                        <span class="font-mono text-sm text-subtle">{{ '@'.$talent->slug }}</span>

                        <div x-data="{ open: false }" class="relative sm:ms-auto">
                            <button @click="open = !open" @click.outside="open = false" aria-label="{{ __('More') }}"
                                    class="rounded-pill px-2 py-0.5 text-xl leading-none text-muted hover:bg-surface hover:text-ink">…</button>
                            <div x-show="open" x-cloak x-transition
                                 class="absolute end-0 z-20 mt-1 w-48 overflow-hidden rounded-md border border-line bg-elevated py-1 shadow-e2">
                                <button type="button"
                                        @click="navigator.clipboard?.writeText(window.location.href); open = false"
                                        class="block w-full px-3 py-2 text-start text-sm text-ink hover:bg-surface">{{ __('Copy profile link') }}</button>
                            </div>
                        </div>
                    </div>

                    {{-- Secondary line: primary skill / headline --}}
                    @if ($secondary)
                        <p class="mt-1 text-sm font-medium text-accent-ink">{{ $secondary }}</p>
                    @endif

                    {{-- Stats row: Projects · Views · Rating (rating hidden when there are no reviews) --}}
                    <div class="mt-5 flex items-start justify-center gap-8 sm:justify-start">
                        <div class="text-center sm:text-start">
                            <div class="font-display text-xl text-ink">{{ number_format($projectsCount) }}</div>
                            <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Projects') }}</div>
                        </div>
                        <div class="text-center sm:text-start">
                            <div class="font-display text-xl text-ink">{{ number_format($talent->view_count) }}</div>
                            <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Views') }}</div>
                        </div>
                        @if ($avgRating !== null)
                            <div class="text-center sm:text-start">
                                <div class="font-display text-xl text-ink">{{ number_format($avgRating, 1) }}<span class="ms-1 text-gold">★</span></div>
                                <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Rating') }}</div>
                            </div>
                        @endif
                    </div>

                    {{-- CTAs: Message (brand↔talent chat entry — ADR-P) + Leave a review --}}
                    <div class="mt-5 flex flex-col justify-center gap-2 sm:flex-row sm:justify-start">
                        <x-ui.button :href="route('brand.talents.message', ['talent' => $talent->slug])" variant="accent" class="w-full sm:w-auto">{{ __('Message') }}</x-ui.button>
                        <x-ui.button :href="route('talent.review.create', ['slug' => $talent->slug])" variant="outline" class="w-full sm:w-auto">{{ __('Leave a review') }}</x-ui.button>
                    </div>
                </div>
            </div>

            {{-- Pricing rate + location + bio + link + skill chips --}}
            <div class="mt-6 space-y-3 text-center sm:text-start">
                <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-2 sm:justify-start">
                    @if ($hasRate)
                        <span class="inline-flex items-baseline gap-1.5 rounded-pill bg-accent-weak px-3.5 py-1.5 text-accent-ink">
                            <span class="font-mono text-[10px] uppercase tracking-wider">{{ __('Rate') }}</span>
                            <span class="font-display text-sm">{{ __('From') }} {{ $talent->rate_currency }} {{ $rateAmount }} / {{ $rateUnitLabels[$talent->rate_unit] ?? $talent->rate_unit }}</span>
                        </span>
                    @endif
                    @if ($talent->base_city)
                        <span class="inline-flex items-center gap-1.5 text-sm text-muted">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 1 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ $talent->base_city }}{{ $talent->base_country ? ', '.$talent->base_country : '' }}
                        </span>
                    @endif
                </div>

                @if ($talent->bio)
                    <p class="mx-auto max-w-2xl text-sm leading-relaxed text-ink sm:mx-0">{{ $talent->bio }}</p>
                @endif

                @if ($externalUrl)
                    <a href="{{ $externalUrl }}" target="_blank" rel="noopener nofollow"
                       class="inline-flex items-center gap-1.5 text-sm font-medium text-accent-ink hover:underline">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M10 13a5 5 0 0 0 7.07 0l3-3A5 5 0 0 0 12.99 3l-1.5 1.5"/><path d="M14 11a5 5 0 0 0-7.07 0l-3 3A5 5 0 0 0 11 21.01l1.5-1.5"/></svg>
                        {{ $externalLabel }}
                    </a>
                @endif
                {{-- Skill chips were removed: the prominent skill TAB BAR below is the
                     profile's navigation (the primary-skill line above stays). --}}
            </div>
        </header>

        {{-- Universal / profile-level blocks (above the tabs), always visible --}}
        <div class="mx-auto mt-12 max-w-6xl px-4 pb-4 sm:px-6">
            @if ($universalBlocks->isNotEmpty())
                <div class="space-y-16">
                    @foreach ($universalBlocks as $block)
                        @include('talent.blocks._dispatch', ['talent' => $talent, 'block' => $block])
                    @endforeach
                </div>
            @endif

            {{-- ═══ REGION 2 — Skill tabs (the profile's primary navigation) ═══ --}}
            @if ($tabSkills->isNotEmpty())
                @if ($tabSkills->count() > 1)
                    <div @class(['mt-12' => $universalBlocks->isNotEmpty()])>
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
                                @include('talent.partials.skill-blocks', ['talent' => $talent, 'blocks' => $blocksFor($activeSkill->id)])
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Single skill: no tab bar (renders directly, per Prompt H). --}}
                    <div x-ref="panel" @class(['mt-12' => $universalBlocks->isNotEmpty()])>
                        @include('talent.partials.skill-blocks', ['talent' => $talent, 'blocks' => $blocksFor($activeSkill->id)])
                    </div>
                @endif
            @endif

            @if ($universalBlocks->isEmpty() && $tabSkills->isEmpty())
                <x-ui.card class="text-center text-subtle">{{ __('This profile is still being set up.') }}</x-ui.card>
            @endif
        </div>
    </div>
</x-public-layout>
