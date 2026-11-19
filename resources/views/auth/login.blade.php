{{--
    Log in — the fama-ui split screen (prototype login.html): the purple showcase
    beside the form pane. Behaviour kept from the app: native POST to route('login')
    with CSRF, old() email, remember me, server validation errors in the field help
    lines, the show/hide toggle, session status, and the role control — radios still
    posting `role` (Talent | Brand only; staff use /admin/login); `?role=brand`
    pre-checks Brand (ADR-P); absent role → talent. The control only renders when the
    brands feature is on (V1 is talent-only sign-in). $intentNote names the feed
    action a guest was sent here from (prototype `next` note).
--}}
@php
    $serverErrors = collect($errors->getMessages())->map(fn ($m) => $m[0]);
    $defaultRole = old('role', request('role', 'talent'));
@endphp
<x-fama.layout :title="__('Log in')" :seo="\App\Support\Seo::make()->description(__('Log in to Fama to update your profile and projects.'))" mode="auth" :css="['auth']">
    <x-slot:styles>
        <style>
            /* Role control (brands feature on): the auth.css .type cards side by side. */
            .roles{display:grid;grid-template-columns:1fr 1fr;gap:var(--s3);border:0;padding:0;margin:0 0 var(--s5)}
            .roles legend{font-weight:600;font-size:15px;margin-bottom:var(--s2);padding:0}
        </style>
    </x-slot:styles>

    <main class="wrap"><div class="auth">
        @include('auth.partials.showcase')
        <section class="pane">
            <span class="label">{{ __('Log in') }}</span>
            <h2 class="h2">{{ __('Welcome back.') }}</h2>
            @if (! empty($intentNote))
                <p class="note" id="next">{{ $intentNote }}</p>
            @endif
            @if (session('status'))
                <p class="note" role="status">{{ session('status') }}</p>
            @endif
            <form id="form" method="POST" action="{{ route('login') }}" novalidate
                  x-data="famaAuthForm({ errors: {{ \Illuminate\Support\Js::from($serverErrors) }}, checks: [
                      { field: 'email', rule: 'email', message: @js(__('Enter the email you signed up with.')) },
                      { field: 'password', rule: 'required', message: @js(__('Enter your password.')) },
                  ] })"
                  @submit="submit($event)">
                @csrf

                @feature('brands')
                    <fieldset class="roles">
                        <legend>{{ __('I am a') }}</legend>
                        @foreach (['talent' => [__('Talent'), __('Model, photographer, stylist…')], 'brand' => [__('Brand'), __('Hiring creative talent')]] as $role => [$label, $desc])
                            <label class="type">
                                <input type="radio" name="role" value="{{ $role }}" @checked($defaultRole === $role)>
                                <div><b>{{ $label }}</b><span>{{ $desc }}</span></div>
                            </label>
                        @endforeach
                        @error('role')<p class="help err" role="alert" style="grid-column:1/-1">{{ $message }}</p>@enderror
                    </fieldset>
                @endfeature

                @include('auth.partials.field', ['name' => 'email', 'type' => 'email', 'label' => __('Email'), 'value' => old('email'), 'autocomplete' => 'email', 'ltr' => true, 'autofocus' => true])
                @include('auth.partials.password', ['name' => 'password', 'id' => 'pw', 'label' => __('Password'), 'autocomplete' => 'current-password'])

                <div class="row2">
                    <label class="check"><input type="checkbox" id="remember" name="remember" @checked(old('remember'))><span>{{ __('Remember me') }}</span></label>
                    @if (Route::has('password.request'))
                        <a class="link" href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
                    @endif
                </div>
                <button class="btn btn-block"><svg class="icon" aria-hidden="true"><use href="#a-log-in"/></svg><span>{{ __('Log in') }}</span></button>
            </form>
            @if (Route::has('register'))
                <p class="alt"><span>{{ __('New here?') }}</span> <a class="link" href="{{ route('register') }}">{{ __('Create an account') }}</a></p>
            @endif
        </section>
    </div></main>
</x-fama.layout>
