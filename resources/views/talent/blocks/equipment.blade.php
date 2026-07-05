@php $equipment = $talent->equipment; @endphp

@if ($equipment->isNotEmpty())
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('Kit')">
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach ($equipment as $item)
                <div class="flex items-center justify-between gap-3 rounded-md border border-line bg-surface px-4 py-2.5">
                    <div class="text-sm text-ink">
                        {{ trim($item->brand.' '.$item->model) }}
                        <span class="text-muted">— {{ $item->name }}</span>
                    </div>
                    <x-ui.chip tone="neutral">{{ __(ucfirst($item->category)) }}</x-ui.chip>
                </div>
            @endforeach
        </div>
    </x-ui.section>
@endif
