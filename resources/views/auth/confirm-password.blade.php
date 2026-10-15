{{--
    Confirm password — the secure-area gate on the fama-ui solo pane. No prototype
    page exists; it follows forgot-password.html (label · h1 · sub · one field ·
    block button). POSTs to route('password.confirm').
--}}
@php
    $serverErrors = collect($errors->getMessages())->map(fn ($m) => $m[0]);
@endphp
<x-fama.layout :title="__('Confirm password')" mode="auth" :css="['auth']">
    <main class="wrap"><section class="pane solo">
        <span class="label">{{ __('Security check') }}</span>
        <h1 class="h2">{{ __('Confirm your password') }}</h1>
        <p class="sub">{{ __("You're opening a secure area. Enter your password to carry on.") }}</p>
        <form id="form" method="POST" action="{{ route('password.confirm') }}" novalidate
              x-data="famaAuthForm({ errors: {{ \Illuminate\Support\Js::from($serverErrors) }}, checks: [
                  { field: 'password', rule: 'required', message: @js(__('Enter your password.')) },
              ] })"
              @submit="submit($event)">
            @csrf
            @include('auth.partials.password', ['name' => 'password', 'id' => 'pw', 'label' => __('Password'), 'autocomplete' => 'current-password'])
            <button class="btn btn-block"><svg class="icon" aria-hidden="true"><use href="#a-check"/></svg><span>{{ __('Confirm') }}</span></button>
        </form>
    </section></main>
</x-fama.layout>
