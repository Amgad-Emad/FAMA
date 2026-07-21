<!DOCTYPE html>
{{--
    Premium standalone registration (its own layout, not the guest card): a
    split screen whose left showcase panel adapts to the chosen account type
    (talent vs brand). Token-only, dark/light + RTL + i18n. `account_type` is a
    native radio set — the form still submits with JS off (talent default).
--}}
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleDirection() }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ __('Create your Fama account') }} — Fama</title>
        @include('partials.design-head')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-bg text-ink antialiased">
        <div class="flex min-h-screen" x-data="{ type: '{{ old('account_type', 'talent') }}' }">
            {{-- Showcase panel (graphite, adapts to account type; hidden on mobile) --}}
            <aside class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-primary p-12 text-on-primary lg:flex" aria-hidden="true">
                <div class="pointer-events-none absolute inset-0">
                    <div class="absolute -top-32 -end-24 h-[28rem] w-[28rem] rounded-pill bg-accent opacity-20 blur-3xl transition-all duration-700"
                         :class="type === 'brand' ? 'translate-y-16' : ''"></div>
                    <div class="absolute -bottom-40 -start-20 h-[26rem] w-[26rem] rounded-pill bg-accent opacity-10 blur-3xl"></div>
                </div>

                <a href="{{ url('/') }}" class="relative font-display text-3xl tracking-tight">Fama<span class="text-accent">.</span></a>

                <div class="relative max-w-md">
                    {{-- Headline swaps per type --}}
                    <h2 class="font-display text-4xl leading-tight">
                        <span x-show="type === 'talent'">{{ __('Get discovered by the brands that matter.') }}</span>
                        <span x-show="type === 'brand'" x-cloak>{{ __('Find and book the right creative talent.') }}</span>
                    </h2>

                    {{-- Feature bullets swap per type --}}
                    <ul class="mt-8 space-y-4">
                        @php
                            $bullets = [
                                'talent' => [
                                    __('Build a living portfolio that grows with you.'),
                                    __('Get booked directly through Fama.'),
                                    __('Build your reputation with verified reviews.'),
                                ],
                                'brand' => [
                                    __('Discover creative talent across MENA.'),
                                    __('Run your projects from brief to delivery.'),
                                    __('Manage contracts and payments in one place.'),
                                ],
                            ];
                        @endphp
                        @foreach (['talent', 'brand'] as $t)
                            <template x-if="type === '{{ $t }}'">
                                <div class="space-y-4">
                                    @foreach ($bullets[$t] as $bullet)
                                        <li class="flex items-start gap-3">
                                            <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-pill bg-accent/20 text-accent">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                            </span>
                                            <span class="text-sm opacity-90">{{ $bullet }}</span>
                                        </li>
                                    @endforeach
                                </div>
                            </template>
                        @endforeach
                    </ul>
                </div>

                <p class="relative font-mono text-[11px] uppercase tracking-[0.18em] opacity-60">
                    {{ __('Trusted by creatives and brands across the region.') }}
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
                            <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-subtle">{{ __('Sign up') }}</p>
                            <h1 class="mt-2 font-display text-3xl text-ink">{{ __('Create your Fama account') }}</h1>
                            <p class="mt-1.5 text-sm text-muted">{{ __('It takes less than a minute.') }}</p>
                        </div>

                        <form method="POST" action="{{ route('register') }}" class="space-y-5">
                            @csrf

                            {{-- Account type: rich selectable cards (native radios; talent | brand only, ADR-I) --}}
                            <fieldset>
                                <legend class="mb-2 block text-sm font-medium text-ink">{{ __("Choose how you'll use Fama") }}</legend>
                                <div class="grid grid-cols-2 gap-3">
                                    @php
                                        $types = [
                                            'talent' => ['label' => __("I'm a Talent"), 'desc' => __('Showcase your work and get booked.'),
                                                'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z'],
                                            'brand' => ['label' => __("I'm a Brand"), 'desc' => __('Discover talent and run projects.'),
                                                'icon' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21'],
                                        ];
                                    @endphp
                                    @foreach ($types as $value => $meta)
                                        <label class="group cursor-pointer">
                                            <input type="radio" name="account_type" value="{{ $value }}" x-model="type"
                                                   @checked(old('account_type', 'talent') === $value) class="peer sr-only">
                                            <div class="h-full rounded-2xl border border-line bg-surface p-4 transition
                                                        peer-checked:border-accent peer-checked:ring-2 peer-checked:ring-accent/30 peer-checked:shadow-e2
                                                        peer-focus-visible:ring-2 peer-focus-visible:ring-accent
                                                        group-hover:border-line-strong">
                                                <span class="grid h-10 w-10 place-items-center rounded-pill transition"
                                                      :class="type === '{{ $value }}' ? 'bg-accent-weak text-accent-ink' : 'bg-elevated text-muted'">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $meta['icon'] }}"/></svg>
                                                </span>
                                                <div class="mt-3 font-display text-base text-ink">{{ $meta['label'] }}</div>
                                                <p class="mt-0.5 text-xs text-muted">{{ $meta['desc'] }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error :messages="$errors->get('account_type')" class="mt-2" />
                            </fieldset>

                            {{-- Name (label adapts) --}}
                            <div>
                                <x-input-label for="name" x-text="type === 'brand' ? '{{ __('Brand name') }}' : '{{ __('Full name') }}'" />
                                <x-text-input id="name" class="mt-1.5 block w-full" type="text" name="name" :value="old('name')"
                                              :error="$errors->has('name')" required autofocus autocomplete="name" />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            {{-- Email --}}
                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')"
                                              :error="$errors->has('email')" required autocomplete="username" placeholder="you@example.com" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            {{-- Passwords (side by side on wider viewports) --}}
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="password" :value="__('Password')" />
                                    <div class="mt-1.5"><x-password-input id="password" :error="$errors->has('password')" autocomplete="new-password" /></div>
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                                    <div class="mt-1.5"><x-password-input id="password_confirmation" :error="$errors->has('password_confirmation')" autocomplete="new-password" /></div>
                                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                                </div>
                            </div>

                            <x-primary-button class="group w-full">
                                {{ __('Create account') }}
                                <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180 rtl:group-hover:-translate-x-0.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                </svg>
                            </x-primary-button>

                            <p class="border-t border-line pt-4 text-center text-sm text-muted">
                                {{ __('Already have an account?') }}
                                <a class="rounded font-medium text-accent-ink hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-accent" href="{{ route('login') }}">
                                    {{ __('Log in') }}
                                </a>
                            </p>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
