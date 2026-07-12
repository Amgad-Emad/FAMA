@props([
    'src' => null,
    'name' => '',
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'h-10 w-10 text-sm',
        'md' => 'h-16 w-16 text-lg',
        'lg' => 'h-24 w-24 text-2xl',
        'xl' => 'h-28 w-28 text-3xl',
        '2xl' => 'h-32 w-32 text-4xl sm:h-36 sm:w-36',
    ];
    $dim = $sizes[$size] ?? $sizes['md'];

    $initials = collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center overflow-hidden rounded-pill border border-line-strong bg-elevated font-display '.$dim]) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $name }}" class="h-full w-full object-cover">
    @else
        <span class="flex h-full w-full items-center justify-center text-accent-ink"
              style="background: linear-gradient(135deg, var(--accent-weak), var(--gold-weak));">
            {{ $initials !== '' ? $initials : '—' }}
        </span>
    @endif
</span>
