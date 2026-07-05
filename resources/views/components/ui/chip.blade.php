@props(['tone' => 'neutral'])

@php
    $tones = [
        'neutral' => 'bg-surface border-line text-muted',
        'accent' => 'bg-accent-weak border-transparent text-accent-ink',
        'gold' => 'bg-gold-weak border-transparent text-gold',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 rounded-pill border px-3 py-1 text-xs font-medium '.($tones[$tone] ?? $tones['neutral']),
]) }}>{{ $slot }}</span>
