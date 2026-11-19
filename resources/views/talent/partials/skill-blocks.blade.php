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

{{-- About the practice (ADR-AG): the skill's custom fields as short facts, after the work
     (prototype 9 Oct 2026). Comfort and coverage is for the owner and signed-in brands, never
     visitors; digitals are their own block; software stays out when the Software block shows. --}}
@isset($skill)
    @php
        $practiceFacts = \App\Support\PracticeFields::facts(
            $skill->slug,
            app(\App\Services\PracticeService::class)->values($talent),
            ($isOwner ?? false) || auth('brand')->check(),
            $blocks->contains(fn ($b) => $b->blockType?->key === 'software_stack'),
        );
    @endphp
    @if ($practiceFacts !== [])
        <section class="blk"><div class="pv-sh"><h2 class="h2">{{ __('About the practice') }}</h2></div>
            <dl class="fx-facts" aria-label="{{ __('About the practice') }}">
                @foreach ($practiceFacts as [$label, $html])
                    <div class="fx-fact"><dt>{{ $label }}</dt><dd>{!! $html !!}</dd></div>
                @endforeach
            </dl>
        </section>
    @endif
@endisset
