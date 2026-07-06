<x-public-layout :title="__('Leave a review')">
    <div class="mx-auto max-w-lg px-4 py-10 sm:px-6"
         x-data="{
            form: { reviewer_name: '', reviewer_role: '', reviewer_company: '', rating: 5, body: '', project_type: '' },
            errors: {}, saving: false, done: false,
            async submit() {
                this.saving = true; this.errors = {};
                try {
                    await window.fama.post('{{ route('talent.review.store', ['slug' => $talent->slug]) }}', this.form);
                    this.done = true;
                } catch (e) {
                    if (e instanceof window.fama.ApiError) this.errors = e.errors || { _: [e.message] };
                } finally { this.saving = false; }
            },
         }">
        <a href="{{ url($talent->slug) }}" class="font-mono text-xs uppercase tracking-[0.18em] text-accent-ink hover:underline">
            ← {{ $talent->display_name }}
        </a>
        <h1 class="mt-4 font-display text-3xl text-ink">{{ __('Leave a review') }}</h1>
        <p class="mt-1 text-sm text-muted">{{ __('Share your experience working with :name.', ['name' => $talent->display_name]) }}</p>

        <template x-if="done">
            <x-ui.card class="mt-8 text-center" elevated>
                <div class="font-display text-2xl text-ink">{{ __('Thank you!') }}</div>
                <p class="mt-2 text-muted">{{ __('Your review has been submitted and is pending approval.') }}</p>
                <a href="{{ url($talent->slug) }}" class="mt-4 inline-block"><x-ui.button variant="outline">{{ __('Back to profile') }}</x-ui.button></a>
            </x-ui.card>
        </template>

        <template x-if="!done">
            <x-ui.card class="mt-8 space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Your name') }}</label>
                    <input x-model="form.reviewer_name" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    <p x-show="errors.reviewer_name" x-cloak class="mt-1 text-xs text-danger" x-text="errors.reviewer_name?.[0]"></p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Role') }}</label>
                        <input x-model="form.reviewer_role" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Company') }}</label>
                        <input x-model="form.reviewer_company" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Rating') }}</label>
                    <div class="flex items-center gap-1 text-2xl text-gold">
                        <template x-for="n in 5" :key="n">
                            <button type="button" @click="form.rating = n" x-text="n <= form.rating ? '★' : '☆'"></button>
                        </template>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Project type') }}</label>
                    <input x-model="form.project_type" type="text" placeholder="{{ __('Editorial, campaign…') }}" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Your review') }}</label>
                    <textarea x-model="form.body" rows="5" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                    <p x-show="errors.body" x-cloak class="mt-1 text-xs text-danger" x-text="errors.body?.[0]"></p>
                </div>
                <x-ui.button variant="accent" x-on:click="submit()" x-bind:disabled="saving">
                    <span x-show="!saving">{{ __('Submit review') }}</span><span x-show="saving" x-cloak>{{ __('Submitting…') }}</span>
                </x-ui.button>
            </x-ui.card>
        </template>
    </div>
</x-public-layout>
