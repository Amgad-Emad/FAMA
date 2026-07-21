<!DOCTYPE html>
{{--
    Staff console sign-in — deliberately its OWN layout, visually distinct from
    the public auth pages (no shared guest layout): an enterprise split screen
    with a graphite brand panel. Token-only; dark/light + RTL + i18n.
--}}
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleDirection() }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ __('Staff sign in') }} — Fama</title>

        @include('partials.design-head')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-bg text-ink antialiased">
        <div class="flex min-h-screen">
            {{-- Brand panel (graphite, hidden on mobile) --}}
            <aside class="relative hidden w-2/5 flex-col justify-between overflow-hidden bg-primary p-10 text-on-primary lg:flex" aria-hidden="true">
                <div class="pointer-events-none absolute inset-0">
                    <div class="absolute -top-24 -end-24 h-96 w-96 rounded-pill bg-accent opacity-15 blur-3xl"></div>
                    <div class="absolute -bottom-32 -start-16 h-96 w-96 rounded-pill bg-accent opacity-10 blur-3xl"></div>
                </div>

                <div class="relative">
                    <span class="font-display text-3xl tracking-tight">Fama<span class="text-accent">.</span></span>
                    <span class="ms-2 rounded-pill border border-on-primary/25 px-2.5 py-1 font-mono text-[10px] uppercase tracking-[0.18em]">{{ __('Admin') }}</span>
                </div>

                <div class="relative max-w-sm">
                    <h2 class="font-display text-4xl leading-tight">{{ __('Operations console') }}</h2>
                    <p class="mt-3 text-sm opacity-80">{{ __('Moderation, contract flows, the block catalog, and platform governance — in one place.') }}</p>
                </div>

                <p class="relative font-mono text-[11px] uppercase tracking-[0.18em] opacity-60">
                    {{ __('Creative talent · Egypt-first · MENA-wide') }}
                </p>
            </aside>

            {{-- Sign-in column --}}
            <main class="flex min-w-0 flex-1 flex-col">
                <div class="flex h-16 items-center justify-between px-5 sm:px-10">
                    <span class="font-display text-2xl tracking-tight text-ink lg:invisible">Fama<span class="text-accent">.</span></span>
                    <div class="flex items-center gap-3">
                        <x-public-locale-switcher />
                        <x-theme-toggle />
                    </div>
                </div>

                <div class="flex flex-1 items-center justify-center px-5 py-10 sm:px-10">
                    <div class="animate-fade-in-up w-full max-w-sm">
                        <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-subtle">{{ __('Restricted area') }}</p>
                        <h1 class="mt-2 font-display text-3xl text-ink">{{ __('Staff sign in') }}</h1>
                        <p class="mt-1.5 text-sm text-muted">{{ __('For Fama team accounts only.') }}</p>

                        <x-auth-session-status class="mt-5" :status="session('status')" />

                        <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 space-y-5">
                            @csrf

                            <div>
                                <x-input-label for="email" :value="__('Work email')" />
                                <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')"
                                              :error="$errors->has('email')" required autofocus autocomplete="username" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div>
                                <div class="mb-1.5 flex items-center justify-between">
                                    <x-input-label for="password" :value="__('Password')" />
                                    @if (Route::has('password.request'))
                                        <a class="rounded text-sm font-medium text-accent-ink hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                           href="{{ route('password.request') }}">
                                            {{ __('Forgot your password?') }}
                                        </a>
                                    @endif
                                </div>
                                <x-password-input id="password" :error="$errors->has('password')" autocomplete="current-password" />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <label for="remember_me" class="inline-flex items-center gap-2">
                                <input id="remember_me" type="checkbox" name="remember"
                                       class="rounded border-line-strong bg-bg text-accent focus:ring-accent/40">
                                <span class="text-sm text-muted">{{ __('Remember me') }}</span>
                            </label>

                            <x-primary-button class="w-full">
                                {{ __('Sign in to the console') }}
                            </x-primary-button>

                            <p class="border-t border-line pt-4 text-center text-xs text-muted">
                                {{ __('Not staff?') }}
                                <a class="rounded font-medium text-accent-ink hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-accent" href="{{ route('login') }}">
                                    {{ __('Go to the Fama login') }}
                                </a>
                            </p>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
