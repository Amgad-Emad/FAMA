{{-- Comp card — the fama-ui "About the practice" facts (fields.css .fx-facts).
     Superseded by the Modelling practice fields (ADR-AG): once a profile's practice
     holds height / measurements / sizes, those render under "About the practice"
     and this block stays silent, so the page never shows two heights. --}}
@php
    $practice = (array) ($talent->practice ?? []);
    $card = collect(['height', 'measure', 'sizes'])->contains(fn ($k) => \App\Support\PracticeFields::filled($k, $practice[$k] ?? null))
        ? null
        : $talent->compCard;
    $facts = $card ? array_filter([
        __('Height') => $card->height_cm ? $card->height_cm.' cm' : null,
        __('Bust') => $card->bust_cm ? $card->bust_cm.' cm' : null,
        __('Waist') => $card->waist_cm ? $card->waist_cm.' cm' : null,
        __('Hips') => $card->hips_cm ? $card->hips_cm.' cm' : null,
        __('Hair') => $card->hair_color,
        __('Eyes') => $card->eye_color,
        __('Shoe') => $card->shoe_size,
        __('Dress') => $card->dress_size,
    ]) : [];
@endphp
<x-profile.block :block="$block" :empty="empty($facts)" :is-owner="$isOwner ?? false">
    <dl class="fx-facts" aria-label="{{ $block->title ?: $block->blockType->name }}">
        @foreach ($facts as $label => $value)
            <div class="fx-fact"><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
        @endforeach
    </dl>
</x-profile.block>
