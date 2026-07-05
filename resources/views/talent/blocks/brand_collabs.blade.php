@php $collabs = $talent->brandCollabs; @endphp

@if ($collabs->isNotEmpty())
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('Brands')">
        <div class="flex flex-wrap gap-3">
            @foreach ($collabs as $collab)
                <x-ui.card class="flex items-center gap-3" :pad="false">
                    <div class="flex items-center gap-3 p-4">
                        <x-ui.avatar :src="$collab->brand_logo_url" :name="$collab->brand_name" size="sm" />
                        <div>
                            <div class="text-sm font-medium text-ink">{{ $collab->brand_name }}</div>
                            @if ($collab->project_title)
                                <div class="text-xs text-muted">{{ $collab->project_title }}{{ $collab->year ? ' · '.$collab->year : '' }}</div>
                            @endif
                        </div>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    </x-ui.section>
@endif
