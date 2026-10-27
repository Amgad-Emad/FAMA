{{--
    The fama-ui header (port of the prototype's shell.js navHTML), server-rendered.
      talent — logo · Feed · Activity · Profile structure · Projects · Credits |
               Preview profile · notification bell (live: /notifications) · avatar menu · burger.
               Plus the pinned "Private" notice while the profile isn't live.
      public — logo · Feed | Log in.
      auth   — logo | "Just looking? Browse the feed".
    Behaviour is the `famaNav` Alpine component (resources/js/ui/shell.js).
--}}
@props(['mode' => 'talent'])

@php
    $talent = $mode === 'talent' ? auth('talent')->user() : null;
    if ($mode === 'talent' && ! $talent) {
        $mode = 'public';
    }
    $items = [
        ['route' => 'feed', 'label' => __('Feed')],
        ['route' => 'talent.feed.activity', 'label' => __('Activity')],
        ['route' => 'talent.profile.edit', 'label' => __('Profile structure')],
        ['route' => 'talent.projects', 'label' => __('Projects')],
        ['route' => 'talent.credits', 'label' => __('Credits')],
    ];
    $initials = $talent
        ? collect(preg_split('/\s+/', trim((string) ($talent->display_name ?: $talent->slug))))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('')
        : '';
    $role = null;
    if ($talent) {
        $primary = $talent->talentTypes()->orderByDesc('talent_talent_type.is_primary')->orderBy('talent_talent_type.position')->first();
        $role = collect([$primary?->name, $talent->base_city])->filter()->implode(' · ');
    }
    $isLive = $talent && (string) $talent->status === 'live' && $talent->is_published;
    // Public mode with a signed-in admin (staff browse the public pages too).
    $staff = $mode === 'public' ? auth('admin')->user() : null;
    $staffInitials = $staff
        ? collect(preg_split('/\s+/', trim((string) $staff->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('')
        : '';
@endphp

@if ($mode === 'talent')
    <header class="fx-nav" x-data="famaNav({ unreadUrl: @js(route('notifications.unread')), listUrl: @js(route('notifications')), readUrl: @js(route('notifications.read')) })"
            @click.outside="closeAll()" @keydown.escape.window="closeAll()">
        <div class="fx-wrap fx-in">
            <a class="fx-logo" href="{{ route('talent.profile.edit') }}"><img src="{{ asset('fama-ui/img/fama-logo-tight.svg') }}" alt="Fama"></a>
            <ul class="fx-links">
                @foreach ($items as $item)
                    <li><a href="{{ route($item['route']) }}" @if (request()->routeIs($item['route'].'*')) aria-current="page" @endif>{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
            <div class="fx-end">
                @if ($talent->slug)
                    <a class="fx-preview" href="{{ url($talent->slug) }}" target="_blank" rel="noopener" @if (request()->is($talent->slug)) aria-current="page" @endif>
                        <svg class="fx-icon" aria-hidden="true"><use href="#fx-i-eye"/></svg><span class="fx-lbl">{{ __('Preview profile') }}</span>
                        <svg class="fx-icon fx-flip" aria-hidden="true"><use href="#fx-i-ext"/></svg>
                    </a>
                @endif
                <div class="fx-bellw">
                    <button type="button" class="fx-bell" @click.stop="toggleBell()" :aria-expanded="bellOpen.toString()" aria-controls="fxNPop"
                            :aria-label="unread ? @js(__('Notifications')) + ', ' + unread + ' ' + @js(__('unread')) : @js(__('Notifications'))">
                        <svg class="fx-icon" aria-hidden="true"><use href="#fx-i-bell"/></svg>
                        <span class="fx-count" aria-hidden="true" x-show="unread > 0" x-text="unread > 9 ? '9+' : unread" x-cloak></span>
                    </button>
                    <div class="fx-npop" id="fxNPop" x-show="bellOpen" x-cloak role="region" aria-label="{{ __('Notifications') }}">
                        <div class="fx-nh"><b>{{ __('Notifications') }}</b><button type="button" @click.stop="readAll()">{{ __('Mark all as read') }}</button></div>
                        <ul class="fx-nlist">
                            <template x-if="loaded && !items.length">
                                <li class="fx-n"><span class="fx-nb"><span class="fx-nex">{{ __('No notifications yet.') }}</span></span></li>
                            </template>
                            <template x-for="n in items.slice(0, 6)" :key="n.id">
                                <li>
                                    <a class="fx-n" :class="{ unread: !n.read_at }" :href="n.url || '#'" @click="markRead(n)">
                                        <span class="fx-nic" aria-hidden="true"><svg class="fx-icon"><use :href="'#' + icon(n)"/></svg></span>
                                        <span class="fx-nb">
                                            <span><b x-text="who(n)"></b><span x-text="rest(n)"></span></span>
                                            <span class="fx-nago" x-text="ago(n.created_at)"></span>
                                        </span>
                                        <span class="fx-ndot" aria-hidden="true" x-show="!n.read_at"></span>
                                    </a>
                                </li>
                            </template>
                        </ul>
                        <a class="fx-nmore" href="{{ route('notifications.page') }}">{{ __('See all notifications') }}</a>
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
                        <li class="fx-who"><b>{{ $talent->display_name ?: $talent->slug }}</b>@if ($role)<span>{{ $role }}</span>@endif</li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">@csrf
                                <a href="{{ route('logout') }}" @click.prevent="$el.closest('form').submit()">{{ __('Log out') }}</a>
                            </form>
                        </li>
                    </ul>
                </div>
                <button type="button" class="fx-burger" @click="drawer = !drawer" :aria-expanded="drawer.toString()" aria-controls="fxDrawer" aria-label="{{ __('Menu') }}">
                    <svg class="fx-icon"><use :href="drawer ? '#fx-i-x' : '#fx-i-menu'" href="#fx-i-menu"/></svg>
                </button>
            </div>
        </div>
        <div class="fx-wrap">
            <nav class="fx-drawer" id="fxDrawer" x-show="drawer" x-cloak aria-label="{{ __('Menu') }}">
                <ul>
                    @foreach ($items as $item)
                        <li><a href="{{ route($item['route']) }}" @if (request()->routeIs($item['route'].'*')) aria-current="page" @endif>{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
        </div>
        @unless ($isLive)
            @php
                // Three states: steps left (Private) → all six done, Fama checking (Under review) → checked (Ready).
                $gateComplete = app(\App\Services\ProfileCompletionService::class)->isComplete($talent);
                $gateVerified = (bool) $talent->is_verified;
            @endphp
            {{-- Profile structure keeps it live: it dispatches `fama-gate` as steps are finished. --}}
            <div class="fx-notice" role="region" aria-label="{{ __('Profile status') }}"
                 x-data="{ complete: @js($gateComplete), verified: @js($gateVerified) }"
                 @fama-gate.window="complete = $event.detail.complete; verified = $event.detail.verified"
                 :class="{ 'is-review': complete && !verified, 'is-ready': complete && verified }">
                <div class="fx-wrap fx-in">
                    <svg class="fx-icon" aria-hidden="true" x-show="!complete" @if ($gateComplete) style="display:none" @endif><use href="#fx-i-lock"/></svg>
                    <svg class="fx-icon" aria-hidden="true" x-show="complete && !verified" x-cloak @if (! $gateComplete || $gateVerified) style="display:none" @endif><use href="#fx-i-eye"/></svg>
                    <svg class="fx-icon" aria-hidden="true" x-show="complete && verified" x-cloak @if (! ($gateComplete && $gateVerified)) style="display:none" @endif><use href="#fx-i-check"/></svg>
                    <p role="status">
                        <template x-if="!complete"><span>
                            <span class="fx-long">{{ __('Your profile is Private: only you can see it. Complete it to go Live and share your link.') }}</span>
                            <span class="fx-short">{{ __('Your profile is Private.') }}</span>
                        </span></template>
                        <template x-if="complete && !verified"><span>
                            <span class="fx-long">{{ __('Your profile is under review: our team is checking it. You can keep editing meanwhile.') }}</span>
                            <span class="fx-short">{{ __('Your profile is under review.') }}</span>
                        </span></template>
                        <template x-if="complete && verified"><span>
                            <span class="fx-long">{{ __('Your profile has been checked. Go Live whenever you’re ready.') }}</span>
                            <span class="fx-short">{{ __('Ready to go Live.') }}</span>
                        </span></template>
                        <noscript>{{ $gateComplete ? ($gateVerified ? __('Your profile has been checked. Go Live whenever you’re ready.') : __('Your profile is under review: our team is checking it. You can keep editing meanwhile.')) : __('Your profile is Private: only you can see it. Complete it to go Live and share your link.') }}</noscript>
                    </p>
                    <a class="fx-ncta" href="{{ route('talent.profile.edit') }}#gate">
                        <span x-text="!complete ? @js(__('Complete your profile')) : (verified ? @js(__('Go Live')) : @js(__('See status')))">{{ $gateComplete ? ($gateVerified ? __('Go Live') : __('See status')) : __('Complete your profile') }}</span>
                        <svg class="fx-icon fx-flip" aria-hidden="true"><use href="#fx-i-arrow"/></svg></a>
                </div>
            </div>
        @endunless
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
                @endif
            </div>
        </div>
    </header>
@endif
