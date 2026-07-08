<x-brand-layout :title="__('Reviews received')">
    <div x-data="brandReviews()" class="space-y-5">
        <p class="text-sm text-muted">{{ __('How talents rated working with you. These are moderated and cannot be edited.') }}</p>

        <div x-show="loading" class="py-10 text-center text-sm text-muted">{{ __('Loading…') }}</div>

        <div x-show="!loading" class="space-y-3">
            <template x-for="review in reviews" :key="review.id">
                <div class="rounded-xl border border-line bg-surface p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium" x-text="review.talent?.display_name || '{{ __('A talent') }}'"></span>
                        <span class="font-display text-lg text-accent" x-text="'★ ' + review.average_rating"></span>
                    </div>
                    <div class="mt-2 grid grid-cols-3 gap-2 text-center text-xs text-muted">
                        <div><div class="font-display text-base text-ink" x-text="review.communication_rating"></div>{{ __('Communication') }}</div>
                        <div><div class="font-display text-base text-ink" x-text="review.fairness_rating"></div>{{ __('Fairness') }}</div>
                        <div><div class="font-display text-base text-ink" x-text="review.creative_respect_rating"></div>{{ __('Creative respect') }}</div>
                    </div>
                    <p x-show="review.body" class="mt-3 text-sm text-ink" x-text="review.body"></p>
                </div>
            </template>
            <template x-if="!reviews.length"><p class="rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('No reviews yet.') }}</p></template>
        </div>
    </div>
</x-brand-layout>
