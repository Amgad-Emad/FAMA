@props(['title' => null])

@php
    $brand = auth('brand')->user();
    $nav = [
        ['route' => 'brand.dashboard', 'label' => __('Dashboard'), 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['route' => 'brand.discover', 'label' => __('Discover talent'), 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
        ['route' => 'brand.campaigns', 'label' => __('Campaigns'), 'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z'],
        ['route' => 'brand.deals', 'label' => __('Deals'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
        ['route' => 'brand.profile', 'label' => __('Profile'), 'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
        ['route' => 'brand.creative-needs', 'label' => __('Creative needs'), 'icon' => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'],
        ['route' => 'brand.reviews', 'label' => __('Reviews'), 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
        ['route' => 'brand.account', 'label' => __('Account'), 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
    ];
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
        <title>{{ $title ?: __('Brand dashboard') }} — Fama</title>
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
                <nav class="space-y-1 px-3 py-2">
                    @foreach ($nav as $item)
                        @php $active = request()->routeIs($item['route']); @endphp
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-accent-weak text-accent-ink' : 'text-muted hover:bg-elevated hover:text-ink' }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                            {{ $item['label'] }}
                        </a>
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
                        @if ($brand?->name)
                            <span class="hidden rounded-pill border border-line-strong px-3 py-1.5 text-xs font-medium text-muted sm:inline-flex">{{ $brand->name }}</span>
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
                    <div class="mx-auto max-w-5xl">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
