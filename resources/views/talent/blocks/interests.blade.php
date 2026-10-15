{{--
    Willing to explore (ADR-Z) — skill + optional speciality (filterable), the
    note as colour below it (fama-ui .wtel).
--}}
@php $interests = $talent->interests; @endphp
<x-profile.block :block="$block" :empty="$interests->isEmpty()" :is-owner="$isOwner ?? false">
    <ul class="wtel">
        @foreach ($interests as $interest)
            <li><span class="wtel-s">{{ $interest->talentType?->name }}@if ($interest->speciality) <span>· {{ $interest->speciality->name }}</span>@endif</span>
                @if ($interest->note)<span class="wtel-n" dir="auto">{{ $interest->note }}</span>@endif</li>
        @endforeach
    </ul>
</x-profile.block>
