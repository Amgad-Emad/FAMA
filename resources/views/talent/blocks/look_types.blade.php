@php $looks = $talent->lookTypes; @endphp

@if ($looks->isNotEmpty())
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('Looks')">
        <div class="flex flex-wrap gap-2">
            @foreach ($looks as $look)
                <x-ui.chip tone="neutral">{{ $look->name }}</x-ui.chip>
            @endforeach
        </div>
    </x-ui.section>
@endif
