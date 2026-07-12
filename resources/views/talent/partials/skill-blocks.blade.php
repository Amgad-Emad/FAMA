{{-- One skill tab's blocks (talent-spec, ADR-R). Rendered server-side for the
     active tab and returned by TalentProfileController@tab for lazy tabs. --}}
<div class="space-y-16">
    @forelse ($blocks as $block)
        @include('talent.blocks._dispatch', ['talent' => $talent, 'block' => $block])
    @empty
        <x-ui.card class="text-center text-subtle">{{ __('Nothing to show here yet.') }}</x-ui.card>
    @endforelse
</div>
