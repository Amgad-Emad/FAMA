<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl text-ink">{{ __('Confirm your password') }}</h1>
        <p class="mt-1 text-sm text-muted">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <div class="mt-1"><x-password-input id="password" :error="$errors->has('password')" autocomplete="current-password" /></div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">
            {{ __('Confirm') }}
        </x-primary-button>
    </form>
</x-guest-layout>
