@php $reviews = $talent->reviews; @endphp

@if ($reviews->isNotEmpty())
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('What clients say')">
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($reviews as $review)
                <x-ui.card>
                    <div class="flex items-center gap-0.5 text-gold" aria-label="{{ $review->rating }}/5">
                        @for ($i = 1; $i <= 5; $i++)
                            <span>{{ $i <= $review->rating ? '★' : '☆' }}</span>
                        @endfor
                    </div>
                    <p class="mt-3 text-ink">&ldquo;{{ $review->body }}&rdquo;</p>
                    <div class="mt-4 flex items-center gap-3">
                        <x-ui.avatar :src="$review->reviewer_avatar_url" :name="$review->reviewer_name" size="sm" />
                        <div>
                            <div class="text-sm font-medium text-ink">{{ $review->reviewer_name }}</div>
                            <div class="font-mono text-[11px] uppercase tracking-wider text-subtle">
                                {{ $review->reviewer_role }}{{ $review->reviewer_company ? ' · '.$review->reviewer_company : '' }}
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    </x-ui.section>
@endif
