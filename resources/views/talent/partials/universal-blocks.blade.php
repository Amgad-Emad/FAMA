{{--
    What is true for every skill (fama-ui preview.html `wide()`, 24 Oct 2026):
    the profile-level sections (talent_type_id NULL) shown ONCE, after the skill
    tabs, under "About {name}" — never repeated inside each tab. The talent's
    point of view (worldview) joins them as its own section, placed before
    "Willing to explore" as in the prototype (else at the end).
    Each section renders to a string first so the whole region — heading
    included — is left out when nothing in it has content for this viewer
    (empty sections only show to the owner, via <x-profile.block>).
      blocks  — the universal ProfileBlocks, in order
      name    — the display name for the heading
--}}
@php
    $povHtml = filled($talent->worldview)
        ? '<section class="blk blk-pov"><div class="pv-sh"><h3 class="h2">'.e(__('Point of view')).'</h3></div><p class="povq" dir="auto">'.e($talent->worldview).'</p></section>'
        : '';
    $parts = [];
    foreach ($blocks as $block) {
        if ($povHtml !== '' && $block->blockType->key === 'interests') {
            $parts[] = $povHtml;
            $povHtml = '';
        }
        $parts[] = trim(view('talent.blocks._dispatch', ['talent' => $talent, 'block' => $block, 'isOwner' => $isOwner ?? false])->render());
    }
    $parts[] = $povHtml;
    $inner = implode('', array_filter($parts, fn ($html) => $html !== ''));
@endphp

@if ($inner !== '')
    <section class="wide-sections" id="universal-blocks" aria-labelledby="wideH">
        <h2 class="h1" id="wideH" dir="auto">{{ __('About :name', ['name' => $name ?? ($talent->display_name ?: $talent->slug)]) }}</h2>
        {!! $inner !!}
    </section>
@endif
