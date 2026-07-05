@php $reels = $talent->showreels; @endphp

@if ($reels->isNotEmpty())
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('Showreel')">
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($reels as $reel)
                <a href="{{ $reel->video_url }}" target="_blank" rel="noopener"
                   class="group block overflow-hidden rounded-lg border border-line bg-surface transition hover:shadow-e2">
                    <div class="relative aspect-video">
                        @if ($reel->thumbnail_url)
                            <img src="{{ $reel->thumbnail_url }}" alt="{{ $reel->title }}" class="h-full w-full object-cover">
                        @else
                            <div class="h-full w-full" style="background:linear-gradient(135deg, var(--accent-weak), var(--gold-weak));"></div>
                        @endif
                        <span class="absolute inset-0 grid place-items-center">
                            <span class="grid h-14 w-14 place-items-center rounded-pill bg-primary text-on-primary transition group-hover:scale-110">
                                <svg class="h-5 w-5 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </span>
                        </span>
                    </div>
                    <div class="p-4">
                        <div class="text-sm font-medium text-ink">{{ $reel->title ?: __('Showreel') }}</div>
                        <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ $reel->platform }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </x-ui.section>
@endif
