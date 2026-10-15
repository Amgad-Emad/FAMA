{{--
    Reset password — the fama-ui solo pane (prototype reset-password.html). POSTs to
    route('password.store') with the hidden token; the email comes from the reset
    link (read-only with a hint when present, editable otherwise — e.g. after an
    "invalid token" error, which Breeze reports on the email field). Success
    redirects to the login page, whose status note confirms the change.
--}}
@php
    $serverErrors = collect($errors->getMessages())->map(fn ($m) => $m[0]);
    $linkEmail = $request->email;
    $locked = filled($linkEmail) && ! $errors->has('email');
@endphp
<x-fama.layout :title="__('Reset password')" mode="auth" :css="['auth']">
    <main class="wrap"><section class="pane solo">
        <div id="ask">
            <span class="label">{{ __('Password help') }}</span>
            <h1 class="h2">{{ __('Choose a new password') }}</h1>
            <form id="form" method="POST" action="{{ route('password.store') }}" novalidate
                  x-data="famaAuthForm({ errors: {{ \Illuminate\Support\Js::from($serverErrors) }}, checks: [
                      { field: 'email', rule: 'email', message: @js(__('Enter an email address like name@studio.com.')) },
                      { field: 'password', rule: 'min8', message: @js(__('Use at least 8 characters, and mix in letters, numbers or symbols.')) },
                      { field: 'password_confirmation', rule: 'match', other: 'password', message: @js(__("The passwords don't match.")) },
                  ] })"
                  @submit="submit($event)">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">
                <input type="hidden" name="account" value="{{ old('account', $request->query('account')) }}">
                @include('auth.partials.field', ['name' => 'email', 'type' => 'email', 'label' => __('Email'), 'value' => old('email', $linkEmail), 'autocomplete' => 'username', 'ltr' => true,
                    'readonly' => $locked, 'autofocus' => ! $locked, 'help' => $locked ? __('Filled in from your reset link.') : ''])
                @include('auth.partials.password', ['name' => 'password', 'id' => 'pw', 'label' => __('New password'), 'autocomplete' => 'new-password', 'meter' => true,
                    'help' => __('At least 8 characters, with a mix of letters, numbers and symbols.')])
                @include('auth.partials.password', ['name' => 'password_confirmation', 'id' => 'pw2', 'label' => __('Confirm new password'), 'autocomplete' => 'new-password'])
                <button class="btn btn-block"><span>{{ __('Save new password') }}</span></button>
            </form>
        </div>
    </section></main>
</x-fama.layout>
