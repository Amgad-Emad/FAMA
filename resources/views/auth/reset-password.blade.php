<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl text-ink">{{ __('Choose a new password') }}</h1>
        <p class="mt-1 text-sm text-muted">{{ __('Set a new password for your account.') }}</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email', $request->email)"
                          :error="$errors->has('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <div class="mt-1"><x-password-input id="password" :error="$errors->has('password')" autocomplete="new-password" /></div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <div class="mt-1"><x-password-input id="password_confirmation" :error="$errors->has('password_confirmation')" autocomplete="new-password" /></div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">
            {{ __('Reset Password') }}
        </x-primary-button>
    </form>
</x-guest-layout>
