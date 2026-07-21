<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl text-ink">{{ __('Verify your email') }}</h1>
        <p class="mt-1 text-sm text-muted">
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-lg border border-line bg-accent-weak px-4 py-3 text-sm font-medium text-accent-ink" role="status">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                {{ __('Resend Verification Email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="rounded text-sm font-medium text-muted transition hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
