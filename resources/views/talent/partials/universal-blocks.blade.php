{{-- The universal / profile-level block region (talent_type_id NULL). Rendered
     above OR below the skill-tab region depending on the talent's section
     order (meta.universal_position — see the editor's Sections pill bar). --}}
@foreach ($blocks as $block)
    @include('talent.blocks._dispatch', ['talent' => $talent, 'block' => $block])
@endforeach
