@php $services = $talent->services; @endphp

@if ($services->isNotEmpty())
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('Rate card')">
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($services as $service)
                <x-ui.card class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="font-medium text-ink">{{ $service->name }}</h3>
                        @if ($service->description)
                            <p class="mt-1 text-sm text-muted">{{ $service->description }}</p>
                        @endif
                    </div>
                    @if ($service->price)
                        <div class="shrink-0 text-end">
                            <div class="font-display text-xl text-ink">{{ number_format((float) $service->price, 0) }} {{ $service->currency }}</div>
                            <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">/ {{ __($service->price_unit) }}</div>
                        </div>
                    @endif
                </x-ui.card>
            @endforeach
        </div>
    </x-ui.section>
@endif
