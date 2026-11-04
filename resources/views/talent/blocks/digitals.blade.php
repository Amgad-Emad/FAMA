{{-- Digitals (fama-ui .dig): a model's digitals are pictures a casting opens, not a
     fact (24 Oct 2026) — their own block, a row of four, each opening the viewer
     with its shot and the date it was taken. --}}
@php
    $digitals = $talent->digitals->sortBy('position')->values();
    $taken = $digitals->pluck('captured_at')->filter()->max();
    $lb = 'digitals-'.$block->id;
@endphp
<x-profile.block :block="$block" :empty="$digitals->isEmpty()" :is-owner="$isOwner ?? false">
    <p class="meta" style="margin-top:var(--s2)">{{ __('Unretouched, natural light.') }}@if ($taken) {{ __('Taken :date', ['date' => \Illuminate\Support\Carbon::parse($taken)->translatedFormat('j M Y')]) }}.@endif</p>
    <div class="dig">
        @foreach ($digitals as $digital)
            @php
                $label = __(ucfirst((string) $digital->shot_type));
                $cap = $digital->captured_at
                    ? $label.' · '.__('Taken :date', ['date' => \Illuminate\Support\Carbon::parse($digital->captured_at)->translatedFormat('j M Y')])
                    : $label;
            @endphp
            <button type="button" data-lb="{{ $lb }}" data-src="{{ $digital->media_url ?: $digital->thumbnail_url }}" data-cap="{{ $cap }}" aria-label="{{ $label }}, {{ __('open') }}">
                @if ($digital->thumbnail_url)
                    <div class="ph cover" style="background-image:url('{{ $digital->thumbnail_url }}')" aria-hidden="true"></div>
                @else
                    <div class="ph" aria-hidden="true"><svg class="icon"><use href="#i-image"/></svg></div>
                @endif
                <b>{{ $label }}</b>
            </button>
        @endforeach
    </div>
</x-profile.block>
