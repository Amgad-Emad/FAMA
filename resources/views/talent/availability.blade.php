<x-talent-layout :title="__('Availability')">
    <div x-data="{
        form: {
            availability_status: @js($talent->availability_status->getValue()),
            willing_to_travel: @js((bool) $talent->willing_to_travel),
            rate_tier: @js($talent->rate_tier),
            travel_regions: @js($talent->travel_regions ?? []),
        },
        regionsText: @js(implode(', ', $talent->travel_regions ?? [])),
        saving: false, saved: false, errors: {},
        async save() {
            this.saving = true; this.saved = false; this.errors = {};
            this.form.travel_regions = this.regionsText.split(',').map(s => s.trim()).filter(Boolean);
            try {
                await window.fama.patch('/talent/availability', this.form);
                this.saved = true; setTimeout(() => this.saved = false, 2000);
            } catch (e) {
                if (e instanceof window.fama.ApiError) this.errors = e.errors || { _: [e.message] };
            } finally { this.saving = false; }
        },
    }">
        <x-ui.section :title="__('Availability & travel')" :eyebrow="__('Drives your hero & booking CTA')">
            <x-ui.card class="space-y-6">
                <div>
                    <label class="mb-2 block text-sm font-medium text-ink">{{ __('Status') }}</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['available' => __('Available'), 'booked' => __('Booked'), 'unavailable' => __('Unavailable')] as $value => $label)
                            <button type="button" @click="form.availability_status = '{{ $value }}'"
                                    class="rounded-pill border px-4 py-1.5 text-sm font-medium transition"
                                    :class="form.availability_status === '{{ $value }}' ? 'border-transparent bg-accent text-on-accent' : 'border-line-strong text-muted hover:text-ink'">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <label class="flex items-center gap-3">
                    <input type="checkbox" x-model="form.willing_to_travel" class="rounded border-line-strong text-accent focus:ring-accent">
                    <span class="text-sm text-ink">{{ __('Willing to travel') }}</span>
                </label>

                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Travel regions') }}</label>
                    <input x-model="regionsText" type="text" placeholder="MENA, GCC, Europe" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    <p class="mt-1 text-xs text-subtle">{{ __('Comma-separated.') }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Rate tier') }}</label>
                    <select x-model="form.rate_tier" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent sm:max-w-xs">
                        <option :value="null">{{ __('Not set') }}</option>
                        <option value="emerging">{{ __('Emerging') }}</option>
                        <option value="established">{{ __('Established') }}</option>
                        <option value="premium">{{ __('Premium') }}</option>
                        <option value="elite">{{ __('Elite') }}</option>
                    </select>
                </div>

                <div class="flex items-center gap-3">
                    <x-ui.button variant="accent" x-on:click="save()" x-bind:disabled="saving">
                        <span x-show="!saving">{{ __('Save') }}</span><span x-show="saving" x-cloak>{{ __('Saving…') }}</span>
                    </x-ui.button>
                    <span x-show="saved" x-cloak class="text-sm text-success">{{ __('Saved') }}</span>
                </div>
            </x-ui.card>
        </x-ui.section>
    </div>
</x-talent-layout>
