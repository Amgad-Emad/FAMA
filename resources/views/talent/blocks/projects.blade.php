@php
    // Projects are skill-tagged (ADR-Q + ADR-X): a projects block shows every project
    // tagged with its tab's skill — a project tagged with two skills appears in both
    // tabs. A universal projects block shows the projects that carry no skill.
    // Since ADR-AA this ALSO includes work this talent was credited on and accepted,
    // filed under the skill they were credited in.
    $projects = $talent->portfolioProjects($block->talent_type_id);
@endphp

@if ($projects->isNotEmpty())
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('Selected work')">
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($projects as $project)
                @php
                    // A credited project belongs to someone else — link to THEIR
                    // copy of it, which is the canonical page for that work.
                    $owner = $project->talent_id === $talent->id ? $talent : $project->talent;
                    $credit = $project->pivot ?? null;
                @endphp
                <a href="{{ route('talent.work', ['slug' => $owner?->slug ?? $talent->slug, 'project' => $project->id]) }}" class="block">
                    <x-ui.card :pad="false" class="group h-full overflow-hidden transition hover:shadow-e2">
                        <div class="aspect-video">
                            @if ($project->lead_image_url)
                                <img src="{{ $project->lead_image_url }}" alt="{{ $project->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <div class="h-full w-full" style="background:linear-gradient(135deg, var(--accent-weak), var(--gold-weak));"></div>
                            @endif
                        </div>
                        <div class="p-5">
                            <h3 class="font-display text-xl text-ink group-hover:text-accent-ink">{{ $project->title }}</h3>
                            {{-- Client is hidden when empty, and the DATE is never
                                 rendered — it is stored for ordering only (ADR-AA). --}}
                            @if (filled($project->client_name) || $credit)
                                <div class="mt-1 font-mono text-[11px] uppercase tracking-wider text-subtle">
                                    @if (filled($project->client_name)){{ $project->client_name }}@endif
                                    @if ($credit)
                                        @if (filled($project->client_name)) · @endif{{ $credit->role }}
                                    @endif
                                </div>
                            @endif
                            @if ($project->summary)
                                <p class="mt-3 text-sm text-muted">{{ $project->summary }}</p>
                            @endif
                        </div>
                    </x-ui.card>
                </a>
            @endforeach
        </div>
    </x-ui.section>
@endif
