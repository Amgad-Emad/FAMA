{{--
    Create account — the fama-ui split screen (prototype register.html). Kept from
    the app: native POST to route('register') with CSRF, old() values, server
    validation errors in the help lines, show/hide toggles, the strength meter, and
    `account_type` (talent | brand only, ADR-I): the .type cards render only when the
    brands feature is on; V1 (brands off) posts a hidden account_type=talent. The
    name label reads "Brand name" while Brand is picked.
--}}
@php
    $serverErrors = collect($errors->getMessages())->map(fn ($m) => $m[0]);
    $type = old('account_type', 'talent');
@endphp
<x-fama.layout :title="__('Create account')" mode="auth" :css="['auth']">
    <x-slot:styles>
        <style>
            /* Account type (brands feature on): the auth.css .type cards side by side. */
            .types{display:grid;grid-template-columns:1fr 1fr;gap:var(--s3);border:0;padding:0;margin:0 0 var(--s5)}
            .types legend{font-weight:600;font-size:15px;margin-bottom:var(--s2);padding:0}
        </style>
    </x-slot:styles>

    <main class="wrap"><div class="auth">
        @include('auth.partials.showcase')
        <section class="pane">
            <span class="label">{{ __('Join Fama') }}</span>
            <h2 class="h2">{{ __('Create your account') }}</h2>
            <p class="sub">{{ __('Your profile goes live at its own link straight away. Complete it and it’s listed in the Creatives Index, where brands search.') }}</p>
            <form id="form" method="POST" action="{{ route('register') }}" novalidate
                  x-data="famaAuthForm({ type: @js($type), errors: {{ \Illuminate\Support\Js::from($serverErrors) }}, checks: [
                      { field: 'name', rule: 'required', message: @js(__('Add your full name.')) },
                      { field: 'email', rule: 'emailDomain', message: @js(__('Enter an email address like name@studio.com.')) },
                      { field: 'password', rule: 'strong', message: @js(__('Use at least 8 characters, and mix in letters, numbers or symbols.')) },
                      { field: 'password_confirmation', rule: 'match', other: 'password', message: @js(__("The passwords don't match.")) },
                  ] })"
                  @submit="submit($event)">
                @csrf

                @feature('brands')
                    <fieldset class="types">
                        <legend>{{ __('Account type') }}</legend>
                        @foreach (['talent' => [__("I'm a talent"), __('Model, photographer, stylist…')], 'brand' => [__("I'm a brand"), __('Hiring creative talent')]] as $value => [$label, $desc])
                            <label class="type">
                                <input type="radio" name="account_type" value="{{ $value }}" x-model="type" @checked($type === $value)>
                                <div><b>{{ $label }}</b><span>{{ $desc }}</span></div>
                            </label>
                        @endforeach
                        @error('account_type')<p class="help err" role="alert" style="grid-column:1/-1">{{ $message }}</p>@enderror
                    </fieldset>
                @else
                    <input type="hidden" name="account_type" value="talent">
                @endfeature

                @include('auth.partials.field', ['name' => 'name', 'label' => __('Full name'), 'value' => old('name'), 'autocomplete' => 'name', 'maxlength' => 255, 'autofocus' => true,
                    'labelBind' => "type === 'brand' ? ".\Illuminate\Support\Js::from(__('Brand name')).' : '.\Illuminate\Support\Js::from(__('Full name'))])
                @include('auth.partials.field', ['name' => 'email', 'type' => 'email', 'label' => __('Email'), 'value' => old('email'), 'autocomplete' => 'email', 'ltr' => true])
                @include('auth.partials.password', ['name' => 'password', 'id' => 'pw', 'label' => __('Password'), 'autocomplete' => 'new-password', 'meter' => true,
                    'help' => __('At least 8 characters, with a mix of letters, numbers and symbols.')])
                @include('auth.partials.password', ['name' => 'password_confirmation', 'id' => 'pw2', 'label' => __('Confirm password'), 'autocomplete' => 'new-password'])

                <button class="btn btn-block"><svg class="icon" aria-hidden="true"><use href="#a-user-plus"/></svg><span>{{ __('Create account') }}</span></button>
            </form>
            <p class="alt"><span>{{ __('Already on Fama?') }}</span> <a class="link" href="{{ route('login') }}">{{ __('Log in') }}</a></p>
        </section>
    </div></main>
</x-fama.layout>
