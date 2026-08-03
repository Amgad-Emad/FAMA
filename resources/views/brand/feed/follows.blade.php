<x-brand-layout :title="__('Following')">
    <div class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold">{{ __('Your follow graph') }}</h2>
            <p class="text-sm text-muted">{{ __('Manage who you follow and who follows you — prune a feed that has gone quiet.') }}</p>
        </div>
        @include('partials.feed-follows', [
            'tab' => $tab,
            'showTypeFilter' => $showTypeFilter,
            'skills' => $skills,
            'dataUrl' => $dataUrl,
        ])
    </div>
</x-brand-layout>
