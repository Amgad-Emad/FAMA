{{-- The STANDARD talent topbar — one component rendered by the talent dashboard
     layout AND by public-layout when a talent is signed in, so the bar is
     identical everywhere. Single row (h-16 = --topbar-h, keeps the feed's
     sticky offsets honest): the Fama logo (<x-fama-logo>) + the two primary links (Feed + Preview
     profile) on the start side | avatar button opening a dropdown with
     everything else (Dashboard, Activity, Profile structure, Profile content,
     flagged marketplace, Log out). No page title in the bar and no locale
     switcher — V1 ships without the AR/EN toggle. --}}
@props(['title' => null])

@php
    $talent = auth('talent')->user();
    // Canonical set (App\Support\Navigation): Feed is a primary link in the
    // bar; every other item moves into the corner dropdown.
    $menuItems = array_values(array_filter(\App\Support\Navigation::talentItems(), fn ($i) => $i['route'] !== 'feed'));
    $initial = mb_strtoupper(mb_substr(trim($talent?->display_name ?: ($talent?->email ?? '?')), 0, 1));
@endphp

<header class="sticky top-0 z-40 border-b border-line bg-bg/85 backdrop-blur">
    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-8">
        {{-- Start side: wordmark + the two primary links (no page title in the bar). --}}
        <div class="flex min-w-0 items-center gap-3 sm:gap-4">
            <a href="{{ url('/') }}" class="shrink-0" aria-label="{{ __('Fama') }}">
                <x-fama-logo class="h-7 w-auto" />
            </a>
            <nav class="flex items-center gap-1.5" aria-label="{{ __('Primary') }}">
                @php $feedActive = request()->routeIs('feed'); @endphp
                <a href="{{ route('feed') }}"
                   @if ($feedActive) aria-current="page" @endif
                   @class([
                       'flex shrink-0 items-center gap-2 rounded-pill px-3.5 py-2 text-sm transition',
                       'bg-accent font-semibold text-on-accent' => $feedActive,
                       'font-medium text-muted hover:bg-elevated hover:text-ink' => ! $feedActive,
                   ])>
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z"/></svg>
                    {{ __('Feed') }}
                </a>
                @if ($talent?->slug)
                    <a href="{{ url($talent->slug) }}" target="_blank" rel="noopener"
                       class="flex shrink-0 items-center gap-2 rounded-pill px-3.5 py-2 text-sm font-medium text-muted transition hover:bg-elevated hover:text-ink">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ __('Preview profile') }}
                    </a>
                @endif
            </nav>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            <x-theme-toggle />

            {{-- Corner menu: everything else lives here. --}}
            <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                <button type="button" @click="open = !open"
                        :aria-expanded="open ? 'true' : 'false'" aria-haspopup="menu" aria-label="{{ __('Menu') }}"
                        class="flex items-center gap-1.5 rounded-pill border border-line-strong p-1 pe-2 transition hover:bg-elevated focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                    <span class="inline-flex h-8 w-8 items-center justify-center overflow-hidden rounded-pill bg-elevated">
                        @if ($talent?->avatar_url)
                            <img src="{{ $talent->avatar_url }}" alt="" class="h-full w-full object-cover">
                        @else
                            <span class="font-display text-sm text-accent-ink" style="background: linear-gradient(135deg, var(--accent-weak), var(--gold-weak));">
                                <span class="flex h-8 w-8 items-center justify-center">{{ $initial }}</span>
                            </span>
                        @endif
                    </span>
                    <svg class="h-3.5 w-3.5 text-subtle transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <div x-show="open" x-cloak role="menu" aria-label="{{ __('Menu') }}"
                     class="absolute end-0 top-full mt-2 w-60 rounded-lg border border-line bg-elevated py-1.5 shadow-e2">
                    @foreach ($menuItems as $item)
                        @php $active = request()->routeIs($item['route']); @endphp
                        <a href="{{ route($item['route'], $item['params'] ?? []) }}" role="menuitem"
                           @if ($active) aria-current="page" @endif
                           @class([
                               'flex items-center gap-3 px-4 py-2.5 text-sm transition',
                               'bg-accent-weak font-semibold text-accent-ink' => $active,
                               'font-medium text-muted hover:bg-surface hover:text-ink' => ! $active,
                           ])>
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                    <div class="my-1.5 border-t border-line" role="separator"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" role="menuitem"
                                class="flex w-full items-center gap-3 px-4 py-2.5 text-sm font-medium text-muted transition hover:bg-surface hover:text-danger">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                            {{ __('Log out') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
