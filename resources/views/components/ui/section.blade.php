@props([
    'title' => null,
    'eyebrow' => null,
])

<section {{ $attributes->merge(['class' => 'scroll-mt-24']) }}>
    @if ($eyebrow || $title)
        <header class="mb-5 flex items-end justify-between gap-4">
            <div>
                @if ($eyebrow)
                    <x-ui.eyebrow>{{ $eyebrow }}</x-ui.eyebrow>
                @endif
                @if ($title)
                    <h2 class="mt-1 font-display text-2xl text-ink">{{ $title }}</h2>
                @endif
            </div>
            @isset($actions)
                <div class="shrink-0">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    {{ $slot }}
</section>
