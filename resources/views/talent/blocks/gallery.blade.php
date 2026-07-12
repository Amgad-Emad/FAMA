@php
    // Gallery items are block-scoped (ADR-Q): each tab's gallery shows its own body
    // of work. Fall back to a talent's un-linked items only for a universal gallery.
    $items = $talent->portfolioItems->where('block_id', $block->id);
@endphp

@if ($items->isNotEmpty())
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('Portfolio')">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($items as $item)
                <figure class="group relative aspect-[4/5] overflow-hidden rounded-md border border-line bg-surface">
                    @if ($item->thumbnail_url)
                        <img src="{{ $item->thumbnail_url }}" alt="{{ $item->caption }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    @else
                        <div class="h-full w-full" style="background:linear-gradient(135deg, var(--accent-weak), var(--gold-weak));"></div>
                    @endif
                    @if ($item->caption)
                        <figcaption class="absolute inset-x-0 bottom-0 p-2 text-xs text-white" style="background:linear-gradient(to top, rgba(0,0,0,.55), transparent);">{{ $item->caption }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </x-ui.section>
@endif
