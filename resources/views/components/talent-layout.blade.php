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
        <title>{{ $title ?: __('Talent dashboard') }} — Fama</title>
        @include('partials.design-head')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-bg text-ink antialiased">
        {{-- The standard talent topbar (shared with public-layout for signed-in
             talents): wordmark + page title + actions, pill nav below. --}}
        <x-talent-topbar :title="$title ?? __('Dashboard')" />

        <main class="px-4 py-8 sm:px-8">
            <div class="mx-auto max-w-7xl">
                {{ $slot }}
            </div>
        </main>
        <x-confirm-dialog />

    </body>
</html>
