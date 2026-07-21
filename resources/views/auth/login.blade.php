<!DOCTYPE html>
{{--
    Premium standalone login (its own layout, matching register): a split screen
    with a graphite showcase panel and the role-aware sign-in form. Token-only,
    dark/light + RTL + i18n. CRITICAL: the role control is native radios still
    posting `role` (Talent | Brand only — staff use /admin/login); `?role=brand`
    pre-checks Brand (ADR-P); absent role defaults to talent; no-JS-safe.
--}}
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleDirection() }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ __('Welcome back') }} — Fama</title>
        @include('partials.design-head')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-bg text-ink antialiased">
        <div class="flex min-h-screen">
            {{-- Showcase panel (graphite; hidden on mobile) --}}
            <aside class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-primary p-12 text-on-primary lg:flex" aria-hidden="true">
                <div class="pointer-events-none absolute inset-0">
                    <div class="absolute -top-32 -end-24 h-[28rem] w-[28rem] rounded-pill bg-accent opacity-20 blur-3xl"></div>
                    <div class="absolute -bottom-40 -start-20 h-[26rem] w-[26rem] rounded-pill bg-accent opacity-10 blur-3xl"></div>
                </div>

                <a href="{{ url('/') }}" class="relative font-display text-3xl tracking-tight">Fama<span class="text-accent">.</span></a>

                <div class="relative max-w-md">
                    <h2 class="font-display text-4xl leading-tight">{{ __('Where creative talent meets the brands that book them.') }}</h2>
                    <p class="mt-4 text-sm opacity-80">{{ __('Sign in to manage your profile, projects, and contracts.') }}</p>
                </div>

                <p class="relative font-mono text-[11px] uppercase tracking-[0.18em] opacity-60">
                    {{ __('Creative talent · Egypt-first · MENA-wide') }}
                </p>
            </aside>

            {{-- Form panel --}}
            <main class="flex min-w-0 flex-1 flex-col">
                <div class="flex h-16 items-center justify-between px-5 sm:px-10">
                    <a href="{{ url('/') }}" class="font-display text-2xl tracking-tight text-ink lg:invisible">Fama<span class="text-accent">.</span></a>
                    <div class="flex items-center gap-3">
                        <x-public-locale-switcher />
                        <x-theme-toggle />
                    </div>
                </div>

                <div class="flex flex-1 items-center justify-center px-5 py-8 sm:px-10">
                    <div class="animate-fade-in-up w-full max-w-md">
                        <div class="mb-7">
                            <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-subtle">{{ __('Sign in') }}</p>
                            <h1 class="mt-2 font-display text-3xl text-ink">{{ __('Welcome back') }}</h1>
                            <p class="mt-1.5 text-sm text-muted">{{ __('Sign in to continue to Fama.') }}</p>
                        </div>

                        <x-auth-session-status class="mb-4" :status="session('status')" />

                        <form method="POST" action="{{ route('login') }}" class="space-y-5">
                            @csrf

                            {{-- Role (guard) selector — Talent | Brand only; ?role=brand pre-checks Brand (ADR-P) --}}
                            <fieldset>
                                <legend class="mb-1.5 block text-sm font-medium text-ink">{{ __('I am a') }}</legend>
                                @php($defaultRole = old('role', request('role', 'talent')))
                                <div class="grid grid-cols-2 gap-1 rounded-pill border border-line bg-elevated p-1">
                                    @foreach (['talent' => __('Talent'), 'brand' => __('Brand')] as $role => $label)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="role" value="{{ $role }}" @checked($defaultRole === $role) class="peer sr-only">
                                            <span class="flex items-center justify-center rounded-pill px-3 py-2 text-sm font-medium text-muted transition
                                                         peer-checked:bg-surface peer-checked:text-ink peer-checked:shadow-e1
                                                         peer-focus-visible:ring-2 peer-focus-visible:ring-accent hover:text-ink">
                                                {{ $label }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error :messages="$errors->get('role')" class="mt-2" />
                            </fieldset>

                            {{-- Email --}}
                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')"
                                              :error="$errors->has('email')" required autofocus autocomplete="username" placeholder="you@example.com" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            {{-- Password --}}
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

                            {{-- Remember me --}}
                            <label for="remember_me" class="inline-flex items-center gap-2">
                                <input id="remember_me" type="checkbox" name="remember"
                                       class="rounded border-line-strong bg-bg text-accent focus:ring-accent/40">
                                <span class="text-sm text-muted">{{ __('Remember me') }}</span>
                            </label>

                            <x-primary-button class="group w-full">
                                {{ __('Log in') }}
                                <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                </svg>
                            </x-primary-button>

                            @if (Route::has('register'))
                                <p class="border-t border-line pt-4 text-center text-sm text-muted">
                                    {{ __('New to Fama?') }}
                                    <a class="rounded font-medium text-accent-ink hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-accent" href="{{ route('register') }}">
                                        {{ __('Create an account') }}
                                    </a>
                                </p>
                            @endif
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
