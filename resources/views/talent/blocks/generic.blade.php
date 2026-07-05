{{-- Fallback renderer for inline/unmapped block types. --}}
<x-ui.section :title="$block->title ?: $block->blockType->name">
    @if (! empty($block->content))
        <x-ui.card>
            <div class="space-y-2 text-ink">
                @foreach ((array) $block->content as $line)
                    <p class="text-sm">{{ is_scalar($line) ? $line : json_encode($line, JSON_UNESCAPED_UNICODE) }}</p>
                @endforeach
            </div>
        </x-ui.card>
    @else
        <x-ui.card class="text-sm text-subtle">{{ __('Coming soon.') }}</x-ui.card>
    @endif
</x-ui.section>
