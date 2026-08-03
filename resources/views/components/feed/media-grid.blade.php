{{-- The media grid geometry (5AE) — ONE component, two contexts (feed frames
     and the composer preview). Fixed layouts by count inside a 3:2 container
     with a 4px gap; the notch lives on the OUTER frame only. $items is a
     plain array: [{url, feed_url, full_url, alt, is_video, poster}].
     Tiles open the lightbox at their index via $dispatch('lightbox-open'). --}}
@props(['items', 'eager' => false, 'interactive' => true])

@php
    $count = count($items);
    $shown = $count >= 4 ? 3 : $count;
    $extra = $count - $shown;
@endphp

@if ($count === 1)
    {{-- Single image: natural ratio clamped 4:5 … 16:9 (container units). --}}
    <div class="media-grid media-grid--single" style="contain-intrinsic-size: auto 480px;">
        <button type="button" class="media-tile" @if($interactive) @click="$dispatch('lightbox-open', { index: 0 })" @endif
                aria-label="{{ $items[0]['alt'] }}">
            @if ($items[0]['is_video'])
                <video controls preload="metadata" class="block h-full w-full object-cover"
                       @if($items[0]['poster']) poster="{{ $items[0]['poster'] }}" @endif>
                    <source src="{{ $items[0]['url'] }}">
                </video>
            @else
                <img src="{{ $items[0]['feed_url'] }}"
                     @if($items[0]['feed_url'] !== $items[0]['full_url']) srcset="{{ $items[0]['feed_url'] }} 1080w, {{ $items[0]['full_url'] }} 1600w" sizes="(min-width: 640px) 640px, 100vw" @endif
                     alt="{{ $items[0]['alt'] }}" @if(!$eager) loading="lazy" @endif
                     class="media-img">
            @endif
        </button>
    </div>
@else
    <div class="media-grid {{ $count === 2 ? 'media-grid--two' : 'media-grid--half' }}" style="contain-intrinsic-size: auto 420px;">
        @foreach (array_slice($items, 0, $shown) as $index => $item)
            <button type="button" class="media-tile {{ $count > 2 && $index === 0 ? 'media-tile--lead' : '' }}"
                    @if($interactive) @click="$dispatch('lightbox-open', { index: {{ $index }} })" @endif
                    aria-label="{{ $item['alt'] }}">
                <img src="{{ $item['feed_url'] }}"
                     @if($item['feed_url'] !== $item['full_url']) srcset="{{ $item['feed_url'] }} 1080w, {{ $item['full_url'] }} 1600w" sizes="(min-width: 640px) 320px, 50vw" @endif
                     alt="{{ $item['alt'] }}" @if(!$eager) loading="lazy" @endif class="media-img">
                @if ($extra > 0 && $index === $shown - 1)
                    {{-- The +N overlay on the final quarter --}}
                    <span class="media-more" aria-hidden="true">+{{ $extra }}</span>
                @endif
            </button>
        @endforeach
    </div>
@endif
