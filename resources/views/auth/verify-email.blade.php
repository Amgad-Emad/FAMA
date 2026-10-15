{{--
    Verify email — the fama-ui solo pane. No prototype page exists; it follows
    forgot-password.html's "sent" state (mint check · h1 · sub) with the resend
    action as the block button and Log out as the .alt link. Resend POSTs to
    route('verification.send'); its confirmation shows as a .note.
--}}
<x-fama.layout :title="__('Verify your email')" mode="auth" :css="['auth']">
    <main class="wrap"><section class="pane solo">
        <div class="done">
            <span class="ic"><svg class="icon" style="width:32px;height:32px" aria-hidden="true"><use href="#a-send"/></svg></span>
            <span class="label">{{ __('One more step') }}</span>
            <h1 class="h2">{{ __('Check your email') }}</h1>
            <p class="sub">{{ __("We've sent you a link to confirm your email address. Open it to finish setting up your account. If it doesn't arrive, look in spam or send a new one.") }}</p>
        </div>
        @if (session('status') == 'verification-link-sent')
            <p class="note" role="status">{{ __('A new verification link has been sent to the email address you provided during registration.') }}</p>
        @endif
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="btn btn-block"><svg class="icon" aria-hidden="true"><use href="#a-send"/></svg><span>{{ __('Resend Verification Email') }}</span></button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="alt">
            @csrf
            <button type="submit" class="link">{{ __('Log Out') }}</button>
        </form>
    </section></main>
</x-fama.layout>
