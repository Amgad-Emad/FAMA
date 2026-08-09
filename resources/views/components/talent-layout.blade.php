@props(['title' => null])

@php
    $talent = auth('talent')->user();
    // Grouped sidebar. `feature` items drop out when the release flag is off (V1);
    // an empty group is skipped. Saved · Liked · Following are consolidated into a
    // single Activity page (three tabs).
    $navGroups = [
        ['heading' => null, 'items' => [
            ['route' => 'talent.dashboard', 'label' => __('Dashboard'), 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['route' => 'feed', 'label' => __('Feed'), 'icon' => 'M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z'],
            ['route' => 'talent.feed.activity', 'label' => __('Activity'), 'icon' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z'],

            ]],
        ['heading' => __('Marketplace'), 'items' => [
            ['route' => 'brands.discover', 'label' => __('Discover brands'), 'feature' => 'brands', 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
            ['route' => 'projects.browse', 'label' => __('Opportunities'), 'feature' => 'brands', 'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z'],
            ['route' => 'talent.contracts', 'label' => __('Contracts'), 'feature' => 'contracts', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
        ]],
        ['heading' => __('Your studio'), 'items' => [
            ['route' => 'talent.profile.edit', 'label' => __('Profile'), 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
            ['route' => 'talent.content', 'label' => __('Content'), 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 6h16a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2z', 'params' => ['type' => 'gallery']],
            ['route' => 'talent.reviews', 'label' => __('Reviews'), 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
        ]],
        // ['heading' => __('Activity'), 'items' => [
        // ]],
    ];
    // Filter items by release flag, then drop any group left empty (V1: no Marketplace).
    $navGroups = array_values(array_filter(array_map(function ($group) {
        $group['items'] = array_values(array_filter($group['items'], fn ($i) => empty($i['feature']) || \App\Support\Features::enabled($i['feature'])));
        return $group;
    }, $navGroups), fn ($group) => ! empty($group['items'])));
@endphp

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleDirection() }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?: __('Talent dashboard') }} — Fama</title>
        @include('partials.design-head')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-bg text-ink antialiased" x-data="{ sidebar: false }">
        <div class="flex min-h-screen">
            {{-- Sidebar --}}
            <aside
                class="fixed inset-y-0 start-0 z-40 w-64 shrink-0 border-e border-line bg-surface transition-transform sm:static sm:translate-x-0"
                :class="sidebar ? 'max-sm:translate-x-0' : 'max-sm:-translate-x-full max-sm:rtl:translate-x-full'"
            >
                <div class="flex h-16 items-center px-6">
                    <a href="{{ url('/') }}" class="font-display text-2xl text-ink">Fama<span class="text-accent">.</span></a>
                </div>
                <nav class="space-y-5 px-3 py-3">
                    @foreach ($navGroups as $group)
                        <div class="space-y-0.5">
                            @if ($group['heading'])
                                <p class="px-3 pb-1.5 font-mono text-[10px] uppercase tracking-wider text-subtle">{{ $group['heading'] }}</p>
                            @endif
                            @foreach ($group['items'] as $item)
                                @php $active = request()->routeIs($item['route']); @endphp
                                <a href="{{ route($item['route'], $item['params'] ?? []) }}"
                                   @if ($active) aria-current="page" @endif
                                   @class([
                                       'group relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                                       'bg-accent-weak text-accent-ink' => $active,
                                       'text-muted hover:bg-elevated hover:text-ink' => ! $active,
                                   ])>
                                    {{-- Active accent bar on the inline-start edge. --}}
                                    <span @class(['absolute inset-y-2 start-0 w-[3px] rounded-full bg-accent transition-opacity', 'opacity-0' => ! $active])></span>
                                    <svg class="h-5 w-5 shrink-0 transition group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </nav>
            </aside>

            {{-- Backdrop (mobile) --}}
            <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-30 bg-black/40 sm:hidden"></div>

            {{-- Main --}}
            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-line bg-bg/85 px-4 backdrop-blur sm:px-8">
                    <div class="flex items-center gap-3">
                        <button @click="sidebar = !sidebar" class="rounded-md p-2 text-muted hover:bg-surface sm:hidden" aria-label="{{ __('Menu') }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <h1 class="font-display text-xl text-ink sm:text-2xl">{{ $title ?? __('Dashboard') }}</h1>
                    </div>
                    <div class="flex items-center gap-3">
                        @if ($talent?->slug)
                            <a href="{{ url($talent->slug) }}" target="_blank" rel="noopener"
                               class="hidden rounded-pill border border-line-strong px-3 py-1.5 text-xs font-medium text-muted hover:text-ink sm:inline-flex">
                                {{ __('View profile') }}
                            </a>
                        @endif
                        <x-public-locale-switcher />
                        <x-theme-toggle />
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-pill bg-primary px-3 py-1.5 text-xs font-medium text-on-primary hover:opacity-90">{{ __('Log out') }}</button>
                        </form>
                    </div>
                </header>

                <main class="min-w-0 flex-1 px-4 py-8 sm:px-8">
                    <div class="mx-auto max-w-7xl">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
        <x-confirm-dialog />

    </body>
</html>
