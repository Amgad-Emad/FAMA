{{-- The STANDARD talent topbar — one component rendered by the talent dashboard
     layout AND by public-layout when a talent is signed in, so the bar is
     identical everywhere. Row 1: wordmark · page title · actions (no locale
     switcher — V1 ships without the AR/EN toggle); row 2: the canonical pill
     nav (App\Support\Navigation), horizontally scrollable on narrow screens.
     Sticky and ~6.5rem tall — public-layout overrides --topbar-h to match so
     the feed's sticky chrome offsets keep clearing it. --}}
@props(['title' => null])

@php
    $talent = auth('talent')->user();
    $navItems = \App\Support\Navigation::talentItems();
@endphp

<header class="sticky top-0 z-40 border-b border-line bg-bg/85 backdrop-blur">
    <div class="flex h-14 items-center justify-between gap-4 px-4 sm:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ url('/') }}" class="font-display text-2xl text-ink">Fama<span class="text-accent">.</span></a>
            @if ($title)
                <span class="hidden h-5 w-px bg-line sm:block" aria-hidden="true"></span>
                <h1 class="hidden truncate font-display text-lg text-ink sm:block">{{ $title }}</h1>
            @endif
        </div>
        <div class="flex items-center gap-3">
            @if ($talent?->slug)
                <a href="{{ url($talent->slug) }}" target="_blank" rel="noopener"
                   class="hidden rounded-pill border border-line-strong px-3 py-1.5 text-xs font-medium text-muted hover:text-ink sm:inline-flex">
                    {{ __('View profile') }}
                </a>
            @endif
            <x-theme-toggle />
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-pill bg-primary px-3 py-1.5 text-xs font-medium text-on-primary hover:opacity-90">{{ __('Log out') }}</button>
            </form>
        </div>
    </div>
    <nav class="flex items-center gap-1.5 overflow-x-auto px-4 pb-2.5 sm:px-8" aria-label="{{ __('Primary') }}">
        @foreach ($navItems as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a href="{{ route($item['route'], $item['params'] ?? []) }}"
               @if ($active) aria-current="page" @endif
               @class([
                   'flex shrink-0 items-center gap-2 rounded-pill px-3.5 py-2 text-sm transition',
                   'bg-accent font-semibold text-on-accent' => $active,
                   'font-medium text-muted hover:bg-elevated hover:text-ink' => ! $active,
               ])>
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
</header>
