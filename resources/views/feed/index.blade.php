{{-- /feed — the fama-ui Feed (port of feed.html). Page 1 of the RANKED stream
     is server-rendered (same frozen candidate list the cursor pages slice —
     D10); more loads at the bottom. "Talent to follow" sits beside the feed on
     wide screens and becomes a strip between posts on narrow ones; "Talent to
     discover" every Nth post (feed.casting_every). Floating buttons: Filters ·
     Back to top · New post. Guests read everything (ADR-F8) and get the way in
     inside the hero. Behaviour = feedStream / feedComposer / $store.famaPeople. --}}
@php
    $isGuest = $currentActor === null;
    // Signed-in admins are not feed actors (read-only) but must not be treated as signed out.
    $staff = $isGuest ? auth('admin')->user() : null;
    $filterKey = $followingOnly ? 'following' : ($activeSkill ?? 'all');
    $me = $currentActor;
    $meName = $me?->feedDisplayName() ?? '';
    $meInitials = collect(preg_split('/\s+/', trim($meName)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $skillNames = $filterSkills->mapWithKeys(fn ($s) => [$s->slug => $s->name]);
@endphp

@php
    // Search + share (ADR-AO): the feed is the front door, so it carries the site-wide
    // Organization + WebSite structured data.
    $seo = \App\Support\Seo::make()
        ->title(__('Fama — the creative feed'))
        ->description(__('See what photographers, models, stylists, directors and other creatives are making right now — their projects, and the people they made them with.'))
        ->canonical(route('feed'))
        ->schema(['@type' => 'Organization', 'name' => config('seo.site_name'), 'url' => url('/'), 'logo' => asset('images/email/fama-logo.png'), 'sameAs' => config('seo.same_as')])
        ->schema(['@type' => 'WebSite', 'name' => config('seo.site_name'), 'url' => url('/')]);
@endphp
<x-fama.layout :title="__('Feed')" :seo="$seo" :css="['posts', 'pages/feed', 'pages/feed-common']">
    <x-slot:head>
        @include('feed.partials.lang')
    </x-slot:head>

    <x-slot:hero>
        @if ($staff)
            <x-fama.hero :label="__('Signed in as Fama staff')" :title="__('Feed')" :lead="__('You’re browsing the public feed as :name. Moderate posts and reports from the admin panel.', ['name' => $staff->name])" photo="feed">
                @if ($staff->can('moderate-content'))
                    <a class="btn" href="{{ route('admin.moderation.feed') }}">{{ __('Feed moderation') }}</a>
                @endif
                <a class="btn btn-2" href="{{ route('admin.dashboard') }}">{{ __('Admin panel') }}</a>
            </x-fama.hero>
        @elseif ($isGuest)
            <x-fama.hero :label="__('Creative talent on Fama')" :title="__('Feed')" :lead="__('Work from stylists, models, photographers and more. Join to post your own.')" photo="feed">
                <a class="btn" href="{{ route('register') }}"><svg class="icon" aria-hidden="true"><use href="#a-user-plus"/></svg>{{ __('Join as talent') }}</a>
                <a class="btn btn-2" href="{{ route('login') }}"><svg class="icon" aria-hidden="true"><use href="#a-log-in"/></svg>{{ __('Log in') }}</a>
            </x-fama.hero>
        @else
            <x-fama.hero :label="__('Your people')" :title="__('Feed')" :lead="__('See what your network is working on, and share your own.')" photo="feed" />
        @endif
    </x-slot:hero>

    @include('feed.partials.glyphs')

    <div x-data="feedStream({
            nextCursor: @js($nextCursor),
            filter: @js($filterKey),
            count: {{ count($items) }},
            isGuest: @js($isGuest),
            skillNames: @js($skillNames),
         })">
        <main class="wrap main"><div class="layout">
            <div>
                <div class="filterbar" x-show="filter !== 'all'" @if ($filterKey === 'all') x-cloak @endif>
                    <span class="tag tag-ink" x-text="showingLabel()">{{ __('Showing: :filter', ['filter' => $filterKey === 'following' ? __('Following') : ($skillNames[$filterKey] ?? $filterKey)]) }}</span>
                    <button type="button" class="link" @click="setFilter('all')">{{ __('Clear filter') }}</button>
                </div>

                @if ($composer !== null)
                    <div class="composer" id="composer">
                        <div class="top">
                            <span class="av md" aria-hidden="true">@if ($me?->feedAvatarUrl())<img src="{{ $me->feedAvatarUrl() }}" alt="">@else{{ $meInitials }}@endif</span>
                            <button type="button" class="start" id="start" aria-haspopup="dialog" @click="$dispatch('open-composer', {})">{{ __('Start a post') }}</button>
                        </div>
                        <div class="quick">
                            <button type="button" aria-haspopup="dialog" @click="$dispatch('open-composer', { mode: 'photo' })"><svg class="icon" aria-hidden="true"><use href="#i-image"/></svg><span>{{ __('Photo') }}</span></button>
                            <button type="button" aria-haspopup="dialog" @click="$dispatch('open-composer', { mode: 'video' })"><svg class="icon" aria-hidden="true"><use href="#i-video"/></svg><span>{{ __('Video') }}</span></button>
                            <button type="button" aria-haspopup="dialog" @click="$dispatch('open-composer', { mode: 'project' })"><svg class="icon" aria-hidden="true"><use href="#i-folder"/></svg><span class="lg">{{ __('Share a project') }}</span><span class="sm">{{ __('Project') }}</span></button>
                        </div>
                    </div>
                @endif

                <div id="posts" x-ref="spine" aria-live="polite">
                    @foreach ($items as $index => $post)
                        @if (in_array($index, $castingPositions, true) && $castingTalents->isNotEmpty())
                            <x-feed.casting :talents="$castingTalents" />
                        @endif
                        @include('feed.partials.post', ['post' => $post, 'eagerMedia' => $index === 0])
                    @endforeach
                </div>

                <div class="card empty" x-show="count === 0 && !loading" @if (count($items) > 0) x-cloak @endif>
                    <h2 class="h2" x-text="filter === 'all' ? @js(__('Be the first — share your work.')) : @js(__('No posts match this filter yet.'))">{{ $filterKey === 'all' ? __('Be the first — share your work.') : __('No posts match this filter yet.') }}</h2>
                    <p>{{ __('Posts from people on Fama show here. Share a set, a note or a project.') }}</p>
                    @if ($composer !== null)
                        <button type="button" class="btn" x-show="filter === 'all'" @click="$dispatch('open-composer', {})"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg>{{ __('New post') }}</button>
                    @endif
                </div>
                <p class="loading" x-show="loading || (!finished && count > 0)" @if ($nextCursor === null) x-cloak @endif>{{ __('Loading more…') }}</p>
                <p class="end" x-show="finished && count > 0 && !loading" @if ($nextCursor !== null || count($items) === 0) x-cloak @endif>{{ __("You're all caught up.") }}</p>
                <div id="sentinel" x-ref="sentinel"></div>
            </div>

            <aside class="side card tight" aria-labelledby="tf" x-data x-show="!$store.famaPeople.loaded || $store.famaPeople.items.length > 0">
                <h2 class="h3" id="tf">{{ __('Talent to follow') }}</h2>
                <ul class="rows" id="toFollow" style="margin-top:var(--s2)">
                    <template x-for="p in $store.famaPeople.items.slice(0, 4)" :key="p.type + '-' + p.id">
                        <li>
                            <a class="av" :href="$store.famaPeople.url(p)" aria-hidden="true" tabindex="-1"><img x-show="p.avatar_url" :src="p.avatar_url" alt=""><span x-show="!p.avatar_url" x-text="$store.famaPeople.initials(p)"></span></a>
                            <span class="grow"><a :href="$store.famaPeople.url(p)"><b x-text="p.display_name"></b></a><span class="why" x-text="$store.famaPeople.why(p)"></span>
                                <button type="button" class="btn btn-2" :aria-pressed="p.following.toString()" style="min-height:36px;padding-inline:var(--s4);margin-top:var(--s2)" @click="$store.famaPeople.follow(p)" x-text="p.following ? @js(__('Following')) : @js(__('Follow'))"></button></span>
                            <button type="button" class="iconbtn plain" @click="$store.famaPeople.dismiss(p)" :aria-label="@js(__('Hide :name from suggestions')).replace(':name', p.display_name)"><svg class="icon sm" aria-hidden="true"><use href="#i-x"/></svg></button>
                        </li>
                    </template>
                </ul>
            </aside>
        </div></main>

        {{-- The narrow-screen "Talent to follow" strip, inserted after the 2nd post. --}}
        <template x-ref="followStrip">
            <section class="strip mobile-follow" aria-label="{{ __('Talent to follow') }}" x-data x-show="$store.famaPeople.items.length > 0">
                <div class="strip-h"><div><h2 class="h3">{{ __('Talent to follow') }}</h2><p class="why">{{ __('People near your work') }}</p></div></div>
                <div class="people">
                    <template x-for="p in $store.famaPeople.items.slice(0, 4)" :key="'s-' + p.type + '-' + p.id">
                        <div class="pp">
                            <button type="button" class="iconbtn plain px" @click="$store.famaPeople.dismiss(p)" :aria-label="@js(__('Hide :name from suggestions')).replace(':name', p.display_name)"><svg class="icon sm" aria-hidden="true"><use href="#i-x"/></svg></button>
                            <a class="av md" :href="$store.famaPeople.url(p)" aria-hidden="true" tabindex="-1"><img x-show="p.avatar_url" :src="p.avatar_url" alt=""><span x-show="!p.avatar_url" x-text="$store.famaPeople.initials(p)"></span></a>
                            <a :href="$store.famaPeople.url(p)"><b x-text="p.display_name"></b></a>
                            <span class="role" x-text="$store.famaPeople.skill(p)"></span>
                            @unless ($isGuest)<span class="why" x-show="p.reason" x-text="p.reason"></span>@endunless
                            <button type="button" class="btn btn-2" :aria-pressed="p.following.toString()" @click="$store.famaPeople.follow(p)"><svg class="icon" aria-hidden="true" x-show="!p.following"><use href="#a-user-plus"/></svg><span x-text="p.following ? @js(__('Following')) : @js(__('Follow'))"></span></button>
                        </div>
                    </template>
                </div>
            </section>
        </template>

        <div class="fab" id="fab">
            <button type="button" id="fabTop" x-show="showTop" x-cloak @click="toTop()" aria-label="{{ __('Back to top') }}" data-tip="{{ __('Back to top') }}"><svg class="icon" aria-hidden="true"><use href="#i-up"/></svg></button>
            <button type="button" id="fabFilter" aria-haspopup="dialog" @click="openFilters()" :aria-label="filterTip()" :data-tip="filterTip()" aria-label="{{ __('Filters') }}" data-tip="{{ __('Filters') }}"><svg class="icon" aria-hidden="true"><use href="#i-filter"/></svg><span class="dot" x-show="filter !== 'all'" @if ($filterKey === 'all') x-cloak @endif></span></button>
            @if ($composer !== null)
                <button type="button" class="pri" id="fabNew" aria-haspopup="dialog" @click="$dispatch('open-composer', {})" aria-label="{{ __('New post') }}" data-tip="{{ __('New post') }}"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg></button>
            @endif
        </div>

        <dialog class="dlg" id="filterDlg" x-ref="filterDlg">
            <form method="dialog" @submit.prevent="applyPick()">
                <h2>{{ __('Filter the feed') }}</h2>
                <p class="d">{{ __('Show everything, only people you follow, or one skill. The link updates so you can share it.') }}</p>
                <input class="in" id="fsearch" type="search" autocomplete="off" x-model="fq" placeholder="{{ __('Search skills') }}" aria-label="{{ __('Search skills') }}" style="margin-top:var(--s5)">
                <div class="chips" id="fpills" style="margin-top:var(--s4);max-height:50vh;overflow:auto">
                    <button type="button" class="chip" :aria-pressed="(pick === 'all').toString()" @click="pick = 'all'">{{ __('All') }}</button>
                    @unless ($isGuest)
                        <button type="button" class="chip" :aria-pressed="(pick === 'following').toString()" @click="pick = 'following'">{{ __('Following') }}</button>
                    @endunless
                    @foreach ($filterSkills as $skillOption)
                        <button type="button" class="chip" x-show="pickVisible(@js($skillOption->slug), @js($skillOption->name))" :aria-pressed="(pick === @js($skillOption->slug)).toString()" @click="pick = @js($skillOption->slug)">{{ $skillOption->name }}</button>
                    @endforeach
                </div>
                <div class="acts">
                    <button type="button" class="btn btn-2" @click="$refs.filterDlg.close()">{{ __('Cancel') }}</button>
                    <button class="btn">{{ __('Show posts') }}</button>
                </div>
            </form>
        </dialog>
    </div>

    @if ($composer !== null)
        @include('feed.partials.composer', ['composer' => $composer])
    @endif
    @include('feed.partials.dialogs')
</x-fama.layout>
