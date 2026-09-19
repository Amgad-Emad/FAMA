{{-- One skill tab's blocks (talent-spec, ADR-R). Rendered server-side for the
     active tab and returned by TalentProfileController@tab for lazy tabs.

     The skill's SPECIALITIES lead the panel (ADR-Y): a skill nests into
     specialities, and they are the first thing that tells a brand what kind of
     subject this person actually shoots. `$skill` is optional so any caller that
     only has blocks still renders. --}}
@php
    // Claims are eager-loaded on the talent; filter in memory, never re-query.
    $skillSpecialities = isset($skill) && $talent->relationLoaded('specialities')
        ? $talent->specialities->where('talent_type_id', $skill->id)
        : collect();
@endphp

@if ($skillSpecialities->isNotEmpty())
    <ul class="mb-10 flex flex-wrap gap-2" aria-label="{{ __('Specialities') }}">
        @foreach ($skillSpecialities as $speciality)
            {{-- A speciality is a search term, so it links into discovery — but
                 only where discovery exists. With the flag off (V1) the pill is
                 static rather than a link to a 404. --}}
            <li>
                @feature('discover')
                    <a href="{{ route('discover', ['filter' => ['type' => $skill->slug, 'speciality' => $speciality->slug]]) }}"
                       class="inline-block rounded-pill border border-line-strong px-3 py-1.5 font-mono text-xs text-muted transition hover:border-accent hover:text-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                        {{ $speciality->name }}
                    </a>
                @else
                    <span class="inline-block rounded-pill border border-line-strong px-3 py-1.5 font-mono text-xs text-muted">
                        {{ $speciality->name }}
                    </span>
                @endfeature
            </li>
        @endforeach
    </ul>
@endif

<div class="space-y-16">
    @forelse ($blocks as $block)
        @include('talent.blocks._dispatch', ['talent' => $talent, 'block' => $block])
    @empty
        <x-ui.card class="text-center text-subtle">{{ __('Nothing to show here yet.') }}</x-ui.card>
    @endforelse
</div>
