{{--
    THE Fama UI shell (the "fama-ui" design, adopted 2026-10-12). Every page
    redesigned onto the new UI renders through this layout instead of the
    Tailwind layouts. It loads ONLY the fama-ui stylesheets (public/fama-ui/css —
    the prototype's CSS verbatim) plus the JS bundle (Alpine + http.js); app.css
    is deliberately NOT loaded so Tailwind utilities can never fight the
    prototype classes.

    Props:
      title  — the <title> (" · Fama" appended).
      mode   — 'talent' (signed-in talent: side bar on wide screens, top bar
               ≤1100px), 'public' (visitor nav: Feed + Log in + Join), 'auth' (sign-in pages: logo + "Browse the feed"),
               'bare' (no nav/footer). Default: talent when a talent is signed
               in, else public.
      css    — extra fama-ui sheets for the page: posts|views|fields|exp|auth.
      footer — render the shared footer (default true).
    Slots: head (extra <head>), hero (the page banner, above <main>), default.
--}}
@props(['title' => null, 'mode' => null, 'css' => [], 'footer' => true])

@php
    $mode = $mode ?? (auth('talent')->check() ? 'talent' : 'public');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleDirection() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · Fama' : 'Fama' }}</title>
    <link rel="icon" href="{{ asset('fama-ui/img/fama-emblem.svg') }}" type="image/svg+xml">
    {{ $head ?? '' }}
    @include('partials.fama-head')
    @if ($mode === 'talent' && auth('talent')->check())
        {{-- The wide-screen side bar (shell.css html.fx-has-side): set before paint so the page never
             jumps; folded by default, an opened bar is remembered in this browser. --}}
        <script>(function(){var d=document.documentElement,f=true;try{f=localStorage.getItem('fama-side-folded')!=='0'}catch(e){}d.classList.add('fx-has-side');d.classList.toggle('fx-folded',f);})();</script>
    @endif
    <link rel="stylesheet" href="{{ \App\Support\FamaAsset::url('fama-ui/css/shell.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\FamaAsset::url('fama-ui/css/ds.css') }}">
    @foreach ((array) $css as $sheet)
        <link rel="stylesheet" href="{{ \App\Support\FamaAsset::url('fama-ui/css/'.$sheet.'.css') }}">
    @endforeach
    <link rel="stylesheet" href="{{ \App\Support\FamaAsset::url('fama-ui/css/app-bridge.css') }}">
    {{ $styles ?? '' }}
    @vite(['resources/js/app.js'])
</head>
<body {{ $attributes }}>
    <x-fama.icons />

    @if ($mode !== 'bare')
        <x-fama.nav :mode="$mode" />
    @endif

    {{ $hero ?? '' }}

    {{ $slot }}

    @if ($footer && $mode !== 'bare')
        <x-fama.footer />
    @endif

    <x-fama.confirm-dialog />
    <x-fama.toast />
    <script src="{{ \App\Support\FamaAsset::url('fama-ui/js/hero.js') }}" defer></script>
    {{ $scripts ?? '' }}
</body>
</html>
