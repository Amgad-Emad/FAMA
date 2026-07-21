@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-lg border border-line bg-accent-weak px-4 py-3 text-sm font-medium text-accent-ink']) }} role="status">
        {{ $status }}
    </div>
@endif
