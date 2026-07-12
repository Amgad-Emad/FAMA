@props(['title' => null])

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

        @include('partials.design-head')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-bg text-ink antialiased">
        <header class="sticky top-0 z-30 border-b border-line bg-bg/85 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                <a href="{{ url('/') }}" class="font-display text-2xl tracking-tight text-ink">
                    Fama<span class="text-accent">.</span>
                </a>
                <div class="flex items-center gap-3">
                    <a href="{{ route('discover') }}" class="hidden text-sm font-medium text-muted hover:text-ink sm:inline">{{ __('Discover') }}</a>
                    <x-public-locale-switcher />
                    <x-theme-toggle />
                </div>
            </div>
        </header>

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="mx-auto mt-4 flex max-w-6xl items-center justify-between gap-3 rounded-lg border border-line bg-accent-weak px-4 py-3 text-sm text-accent-ink sm:px-6">
                <span>{{ session('status') }}</span>
                <button @click="show = false" class="text-accent-ink/70 hover:text-accent-ink" aria-label="{{ __('Dismiss') }}">✕</button>
            </div>
        @endif

        <main class="animate-fade-in-up">
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
    </body>
</html>
