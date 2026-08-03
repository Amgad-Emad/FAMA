<x-brand-layout :title="__('Saved')">
    <div class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold">{{ __('Saved posts') }}</h2>
            <p class="text-sm text-muted">{{ __('Private to you — only you can see this collection.') }}</p>
        </div>
        @include('partials.feed-collection', ['mode' => 'saved', 'dataUrl' => route('brand.feed.saved.data'), 'skills' => $skills])
    </div>
</x-brand-layout>
