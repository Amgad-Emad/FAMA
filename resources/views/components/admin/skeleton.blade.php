@props(['rows' => 4])

{{-- Pulsing placeholder rows shown while a list loads (pair with x-show="loading"). --}}
<div {{ $attributes->merge(['class' => 'space-y-2']) }} aria-hidden="true">
    @for ($i = 0; $i < $rows; $i++)
        <div class="animate-pulse rounded-lg border border-line bg-surface px-4 py-3.5">
            <div class="h-4 rounded bg-elevated" style="width: {{ 30 + ($i * 13) % 40 }}%"></div>
            <div class="mt-2 h-3 rounded bg-elevated" style="width: {{ 15 + ($i * 7) % 20 }}%"></div>
        </div>
    @endfor
</div>
