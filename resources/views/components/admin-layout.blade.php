@props(['title' => null])

@php
    $admin = auth('admin')->user();
    $currentQueue = request()->query('queue', 'talents');
    $onModeration = request()->routeIs('admin.moderation.index');

    // Grouped admin nav. `queue` items deep-link a moderation tab (?queue=) and
    // are active only when THEIR tab is open; plain items match by route name.
    $navGroups = [
        ['heading' => null, 'items' => [
            ['route' => 'admin.dashboard', 'label' => __('Dashboard'), 'icon' => '<rect width="7" height="9" x="3" y="3" rx="1.5"/><rect width="7" height="5" x="14" y="3" rx="1.5"/><rect width="7" height="9" x="14" y="12" rx="1.5"/><rect width="7" height="5" x="3" y="16" rx="1.5"/>', 'can' => null],
            ['route' => 'feed', 'label' => __('Feed'), 'icon' => '<path d="M15 18h-5"/><path d="M18 14h-8"/><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-4 0v-9a2 2 0 0 1 2-2h2"/><rect width="8" height="4" x="10" y="6" rx="1"/>', 'can' => null],
        ]],
        ['heading' => __('Moderation'), 'items' => [
            ['route' => 'admin.moderation.index', 'queue' => 'talents', 'label' => __('Talent profiles'), 'icon' => '<circle cx="12" cy="8" r="4.5"/><path d="M20 21a8 8 0 0 0-16 0"/>', 'can' => 'moderate-content'],
            ['route' => 'admin.moderation.index', 'queue' => 'brands', 'label' => __('Brands'), 'icon' => '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/>', 'can' => 'moderate-content', 'feature' => 'brands'],
            ['route' => 'admin.moderation.index', 'queue' => 'brand-reviews', 'label' => __('Brand reviews'), 'icon' => '<path d="M11.5 2.8a.5.5 0 0 1 1 0l2.3 4.7 5.2.8a.5.5 0 0 1 .3.9l-3.8 3.7.9 5.2a.5.5 0 0 1-.7.5L12 16.2l-4.7 2.4a.5.5 0 0 1-.7-.5l.9-5.2-3.8-3.7a.5.5 0 0 1 .3-.9l5.2-.8z"/>', 'can' => 'moderate-content', 'feature' => 'brands'],
            ['route' => 'admin.moderation.index', 'queue' => 'recognitions', 'label' => __('Recognitions'), 'icon' => '<circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>', 'can' => 'moderate-content'],
            ['route' => 'admin.moderation.index', 'queue' => 'all-reviews', 'label' => __('Global review queue'), 'icon' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>', 'can' => 'moderate-content'],
            ['route' => 'admin.moderation.feed', 'label' => __('Posts'), 'icon' => '<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.09-3.09a2 2 0 0 0-2.82 0L6 21"/>', 'can' => 'moderate-content'],
        ]],
        ['heading' => __('Marketplace'), 'items' => [
            ['route' => 'admin.moderation.index', 'queue' => 'projects', 'label' => __('Projects oversight'), 'icon' => '<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>', 'can' => 'moderate-content', 'feature' => 'brands'],
            ['route' => 'admin.contracts', 'label' => __('Contracts'), 'icon' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 18h1"/><path d="M8 13h8"/>', 'can' => 'intervene-contracts', 'feature' => 'contracts'],
        ]],
        ['heading' => __('Configuration'), 'items' => [
            ['route' => 'admin.flows', 'label' => __('Contract flows'), 'icon' => '<rect width="8" height="8" x="3" y="3" rx="2"/><path d="M7 11v4a2 2 0 0 0 2 2h4"/><rect width="8" height="8" x="13" y="13" rx="2"/>', 'can' => 'manage-flows', 'feature' => 'contracts'],
            ['route' => 'admin.contract-templates', 'label' => __('Contract templates'), 'icon' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>', 'can' => 'manage-flows', 'feature' => 'contracts'],
            ['route' => 'admin.skills', 'label' => __('Skills templates'), 'icon' => '<path d="M21 4h-7M10 4H3M21 12h-9M8 12H3M21 20h-5M12 20H3M14 2v4M8 10v4M16 18v4"/>', 'can' => 'manage-flows'],
            ['route' => 'admin.blocks', 'label' => __('Block catalog'), 'icon' => '<rect width="7" height="7" x="14" y="3" rx="1.5"/><path d="M10 21V8a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H3"/>', 'can' => 'manage-blocks'],
        ]],
        ['heading' => __('System'), 'items' => [
            ['route' => 'admin.trash', 'label' => __('Trash'), 'icon' => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11v6M14 11v6"/>', 'can' => 'moderate-content'],
            ['route' => 'admin.activity', 'label' => __('Activity log'), 'icon' => '<path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/>', 'can' => 'manage-settings'],
            ['route' => 'admin.settings', 'label' => __('Settings'), 'icon' => '<path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/>', 'can' => 'manage-settings'],
            ['route' => 'admin.users', 'label' => __('Accounts'), 'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>', 'can' => 'manage-users'],
        ]],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleDirection() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ $title ?: __('Admin') }} — Fama</title>
        <link rel="icon" href="{{ asset('fama-ui/img/fama-emblem.svg') }}" type="image/svg+xml">
        @include('partials.design-head')
        {{-- The admin wears the fama-ui language (cream, ink, purple; Oswald + Sofia Sans) — light only. --}}
        <script>document.documentElement.setAttribute('data-theme', 'light');</script>
        <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600&family=Sofia+Sans:wght@400;500;600;700&family=IBM+Plex+Sans+Arabic:wght@400;500;600&display=swap">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-bg text-ink antialiased" data-surface="fama-admin" x-data="{ sidebar: false }">
        <div class="flex min-h-screen">
            <aside class="fixed inset-y-0 start-0 z-40 w-72 shrink-0 border-e border-line bg-surface transition-transform sm:static sm:translate-x-0"
                   :class="sidebar ? 'max-sm:translate-x-0' : 'max-sm:-translate-x-full max-sm:rtl:translate-x-full'">
                <div class="flex h-20 items-center gap-3 px-6">
                    <a href="{{ route('admin.dashboard') }}" class="block"><img src="{{ asset('fama-ui/img/fama-logo-tight.svg') }}" alt="Fama" class="h-8 w-auto"></a>
                    <span class="rounded-pill border border-line px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">{{ __('Admin') }}</span>
                </div>
                <nav class="max-h-[calc(100vh-5rem)] space-y-5 overflow-y-auto px-4 pb-8 pt-2" aria-label="{{ __('Admin') }}">
                    @foreach ($navGroups as $group)
                        @php
                            // Hide items whose capability is missing OR whose release feature flag
                            // is off (V1: brand/contract admin surfaces are gated, not permission-revoked,
                            // because `manage-flows` is shared with Skills templates).
                            $visible = array_filter($group['items'], fn ($item) => (! ($item['can'] ?? null) || $admin?->can($item['can']))
                                && (empty($item['feature']) || \App\Support\Features::enabled($item['feature'])));
                        @endphp
                        @continue(empty($visible))
                        <div class="space-y-1">
                            @if ($group['heading'])
                                <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-subtle">{{ $group['heading'] }}</p>
                            @endif
                            @foreach ($visible as $item)
                                @php
                                    // Queue items are active only when THEIR moderation tab is
                                    // open; plain items match by route name (incl. drill-downs).
                                    $active = isset($item['queue'])
                                        ? $onModeration && $currentQueue === $item['queue']
                                        : request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*');
                                @endphp
                                <a href="{{ route($item['route'], isset($item['queue']) ? ['queue' => $item['queue']] : []) }}"
                                   @if ($active) aria-current="page" @endif
                                   class="group flex min-h-11 items-center gap-3 rounded-pill py-1.5 pe-4 ps-1.5 text-[15px] font-medium transition {{ $active ? 'bg-primary text-on-primary' : 'text-muted hover:bg-elevated hover:text-ink' }}">
                                    {{-- Icon tile: soft on idle rows, inverted on the active one (Lucide-style 24px set). --}}
                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-[10px] transition {{ $active ? 'bg-white/15 text-on-primary' : 'bg-elevated text-ink group-hover:bg-accent-weak group-hover:text-accent-ink' }}">
                                        <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">{!! $item['icon'] !!}</svg>
                                    </span>
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </nav>
            </aside>

            <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-30 bg-black/40 sm:hidden"></div>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-20 flex h-20 items-center justify-between gap-4 border-b border-line bg-surface/90 px-4 backdrop-blur sm:px-10">
                    <div class="flex min-w-0 items-center gap-3">
                        <button @click="sidebar = !sidebar" class="rounded-pill border border-line p-2.5 text-ink hover:border-ink sm:hidden" aria-label="{{ __('Menu') }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <div class="min-w-0">
                            <p class="text-[12px] font-semibold uppercase tracking-[0.14em] text-accent-ink">{{ __('Operations console') }}</p>
                            <h1 class="truncate font-display text-2xl font-semibold leading-tight text-ink sm:text-[28px]">{{ $title ?? __('Dashboard') }}</h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @if ($admin?->name)
                            <span class="hidden items-center gap-2 rounded-pill border border-line bg-surface py-1 pe-3 ps-1 text-sm font-medium text-ink sm:inline-flex">
                                <span class="grid h-8 w-8 place-items-center rounded-pill bg-accent-weak text-xs font-semibold text-accent-ink">{{ \Illuminate\Support\Str::of($admin->name)->explode(' ')->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') }}</span>
                                {{ $admin->name }}
                            </span>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="min-h-10 rounded-pill bg-primary px-5 text-sm font-semibold text-on-primary transition hover:opacity-90">{{ __('Log out') }}</button>
                        </form>
                    </div>
                </header>

                <main class="min-w-0 flex-1 px-4 py-8 sm:px-10 sm:py-10">
                    <div class="mx-auto max-w-7xl">{{ $slot }}</div>
                </main>
            </div>
        </div>
        <x-confirm-dialog />

    </body>
</html>
