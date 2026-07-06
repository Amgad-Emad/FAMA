@php
    $primaryType = $talent->talentTypes->firstWhere(fn ($type) => (bool) $type->pivot->is_primary)
        ?? $talent->talentTypes->first();
    $hero = $talent->hero_image_url;
    $avatar = $talent->avatar_url;
@endphp

<x-public-layout :title="$talent->display_name">
    {{-- Hero + identity header --}}
    <div class="mx-auto max-w-6xl px-4 pt-8 sm:px-6">
        <div class="relative overflow-hidden rounded-lg border border-line"
             @style([
                 "background-image:url('$hero');background-size:cover;background-position:center" => $hero,
                 'background-color:var(--surface);background-image:repeating-linear-gradient(135deg, var(--line) 0 1px, transparent 1px 16px)' => ! $hero,
             ])>
            <div class="flex h-[280px] items-start p-5 sm:h-[380px]">
                <x-ui.badge :status="$talent->availability_status" />
            </div>
        </div>

        <x-ui.card elevated class="relative z-10 -mt-14 flex flex-col gap-6 sm:-mt-16 sm:flex-row sm:items-end">
            <x-ui.avatar :src="$avatar" :name="$talent->display_name" size="xl" class="-mt-20 shadow-e2 sm:mt-0" />

            <div class="flex-1">
                @if ($primaryType)
                    <x-ui.eyebrow>{{ $primaryType->name }}</x-ui.eyebrow>
                @endif
                <h1 class="mt-1 font-display text-4xl leading-[0.95] text-ink sm:text-6xl">{{ $talent->display_name }}</h1>

                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted">
                    @if ($talent->base_city)
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 1 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ $talent->base_city }}, {{ $talent->base_country }}
                        </span>
                    @endif
                    <span class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ number_format($talent->view_count) }} {{ __('views') }}</span>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($talent->talentTypes as $type)
                        <x-ui.chip tone="accent">{{ $type->name }}</x-ui.chip>
                    @endforeach
                    @if ($talent->rate_tier)
                        <x-ui.chip tone="gold">{{ __(ucfirst($talent->rate_tier)) }}</x-ui.chip>
                    @endif
                    @if ($talent->willing_to_travel)
                        <x-ui.chip tone="neutral">{{ __('Willing to travel') }}</x-ui.chip>
                    @endif
                </div>
            </div>

            <div class="flex flex-col gap-2 sm:self-center">
                <x-ui.button variant="accent" size="lg" class="w-full sm:w-auto">{{ __('Contact') }}</x-ui.button>
                <x-ui.button :href="route('talent.review.create', ['slug' => $talent->slug])" variant="outline" size="sm" class="w-full sm:w-auto">{{ __('Leave a review') }}</x-ui.button>
            </div>
        </x-ui.card>
    </div>

    {{-- Blocks in position order (hero rendered above) --}}
    <div class="mx-auto mt-16 max-w-6xl space-y-16 px-4 pb-4 sm:px-6">
        @foreach ($talent->profileBlocks as $block)
            @php $key = $block->blockType->key; @endphp
            @continue($key === 'hero')

            @php $partial = 'talent.blocks.'.$key; @endphp
            @includeWhen(view()->exists($partial), $partial, ['talent' => $talent, 'block' => $block])
            @includeUnless(view()->exists($partial), 'talent.blocks.generic', ['talent' => $talent, 'block' => $block])
        @endforeach

        @if ($talent->profileBlocks->where('blockType.key', '!=', 'hero')->isEmpty())
            <x-ui.card class="text-center text-subtle">{{ __('This profile is still being set up.') }}</x-ui.card>
        @endif
    </div>
</x-public-layout>
