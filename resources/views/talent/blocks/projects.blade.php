{{--
    Projects (fama-ui .projs — a tight grid; the title shows on hover/focus).
    Skill-tagged (ADR-Q + ADR-X) plus accepted credits on other people's work
    (ADR-AA), filed under the credited skill. The date is never shown (ADR-AA).
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
            @endphp
            <a class="proj" @if ($credit) id="cr-{{ $project->id }}" @endif href="{{ route('talent.work', ['slug' => $owner?->slug ?? $talent->slug, 'project' => $project->id]) }}">
                @if ($project->lead_image_url)
                    <div class="ph cover" style="background-image:url('{{ $project->lead_image_url }}')" role="img" aria-label="{{ $project->title }}"></div>
                @else
                    <div class="ph" aria-hidden="true"><svg class="icon"><use href="#i-image"/></svg></div>
                @endif
                <b>{{ $project->title }}</b>@if (filled($meta))<span class="meta">{{ $meta }}</span>@endif
            </a>
        @endforeach
    </div>
</x-profile.block>
