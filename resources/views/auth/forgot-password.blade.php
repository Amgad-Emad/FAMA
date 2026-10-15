{{--
    Forgot password — the fama-ui solo pane (prototype forgot-password.html). The
    form POSTs to route('password.email'); on success Breeze redirects back with
    session('status') (+ the flashed email), which flips the pane to the "sent"
    state. "Use a different email" flips back in place (and is a real link
    without JS). A failure (unknown address, throttled) shows in the email help line.
--}}
@php
    $serverErrors = collect($errors->getMessages())->map(fn ($m) => $m[0]);
    $sent = (bool) session('status');
    $sentEmail = old('email');
@endphp
<x-fama.layout :title="__('Forgot password')" mode="auth" :css="['auth']">
    <main class="wrap"><section class="pane solo" id="pane" x-data="{ sent: @js($sent) }">
        <div id="ask" x-show="!sent" @if ($sent) x-cloak @endif>
            <span class="label">{{ __('Password help') }}</span>
            <h1 class="h2">{{ __('Forgot your password?') }}</h1>
            <p class="sub">{{ __("Enter your email and we'll send you a link to choose a new password.") }}</p>
            <form id="form" method="POST" action="{{ route('password.email') }}" novalidate
                  x-data="famaAuthForm({ errors: {{ \Illuminate\Support\Js::from($serverErrors) }}, checks: [
                      { field: 'email', rule: 'email', message: @js(__('Enter an email address like name@studio.com.')) },
                  ] })"
                  @submit="submit($event)">
                @csrf
                @if (in_array(request('role', old('role')), ['talent', 'brand'], true))
                    <input type="hidden" name="role" value="{{ request('role', old('role')) }}">
                @endif
                @include('auth.partials.field', ['name' => 'email', 'type' => 'email', 'label' => __('Email'), 'value' => old('email'), 'autocomplete' => 'email', 'ltr' => true, 'autofocus' => ! $sent])
                <button class="btn btn-block"><span>{{ __('Email reset link') }}</span></button>
            </form>
            <p class="alt"><a class="link" href="{{ route('login') }}">{{ __('Back to log in') }}</a></p>
        </div>
        @if ($sent)
            <div id="sent" class="done" role="status" x-show="sent">
                <span class="ic"><svg class="icon" style="width:32px;height:32px" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg></span>
                <h1 class="h2">{{ __("We've emailed your reset link") }}</h1>
                <p class="sub" id="sentP">
                    @if ($sentEmail)
                        {{ __("Check :email. The link works for one hour. If it doesn't arrive, look in spam.", ['email' => $sentEmail]) }}
                    @else
                        {{ session('status') }}
                    @endif
                </p>
                <p class="alt"><a class="link" id="again" href="{{ route('password.request') }}"
                    @click.prevent="sent = false; $nextTick(() => document.getElementById('email')?.select())">{{ __('Use a different email') }}</a></p>
            </div>
        @endif
    </section></main>
</x-fama.layout>
