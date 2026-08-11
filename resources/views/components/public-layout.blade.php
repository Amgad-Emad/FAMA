{{-- $animate: the page entrance fade. The feed opts OUT (:animate="false")
     because that animation puts a `transform` on <main>, which becomes the
     containing block for position:fixed/sticky descendants (the FABs, the
     sticky filter chrome) and breaks them. The feed runs its own motion layer. --}}
@props(['title' => null, 'animate' => true])

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleDirection() }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' — Fama' : config('app.name', 'Fama') }}</title>

        {{-- Per-page head extras (Open Graph / Twitter cards for link unfurls). --}}
        {{ $head ?? '' }}

        @include('partials.design-head')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php
        // A signed-in TALENT gets the STANDARD talent topbar — the exact same
        // component the dashboard renders (<x-talent-topbar>), so the bar is
        // identical everywhere. It is sticky/in-flow and taller than the fixed
        // public bar, so the body padding is dropped and --topbar-h is
        // overridden to its height — every sticky offset that reads the token
        // (feed chrome, rails, toasts) keeps clearing the bar.
        $talentTopbar = auth('talent')->check();
    @endphp
    <body class="min-h-screen bg-bg text-ink antialiased" style="{{ $talentTopbar ? '--topbar-h: 6.5rem;' : 'padding-block-start: var(--topbar-h);' }}">
        @if ($talentTopbar)
            <x-talent-topbar :title="$title" />
        @else
            @php
                // Feed first (5AB): the feed is the daily-return surface; Discover
                // is the with-intent tool. aria-current + the gestural underline
                // mark the active item — never the underline alone. `feature`
                // items drop out of the nav when that release flag is off (V1).
                $navItems = array_values(array_filter([
                    ['route' => 'feed', 'label' => __('Feed')],
                    ['route' => 'discover', 'label' => __('Discover')],
                    ['route' => 'brands.discover', 'label' => __('Brands'), 'feature' => 'brands'],
                    ['route' => 'projects.browse', 'label' => __('Opportunities'), 'feature' => 'brands'],
                ], fn ($i) => empty($i['feature']) || \App\Support\Features::enabled($i['feature'])));
                $isAuthed = auth('brand')->check() || auth('admin')->check();
            @endphp
            <header class="site-topbar" data-topbar>
                <div class="mx-auto flex h-full max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                    <a href="{{ url('/') }}" class="shrink-0 font-display text-2xl tracking-tight text-ink">
                        Fama<span class="text-accent">.</span>
                    </a>
                    <nav class="flex min-w-0 items-center gap-3 overflow-x-auto" aria-label="{{ __('Primary') }}">
                        @foreach ($navItems as $item)
                            @php $active = request()->routeIs($item['route']); @endphp
                            <a href="{{ route($item['route']) }}"
                               @if ($active) aria-current="page" @endif
                               class="nav-underline hidden shrink-0 text-sm font-medium sm:inline {{ $active ? 'text-ink' : 'text-muted hover:text-ink' }}">
                                {{ $item['label'] }}
                                @if ($active)
                                    <svg viewBox="0 0 60 6" preserveAspectRatio="none" aria-hidden="true"><path d="M2 4 C 14 1.5, 30 5, 58 2.5" /></svg>
                                @endif
                            </a>
                        @endforeach

                        {{-- Signed-in brand/admin users get a link to their dashboard;
                             guests get a sign-in link instead. --}}
                        @if ($isAuthed)
                            <a href="{{ route('dashboard') }}"
                               class="nav-underline hidden shrink-0 text-sm font-medium text-muted hover:text-ink sm:inline">{{ __('Profile') }}</a>
                        @else
                            <a href="{{ route('login') }}"
                               class="hidden shrink-0 rounded-pill bg-primary px-3.5 py-1.5 text-sm font-medium text-on-primary transition hover:opacity-90 sm:inline-block">{{ __('Log in') }}</a>
                        @endif

                        {{-- Language switcher hidden for now — English only until the
                             Arabic pass ships (keep the component for later). --}}
                        {{-- <x-public-locale-switcher /> --}}
                        <x-theme-toggle />
                    </nav>
                </div>
            </header>
        @endif

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="mx-auto mt-4 flex max-w-6xl items-center justify-between gap-3 rounded-lg border border-line bg-accent-weak px-4 py-3 text-sm text-accent-ink sm:px-6">
                <span>{{ session('status') }}</span>
                <button @click="show = false" class="text-accent-ink/70 hover:text-accent-ink" aria-label="{{ __('Dismiss') }}">✕</button>
            </div>
        @endif

        <main class="{{ $animate ? 'animate-fade-in-up' : '' }}">
            {{ $slot }}
        </main>

        <footer class="mt-24 border-t border-line py-10">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 sm:flex-row sm:px-6">
                <span class="font-display text-lg text-ink">Fama<span class="text-accent">.</span></span>
                <span class="font-mono text-[11px] uppercase tracking-[0.18em] text-subtle">
                    {{ __('Creative talent · Egypt-first · MENA-wide') }}
                </span>
            </div>
        </footer>

        {{-- Global confirm dialog — feed actions (composer discard, comment
             delete) use the $confirm store, which needs this markup mounted. --}}
        <x-confirm-dialog />

        {{-- Toast rack (single source for every public page). The rack carries
             its own data-surface=daylight so it renders identically off-feed
             (e.g. the brand-profile notify-bell). --}}
        @include('feed.partials.toasts')
    </body>
</html>
