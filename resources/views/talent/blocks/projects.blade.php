{{--
    Projects (fama-ui .projs — each project its own card, the name and client
    always under the picture so a brand scanning the grid reads the clients
    without hovering; 24 Oct 2026). Skill-tagged (ADR-Q + ADR-X) plus accepted
    credits on other people's work (ADR-AA), filed under the credited skill.
    Year tag on the picture: a year typed into the title ("Name — 2026"), else
    the recorded year (App\Support\ProjectTitle::forProject — 2026-11-04,
    co-founder prototype supersedes ADR-AA's "date never shown").
--}}
@php $projects = $talent->portfolioProjects($block->talent_type_id); @endphp
<x-profile.block :block="$block" :empty="$projects->isEmpty()" :is-owner="$isOwner ?? false">
    <div class="projs">
        @foreach ($projects as $project)
            @php
                // A credited project belongs to someone else — link to THEIR copy.
                $owner = $project->talent_id === $talent->id ? $talent : $project->talent;
                $credit = $project->pivot ?? null;
                $meta = $credit
                    ? trim(($credit->role ? $credit->role.' · ' : '').__('with :who', ['who' => $owner?->display_name]))
                    : (string) $project->client_name;
                $title = \App\Support\ProjectTitle::forProject($project);
            @endphp
            <a class="proj" @if ($credit) id="cr-{{ $project->id }}" @endif href="{{ route('talent.work', ['slug' => $owner?->slug ?? $talent->slug, 'project' => $project->id]) }}">
                @if ($title['year'] !== '')<span class="ptag">{{ $title['year'] }}</span>@endif
                @if ($project->lead_image_url)
                    <div class="ph cover" style="background-image:url('{{ $project->lead_image_url }}')" role="img" aria-label="{{ $project->title }}"></div>
                @else
                    <div class="ph" aria-hidden="true"><svg class="icon"><use href="#i-image"/></svg></div>
                @endif
                <b>{{ $title['sub'] !== '' ? $title['name'].' — '.$title['sub'] : $title['name'] }}</b>@if (filled($meta))<span class="meta">{{ $meta }}</span>@endif
            </a>
        @endforeach
    </div>
</x-profile.block>
