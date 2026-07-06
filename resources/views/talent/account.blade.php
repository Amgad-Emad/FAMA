<x-talent-layout :title="__('Account')">
    <div x-data="{
        slug: @js($talent->slug),
        isPublished: @js((bool) $talent->is_published),
        status: @js($talent->status->getValue()),
        saving: false, saved: false, errors: {}, publishing: false, publishError: '',
        async saveAccount() {
            this.saving = true; this.saved = false; this.errors = {};
            try {
                const { data } = await window.fama.patch('/talent/account', { slug: this.slug });
                this.slug = data.slug; this.saved = true; setTimeout(() => this.saved = false, 2000);
            } catch (e) {
                if (e instanceof window.fama.ApiError) this.errors = e.errors || { _: [e.message] };
            } finally { this.saving = false; }
        },
        async togglePublish() {
            this.publishing = true; this.publishError = '';
            try {
                const { data } = await window.fama.patch('/talent/account/publish', { publish: !this.isPublished });
                this.isPublished = data.is_published; this.status = data.status;
            } catch (e) {
                if (e instanceof window.fama.ApiError) this.publishError = e.message;
            } finally { this.publishing = false; }
        },
    }" class="space-y-8">

        <x-ui.section :title="__('Publishing')" :eyebrow="__('Profile visibility')">
            <x-ui.card class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-pill" :class="isPublished ? 'bg-success' : 'bg-warn'"></span>
                        <span class="font-display text-xl text-ink" x-text="isPublished ? '{{ __('Live') }}' : ('{{ __('Not live') }}' + ' · ' + status)"></span>
                    </div>
                    <p x-show="publishError" x-cloak class="mt-1 text-xs text-danger" x-text="publishError"></p>
                </div>
                <x-ui.button variant="accent" x-on:click="togglePublish()" x-bind:disabled="publishing">
                    <span x-text="isPublished ? '{{ __('Unpublish') }}' : '{{ __('Publish') }}'"></span>
                </x-ui.button>
            </x-ui.card>
        </x-ui.section>

        <x-ui.section :title="__('Public URL')" :eyebrow="__('Your slug')">
            <x-ui.card class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Slug') }}</label>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-sm text-subtle">fama.com/</span>
                        <input x-model="slug" type="text" class="flex-1 rounded-md border-line bg-elevated font-mono text-sm text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </div>
                    <p x-show="errors.slug" x-cloak class="mt-1 text-xs text-danger" x-text="errors.slug?.[0]"></p>
                </div>
                <div class="flex items-center gap-3">
                    <x-ui.button variant="accent" x-on:click="saveAccount()" x-bind:disabled="saving">{{ __('Save') }}</x-ui.button>
                    <span x-show="saved" x-cloak class="text-sm text-success">{{ __('Saved') }}</span>
                </div>
            </x-ui.card>
        </x-ui.section>
    </div>
</x-talent-layout>
