{{--
    Willing to explore (ADR-Z) — the one section that ASKS for something instead
    of proving something.

    Structured, not prose: each entry is a skill + optional kind of work, which
    is what makes it filterable. The optional note is colour, never the thing
    being matched, so it is set below the structured pair rather than replacing it.
--}}
@php $interests = $talent->interests; @endphp

@if ($interests->isNotEmpty())
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('Open to')">
        <ul class="grid gap-3 sm:grid-cols-2">
            @foreach ($interests as $interest)
                <li>
                    <x-ui.card class="h-full">
                        <div class="flex items-start gap-3">
                            <x-skill-icon :icon="$interest->talentType?->icon" class="mt-0.5 h-4 w-4 shrink-0 text-accent" />
                            <div class="min-w-0">
                                <div class="text-ink">
                                    {{ $interest->talentType?->name }}
                                    @if ($interest->speciality)
                                        <span class="text-subtle">·</span>
                                        <span class="text-muted">{{ $interest->speciality->name }}</span>
                                    @endif
                                </div>
                                @if ($interest->note)
                                    <p class="mt-1 text-sm leading-relaxed text-muted">{{ $interest->note }}</p>
                                @endif
                            </div>
                        </div>
                    </x-ui.card>
                </li>
            @endforeach
        </ul>
    </x-ui.section>
@endif
