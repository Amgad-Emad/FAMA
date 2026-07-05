@props(['label' => '', 'value' => ''])

<div {{ $attributes->merge(['class' => 'rounded-md border border-line bg-surface px-4 py-3']) }}>
    <div class="font-display text-2xl leading-none text-ink">{{ $value }}</div>
    <div class="mt-1 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ $label }}</div>
</div>
