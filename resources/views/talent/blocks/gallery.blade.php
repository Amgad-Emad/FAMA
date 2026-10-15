{{-- Gallery (fama-ui .gal): block-scoped items (ADR-Q); the first spans two rows; every picture opens the lightbox. --}}
@php
    $items = $talent->portfolioItems->where('block_id', $block->id)->sortBy('position')->values();
    $lb = 'gallery-'.$block->id;
@endphp
<x-profile.block :block="$block" :empty="$items->isEmpty()" :is-owner="$isOwner ?? false">
    <div class="gal">
        @foreach ($items as $item)
            @php $cap = (string) $item->caption; $thumb = $item->thumbnail_url; @endphp
            <button type="button" @class(['first' => $loop->first]) data-lb="{{ $lb }}" data-src="{{ $item->media_url ?: $thumb }}" data-cap="{{ $cap }}" aria-label="{{ $cap ?: __('Picture') }}, {{ __('open') }}">
                @if ($thumb)
                    <div @class(['ph', 'cover', 'wide' => $loop->first]) style="background-image:url('{{ $thumb }}')" aria-hidden="true"></div>
                @else
                    <div @class(['ph', 'wide' => $loop->first]) aria-hidden="true"><svg class="icon"><use href="#{{ $item->media_type === 'image' ? 'i-image' : 'i-play' }}"/></svg></div>
                @endif
                @if ($cap)<small>{{ $cap }}</small>@endif
            </button>
        @endforeach
    </div>
</x-profile.block>
