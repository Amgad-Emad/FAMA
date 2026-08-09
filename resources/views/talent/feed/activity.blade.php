@php
    // The three consolidated tabs (Saved · Liked · Following). `view` is the
    // active one; the Following tab keeps its own FOLLOWING/FOLLOWERS sub-toggle.
    $tabs = [
        'saved' => ['label' => __('Saved'), 'icon' => 'M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z'],
        'liked' => ['label' => __('Liked'), 'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
        'following' => ['label' => __('Following'), 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8z'],
    ];
    $subtitles = [
        'saved' => __('Posts you bookmarked — private to you.'),
        'liked' => __('Posts you liked — private to you.'),
        'following' => __('Manage who you follow and who follows you.'),
    ];
@endphp

<x-talent-layout :title="__('Activity')">
    <div class="space-y-6">
        {{-- Header + premium segmented tab bar --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-muted">{{ $subtitles[$view] }}</p>

            <div class="inline-flex rounded-pill border border-line bg-surface p-1 shadow-e1" role="tablist" aria-label="{{ __('Activity') }}">
                @foreach ($tabs as $key => $meta)
                    <a href="{{ route('talent.feed.activity', ['view' => $key]) }}"
                       role="tab" aria-selected="{{ $view === $key ? 'true' : 'false' }}"
                       @class([
                           'inline-flex items-center gap-2 rounded-pill px-4 py-2 text-sm font-medium transition',
                           'bg-accent text-on-accent shadow-e1' => $view === $key,
                           'text-muted hover:bg-elevated hover:text-ink' => $view !== $key,
                       ])>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $meta['icon'] }}"/></svg>
                        <span>{{ $meta['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Active tab content (existing partials + their Ajax endpoints) --}}
        @if ($view === 'following')
            @include('partials.feed-follows', [
                'tab' => $tab,
                'showTypeFilter' => $showTypeFilter,
                'skills' => $skills,
                'dataUrl' => $dataUrl,
            ])
        @else
            @include('partials.feed-collection', [
                'mode' => $view,
                'dataUrl' => $dataUrl,
                'skills' => $skills,
            ])
        @endif
    </div>
</x-talent-layout>
