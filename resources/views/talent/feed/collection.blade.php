<x-talent-layout :title="$mode === 'saved' ? __('Saved') : __('Liked')">
    <div class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold">{{ $mode === 'saved' ? __('Saved posts') : __('Liked posts') }}</h2>
            <p class="text-sm text-muted">{{ __('Private to you — only you can see this collection.') }}</p>
        </div>
        @include('partials.feed-collection', ['mode' => $mode, 'dataUrl' => route('talent.feed.collection.data'), 'skills' => $skills])
    </div>
</x-talent-layout>
