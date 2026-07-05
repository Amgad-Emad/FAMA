@php $press = $talent->pressFeatures; @endphp

@if ($press->isNotEmpty())
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('Press')">
        <div class="grid gap-2">
            @foreach ($press as $feature)
                <a href="{{ $feature->url }}" target="_blank" rel="noopener"
                   class="flex items-center justify-between gap-4 rounded-md border border-line bg-surface px-4 py-3 transition hover:border-line-strong">
                    <div>
                        <div class="text-sm font-medium text-ink">{{ $feature->title }}</div>
                        <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">
                            {{ $feature->publication }}{{ $feature->published_date ? ' · '.$feature->published_date->isoFormat('MMM YYYY') : '' }}
                        </div>
                    </div>
                    <svg class="h-4 w-4 shrink-0 text-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M7 17 17 7M7 7h10v10"/></svg>
                </a>
            @endforeach
        </div>
    </x-ui.section>
@endif
