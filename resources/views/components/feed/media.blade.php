{{-- Post media (rebuilt 5AE): maps the medialibrary set onto the shared grid
     geometry and mounts the lightbox. Alt text: per-image `alt` custom
     property when the talent supplied one, else the post body truncated —
     never empty. Videos keep native controls in the grid. --}}
@props(['post', 'eager' => false])

@php
    $fallbackAlt = str(strip_tags((string) $post->body))->squish()->limit(120)->toString()
        ?: __('Post media by :name', ['name' => $post->author?->feedDisplayName()]);

    $items = $post->getMedia('post_media')->map(function ($media) use ($fallbackAlt) {
        $feed = $media->hasGeneratedConversion('feed') ? $media->getUrl('feed') : $media->getUrl();
        $full = $media->hasGeneratedConversion('full') ? $media->getUrl('full') : $media->getUrl();

        return [
            'url' => $media->getUrl(),
            'feed_url' => $feed,
            'full_url' => $full,
            'alt' => $media->getCustomProperty('alt') ?: $fallbackAlt,
            'is_video' => str_starts_with((string) $media->mime_type, 'video/'),
            'poster' => $media->hasGeneratedConversion('poster') ? $media->getUrl('poster') : null,
        ];
    })->values()->all();
@endphp

<div x-data="mediaLightbox({ items: @js($items), caption: @js(str(strip_tags((string) $post->body))->limit(180)->toString()) })"
     @lightbox-open="openAt($event.detail.index)">
    <x-feed.media-grid :items="$items" :eager="$eager" />

    {{-- The lightbox (5AE): full-screen, arrows/swipe/Escape, counter,
         adjacent preload, focus returns to the opening tile. --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak class="lightbox" style="z-index: var(--z-modal);"
             role="dialog" aria-modal="true" :aria-label="items[current]?.alt"
             @click.self="close()"
             @keydown.escape.window="open && close()"
             @keydown.arrow-right.window="open && next()"
             @keydown.arrow-left.window="open && prev()"
             tabindex="-1" x-ref="box"
             @touchstart="touchX = $event.touches[0].clientX"
             @touchend="onSwipe($event.changedTouches[0].clientX)">
            <button type="button" class="lightbox-close t-meta" @click="close()" aria-label="{{ __('Close') }}">✕</button>

            <button type="button" class="lightbox-nav lightbox-nav--prev" @click="prev()" aria-label="{{ __('Previous image') }}">‹</button>
            <figure class="lightbox-stage">
                <img :src="items[current]?.full_url" :alt="items[current]?.alt">
                <figcaption class="lightbox-meta">
                    <span class="t-meta tabular-nums" x-text="String(current + 1).padStart(2, '0') + ' / ' + String(items.length).padStart(2, '0')"></span>
                    <span class="t-body" x-show="caption" x-text="caption"></span>
                </figcaption>
            </figure>
            <button type="button" class="lightbox-nav lightbox-nav--next" @click="next()" aria-label="{{ __('Next image') }}">›</button>
        </div>
    </template>
</div>
