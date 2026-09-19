{{--
    Write a recognition (ADR-Z) — one talent vouching for another.

    Deliberately NOT a review form: no rating, because a peer is not scoring a
    booking. The two fields that matter are HOW YOU KNOW THEM and WHAT YOU'D SAY,
    and the copy prompts for specifics rather than leaving an empty box — an
    unprompted box is what turns this section into generic praise.
--}}
<x-public-layout :title="__('Write a recognition')">
    <div class="mx-auto max-w-lg px-4 py-10 sm:px-6"
         x-data="{
            form: {
                relationship: @js($existing?->relationship ?? ''),
                body: @js($existing?->body ?? ''),
            },
            errors: {}, saving: false, done: false,
            async submit() {
                this.saving = true; this.errors = {};
                try {
                    await window.fama.post('{{ route('talent.recognition.store', ['slug' => $talent->slug]) }}', this.form);
                    this.done = true;
                } catch (e) {
                    if (e instanceof window.fama.ApiError) this.errors = e.errors || { _: [e.message] };
                } finally { this.saving = false; }
            },
         }">
        <a href="{{ url($talent->slug) }}" class="font-mono text-xs uppercase tracking-[0.18em] text-accent-ink hover:underline">
            ← {{ $talent->display_name }}
        </a>
        <h1 class="mt-4 font-display text-3xl text-ink">{{ __('Write a recognition') }}</h1>
        <p class="mt-1 text-sm leading-relaxed text-muted">
            {{ __('A vouch for :name from someone who has actually worked with them. Unlike a review, this does not need a booking on Fama — which is why it counts.', ['name' => $talent->display_name]) }}
        </p>

        @if ($existing)
            <p class="mt-4 rounded-lg border border-line bg-elevated px-4 py-3 text-sm text-muted">
                {{ __('You have already written one for :name. Saving replaces it, and it goes back to our team for approval.', ['name' => $talent->display_name]) }}
            </p>
        @endif

        <template x-if="done">
            <x-ui.card class="mt-8 text-center" elevated>
                <div class="font-display text-2xl text-ink">{{ __('Thank you!') }}</div>
                <p class="mt-2 text-muted">{{ __('Your recognition has been submitted and is pending approval.') }}</p>
                <a href="{{ url($talent->slug) }}" class="mt-4 inline-block"><x-ui.button variant="outline">{{ __('Back to profile') }}</x-ui.button></a>
            </x-ui.card>
        </template>

        <template x-if="!done">
            <x-ui.card class="mt-8 space-y-5">
                <div>
                    <label for="rec-relationship" class="mb-1 block text-sm font-medium text-ink">{{ __('How do you know them?') }}</label>
                    <input id="rec-relationship" x-model="form.relationship" type="text" maxlength="160"
                           placeholder="{{ __('Shot three campaigns together · Assisted me for two years') }}"
                           class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    <p class="mt-1 text-xs text-subtle">{{ __('Be specific. This is what tells a reader your vouch is worth something.') }}</p>
                    <p x-show="errors.relationship" x-cloak class="mt-1 text-xs text-danger" x-text="errors.relationship?.[0]"></p>
                </div>
                <div>
                    <label for="rec-body" class="mb-1 block text-sm font-medium text-ink">{{ __('What would you say about them?') }}</label>
                    <textarea id="rec-body" x-model="form.body" rows="6" maxlength="1500"
                              placeholder="{{ __('What are they good at that a portfolio does not show? What would you trust them with?') }}"
                              class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                    <p x-show="errors.body" x-cloak class="mt-1 text-xs text-danger" x-text="errors.body?.[0]"></p>
                </div>
                <p x-show="errors._" x-cloak class="text-xs text-danger" x-text="errors._?.[0]"></p>
                <x-ui.button variant="accent" x-on:click="submit()" x-bind:disabled="saving">
                    <span x-show="!saving">{{ $existing ? __('Update recognition') : __('Submit recognition') }}</span>
                    <span x-show="saving" x-cloak>{{ __('Submitting…') }}</span>
                </x-ui.button>
            </x-ui.card>
        </template>
    </div>
</x-public-layout>
