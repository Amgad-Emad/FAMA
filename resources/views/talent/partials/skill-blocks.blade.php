{{-- One skill tab's body (ADR-R): rendered server-side for the active tab and
     returned by TalentProfileController@tab for lazy tabs. The skill's
     SPECIALITIES lead it (ADR-Y — fama-ui .specs-row chips), then its sections.
     `$skill` and `$isOwner` are optional so any caller with only blocks renders. --}}
@php
    // Claims are eager-loaded on the talent; filter in memory, never re-query.
    $skillSpecialities = isset($skill) && $talent->relationLoaded('specialities')
        ? $talent->specialities->where('talent_type_id', $skill->id)
        : collect();
@endphp

@if ($skillSpecialities->isNotEmpty())
    <div class="pv-sh specs-row">
        <ul class="chips specs" aria-label="{{ __('Specialities') }}" style="list-style:none;padding:0;margin-bottom:0">
            @foreach ($skillSpecialities as $speciality)
                {{-- A speciality is a search term, so it links into discovery —
                     only where discovery exists (V1: a static chip, not a 404). --}}
                <li>
                    @feature('discover')
                        <a class="chip" href="{{ route('discover', ['filter' => ['type' => $skill->slug, 'speciality' => $speciality->slug]]) }}">{{ $speciality->name }}</a>
                    @else
                        <span class="chip">{{ $speciality->name }}</span>
                    @endfeature
                </li>
            @endforeach
        </ul>
    </div>
@endif

@foreach ($blocks as $block)
    @include('talent.blocks._dispatch', ['talent' => $talent, 'block' => $block, 'isOwner' => $isOwner ?? false])
@endforeach
