@php $digitals = $talent->digitals; @endphp

@if ($digitals->isNotEmpty())
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('Digitals')">
        <div class="grid grid-cols-3 gap-3 sm:grid-cols-6">
            @foreach ($digitals as $digital)
                <figure class="aspect-[3/4] overflow-hidden rounded-md border border-line">
                    @if ($digital->thumbnail_url)
                        <img src="{{ $digital->thumbnail_url }}" alt="{{ $digital->shot_type }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center font-mono text-[10px] uppercase tracking-wider text-subtle"
                             style="background:linear-gradient(135deg, var(--accent-weak), var(--gold-weak));">{{ $digital->shot_type }}</div>
                    @endif
                </figure>
            @endforeach
        </div>
    </x-ui.section>
@endif
