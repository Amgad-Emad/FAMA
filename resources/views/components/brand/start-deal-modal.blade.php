{{-- Reusable "Start a deal" modal. Include once on any brand-facing page; open it
     from a CTA with $dispatch('open-start-deal', { talentId, talentName, campaignId }).
     Posts to /brand/deals via Alpine (brandStartDeal) and redirects to the room. --}}
<div x-data="brandStartDeal()"
     @open-start-deal.window="onOpen($event.detail)"
     x-cloak>
    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition.opacity>
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>

        <div class="relative w-full max-w-md rounded-xl border border-line bg-surface p-6 shadow-xl"
             @keydown.escape.window="open = false">
            <h2 class="font-display text-xl text-ink">{{ __('Start a deal') }}</h2>
            <p class="mt-1 text-sm text-muted">
                {{ __('with') }} <span class="font-medium text-ink" x-text="talentName"></span>
            </p>

            <label class="mt-4 block text-sm font-medium text-ink">{{ __('Brief (optional)') }}</label>
            <textarea x-model="brief" rows="4"
                      class="mt-1 w-full rounded-lg border border-line bg-bg px-3 py-2 text-sm text-ink placeholder:text-subtle focus:border-accent focus:outline-none"
                      placeholder="{{ __('Describe the project, dates and budget…') }}"></textarea>

            <p x-show="campaignId" class="mt-2 text-xs text-muted">{{ __('This deal will be linked to the selected campaign.') }}</p>
            <p x-show="error" x-text="error" class="mt-2 text-sm text-danger"></p>

            <div class="mt-5 flex justify-end gap-3">
                <button type="button" @click="open = false"
                        class="rounded-pill border border-line px-4 py-2 text-sm text-muted hover:text-ink">
                    {{ __('Cancel') }}
                </button>
                <button type="button" @click="start()" :disabled="saving"
                        class="rounded-pill bg-accent px-5 py-2 text-sm font-medium text-on-primary hover:opacity-90 disabled:opacity-50"
                        x-text="saving ? '{{ __('Starting…') }}' : '{{ __('Start deal') }}'"></button>
            </div>
        </div>
    </div>
</div>
