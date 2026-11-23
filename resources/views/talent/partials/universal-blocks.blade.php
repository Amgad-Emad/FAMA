{{--
    What is true for every skill (fama-ui preview.html `wide()`, 24 Oct 2026):
    the profile-level sections (talent_type_id NULL) shown ONCE, after the skill
    tabs, under "About {name}" — never repeated inside each tab. The talent's
    point of view (worldview) is NOT here: it moved up into the hero
    (13 Nov 2026 review — it is part of the person's details).
    Each section renders to a string first so the whole region — heading
    included — is left out when nothing in it has content for this viewer
    (empty sections are left out for everyone, via <x-profile.block>).
      blocks  — the universal ProfileBlocks, in order
      name    — the display name for the heading
--}}
@php
    $parts = [];
    foreach ($blocks as $block) {
        $parts[] = trim(view('talent.blocks._dispatch', ['talent' => $talent, 'block' => $block, 'isOwner' => $isOwner ?? false])->render());
    }
    $inner = implode('', array_filter($parts, fn ($html) => $html !== ''));
@endphp

@if ($inner !== '')
    <section class="wide-sections" id="universal-blocks" aria-labelledby="wideH">
        <h2 class="h1" id="wideH" dir="auto">{{ __('About :name', ['name' => $name ?? ($talent->display_name ?: $talent->slug)]) }}</h2>
        {!! $inner !!}
    </section>
@endif
