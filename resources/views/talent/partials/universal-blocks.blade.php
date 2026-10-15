{{-- The universal / profile-level sections (talent_type_id NULL). Rendered
     above OR below the skill tabs depending on the talent's section order
     (meta.universal_position — the Sections panel on Profile structure). --}}
@foreach ($blocks as $block)
    @include('talent.blocks._dispatch', ['talent' => $talent, 'block' => $block, 'isOwner' => $isOwner ?? false])
@endforeach
