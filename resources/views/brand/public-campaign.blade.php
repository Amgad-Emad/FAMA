@php
    $cover = $campaign->cover_image_url;
    $budget = collect([$campaign->budget_min, $campaign->budget_max])->filter(fn ($v) => $v !== null);
    $location = collect([$campaign->location_city, $campaign->location_country])->filter()->implode(', ');
    $dates = collect([$campaign->start_date?->translatedFormat('M j, Y'), $campaign->end_date?->translatedFormat('M j, Y')])->filter()->implode(' – ');
@endphp

<x-public-layout :title="$campaign->title">
    <div class="mx-auto max-w-5xl px-4 pt-8 sm:px-6">
        <a href="{{ route('brand.public', $brand) }}" class="text-sm text-muted hover:text-ink">← {{ $brand->name }}</a>

        {{-- Cover --}}
        <div class="mt-4 overflow-hidden rounded-lg border border-line">
            <div class="h-56 bg-elevated bg-cover bg-center sm:h-80"
                 @style([
                     "background-image:url('$cover')" => $cover,
                     'background-image:repeating-linear-gradient(135deg, var(--line) 0 1px, transparent 1px 16px)' => ! $cover,
                 ])></div>
        </div>

        {{-- Header --}}
        <div class="mt-6">
            <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-subtle">{{ __(ucfirst($campaign->type)) }} · {{ __(ucfirst(str_replace('_', ' ', $campaign->status->getValue()))) }}</p>
            <h1 class="mt-2 font-display text-4xl leading-[0.98] text-ink sm:text-5xl">{{ $campaign->title }}</h1>
            @if ($campaign->description)
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-muted">{{ $campaign->getTranslation('description', app()->getLocale()) }}</p>
            @endif
        </div>

        {{-- Facts --}}
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3">
            @if ($budget->isNotEmpty())
                <div class="rounded-lg border border-line bg-surface p-4">
                    <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Budget') }}</div>
                    <div class="mt-1 font-display text-lg text-ink">{{ $budget->map(fn ($v) => number_format((float) $v))->implode(' – ') }} {{ $campaign->currency }}</div>
                </div>
            @endif
            @if ($location)
                <div class="rounded-lg border border-line bg-surface p-4">
                    <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Location') }}</div>
                    <div class="mt-1 font-display text-lg text-ink">{{ $location }}</div>
                </div>
            @endif
            @if ($dates)
                <div class="rounded-lg border border-line bg-surface p-4">
                    <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Dates') }}</div>
                    <div class="mt-1 font-display text-lg text-ink">{{ $dates }}</div>
                </div>
            @endif
        </div>

        {{-- Roles sought --}}
        @if ($campaign->talentTypes->isNotEmpty())
            <section class="mt-10">
                <h2 class="mb-4 font-display text-2xl text-ink">{{ __('Roles sought') }}</h2>
                <div class="flex flex-wrap gap-3">
                    @foreach ($campaign->talentTypes as $type)
                        <span class="inline-flex items-center gap-2 rounded-lg border border-line bg-surface px-4 py-2 text-sm">
                            <span class="text-ink">{{ $type->getTranslation('name', app()->getLocale()) }}</span>
                            <span class="rounded-pill bg-accent-weak px-2 py-0.5 text-xs text-accent-ink">× {{ (int) $type->pivot->quantity }}</span>
                        </span>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Gallery --}}
        @if ($campaign->gallery->isNotEmpty())
            <section class="mt-10">
                <h2 class="mb-4 font-display text-2xl text-ink">{{ __('Gallery') }}</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @foreach ($campaign->gallery as $item)
                        <div class="aspect-square overflow-hidden rounded-lg border border-line">
                            <img src="{{ $item->thumbnail_url ?: $item->media_url }}" alt="{{ $item->getTranslation('caption', app()->getLocale()) }}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-public-layout>
