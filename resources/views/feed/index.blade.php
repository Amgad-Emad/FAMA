<x-public-layout :title="__('Feed')" :animate="false">
    @include('feed.partials.daylight-head')
    @include('feed.partials.glyphs')
    @include('feed.partials.report-dialog')

    {{-- The Daylight surface (5M/5AA): TALENT rail inline-start · the spine ·
         BRANDS rail inline-end — the two sides of the marketplace embodied.
         Rails hydrate AFTER the feed (Ajax) so they never delay first paint;
         a brand viewer gets no brand rail and the grid rebalances. Guests
         read everything (ADR-F8). --}}
    @php $viewerIsBrand = $currentActor instanceof \App\Models\Brand; @endphp

    <div data-surface="daylight" class="min-h-screen"
         x-data="feedStream({
            nextCursor: @js($nextCursor),
            castingEvery: {{ (int) config('feed.ranking.casting_every') }},
            skill: @js($activeSkill),
            following: {{ $followingOnly ? 'true' : 'false' }},
         })">

        <div class="light-bar" aria-hidden="true"></div>
        <div class="grain" aria-hidden="true"></div>
        <div class="fab-scroll-marker" x-ref="fabMarker" aria-hidden="true"></div>

        {{-- Floating actions (5AC): New post + Back to top, bottom inline-start
             (right in RTL), hidden while any modal is open. TELEPORTED to
             <body>: the page's `<main class="animate-fade-in-up">` animates a
             transform, and a transformed ancestor becomes the containing block
             for position:fixed — which would pin the FABs to the bottom of the
             full-height feed, far below the fold, instead of the viewport. The
             daylight host restores the scoped tokens outside the surface. --}}
        <template x-teleport="body">
            <div data-surface="daylight" class="fab-host">
                <div class="fab-stack" x-data="fabStack({ canPost: @js($composer !== null), isGuest: @js($currentActor === null) })">
                    <button type="button" x-show="showTop" x-cloak
                            class="fab fab--quiet fab-appear" :class="{ 'is-visible': showTop }"
                            @click="toTop()" aria-label="{{ __('Back to top') }}">
                        <span aria-hidden="true">↑</span>
                    </button>

                    {{-- Filters FAB: THE feed filter surface (the chrome header
                         and its inline chips were removed) — always visible.
                         Opens the chips in a popover above the button. --}}
                    <div class="relative"
                         @keydown.escape="filtersOpen = false" @click.outside="filtersOpen = false">
                        <div x-show="filtersOpen" x-cloak class="fab-filter-panel" role="group" aria-label="{{ __('Feed filters') }}">
                            <button type="button" class="dl-chip" :class="{ 'is-active': !skill && !following }"
                                    @click="setFilter(null, false); filtersOpen = false; window.scrollTo({ top: 0, behavior: 'smooth' })">{{ __('ALL') }}</button>
                            @if ($currentActor !== null)
                                <button type="button" class="dl-chip" :class="{ 'is-active': following }"
                                        @click="setFilter(null, true); filtersOpen = false; window.scrollTo({ top: 0, behavior: 'smooth' })">{{ __('FOLLOWING') }}</button>
                            @endif
                            @foreach ($filterSkills as $skillOption)
                                <button type="button" class="dl-chip" :class="{ 'is-active': skill === '{{ $skillOption->slug }}' }"
                                        @click="setFilter('{{ $skillOption->slug }}', false); filtersOpen = false; window.scrollTo({ top: 0, behavior: 'smooth' })">{{ $skillOption->name }}</button>
                            @endforeach
                        </div>
                        <button type="button" class="fab" @click="filtersOpen = !filtersOpen"
                                :aria-expanded="filtersOpen ? 'true' : 'false'" aria-haspopup="true"
                                aria-label="{{ __('Feed filters') }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h18M6 12h12M10 19h4" />
                            </svg>
                            <span class="fab-label t-meta">{{ __('FILTERS') }}</span>
                        </button>
                    </div>

                    @if ($composer !== null || $currentActor === null)
                        <div class="relative">
                            <div x-show="gate" x-cloak class="absolute bottom-full mb-2 w-56 rounded-md border border-seam bg-veil/40 p-3"
                                 style="background: var(--dl-surface); box-shadow: var(--dl-shadow-raised);">
                                <p class="t-body">{{ __('Sign in to share your work') }}</p>
                                <div class="mt-2 flex gap-2">
                                    <a href="{{ route('login', ['role' => 'talent']) }}" class="dl-chip">{{ __("I'm a talent") }}</a>
                                    @feature('brands')
                                        <a href="{{ route('login', ['role' => 'brand']) }}" class="dl-chip">{{ __("I'm a brand") }}</a>
                                    @endfeature
                                </div>
                            </div>
                            <button type="button" class="fab" @click="newPost()" aria-label="{{ __('New post') }}">
                                <span aria-hidden="true" class="t-display" style="font-size: 24px; line-height: 1;">+</span>
                                <span class="fab-label t-meta">{{ __('NEW POST') }}</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-[1400px] px-5 py-10">
            {{-- Rebalance the grid (drop the brand-rail column) for brand viewers OR
                 when the brands feature is off (V1). --}}
            <div class="feed-grid {{ ($viewerIsBrand || ! \App\Support\Features::brands()) ? 'no-brand-rail' : '' }}">

                {{-- Left rail: TALENT TO FOLLOW (≥1400px) --}}
                <aside class="feed-rail feed-rail--talents"
                       x-data="suggestionRail({ type: 'talents' })" x-show="!collapsed" x-cloak
                       aria-label="{{ __('Talent to follow') }}">
                    <h2 class="rail-header t-meta">{{ __('TALENT TO FOLLOW') }}</h2>
                    <p class="sr-only" aria-live="polite" x-text="announcement"></p>
                    <div x-show="undo.active" x-cloak class="mb-3 rounded-md border border-seam bg-veil/40 px-3 py-2">
                        <span class="t-meta" x-text="undo.label"></span>
                        <button type="button" class="t-meta underline ms-2" @click="undoLast()">{{ __('Undo') }}</button>
                    </div>
                    <ul class="rail-list" x-ref="list"
                        @rail-follow.stop="onFollow($event.detail)"
                        @rail-dismiss.stop="onDismiss($event.detail)"></ul>
                    <div x-show="loading" aria-hidden="true" class="mt-1 space-y-3">
                        <div class="skeleton" style="block-size: 58px; border-radius: 12px;"></div>
                        <div class="skeleton" style="block-size: 58px; border-radius: 12px;"></div>
                        <div class="skeleton" style="block-size: 58px; border-radius: 12px;"></div>
                        <div class="skeleton" style="block-size: 58px; border-radius: 12px;"></div>
                    </div>
                    <button type="button" x-show="nextOffset !== null" x-cloak
                            class="dl-btn is-ready mt-4 w-full justify-center" @click="showMore()">{{ __('SHOW MORE') }}</button>
                </aside>

                {{-- The spine column. The old chrome header (title · eyebrow ·
                     inline filter chips) is gone — filtering lives in the
                     always-visible Filters FAB. --}}
                <div class="feed-spine min-w-0">
                    @if ($composer !== null)
                        <div class="mb-10 flex justify-center">
                            @include('feed.partials.composer', ['composer' => $composer])
                        </div>
                    @endif

                    {{-- The spine --}}
                    <div class="flex flex-col items-center gap-10" x-ref="spine">
                        @forelse ($items as $index => $post)
                            @if (in_array($index, $castingPositions, true) && $castingTalents->isNotEmpty())
                                <x-feed.casting :talents="$castingTalents" />
                            @endif
                            @include('feed.partials.post', ['post' => $post, 'eagerMedia' => $index === 0])
                        @empty
                            <div class="feed-col frame-shadow">
                                <div class="frame p-10 text-center">
                                    <p class="t-note">{{ __('Nothing here yet.') }}</p>
                                    <p class="t-meta mt-2">{{ __('BE THE FIRST — SHARE YOUR WORK') }}</p>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    {{-- Loading skeletons while a page is in flight --}}
                    <div x-show="loading" x-cloak class="mt-10 flex flex-col items-center gap-10">
                        <x-feed.skeleton :count="2" />
                    </div>

                    {{-- Infinite-scroll sentinel --}}
                    <div x-ref="sentinel" class="h-px w-full" aria-hidden="true"></div>

                    <p x-show="finished" x-cloak class="t-meta mt-10 text-center">{{ __('YOU’RE ALL CAUGHT UP') }}</p>
                </div>

                {{-- Right rail: brands to get opportunity alerts from (bell) —
                     ≥1100px; never for brand viewers. Gated behind the `brands`
                     release flag (V1 ships a talent-only feed). --}}
                @feature('brands')
                @unless ($viewerIsBrand)
                    <aside class="feed-rail feed-rail--brands"
                           x-data="suggestionRail({ type: 'brands' })" x-show="!collapsed" x-cloak
                           aria-label="{{ __('Brands to watch') }}">
                        <h2 class="rail-header t-meta">{{ __('BRANDS TO WATCH') }}</h2>
                        <p class="sr-only" aria-live="polite" x-text="announcement"></p>
                        <div x-show="undo.active" x-cloak class="mb-3 rounded-md border border-seam bg-veil/40 px-3 py-2">
                            <span class="t-meta" x-text="undo.label"></span>
                            <button type="button" class="t-meta underline ms-2" @click="undoLast()">{{ __('Undo') }}</button>
                        </div>
                        <ul class="rail-list" x-ref="list"
                            @rail-follow.stop="onFollow($event.detail)"
                            @rail-dismiss.stop="onDismiss($event.detail)"></ul>
                        <div x-show="loading" aria-hidden="true" class="rail-list mt-3">
                            <div class="skeleton frame" style="block-size: 200px;"></div>
                            <div class="skeleton frame" style="block-size: 200px;"></div>
                        </div>
                        <button type="button" x-show="nextOffset !== null" x-cloak
                                class="dl-btn is-ready mt-4 w-full justify-center" @click="showMore()">{{ __('SHOW MORE') }}</button>
                    </aside>
                @endunless
                @endfeature
            </div>
        </div>
    </div>
</x-public-layout>
