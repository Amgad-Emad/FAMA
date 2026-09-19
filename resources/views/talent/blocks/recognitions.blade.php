{{--
    Recognitions (ADR-Z) — peer vouches, talent → talent.

    Deliberately styled to read DIFFERENTLY from reviews: no star ratings (a peer
    is not scoring a booking), a quote-led layout, and the RELATIONSHIP given
    prominence, because "assisted me for two years" is what makes a vouch
    readable as something other than praise. If recognitions and reviews rendered
    the same way, the page would be one wall of praise and neither would get read.
--}}
@php $recognitions = $talent->recognitions->where('is_approved', true); @endphp

@if ($recognitions->isNotEmpty())
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('Vouched for by peers')">
        <div class="space-y-px overflow-hidden rounded-lg border border-line bg-line">
            @foreach ($recognitions as $recognition)
                <figure class="bg-surface p-6 sm:p-7">
                    <blockquote class="font-display text-xl leading-snug text-ink sm:text-2xl">
                        {{ $recognition->body }}
                    </blockquote>
                    <figcaption class="mt-5 flex flex-wrap items-center gap-x-3 gap-y-1">
                        @if ($recognition->author)
                            <a href="{{ url($recognition->author->slug) }}"
                               class="text-sm font-medium text-ink underline-offset-4 hover:underline">
                                {{ $recognition->author->display_name }}
                            </a>
                        @endif
                        <span class="font-mono text-[11px] uppercase tracking-wider text-subtle">
                            {{ $recognition->relationship }}
                        </span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </x-ui.section>
@endif
