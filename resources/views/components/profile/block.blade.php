{{--
    One section of the public profile (fama-ui preview.html `section.blk`): the
    heading row (.pv-sh) then the content. A section with nothing in it is left
    out for visitors; the OWNER sees it with the prototype's "Nothing here yet"
    note, so an empty section is never a mystery on their Preview.
      block   — the ProfileBlock (its admin-governed title / catalog name)
      empty   — whether the section has no content
      isOwner — whether the viewer is the profile's owner
      side    — optional slot beside the heading
--}}
@props(['block', 'empty' => false, 'isOwner' => false])

@if (! $empty || $isOwner)
    <section {{ $attributes->class(['blk', 'blk-'.$block->blockType->key]) }}>
        <div class="pv-sh"><div><h2 class="h2">{{ $block->title ?: $block->blockType->name }}</h2>{{ $sum ?? '' }}</div>{{ $side ?? '' }}</div>
        @if ($empty)
            <div class="pv-empty"><b>{{ __('Nothing here yet.') }}</b> {{ __('Visitors won\'t see this section until you add to it.') }}</div>
        @else
            {{ $slot }}
        @endif
    </section>
@endif
