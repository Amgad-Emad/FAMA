{{-- One role's highlights (one per line) and the skills it used — FAMA_EXP hl()/sk(). --}}
@php
    $lines = collect(preg_split('/\n/', (string) $r['hl']))->map(fn ($l) => trim(preg_replace('/^[\s·\-•]+/u', '', $l)))->filter()->values();
    $used = collect($r['sk'])->map(fn ($id) => optional($talent->talentTypes->firstWhere('id', $id))->name)->filter()->implode(', ');
@endphp
@if ($lines->isNotEmpty())
    <ul class="xp-hl">@foreach ($lines as $line)<li>{{ $line }}</li>@endforeach</ul>
@endif
@if ($used !== '')
    <p class="xp-sk"><b>{{ __('Skills:') }}</b> {{ $used }}</p>
@endif
