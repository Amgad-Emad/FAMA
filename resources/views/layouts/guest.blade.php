<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleDirection() }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Fama') }}</title>

        @include('partials.design-head')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-bg text-ink antialiased">
        <div class="flex min-h-screen flex-col">
            {{-- Slim header: wordmark + theme/locale, same treatment as the public layout --}}
            <header class="border-b border-line">
                <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 sm:px-6">
                    <a href="{{ url('/') }}" class="font-display text-2xl tracking-tight text-ink">
                        Fama<span class="text-accent">.</span>
                    </a>
                    <div class="flex items-center gap-3">
                        <x-public-locale-switcher />
                        <x-theme-toggle />
                    </div>
                </div>
            </header>

            {{-- Centered auth card over a soft ambient accent glow (token-only:
                 accent-weak blobs blurred — reads premium in light and dark).
                 Entrance is the shared reduce-motion-aware reveal layer. --}}
            <main class="relative flex flex-1 items-center justify-center overflow-hidden px-4 py-10 sm:px-6">
                <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                    <div class="absolute -top-24 start-1/4 h-72 w-72 rounded-pill bg-accent-weak opacity-60 blur-3xl"></div>
                    <div class="absolute -bottom-24 end-1/4 h-80 w-80 rounded-pill bg-accent-weak opacity-40 blur-3xl"></div>
                </div>
                <div class="animate-fade-in-up relative w-full max-w-md">
                    <div class="rounded-2xl border border-line bg-surface p-6 shadow-e2 sm:p-8">
                        {{ $slot }}
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
