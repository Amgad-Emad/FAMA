{{--
    Staff console sign-in — the fama-ui split screen (same language as the talent
    login, auth.css), with an INK showcase so staff can tell it from the purple
    public sign-in at a glance. Posts to admin.login.store (guard pinned server-side,
    no role control); noindex.
--}}
@php
    $serverErrors = collect($errors->getMessages())->map(fn ($m) => $m[0]);
@endphp
<x-fama.layout :title="__('Staff sign in')" mode="bare" :css="['auth']" :footer="false">
    <x-slot:head>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>
    <x-slot:styles>
        <style>
            .staff-top{display:flex;align-items:center;gap:var(--s3);padding:var(--s5) 0}
            .staff-top img{height:34px;display:block}
            .staff-badge{font-size:11px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;border:1px solid var(--line);border-radius:var(--radius-pill);padding:4px 10px;color:var(--grey);background:var(--white)}
            .au-show.staff{background:var(--ink)}
            .au-show.staff .hstar{fill:var(--purple)}
            .au-show.staff p{color:#D9D3CA}
            .au-show.staff .au-tags{font-size:12px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:#9C958C;max-width:none;margin:0}
            .staff-alt{margin-top:var(--s5);padding-top:var(--s4);border-top:var(--border);text-align:center;color:var(--grey);font-size:var(--t-meta)}
        </style>
    </x-slot:styles>

    <main class="wrap">
        <div class="staff-top">
            <img src="{{ asset('fama-ui/img/fama-logo-tight.svg') }}" alt="Fama">
            <span class="staff-badge">{{ __('Admin') }}</span>
        </div>
        <div class="auth">
            <section class="au-show staff" aria-hidden="true">
                <div class="au-in">
                    <div>
                        <h1>{{ __('Operations console') }}</h1>
                        <p>{{ __('Moderation, verification, the skills and block catalogs, and platform settings — in one place.') }}</p>
                    </div>
                    <p class="au-tags">{{ __('Creative talent · Egypt-first · MENA-wide') }}</p>
                </div>
                <div class="au-photo" role="presentation"></div>
                <svg class="hstar" viewBox="10 80 760 690" aria-hidden="true"><polygon points="314.63 635.59 16.74 747.68 217.29 533.15 86.97 370.29 403.26 356.02 701.63 87.21 636.51 355.76 760 356.91 574.68 534.04 422.33 756.79 314.63 635.59"/></svg>
            </section>

            <section class="pane">
                <span class="label">{{ __('Restricted area') }}</span>
                <h2 class="h2">{{ __('Staff sign in') }}</h2>
                <p class="sub">{{ __('For Fama team accounts only.') }}</p>
                @if (session('status'))
                    <p class="note" role="status">{{ session('status') }}</p>
                @endif
                <form method="POST" action="{{ route('admin.login.store') }}" novalidate
                      x-data="famaAuthForm({ errors: {{ \Illuminate\Support\Js::from($serverErrors) }}, checks: [
                          { field: 'email', rule: 'email', message: @js(__('Enter your work email.')) },
                          { field: 'password', rule: 'required', message: @js(__('Enter your password.')) },
                      ] })"
                      @submit="submit($event)">
                    @csrf
                    @include('auth.partials.field', ['name' => 'email', 'type' => 'email', 'label' => __('Work email'), 'value' => old('email'), 'autocomplete' => 'username', 'ltr' => true, 'autofocus' => true])
                    @include('auth.partials.password', ['name' => 'password', 'id' => 'password', 'label' => __('Password'), 'autocomplete' => 'current-password'])

                    <div class="row2">
                        <label class="check"><input type="checkbox" id="remember_me" name="remember" @checked(old('remember'))><span>{{ __('Remember me') }}</span></label>
                        @if (Route::has('password.request'))
                            <a class="link" href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
                        @endif
                    </div>
                    <button class="btn btn-block"><svg class="icon" aria-hidden="true"><use href="#a-log-in"/></svg><span>{{ __('Sign in to the console') }}</span></button>
                </form>
                <p class="staff-alt">{{ __('Not staff?') }} <a class="link" href="{{ route('login') }}">{{ __('Go to the Fama login') }}</a></p>
            </section>
        </div>
    </main>
</x-fama.layout>
