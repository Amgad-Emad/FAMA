@props([
    'pad' => true,
    'elevated' => false,
])

<div {{ $attributes->merge([
    'class' => 'rounded-lg border border-line bg-surface '.($elevated ? 'shadow-e2' : 'shadow-e1').' '.($pad ? 'p-6' : ''),
]) }}>
    {{ $slot }}
</div>
