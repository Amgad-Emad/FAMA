@props(['status' => 'available'])

@php
    $status = (string) $status; // accepts an Availability state object or a plain string
    $map = [
        'available' => ['dot' => 'bg-success', 'text' => 'text-success', 'bg' => 'bg-success-weak', 'label' => __('Available')],
        'booked' => ['dot' => 'bg-warn', 'text' => 'text-warn', 'bg' => 'bg-warn-weak', 'label' => __('Booked')],
        'unavailable' => ['dot' => 'bg-subtle', 'text' => 'text-muted', 'bg' => 'bg-surface', 'label' => __('Unavailable')],
    ];
    $s = $map[$status] ?? $map['available'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 rounded-pill px-3 py-1 text-xs font-medium '.$s['bg'].' '.$s['text']]) }}>
    <span class="h-2 w-2 rounded-pill {{ $s['dot'] }}" @if ($status === 'available') style="animation: pulseDot 2.4s var(--ease-out) infinite" @endif></span>
    {{ $slot->isEmpty() ? $s['label'] : $slot }}
</span>
