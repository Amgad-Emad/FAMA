{{--
    One section of the public profile (fama-ui preview.html `section.blk`): the
    heading row (.pv-sh) then the content. A section with nothing in it is left
    out for EVERYONE — the owner included (2026-11-23): View profile shows the
    page exactly as visitors see it, no "Nothing here yet" placeholders. Empty
    sections are managed from Edit profile.
      block   — the ProfileBlock (its admin-governed title / catalog name)
      empty   — whether the section has no content
      isOwner — accepted for callers; no longer changes what renders
      side    — optional slot beside the heading
--}}
@props(['block', 'empty' => false, 'isOwner' => false])

@unless ($empty)
    <section {{ $attributes->class(['blk', 'blk-'.$block->blockType->key]) }}>
        <div class="pv-sh"><div><h2 class="h2">{{ $block->title ?: $block->blockType->name }}</h2>{{ $sum ?? '' }}</div>{{ $side ?? '' }}</div>
        {{ $slot }}
    </section>
@endunless
