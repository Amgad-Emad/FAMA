<!DOCTYPE html>
{{--
    Shared error-page shell (403/404/419/429/500/503). Deliberately
    dependency-light: token styling via the app stylesheet, no Alpine
    components, no data queries — an error page must render even when the app
    is degraded. The dashboard link is resolved defensively (rescue) because
    auth/session may be unavailable mid-failure.
--}}
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleDirection() }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('code') — {{ config('app.name', 'Fama') }}</title>

        @include('partials.design-head')

        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-bg text-ink antialiased">
        <div class="flex min-h-screen flex-col">
            <header class="border-b border-line">
                <div class="mx-auto flex h-16 max-w-6xl items-center px-4 sm:px-6">
                    <a href="{{ url('/') }}" class="font-display text-2xl tracking-tight text-ink">
                        Fama<span class="text-accent">.</span>
                    </a>
                </div>
            </header>

            <main class="flex flex-1 items-center justify-center px-4 py-16 sm:px-6">
                <div class="w-full max-w-md text-center">
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-subtle">{{ __('Error') }} @yield('code')</p>
                    <h1 class="mt-3 font-display text-7xl leading-none text-ink">@yield('code')</h1>
                    <h2 class="mt-4 font-display text-2xl text-ink">@yield('title')</h2>
                    <p class="mt-2 text-sm text-muted">@yield('message')</p>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <a href="{{ url('/') }}"
                           class="inline-flex items-center justify-center rounded-pill bg-primary px-4 py-2.5 text-sm font-medium text-on-primary transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-bg">
                            {{ __('Back to home') }}
                        </a>
                        @php
                            $hasSession = rescue(fn () => auth('admin')->check() || auth('brand')->check() || auth('talent')->check(), false, false);
                        @endphp
                        @if ($hasSession && Route::has('dashboard'))
                            <a href="{{ route('dashboard') }}"
                               class="inline-flex items-center justify-center rounded-pill border border-line-strong px-4 py-2.5 text-sm font-medium text-ink transition hover:bg-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-bg">
                                {{ __('Go to dashboard') }}
                            </a>
                        @endif
                    </div>
                </div>
            </main>

            <footer class="border-t border-line py-6">
                <div class="mx-auto flex max-w-6xl items-center justify-center px-4 sm:px-6">
                    <span class="font-mono text-[11px] uppercase tracking-[0.18em] text-subtle">
                        {{ __('Creative talent · Egypt-first · MENA-wide') }}
                    </span>
                </div>
            </footer>
        </div>
    </body>
</html>
