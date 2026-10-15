{{--
    Project page (fama-ui `project.html`) — `/{slug}/work/{project}`.

    One narrow column that tells the project as a story: the lead frame, the
    summary, the rest of the work in pairs with the full story between, then
    Results and Credits cards and the owner band with more of their work.

    Rules kept from ADR-AA:
      - CLIENT is hidden when empty — no "personal project" placeholder, no label.
      - The DATE is never rendered anywhere on this page (recorded for ordering).
      - Only credits cleared for display are listed: accepted by the person, or
        off-platform names that never needed confirming. Pending appear nowhere.
--}}
@php
    $initials = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

    // The frames, in the owner's order: the chosen cover leads when there is one.
    $frames = collect();
    if ($project->hasMedia('cover')) {
        $frames->push(['url' => $project->cover_image_url, 'video' => false, 'mime' => null]);
    }
    foreach ($project->orderedMedia() as $item) {
        $frames->push(['url' => $item->getUrl(), 'video' => str_starts_with((string) $item->mime_type, 'video/'), 'mime' => $item->mime_type]);
    }
    $count = max(1, $frames->count());
    $rest = $frames->slice(1)->values()->chunk(2);

    // A result shows by what was typed: + or − is a change, a trailing % a percent.
    $resultType = function (string $value): string {
        if (preg_match('/^\s*[+\-−]/u', $value)) {
            return 'change';
        }

        return preg_match('/%\s*$/', $value) ? 'percent' : 'number';
    };
    $results = collect($project->resultRows());
    // Owners line: "Lina Haddad", "Lina Haddad and Omar Saleh", "A, B and C".
    $ownerList = $project->owners()->pluck('display_name')->filter()->values();
    $ownerNames = $ownerList->count() < 3
        ? $ownerList->implode(' '.__('and').' ')
        : $ownerList->slice(0, -1)->implode(', ').' '.__('and').' '.$ownerList->last();
    $credits = $project->credits->filter(fn ($credit) => $credit->status->isPublic());
    $primary = $talent->talentTypes->sortByDesc(fn ($t) => (int) $t->pivot->is_primary)->first();
    $ownerLine = collect([$primary?->name, $talent->base_city])->filter()->implode(' · ');
    $summary = $project->summary ? \App\Support\Html\BriefSanitizer::clean((string) $project->summary) : '';
    $body = $project->body ? \App\Support\Html\BriefSanitizer::clean((string) $project->body) : '';
@endphp
<x-fama.layout :title="$project->title">
    <x-slot:styles>
        <link rel="stylesheet" href="{{ \App\Support\FamaAsset::url('fama-ui/css/pages/project.css') }}">
        <style>
            /* App additions: real media inside the prototype's frames. */
            .st-img video.m{display:block;object-fit:cover;background:#14110F}
            .av img{width:100%;height:100%;border-radius:inherit;object-fit:cover;display:block}
            .st-text p{margin:0 0 var(--s4)}.st-text p:last-child{margin-bottom:0}
            .st-text a{color:var(--purple-dk)}
            .st-text ul,.st-text ol{padding-inline-start:var(--s5)}
        </style>
    </x-slot:styles>

    <main class="wrap main" id="page">
        @if ($preview)
            <div class="st-prev"><span><b>{{ __('Preview') }}</b> · {{ __('This is how it looks on your profile.') }}</span><a class="btn btn-2" href="{{ route('talent.projects.edit', $project) }}">{{ __('Back to editing') }}</a></div>
        @endif

        <article class="st">
            <header class="st-top">
                <a class="st-who" href="{{ url($talent->slug) }}">
                    <span class="av">@if ($talent->avatar_url)<img src="{{ $talent->avatar_url }}" alt="">@else{{ $initials($talent->display_name) }}@endif</span>
                    <span><b>{{ $project->title }}</b><small>{{ $ownerNames }}@if (filled($project->client_name)) · {{ $project->client_name }}@endif</small></span>
                </a>
                @if ($isOwner)
                    <a class="btn btn-2" href="{{ route('talent.projects.edit', $project) }}"><svg class="icon" aria-hidden="true"><use href="#i-pen"/></svg>{{ __('Edit project') }}</a>
                @else
                    <button class="btn" type="button"
                            x-data="famaProjectFollow({ id: {{ (int) $talent->id }}, following: @js($following) })"
                            @click="toggle()" :class="{ 'btn-2': following }" :aria-pressed="following.toString()"
                            x-text="following ? @js(__('Following')) : @js(__('Follow'))">{{ $following ? __('Following') : __('Follow') }}</button>
                @endif
            </header>

            <ul class="st-meta">
                @if (filled($project->role))
                    <li><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>{{ $project->role }}</li>
                @endif
                @if (filled($project->client_name))
                    <li><svg class="icon" aria-hidden="true"><use href="#i-brief"/></svg>{{ $project->client_name }}</li>
                @endif
                @if ($project->url)
                    <li><a href="{{ $project->url }}" rel="noopener" target="_blank"><svg class="icon" aria-hidden="true"><use href="#i-ext"/></svg>{{ __('See it live') }}</a></li>
                @endif
                <li><button type="button" x-data @click="navigator.clipboard?.writeText(location.href.split('?')[0]).catch(() => {}); window.famaToast(@js(__('Link copied')))"><svg class="icon" aria-hidden="true"><use href="#i-link"/></svg>{{ __('Copy link') }}</button></li>
            </ul>

            @if ($project->talentTypes->isNotEmpty() || $project->specialities->isNotEmpty())
                <ul class="st-tags">
                    @foreach ($project->talentTypes as $skill)<li class="sk">{{ $skill->name }}</li>@endforeach
                    @foreach ($project->specialities as $speciality)<li>{{ $speciality->name }}</li>@endforeach
                </ul>
            @endif

            <div class="st-body">
                {{-- The lead frame --}}
                @include('public.partials.project-frame', ['frame' => $frames->first(), 'index' => 0, 'count' => $count, 'title' => $project->title])

                @if ($summary !== '')
                    <div class="st-text">{!! $summary !!}</div>
                @endif

                @foreach ($rest as $pairIndex => $pair)
                    @php $start = 1 + $pairIndex * 2; @endphp
                    @if ($pair->count() > 1)
                        <div class="st-pair">
                            @foreach ($pair->values() as $offset => $frame)
                                @include('public.partials.project-frame', ['frame' => $frame, 'index' => $start + $offset, 'count' => $count, 'title' => $project->title])
                            @endforeach
                        </div>
                    @else
                        @include('public.partials.project-frame', ['frame' => $pair->first(), 'index' => $start, 'count' => $count, 'title' => $project->title])
                    @endif

                    {{-- The full story sits after the first run of pictures --}}
                    @if ($loop->first && $body !== '')
                        <div class="st-text">{!! $body !!}</div>
                    @endif
                @endforeach

                @if ($rest->isEmpty() && $body !== '')
                    <div class="st-text">{!! $body !!}</div>
                @endif
            </div>

            @if ($results->isNotEmpty())
                <section class="st-card"><h2>{{ __('Results') }}</h2>
                    <div class="results">
                        @foreach ($results as $result)
                            @php
                                $kind = $resultType($result['value']);
                                $down = $kind === 'change' && preg_match('/^\s*[\-−]/u', $result['value']);
                                $pct = max(0, min(100, (float) preg_replace('/[^0-9.\-]/', '', $result['value'])));
                            @endphp
                            <div class="r r-{{ $kind }}{{ $down ? ' down' : '' }}">
                                <b>@if ($kind === 'change')<i class="ar" aria-hidden="true"><svg class="icon"><use href="#i-trend"/></svg></i>@endif{{ $result['value'] }}</b>
                                <span>{{ $result['label'] }}</span>
                                @if ($kind === 'percent')<span class="bar" aria-hidden="true"><i style="width:{{ $pct }}%"></i></span>@endif
                                @if ($kind === 'change' && $result['compare'] !== '')<small>{{ $result['compare'] }}</small>@endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($credits->isNotEmpty())
                <section class="st-card"><h2>{{ __('Credits') }}</h2>
                    <ul class="people">
                        @foreach ($credits as $credit)
                            <li>
                                <span class="av">@if ($credit->talent?->avatar_url)<img src="{{ $credit->talent->avatar_url }}" alt="">@else{{ $initials($credit->displayName()) }}@endif</span>
                                <span>
                                    @if ($credit->talent)
                                        <a href="{{ url($credit->talent->slug) }}"><b>{{ $credit->talent->display_name }}</b></a>
                                    @else
                                        <b>{{ $credit->name }}</b>
                                    @endif
                                    {{-- Role leads: skill says what someone can do, role says what they did HERE. --}}
                                    <span class="meta">{{ collect([$credit->role, $credit->talentType?->name])->filter()->implode(' · ') }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </article>

        <section class="st-owner">
            <span class="av big">@if ($talent->avatar_url)<img src="{{ $talent->avatar_url }}" alt="">@else{{ $initials($talent->display_name) }}@endif</span>
            <b>{{ $talent->display_name }}</b>
            @if ($ownerLine !== '')<small>{{ $ownerLine }}</small>@endif
            <a class="btn" href="{{ url($talent->slug) }}">{{ __('View profile') }}</a>
            @if ($more->isNotEmpty())
                <div class="mgrid">
                    @foreach ($more as $other)
                        <a href="{{ route('talent.work', ['slug' => $talent->slug, 'project' => $other]) }}">
                            <span class="mi" @if ($other->lead_image_url) style="background-image:url('{{ $other->lead_image_url }}')" @endif></span>
                            <span class="mt">{{ $other->title }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </main>
</x-fama.layout>
