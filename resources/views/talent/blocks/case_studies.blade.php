@php $studies = $talent->caseStudies; @endphp

@if ($studies->isNotEmpty())
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('Selected work')">
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($studies as $study)
                <x-ui.card :pad="false" class="overflow-hidden">
                    <div class="aspect-[16/9]">
                        @if ($study->cover_image_url)
                            <img src="{{ $study->cover_image_url }}" alt="{{ $study->title }}" class="h-full w-full object-cover">
                        @else
                            <div class="h-full w-full" style="background:linear-gradient(135deg, var(--accent-weak), var(--gold-weak));"></div>
                        @endif
                    </div>
                    <div class="p-5">
                        <h3 class="font-display text-xl text-ink">{{ $study->title }}</h3>
                        <div class="mt-1 font-mono text-[11px] uppercase tracking-wider text-subtle">
                            {{ $study->client_name }}{{ $study->year ? ' · '.$study->year : '' }}
                        </div>
                        @if ($study->summary)
                            <p class="mt-3 text-sm text-muted">{{ $study->summary }}</p>
                        @endif
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    </x-ui.section>
@endif
