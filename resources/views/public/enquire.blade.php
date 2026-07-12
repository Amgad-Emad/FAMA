<x-public-layout :title="__('Contact :name', ['name' => $talent->display_name])">
    <div class="mx-auto max-w-lg px-4 py-10 sm:px-6"
         x-data="{
            form: { contact_name: '', contact_email: '', contact_company: '', brief: '' },
            errors: {}, saving: false, done: false,
            async submit() {
                this.saving = true; this.errors = {};
                try {
                    await window.fama.post('{{ route('talent.enquire.store', ['slug' => $talent->slug]) }}', this.form);
                    this.done = true;
                } catch (e) {
                    if (e instanceof window.fama.ApiError) this.errors = e.errors || { _: [e.message] };
                } finally { this.saving = false; }
            },
         }">
        <a href="{{ url($talent->slug) }}" class="font-mono text-xs uppercase tracking-[0.18em] text-accent-ink hover:underline">
            ← {{ $talent->display_name }}
        </a>
        <h1 class="mt-4 font-display text-3xl text-ink">{{ __('Start a booking') }}</h1>
        <p class="mt-1 text-sm text-muted">{{ __('Tell :name about your project — they will be in touch to open a deal.', ['name' => $talent->display_name]) }}</p>

        <template x-if="done">
            <x-ui.card class="mt-8 text-center">
                <div class="font-display text-2xl text-ink">{{ __('Enquiry sent') }}</div>
                <p class="mt-2 text-muted">{{ __('Your enquiry is on its way.') }}</p>
                <a href="{{ url($talent->slug) }}" class="mt-4 inline-block"><x-ui.button variant="outline">{{ __('Back to profile') }}</x-ui.button></a>
            </x-ui.card>
        </template>

        <template x-if="!done">
            <x-ui.card class="mt-8 space-y-4">
                <p x-show="errors._" x-cloak class="rounded-md bg-danger-weak px-3 py-2 text-sm text-danger" x-text="errors._?.[0]"></p>

                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Your name') }}</label>
                    <input x-model="form.contact_name" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    <p x-show="errors.contact_name" x-cloak class="mt-1 text-xs text-danger" x-text="errors.contact_name?.[0]"></p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Email') }}</label>
                        <input x-model="form.contact_email" type="email" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                        <p x-show="errors.contact_email" x-cloak class="mt-1 text-xs text-danger" x-text="errors.contact_email?.[0]"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Company') }}</label>
                        <input x-model="form.contact_company" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Project brief') }}</label>
                    <textarea x-model="form.brief" rows="5" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                    <p x-show="errors.brief" x-cloak class="mt-1 text-xs text-danger" x-text="errors.brief?.[0]"></p>
                </div>

                <x-ui.button variant="accent" x-on:click="submit()" x-bind:disabled="saving">
                    <span x-show="!saving">{{ __('Send enquiry') }}</span><span x-show="saving" x-cloak>{{ __('Sending…') }}</span>
                </x-ui.button>
            </x-ui.card>
        </template>
    </div>
</x-public-layout>
