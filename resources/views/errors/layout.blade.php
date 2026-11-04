<!DOCTYPE html>
{{--
    Shared error-page shell (403/404/419/429/500/503) on the fama-ui design —
    the composition of the prototype's 404.html (big purple code · h1 · line ·
    one way out). Deliberately dependency-light: it links the fama-ui CSS
    directly (no Vite, no Alpine, no <x-fama.layout>, whose nav queries the DB)
    and renders a STATIC header (logo + the public nav) and footer, so it keeps
    rendering when the app is degraded. Every auth/route lookup is wrapped in
    rescue() because session/routing may be unavailable mid-failure.
    Sections: code, title (<title>), heading (h1), message.
--}}
@php
    $isTalent = rescue(fn () => auth('talent')->check(), false, false);
    $href = fn (string $name, string $fallback = '/') => rescue(fn () => route($name), url($fallback), false);
    $feedUrl = $href('feed');
    $ctaUrl = $isTalent ? $href('talent.profile.edit', '/talent/profile') : $feedUrl;
    $dir = rescue(fn () => \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleDirection(), 'ltr', false);
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title>@yield('title') · Fama</title>
    <link rel="icon" href="{{ asset('fama-ui/img/fama-emblem.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ \App\Support\FamaAsset::url('fama-ui/css/shell.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\FamaAsset::url('fama-ui/css/ds.css') }}">
    <style>
    /* One clear way out (UX audit #22): back to the profile for talents, the feed for guests. */
    .nf{max-width:640px;margin:var(--s9) auto 0;text-align:center}
    .nf .code{font-family:var(--font-display);font-weight:600;font-size:clamp(96px,16vw,160px);line-height:1;color:var(--purple)}
    .nf .h1{margin-top:var(--s3)}
    .nf p{color:var(--grey);margin:var(--s3) auto var(--s6);max-width:44ch}
    </style>
</head>
<body>
    <svg width="0" height="0" style="position:absolute" aria-hidden="true">
        <symbol id="a-home" viewBox="0 0 24 24"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></symbol>
    </svg>

    <header class="fx-nav">
        <div class="fx-wrap fx-in">
            <a class="fx-logo" href="{{ $feedUrl }}"><img src="{{ asset('fama-ui/img/fama-logo-tight.svg') }}" alt="Fama"></a>
            <ul class="fx-links fx-keep">
                <li><a href="{{ $feedUrl }}">{{ __('Feed') }}</a></li>
            </ul>
            <div class="fx-end">
                @if ($isTalent)
                    <a class="fx-preview fx-login" href="{{ $ctaUrl }}">{{ __('Edit profile') }}</a>
                @else
                    <a class="fx-preview fx-login" href="{{ $href('login', '/login') }}">{{ __('Log in') }}</a>
                @endif
            </div>
        </div>
    </header>

    <main class="wrap"><section class="nf">
        <p class="code" aria-hidden="true">@yield('code')</p>
        <h1 class="h1">@yield('heading')</h1>
        <p>@yield('message')</p>
        <a class="btn" href="{{ $ctaUrl }}"><svg class="icon" aria-hidden="true"><use href="#a-home"/></svg><span>{{ $isTalent ? __('Go to your profile') : __('Go to the feed') }}</span></a>
    </section></main>

    <footer class="fx-foot">
        <div class="fx-wrap"><div class="fx-in">
            <img src="{{ asset('fama-ui/img/fama-logo-tight.svg') }}" alt="Fama">
            <span>© {{ date('Y') }} {{ __('Fama') }}. {{ __('Where creative talent gets seen.') }}</span>
        </div></div>
    </footer>
</body>
</html>
