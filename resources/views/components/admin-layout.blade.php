@props(['title' => null])

@php
    $admin = auth('admin')->user();
    $nav = [
        ['route' => 'admin.dashboard', 'label' => __('Dashboard'), 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'can' => null],
        ['route' => 'admin.flows', 'label' => __('Deal flows'), 'icon' => 'M4 6h16M4 12h16M4 18h7', 'can' => 'manage-flows'],
        ['route' => 'admin.professions', 'label' => __('Professions'), 'icon' => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4', 'can' => 'manage-flows'],
        ['route' => 'admin.moderation.index', 'label' => __('Moderation'), 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'can' => 'moderate-content'],
        ['route' => 'admin.deals', 'label' => __('Deal console'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', 'can' => 'intervene-deals'],
        ['route' => 'admin.activity', 'label' => __('Activity log'), 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'can' => 'manage-settings'],
        ['route' => 'admin.settings', 'label' => __('Settings'), 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z', 'can' => 'manage-settings'],
        ['route' => 'admin.users', 'label' => __('Admins'), 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-8a4 4 0 11-8 0 4 4 0 018 0z', 'can' => 'manage-users'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleDirection() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?: __('Admin') }} — Fama</title>
        @include('partials.design-head')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-bg text-ink antialiased" x-data="{ sidebar: false }">
        <div class="flex min-h-screen">
            <aside class="fixed inset-y-0 start-0 z-40 w-64 shrink-0 border-e border-line bg-surface transition-transform sm:static sm:translate-x-0"
                   :class="sidebar ? 'max-sm:translate-x-0' : 'max-sm:-translate-x-full max-sm:rtl:translate-x-full'">
                <div class="flex h-16 items-center gap-2 px-6">
                    <a href="{{ url('/') }}" class="font-display text-2xl text-ink">Fama<span class="text-accent">.</span></a>
                    <span class="rounded-pill bg-elevated px-2 py-0.5 font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Admin') }}</span>
                </div>
                <nav class="space-y-1 px-3 py-2">
                    @foreach ($nav as $item)
                        @continue($item['can'] && ! $admin?->can($item['can']))
                        @php $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'); @endphp
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-accent-weak text-accent-ink' : 'text-muted hover:bg-elevated hover:text-ink' }}">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </aside>

            <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-30 bg-black/40 sm:hidden"></div>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-line bg-bg/85 px-4 backdrop-blur sm:px-8">
                    <div class="flex items-center gap-3">
                        <button @click="sidebar = !sidebar" class="rounded-md p-2 text-muted hover:bg-surface sm:hidden" aria-label="{{ __('Menu') }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <h1 class="font-display text-xl text-ink sm:text-2xl">{{ $title ?? __('Dashboard') }}</h1>
                    </div>
                    <div class="flex items-center gap-3">
                        @if ($admin?->name)
                            <span class="hidden rounded-pill border border-line-strong px-3 py-1.5 text-xs font-medium text-muted sm:inline-flex">{{ $admin->name }}</span>
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
                    <div class="mx-auto max-w-7xl">{{ $slot }}</div>
                </main>
            </div>
        </div>
    </body>
</html>
