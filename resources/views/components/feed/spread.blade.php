{{-- The Project spread (revised 5W, ADR-F2) — the STRONGEST frame in the feed:
     full-bleed cover under the notch, eyebrow, Fraunces title, mono meta line,
     a thumbnail strip (+N counter), a 2-line clamped description with a fade,
     skill chips, and VIEW THE PROJECT. The whole frame click-targets the
     Project via a stretched link; inner links sit above it. A dead Project
     renders COMPLETE from the widened snapshot, minus the links. --}}
@props(['post'])

@php
    $project = $post->relationLoaded('project') ? $post->project : null;
    $snapshot = $post->shared_snapshot ?? [];

    $title = $project?->title
        ?? ($snapshot['title'][app()->getLocale()] ?? $snapshot['title']['en'] ?? __('A Project'));
    $cover = $project?->cover_image_url ?? ($snapshot['cover_media_path'] ?? null);
    $client = $project?->client_name ?? ($snapshot['client_name'] ?? null);
    $year = $project?->year ?? ($snapshot['year'] ?? null);
    $mediaCount = $project !== null
        ? $project->media->count()
        : (int) ($snapshot['media_count'] ?? 0);
    $thumbs = $project !== null ? $project->media->skip(1)->take(3) : collect();
    $extraCount = max(0, $mediaCount - 1 - $thumbs->count());
    $url = ($project !== null && $post->author !== null)
        ? route('talent.work', ['slug' => $post->author->feedUsername(), 'project' => $project->id])
        : null;

    $meta = collect([$client, $year])->filter()->implode(' · ');
@endphp

<div class="relative">
    {{-- The stretched click target (live Projects only). --}}
    @if ($url)
        <a href="{{ $url }}" class="absolute inset-0" style="z-index: var(--z-base);" aria-label="{{ $title }}"><span class="sr-only">{{ $title }}</span></a>
    @endif

    @if ($cover)
        <figure class="pointer-events-none">
            <img src="{{ $cover }}" alt="{{ $title }}" loading="lazy" class="block max-h-[480px] w-full object-cover">
        </figure>
    @endif

    <div class="relative pointer-events-none p-6 sm:p-8" style="z-index: var(--z-frame-raised);">
        <p class="t-meta" style="color: var(--dl-ink-faint);">{{ __('FROM THE PROJECT') }}</p>

        <h3 class="t-display mt-2">
            @if ($url)
                <a href="{{ $url }}" class="pointer-events-auto hover:text-daylight transition-colors">{{ $title }}</a>
            @else
                <span class="opacity-80">{{ $title }}</span>
            @endif
        </h3>

        @if ($meta !== '')
            <p class="t-meta mt-1.5">{{ $meta }}</p>
        @endif

        @if ($project?->summary)
            <p class="t-body dl-clamp-2 mt-3 text-muted">{{ $project->summary }}</p>
        @endif

        {{-- Thumbnail strip: up to 3 further images + a "+N" counter chip --}}
        @if ($thumbs->isNotEmpty())
            <div class="pointer-events-auto mt-4 flex items-center gap-2">
                @foreach ($thumbs as $mediaItem)
                    <a @if($url) href="{{ $url }}" @endif class="block h-16 w-16 overflow-hidden rounded-md border border-seam">
                        <img src="{{ $mediaItem->getUrl() }}" alt="" loading="lazy" class="h-full w-full object-cover">
                    </a>
                @endforeach
                @if ($extraCount > 0)
                    <a @if($url) href="{{ $url }}" @endif class="dl-chip">+{{ $extraCount }}</a>
                @endif
            </div>
        @endif

        {{-- The Skills the share covers, as mono chips --}}
        @if ($post->skills->isNotEmpty())
            <div class="pointer-events-auto mt-4 flex flex-wrap gap-2">
                @foreach ($post->skills as $skill)
                    <a href="{{ route('discover', ['filter' => ['type' => $skill->slug]]) }}" class="dl-chip">{{ $skill->name }}</a>
                @endforeach
            </div>
        @endif

        <div class="pointer-events-auto mt-5">
            @if ($url)
                <a href="{{ $url }}" class="t-meta text-daylight hover:underline">{{ __('VIEW THE PROJECT') }} →</a>
            @else
                <span class="t-meta" style="color: var(--dl-ink-faint);">{{ __('This Project is no longer available.') }}</span>
            @endif
        </div>
    </div>
</div>
