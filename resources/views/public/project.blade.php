<x-public-layout :title="$project->title">
    <article class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <a href="{{ url($talent->slug) }}" class="font-mono text-xs uppercase tracking-[0.18em] text-accent-ink hover:underline">
            ← {{ $talent->display_name }}
        </a>

        {{-- CLIENT is hidden when empty (ADR-AA) — no "personal project"
             placeholder and no empty label. A test, a personal shoot, an
             editorial nobody commissioned: the absence says it.
             The DATE is deliberately NOT rendered anywhere on this page. It is
             stored for ordering and recency questions only — a strong project
             from three years ago should not be discounted for its age, and a
             date in the corner is exactly how that discounting happens. --}}
        <header class="mt-6">
            @if (filled($project->client_name))
                <x-ui.eyebrow>{{ $project->client_name }}</x-ui.eyebrow>
            @endif
            <h1 @class(['font-display text-4xl leading-tight text-ink sm:text-5xl', 'mt-2' => filled($project->client_name)])>{{ $project->title }}</h1>
            @if ($project->role)
                <p class="mt-2 text-muted">{{ $project->role }}</p>
            @endif
        </header>

        {{-- THE WORK (ADR-AA). Ordered by the person who logged the project, so
             the first item leads — chosen, not defaulted. Falls back to the
             single cover for projects logged before the gallery existed. --}}
        @php $work = $project->orderedMedia(); @endphp

        @if ($work->isNotEmpty())
            <div class="mt-8 space-y-3">
                @foreach ($work as $index => $item)
                    @if (str_starts_with((string) $item->mime_type, 'video/'))
                        <video controls preload="metadata" class="w-full rounded-lg border border-line bg-elevated"
                               @if ($index > 0) loading="lazy" @endif>
                            <source src="{{ $item->getUrl() }}" type="{{ $item->mime_type }}">
                        </video>
                    @else
                        <img src="{{ $item->getUrl() }}"
                             alt="{{ $item->getCustomProperty('alt') ?: $project->title }}"
                             @if ($index > 0) loading="lazy" @endif
                             class="w-full rounded-lg border border-line object-cover">
                    @endif
                @endforeach
            </div>
        @elseif ($project->cover_image_url)
            <img src="{{ $project->cover_image_url }}" alt="{{ $project->title }}" class="mt-8 aspect-video w-full rounded-lg border border-line object-cover">
        @else
            <div class="mt-8 aspect-video w-full rounded-lg border border-line" style="background:linear-gradient(135deg, var(--accent-weak), var(--gold-weak));"></div>
        @endif

        @if ($project->summary)
            {{-- Rich text (sanitized on save; re-cleaned here, defence in depth). --}}
            <div class="prose-rich mt-8 font-display text-xl leading-relaxed text-ink">{!! \App\Support\Html\BriefSanitizer::clean((string) $project->summary) !!}</div>
        @endif

        @if (! empty($project->results))
            <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3">
                @foreach ($project->results as $label => $value)
                    <x-ui.stat :label="is_string($label) ? $label : ''" :value="is_scalar($value) ? $value : json_encode($value)" />
                @endforeach
            </div>
        @endif

        @if ($project->body)
            <div class="prose-rich mt-8 leading-relaxed text-ink">{!! \App\Support\Html\BriefSanitizer::clean((string) $project->body) !!}</div>
        @endif

        {{-- COLLABORATORS (ADR-AA). Only credits cleared for display: accepted
             by the person, or off-platform ones that never needed confirming.
             A pending credit appears NOWHERE — that is the whole safeguard. --}}
        @php $credits = $project->credits->filter(fn ($credit) => $credit->status->isPublic()); @endphp

        @if ($credits->isNotEmpty())
            <section class="mt-10 border-t border-line pt-8">
                <h2 class="font-mono text-[11px] uppercase tracking-[0.14em] text-subtle">{{ __('Collaborators') }}</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($credits as $credit)
                        <li class="flex items-baseline gap-2">
                            {{-- Role leads: skill says what someone can do, role
                                 says what they did HERE. --}}
                            <span class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ $credit->role }}</span>
                            @if ($credit->talent)
                                <a href="{{ url($credit->talent->slug) }}" class="text-ink underline-offset-4 hover:underline">
                                    {{ $credit->talent->display_name }}
                                </a>
                            @else
                                <span class="text-ink">{{ $credit->name }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($project->url)
            <a href="{{ $project->url }}" target="_blank" rel="noopener" class="mt-8 inline-block">
                <x-ui.button variant="outline">{{ __('View the project') }}</x-ui.button>
            </a>
        @endif
    </article>
</x-public-layout>
