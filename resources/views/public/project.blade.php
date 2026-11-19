{{--
    Project page (fama-ui `project.html`, 16–31 Oct 2026) — `/{slug}/work/{project}`.

    An editorial case study (project-page.css). The prototype lays the page out
    from the blocks the talent wrote; a Fama project is a fixed set of fields, so
    the page always takes the prototype's layout for a project WITHOUT chapter
    headings (its "short project"): the cover with the title on the picture, the
    opening line beside the facts, the pictures as a magazine spread (the first
    wide, the rest in pairs), the full story, the Results numbers, then the team
    ("Made by"), the next project full width and more work.

      cover        → the project's `cover`; with none, the first picture of the
                     work stands in (and is not repeated in the spread)
      title        → "Name — 2026": the name is the headline, a year joins the
                     line above it, any other second part sits under the name
      kicker       → the year tag (from the title, else the project's year) ·
                     client · first skill
      opening line → the summary, as plain text in the serif
      facts        → Client (hidden when empty — ADR-AA), Role, Skills +
                     specialities, See it live
      story        → the body (sanitised rich text)
      results      → number / percent bar / rise-or-fall, by what was typed
      team         → 13 Nov 2026 review: a shared project reads the same for
                     everyone on it — the owner, then confirmed + off-platform
                     people, listed as EQUALS: "By A and B" (stacked avatars) on
                     the cover and the "Made by" band (name + role, side by side)
                     at the end. The separate Credits list is folded into that
                     band. Pending credits appear nowhere (ADR-AA). No hiring on
                     Fama yet: no "Work with", no "Open for work", no phone bar.
      pictures     → numbers (01 / 09) show only in the full-screen viewer
                     (12 Nov 2026 review)

    Behaviour: famaProjectLike (♡ Like, ADR-AJ) + famaProjectPage (Share, the
    full-screen pictures) in resources/js/ui/projects.js;
    motion = public/fama-ui/js/project-motion.js on GSAP (the prototype's).
--}}
@php
    $initials = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

    // "Desert Light — 2026" → name + year tag; "Nomad — Autumn launch" → sub-line (App\Support\ProjectTitle).
    $title = \App\Support\ProjectTitle::forProject($project);
    $title['main'] = $title['name'];
    $year = $title['year'];
    $h1Size = mb_strlen($title['main']) > 28 ? 'clamp(44px,6vw,88px)' : (mb_strlen($title['main']) > 18 ? 'clamp(52px,7.5vw,108px)' : '');

    // The story, laid out from the blocks by the prototype's rules (ADR-AN).
    $story = \App\Support\ProjectStory::for($project);
    // The cover: the chosen cover, else the story's first picture stands in.
    $coverUrl = $project->lead_image_url;

    $skills = $project->talentTypes;
    // Work under an NDA (prototype Settings): the page says "Confidential client" and hides the link.
    $nda = (bool) $project->client_confidential;
    $client = filled($project->client_name) ? $project->client_name : '';
    $kicker = collect([$nda ? __('Confidential client') : $client, $skills->first()?->name])->filter()->implode(' · ');

    // The team (13 Nov 2026 review): one list of equals — the owner, then every confirmed
    // on-platform person and every off-platform name. Pending credits appear nowhere.
    $credits = $project->credits
        ->filter(fn ($credit) => $credit->status->isPublic())
        ->reject(fn ($credit) => $credit->talent && (int) $credit->talent_id === (int) $talent->id)
        ->unique(fn ($credit) => $credit->talent_id ? 'talent:'.$credit->talent_id : 'name:'.mb_strtolower((string) $credit->name))
        ->values();
    $team = collect([[
        'name' => $talent->display_name,
        'role' => (string) $project->role,
        'url' => url($talent->slug),
        'who' => $talent,
    ]])->concat($credits->map(fn ($credit) => [
        'name' => $credit->talent ? $credit->talent->display_name : (string) $credit->name,
        'role' => (string) ($credit->role ?: $credit->talentType?->name),
        'url' => $credit->talent?->slug ? url($credit->talent->slug) : null,
        'who' => $credit->talent,
    ]))->filter(fn ($member) => filled($member['name']))->values();
    $memberName = fn (array $m) => $m['url'] ? '<a href="'.e($m['url']).'"><b>'.e($m['name']).'</b></a>' : '<b>'.e($m['name']).'</b>';
    $names = $team->map($memberName);
    // "By A", "By A and B", "By A, B and C".
    $byLine = $names->count() > 1
        ? $names->slice(0, -1)->implode(', ').' '.e(__('and')).' '.$names->last()
        : $names->first();

    // One person alone: "By Lina Haddad, photographer".
    $byTail = $team->count() === 1 && filled($project->role) ? ', '.mb_strtolower($project->role) : '';
    $liveHost = $project->url ? rtrim(preg_replace('#^https?://(www\.)?#', '', $project->url), '/') : '';
    $lock = '<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="16" height="11" x="4" y="11" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>';

    $facts = collect([
        $nda ? [__('Client'), '<span class="nda">'.$lock.e(__('Confidential')).'</span><span class="nda-note">'.e(__('The client asked not to be named')).'</span>']
            : ($client !== '' ? [__('Client'), e($client)] : null),
        filled($project->role) ? [__('Role'), e($project->role)] : null,
        $skills->isNotEmpty() ? [__('Skills'), e($skills->pluck('name')->implode(' · ')).($project->specialities->isNotEmpty() ? '<small>'.e($project->specialities->pluck('name')->implode(', ')).'</small>' : '')] : null,
        $project->url && ! $nda ? [__('See it live'), '<a href="'.e($project->url).'" target="_blank" rel="noopener">'.e($liveHost).' ↗</a>'] : null,
    ])->filter()->values();

    $next = $more->first();
    $others = $more->slice(1, 3)->values();
    $avatar = fn ($who) => $who?->avatar_url ? '<img src="'.e($who->avatar_url).'" alt="">' : e($initials($who?->display_name));
    $memberAvatar = fn (array $m) => $m['who'] ? $avatar($m['who']) : e($initials($m['name']));
@endphp
<x-fama.layout :title="$project->title" :css="['project-page']">
    <x-slot:head>
        <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;1,6..72,400&display=swap">
    </x-slot:head>
    <x-slot:styles>
        <link rel="stylesheet" href="{{ \App\Support\FamaAsset::url('fama-ui/css/pages/project.css') }}">
    </x-slot:styles>

    <main class="ed{{ $story['short'] ? ' is-short' : '' }}" id="page"
          x-data="famaProjectLike({ id: {{ (int) $project->id }}, liked: @js($liked) })">
    <div class="ed-in" x-data="famaProjectPage({ copied: @js(__('Link copied')) })">
        @if ($preview)
            <div class="st-prev"><span><b>{{ __('Preview') }}</b> · {{ __('This is how it looks on your profile.') }}</span><a class="btn btn-2" href="{{ route('talent.projects.edit', $project) }}">{{ __('Back to editing') }}</a></div>
        @endif

        <header class="cover">
            <div class="ph-img" @if ($coverUrl) style="background-image:url('{{ $coverUrl }}')" @endif role="img" aria-label="{{ __(':title, the cover picture', ['title' => $project->title]) }}"></div>
            <div class="cap grid">
                <div>
                    <p class="kick">@if ($year)<span class="ytag">{{ $year }}</span>@endif{{ $kicker }}</p>
                    <h1 @if ($h1Size) style="font-size:{{ $h1Size }}" @endif>{{ $title['main'] }}@if ($title['sub'] !== '')<span class="vh"> — </span><span class="h1-sub">{{ $title['sub'] }}</span>@endif @if ($year)<span class="vh">, {{ $year }}</span>@endif</h1>
                    <div class="by"><span class="avs" aria-hidden="true">@foreach ($team->take(4) as $member)<span class="av">{!! $memberAvatar($member) !!}</span>@endforeach</span><span>{{ __('By') }} {!! $byLine !!}{{ $byTail }}</span></div>
                </div>
                <div class="acts">
                    {{-- Share + Like for everyone; owners also get Edit (prototype). --}}
                    <button class="btn btn-2" type="button" @click="share()">{{ __('Share') }}</button>
                    <button class="btn btn-2" type="button" @click="toggle()" :aria-pressed="liked.toString()" aria-pressed="{{ $liked ? 'true' : 'false' }}"
                                x-text="(liked ? '♥ ' : '♡ ') + @js(__('Like'))">{{ $liked ? '♥' : '♡' }} {{ __('Like') }}</button>
                    @if ($isOwner)
                        <a class="btn" href="{{ route('talent.projects.edit', $project) }}">{{ __('Edit project') }}</a>
                    @endif
                </div>
            </div>
        </header>

        @unless ($story['short'])
            <div class="grid"><dl class="colo" style="--n:{{ $facts->count() }}">
                @foreach ($facts as [$label, $value])
                    <div><dt>{{ $label }}</dt><dd>{!! $value !!}</dd></div>
                @endforeach
            </dl></div>
        @endunless

        <div class="grid"><article class="story{{ $story['short'] ? ' short' : '' }}">
            @if ($story['short'])
                <div class="s-intro">
                    @if ($story['lead'] !== '')<p class="lead">{{ $story['lead'] }}</p>@else<span></span>@endif
                    <dl class="s-facts">
                        @foreach ($facts as [$label, $value])
                            @continue($label === __('See it live'))
                            <div><dt>{{ $label }}</dt><dd>{!! $value !!}</dd></div>
                        @endforeach
                    </dl>
                </div>
                @if ($story['photos'])
                    <div class="sgrid">
                        @foreach ($story['photos'] as $k => $fig)
                            @include('public.partials.project-fig', ['fig' => $fig, 'cls' => $k === 0 ? 'big' : '', 'title' => $project->title])
                        @endforeach
                    </div>
                @endif
            @elseif ($story['lead'] !== '')
                <p class="lead">{{ $story['lead'] }}</p>
            @endif

            @include('public.partials.project-story', ['pieces' => $story['pieces'], 'title' => $project->title])
        </article></div>

        {{-- "Made by": everyone on the project as equals, name + role side by side (13 Nov 2026 review). --}}
        <div class="grid"><section class="hire team" id="hire" aria-labelledby="hireT" x-ref="hire">
            <div class="hwho"><h2 class="hk" id="hireT">{{ __('Made by') }}</h2>
                <ul class="tm">
                    @foreach ($team as $member)
                        <li><span class="av" aria-hidden="true">{!! $memberAvatar($member) !!}</span><span>{!! $memberName($member) !!}@if (filled($member['role']))<span class="r">{{ $member['role'] }}</span>@endif</span></li>
                    @endforeach
                </ul></div>
            <div class="hacts">
                @if ($isOwner)
                    <a class="btn btn-2" href="{{ route('talent.work', ['slug' => $talent->slug, 'project' => $project, 'visitor' => 1]) }}">{{ __('See it as a visitor') }}</a>
                    <a class="btn" href="{{ route('talent.projects.edit', $project) }}">{{ __('Edit project') }}</a>
                @else
                    <button class="btn btn-2" type="button" @click="share()">{{ __('Share') }}</button>
<button class="btn" type="button" @click="toggle()" :aria-pressed="liked.toString()" aria-pressed="{{ $liked ? 'true' : 'false' }}"
                                x-text="(liked ? '♥ ' : '♡ ') + @js(__('Like'))">{{ $liked ? '♥' : '♡' }} {{ __('Like') }}</button>
                @endif
            </div>
        </section></div>

        @if ($next)
            @php $nt = \App\Support\ProjectTitle::forProject($next); $nt['main'] = $nt['name']; @endphp
            <a class="next" href="{{ route('talent.work', ['slug' => $talent->slug, 'project' => $next]) }}"><div class="ph-img" @if ($next->lead_image_url) style="background-image:url('{{ $next->lead_image_url }}')" @endif></div><div class="cap grid"><small>{{ __('Next project') }}</small><b>{{ $nt['main'] }}</b></div></a>
        @endif
        @if ($others->isNotEmpty())
            <div class="grid"><div class="more">
                @foreach ($others as $other)
                    @php $ot = \App\Support\ProjectTitle::forProject($other); $ot['main'] = $ot['name']; $oy = $ot['year']; @endphp
                    <a href="{{ route('talent.work', ['slug' => $talent->slug, 'project' => $other]) }}"><div class="ph-img" style="position:relative;@if ($other->lead_image_url)background-image:url('{{ $other->lead_image_url }}')@endif">@if ($oy)<span class="mtag">{{ $oy }}</span>@endif</div><b>{{ $ot['main'] }}</b><span>{{ $other->client_name ?: $other->role }}</span></a>
                @endforeach
            </div></div>
        @endif

        {{-- Full-screen pictures: every photo on the page, in order. --}}
        <dialog class="lb" x-ref="lb" aria-label="{{ __('Pictures') }}" @keydown.arrow-right="show(index + 1)" @keydown.arrow-left="show(index - 1)">
            <div class="lb-top"><span class="lb-n" x-text="counter"></span><button class="lb-x" type="button" @click="$refs.lb.close()" aria-label="{{ __('Close') }}">✕</button></div>
            <button class="lb-arr lb-prev" type="button" @click="show(index - 1)" aria-label="{{ __('Previous picture') }}">‹</button>
            <div class="lb-stage" @pointerdown="x0 = $event.clientX" @pointerup="swipe($event)"><div class="ph-img" role="img" :style="current ? { backgroundImage: current.bg } : {}" :aria-label="current ? current.cap : ''"></div></div>
            <button class="lb-arr lb-next" type="button" @click="show(index + 1)" aria-label="{{ __('Next picture') }}">›</button>
            <p class="lb-cap" x-text="current ? current.cap : ''"></p>
        </dialog>
    </div>
    </main>

    <x-slot:scripts>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" defer></script>
        <script src="{{ \App\Support\FamaAsset::url('fama-ui/js/project-motion.js') }}" defer></script>
    </x-slot:scripts>
</x-fama.layout>
