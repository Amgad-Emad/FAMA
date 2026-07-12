@php
    // Projects are skill-scoped (ADR-Q): a projects block shows the projects belonging
    // to its tab's skill (or the un-scoped ones for a universal projects block).
    $projects = $talent->projects->where('talent_type_id', $block->talent_type_id);
@endphp

@if ($projects->isNotEmpty())
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('Selected work')">
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($projects as $project)
                <a href="{{ route('talent.work', ['slug' => $talent->slug, 'project' => $project->id]) }}" class="block">
                    <x-ui.card :pad="false" class="group h-full overflow-hidden transition hover:shadow-e2">
                        <div class="aspect-video">
                            @if ($project->cover_image_url)
                                <img src="{{ $project->cover_image_url }}" alt="{{ $project->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <div class="h-full w-full" style="background:linear-gradient(135deg, var(--accent-weak), var(--gold-weak));"></div>
                            @endif
                        </div>
                        <div class="p-5">
                            <h3 class="font-display text-xl text-ink group-hover:text-accent-ink">{{ $project->title }}</h3>
                            <div class="mt-1 font-mono text-[11px] uppercase tracking-wider text-subtle">
                                {{ $project->client_name }}{{ $project->year ? ' · '.$project->year : '' }}
                            </div>
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
