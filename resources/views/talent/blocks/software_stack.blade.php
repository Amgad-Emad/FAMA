@php $stack = $talent->softwareStack; @endphp

@if ($stack->isNotEmpty())
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('Tools')">
        <div class="flex flex-wrap gap-2">
            @foreach ($stack as $tool)
                <span class="inline-flex items-center gap-2 rounded-pill border border-line bg-surface py-1 pe-3 ps-1 text-sm">
                    <x-ui.avatar :src="$tool->icon_url" :name="$tool->software_name" size="sm" class="!h-7 !w-7 !text-xs" />
                    <span class="text-ink">{{ $tool->software_name }}</span>
                    <span class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __(ucfirst($tool->proficiency)) }}</span>
                </span>
            @endforeach
        </div>
    </x-ui.section>
@endif
