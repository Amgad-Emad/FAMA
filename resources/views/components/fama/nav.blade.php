{{--
    The fama-ui header (port of the prototype's shell.js navHTML), server-rendered.
      talent — wide screens: the side bar (account · Create · Your work: Projects/Credits ·
               Your profile: Edit/View · Community: Feed/Notifications/Activity), folded to a rail by
               default (icons only, the name as a tooltip — 12–13 Nov 2026 review).
               ≤1100px: logo · Projects · Edit profile · Credits · Feed · Activity | View profile ·
               bell · avatar menu · burger (the drawer opens over the page, with the account).
               Plus the pinned "Private" notice while the profile isn't live.
      public — logo · Feed | Log in · Join.
      auth   — logo | "Just looking? Browse the feed".
    Behaviour: `famaSide` (side bar) + `famaNav` (top bar) share the `famaNotifs` store
    (resources/js/ui/shell.js).
--}}
@props(['mode' => 'talent'])

@php
    $talent = $mode === 'talent' ? auth('talent')->user() : null;
    if ($mode === 'talent' && ! $talent) {
        $mode = 'public';
    }
    // 30 Oct 2026: "Profile structure" reads as "Edit profile". 13 Nov 2026 review: the portfolio comes
    // first, the feed after it.
    $items = [
        ['route' => 'talent.projects', 'label' => __('Projects'), 'icon' => 'grid'],
        ['route' => 'talent.profile.edit', 'label' => __('Edit profile'), 'icon' => 'upen'],
        ['route' => 'talent.credits', 'label' => __('Credits'), 'icon' => 'award'],
        ['route' => 'feed', 'label' => __('Feed'), 'icon' => 'home'],
        ['route' => 'talent.feed.activity', 'label' => __('Activity'), 'icon' => 'heart'],
    ];
    $initials = $talent
        ? collect(preg_split('/\s+/', trim((string) ($talent->display_name ?: $talent->slug))))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('')
        : '';
    $role = null;
    if ($talent) {
        $primary = $talent->talentTypes()->orderByDesc('talent_talent_type.is_primary')->orderBy('talent_talent_type.position')->first();
        $role = collect([$primary?->name, $talent->base_city])->filter()->implode(' · ');
    }
    $displayName = $talent ? ($talent->display_name ?: $talent->slug) : '';
    // The account line under the name: the talent's public address (fama.so/slug in the prototype).
    $handle = $talent && $talent->slug ? preg_replace('#^https?://#', '', rtrim(url($talent->slug), '/')) : $role;
    $onOwnProfile = $talent && $talent->slug && request()->is($talent->slug, '*/'.$talent->slug);
    $onNotifications = request()->routeIs('notifications.page');
    // Public mode with a signed-in admin (staff browse the public pages too).
    $staff = $mode === 'public' ? auth('admin')->user() : null;
    $staffInitials = $staff
        ? collect(preg_split('/\s+/', trim((string) $staff->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('')
        : '';
@endphp

@if ($mode === 'talent')
    {{-- Wide screens (≥1101px, 30–31 Oct 2026 prototype): no top bar — the talent's links, the account
         and Create live in this side bar, folded to an icon rail by default (the choice is remembered in
         this browser; the html classes are set before paint in the layout <head>). --}}
    <aside class="fx-side" aria-label="{{ __('Main') }}" x-data="famaSide({ folded: true })"
           @keydown.escape.window="closeAll()" @click.outside="closeAll()">
        <div class="fx-shead">
            <a class="fx-slogo" href="{{ route('home') }}" aria-label="Fama"><img class="fx-sfull" src="{{ asset('fama-ui/img/fama-logo-tight.svg') }}" alt=""><img class="fx-smark" src="{{ asset('fama-ui/img/fama-emblem-rev.svg') }}" alt=""></a>
            <button type="button" class="fx-fold" @click="toggleFold()" :aria-pressed="folded.toString()"
                    :aria-label="(folded ? @js(__('Expand the menu')) : @js(__('Collapse the menu')))" :data-tip="(folded ? @js(__('Expand the menu')) : @js(__('Collapse the menu')))"><svg class="fx-icon" aria-hidden="true"><use href="#fx-i-panel"/></svg></button>
        </div>
        <button type="button" class="fx-sme" x-ref="me" @click.stop="openAt('me', $refs.me)" :aria-expanded="(open === 'me').toString()" aria-controls="fxSPop">
            <span class="fx-av fx-av-s" aria-hidden="true">@if ($talent->avatar_url)<img src="{{ $talent->avatar_url }}" alt="" style="width:100%;height:100%;border-radius:999px;object-fit:cover">@else{{ $initials }}@endif</span><span class="fx-sl">{{ $displayName }}</span>
            <svg class="fx-icon fx-chev" aria-hidden="true"><use href="#fx-i-chev"/></svg></button>
        <button type="button" class="fx-snew" x-ref="create" @click.stop="openAt('create', $refs.create, $event)" aria-haspopup="menu" :aria-expanded="(open === 'create').toString()" aria-controls="fxSNewPop"><svg class="fx-icon" aria-hidden="true"><use href="#fx-i-plus"/></svg><span class="fx-sl">{{ __('Create') }}</span></button>
        <nav class="fx-snav" aria-label="{{ __('Menu') }}">
            <p class="fx-sgroup">{{ __('Your work') }}</p>
            <ul class="fx-slinks">
                <li><a data-tip="{{ __('Projects') }}" href="{{ route('talent.projects') }}" @if (request()->routeIs('talent.projects*')) aria-current="page" @endif><span class="fx-si"><svg class="fx-icon" aria-hidden="true"><use href="#fx-i-grid"/></svg></span><span class="fx-sl">{{ __('Projects') }}</span></a></li>
                <li><a data-tip="{{ __('Credits') }}" href="{{ route('talent.credits') }}" @if (request()->routeIs('talent.credits*')) aria-current="page" @endif><span class="fx-si"><svg class="fx-icon" aria-hidden="true"><use href="#fx-i-award"/></svg></span><span class="fx-sl">{{ __('Credits') }}</span></a></li>
            </ul>
            <p class="fx-sgroup">{{ __('Your profile') }}</p>
            <ul class="fx-slinks">
                <li><a data-tip="{{ __('Edit profile') }}" href="{{ route('talent.profile.edit') }}" @if (request()->routeIs('talent.profile.edit*')) aria-current="page" @endif><span class="fx-si"><svg class="fx-icon" aria-hidden="true"><use href="#fx-i-upen"/></svg></span><span class="fx-sl">{{ __('Edit profile') }}</span></a></li>
                @if ($talent->slug)
                    <li><a data-tip="{{ __('View profile') }}" href="{{ url($talent->slug) }}" @if ($onOwnProfile) aria-current="page" @endif><span class="fx-si"><svg class="fx-icon" aria-hidden="true"><use href="#fx-i-eye"/></svg></span><span class="fx-sl">{{ __('View profile') }}</span></a></li>
                @endif
            </ul>
            <p class="fx-sgroup">{{ __('Community') }}</p>
            <ul class="fx-slinks">
                <li><a data-tip="{{ __('Feed') }}" href="{{ route('feed') }}" @if (request()->routeIs('feed')) aria-current="page" @endif><span class="fx-si"><svg class="fx-icon" aria-hidden="true"><use href="#fx-i-home"/></svg></span><span class="fx-sl">{{ __('Feed') }}</span></a></li>
                <li><a data-tip="{{ __('Notifications') }}" href="{{ route('notifications.page') }}" @if ($onNotifications) aria-current="page" @else x-ref="nlink" aria-haspopup="true" :aria-expanded="(open === 'notifs').toString()" @mouseenter="hoverNotifs(true)" @mouseleave="hoverNotifs(false)" @keydown.arrow-down.prevent="openNotifs(true)" @endif>
                    <span class="fx-si"><svg class="fx-icon" aria-hidden="true"><use href="#fx-i-bell"/></svg><span class="fx-count" aria-hidden="true" x-show="$store.famaNotifs.unread > 0" x-text="$store.famaNotifs.badge()" x-cloak></span></span><span class="fx-sl">{{ __('Notifications') }}</span></a></li>
                <li><a data-tip="{{ __('Activity') }}" href="{{ route('talent.feed.activity') }}" title="{{ __('Saved, liked, and the people you follow') }}" @if (request()->routeIs('talent.feed.activity*')) aria-current="page" @endif><span class="fx-si"><svg class="fx-icon" aria-hidden="true"><use href="#fx-i-heart"/></svg></span><span class="fx-sl">{{ __('Activity') }}</span></a></li>
            </ul>
        </nav>
        <div class="fx-spop" id="fxSPop" x-show="open === 'me'" x-cloak :style="{ top: popTop + 'px' }">
            <div class="fx-spop-h">@include('components.fama.partials.nav-account')</div>
            @if ($talent->slug)
                <a href="{{ url($talent->slug) }}"><svg class="fx-icon" aria-hidden="true"><use href="#fx-i-eye"/></svg>{{ __('View your profile') }}</a>
            @endif
            <a href="{{ route('talent.profile.edit') }}"><svg class="fx-icon" aria-hidden="true"><use href="#fx-i-upen"/></svg>{{ __('Edit profile') }}</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <a class="fx-sout" href="{{ route('logout') }}" @click.prevent="$el.closest('form').submit()"><svg class="fx-icon" aria-hidden="true"><use href="#fx-i-out"/></svg>{{ __('Log out') }}</a>
            </form>
        </div>
        <div class="fx-spop fx-newpop" id="fxSNewPop" role="menu" aria-label="{{ __('Create') }}" x-show="open === 'create'" x-cloak :style="{ top: popTop + 'px' }"
             @keydown.arrow-down.prevent="cycle(1)" @keydown.arrow-up.prevent="cycle(-1)">
            <a role="menuitem" href="{{ route('talent.projects.new') }}"><svg class="fx-icon" aria-hidden="true"><use href="#fx-i-grid"/></svg><span><b>{{ __('New project') }}</b><small>{{ __('A case study for your profile') }}</small></span></a>
            <a role="menuitem" href="{{ route('feed', ['new' => 'post']) }}"><svg class="fx-icon" aria-hidden="true"><use href="#fx-i-msg"/></svg><span><b>{{ __('New post') }}</b><small>{{ __('Share an update with your people') }}</small></span></a>
        </div>
        @unless ($onNotifications)
            <div class="fx-npop fx-snpop" id="fxSNPop" x-show="open === 'notifs'" x-cloak role="region" aria-label="{{ __('Notifications') }}" :style="{ top: popTop + 'px' }"
                 @mouseenter="hoverNotifs(true)" @mouseleave="hoverNotifs(false)" @focusout="if (!$el.contains($event.relatedTarget) && $event.relatedTarget !== $refs.nlink) close()">
                @include('components.fama.partials.nav-notifs')
            </div>
        @endunless
    </aside>

    {{-- ≤1100px: the top bar (logo · links · View profile · bell · account · burger). On wide screens
         shell.css hides its row and keeps the (empty) header so the profile tracker floats from it. --}}
    <header class="fx-nav" x-data="famaNav()" :class="{ 'fx-lifted': lifted }"
            @click.outside="closeAll()" @keydown.escape.window="closeAll()">
        <div class="fx-wrap fx-in">
            <a class="fx-logo" href="{{ route('home') }}"><img src="{{ asset('fama-ui/img/fama-logo-tight.svg') }}" alt="Fama"></a>
            <ul class="fx-links">
                @foreach ($items as $item)
                    <li><a href="{{ route($item['route']) }}" @if (request()->routeIs($item['route'].'*')) aria-current="page" @endif>{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
            <div class="fx-end">
                @if ($talent->slug)
                    <a class="fx-preview" href="{{ url($talent->slug) }}" @if ($onOwnProfile) aria-current="page" @endif>
                        <svg class="fx-icon" aria-hidden="true"><use href="#fx-i-eye"/></svg><span class="fx-lbl">{{ __('View profile') }}</span></a>
                @endif
                <div class="fx-bellw">
                    <button type="button" class="fx-bell" @click.stop="toggleBell()" :aria-expanded="bellOpen.toString()" aria-controls="fxNPop"
                            :aria-label="$store.famaNotifs.unread ? @js(__('Notifications')) + ', ' + $store.famaNotifs.unread + ' ' + @js(__('unread')) : @js(__('Notifications'))">
                        <svg class="fx-icon" aria-hidden="true"><use href="#fx-i-bell"/></svg>
                        <span class="fx-count" aria-hidden="true" x-show="$store.famaNotifs.unread > 0" x-text="$store.famaNotifs.badge()" x-cloak></span>
                    </button>
                    <div class="fx-npop" id="fxNPop" x-show="bellOpen" x-cloak role="region" aria-label="{{ __('Notifications') }}">
                        @include('components.fama.partials.nav-notifs')
                    </div>
                </div>
                <div class="fx-me">
                    <button type="button" class="fx-av" @click.stop="toggleMe()" :aria-expanded="meOpen.toString()" aria-controls="fxPop" aria-label="{{ __('Your account') }}">
                        @if ($talent->avatar_url)
                            <img src="{{ $talent->avatar_url }}" alt="" style="width:100%;height:100%;border-radius:999px;object-fit:cover">
                        @else
                            {{ $initials }}
                        @endif
                    </button>
                    <ul class="fx-pop" id="fxPop" x-show="meOpen" x-cloak>
                        <li><a class="fx-acct-link" href="{{ $talent->slug ? url($talent->slug) : route('talent.profile.edit') }}">@include('components.fama.partials.nav-account')<span class="fx-acct-go">{{ __('View your profile') }}</span></a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">@csrf
                                <a class="fx-out" href="{{ route('logout') }}" @click.prevent="$el.closest('form').submit()">{{ __('Log out') }}</a>
                            </form>
                        </li>
                    </ul>
                </div>
                <button type="button" class="fx-burger" @click.stop="drawer = !drawer" :aria-expanded="drawer.toString()" aria-controls="fxDrawer" aria-label="{{ __('Menu') }}">
                    <svg class="fx-icon"><use :href="drawer ? '#fx-i-x' : '#fx-i-menu'" href="#fx-i-menu"/></svg>
                </button>
            </div>
        </div>
        {{-- The phone menu opens OVER the page (24 Oct 2026) and carries the account, so phones need one menu. --}}
        <div class="fx-dwrap">
            <nav class="fx-drawer" id="fxDrawer" x-show="drawer" x-cloak aria-label="{{ __('Menu') }}">
                <div class="fx-wrap">
                    <ul>
                        @foreach ($items as $item)
                            <li><a href="{{ route($item['route']) }}" @if (request()->routeIs($item['route'].'*')) aria-current="page" @endif><svg class="fx-icon" aria-hidden="true"><use href="#fx-i-{{ $item['icon'] }}"/></svg><span class="fx-sl">{{ $item['label'] }}</span></a></li>
                        @endforeach
                    </ul>
                    <div class="fx-dacc">
                        @include('components.fama.partials.nav-account')
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <a class="fx-dlink fx-out" href="{{ route('logout') }}" @click.prevent="$el.closest('form').submit()">{{ __('Log out') }}</a>
                        </form>
                    </div>
                </div>
            </nav>
        </div>
        @if (! $talent->is_published && ! request()->routeIs('talent.profile.edit'))
            {{-- Pinned while the profile is Private (ADR-AC, 2026-11-04: no review step — the Go Live
                 steps alone put it Live). Edit profile has its own floating steps tracker instead. --}}
            <div class="fx-notice" role="region" aria-label="{{ __('Profile status') }}">
                <div class="fx-wrap fx-in">
                    <svg class="fx-icon" aria-hidden="true"><use href="#fx-i-lock"/></svg>
                    <p role="status">
                        <span class="fx-long">{{ __('Your profile is Private: only you can see it. Complete it to go Live and share your link.') }}</span>
                        <span class="fx-short">{{ __('Your profile is Private.') }}</span>
                    </p>
                    <a class="fx-ncta" href="{{ route('talent.profile.edit') }}#gate">{{ __('Complete your profile') }}
                        <svg class="fx-icon fx-flip" aria-hidden="true"><use href="#fx-i-arrow"/></svg></a>
                </div>
            </div>
        @endif
    </header>
@elseif ($mode === 'auth')
    <header class="fx-nav">
        <div class="fx-wrap fx-in">
            <a class="fx-logo" href="{{ route('feed') }}"><img src="{{ asset('fama-ui/img/fama-logo-tight.svg') }}" alt="Fama"></a>
            <div class="fx-end">
                <a class="fx-browse" href="{{ route('feed') }}"><span class="fx-lbl2">{{ __('Just looking?') }} </span><b>{{ __('Browse the feed') }}</b>
                    <svg class="fx-icon fx-flip" aria-hidden="true"><use href="#fx-i-arrow"/></svg></a>
            </div>
        </div>
    </header>
@else
    <header class="fx-nav">
        <div class="fx-wrap fx-in">
            <a class="fx-logo" href="{{ route('feed') }}"><img src="{{ asset('fama-ui/img/fama-logo-tight.svg') }}" alt="Fama"></a>
            <ul class="fx-links fx-keep">
                <li><a href="{{ route('feed') }}" @if (request()->routeIs('feed')) aria-current="page" @endif>{{ __('Feed') }}</a></li>
            </ul>
            <div class="fx-end">
                @if ($staff)
                    {{-- A signed-in admin is not a feed actor, but IS signed in: show their
                         account (back to the admin panel + Log out), never the visitor "Log in". --}}
                    <a class="fx-preview" href="{{ route('admin.dashboard') }}">{{ __('Admin panel') }}</a>
                    <div class="fx-me" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                        <button type="button" class="fx-av" @click.stop="open = !open" :aria-expanded="open.toString()" aria-controls="fxStaffPop" aria-label="{{ __('Your account') }}">{{ $staffInitials }}</button>
                        <ul class="fx-pop" id="fxStaffPop" x-show="open" x-cloak>
                            <li class="fx-who"><b>{{ $staff->name }}</b><span>{{ __('Fama staff') }}</span></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">@csrf
                                    <a href="{{ route('logout') }}" @click.prevent="$el.closest('form').submit()">{{ __('Log out') }}</a>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a class="fx-preview fx-login" href="{{ route('login') }}" @if (request()->routeIs('login')) aria-current="page" @endif>{{ __('Log in') }}</a>
                    <a class="fx-join" href="{{ route('register') }}">{{ __('Join') }}</a>
                @endif
            </div>
        </div>
    </header>
@endif
