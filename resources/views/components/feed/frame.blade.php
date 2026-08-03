{{-- The notched Daylight frame shell (Prompt 5M, docs/design-system.md).
     Every post is ONE uniform width (640px) on a single spine — notes, media
     and Project spreads all match, for a calm, even feed. The blue shadow
     lives on the wrapper (drop-shadow follows the notch). --}}
@props(['width' => 'media', 'id' => null])

<div {{ $attributes->merge(['class' => 'relative mx-auto w-full max-w-[640px] frame-shadow']) }} @if($id) id="{{ $id }}" @endif>
    <article class="frame overflow-hidden">
        <span class="dl-light-veil" aria-hidden="true"></span>
        {{ $slot }}
    </article>

</div>
