{{--
    Project page (fama-ui `project.html`, 16–31 Oct 2026) — `/{slug}/work/{project}`.

    An editorial case study (project-page.css). The prototype lays the page out
    from the blocks the talent wrote; a Fama project is a fixed set of fields, so
    the page always takes the prototype's layout for a project WITHOUT chapter
    headings (its "short project"): the cover with the title on the picture, the
    opening line beside the facts, the pictures as a magazine spread (the first
    wide, the rest in pairs), the full story, the Results numbers, then the team
    (Credits), the "Made by" band, the next project full width and more work.

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
      credits      → the owner, then confirmed + off-platform people only;
                     pending credits appear nowhere (ADR-AA)

    Behaviour: famaProjectFollow (Follow) + famaProjectPage (Share, the
    full-screen pictures, the phone's follow bar) in resources/js/ui/projects.js;
    motion = public/fama-ui/js/project-motion.js on GSAP (the prototype's).
--}}
@php
    $initials = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $two = fn (int $n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT);

    // "Desert Light — 2026" → name + year tag; "Nomad — Autumn launch" → sub-line (App\Support\ProjectTitle).
    $title = \App\Support\ProjectTitle::forProject($project);
    $title['main'] = $title['name'];
    $year = $title['year'];
    $h1Size = mb_strlen($title['main']) > 28 ? 'clamp(44px,6vw,88px)' : (mb_strlen($title['main']) > 18 ? 'clamp(52px,7.5vw,108px)' : '');

    // The cover: the chosen cover, else the first picture of the work (then left out of the spread).
    $items = $project->orderedMedia();
    $coverUrl = $project->cover_image_url;
    if (! $coverUrl) {
        $standIn = $items->first(fn ($item) => ! str_starts_with((string) $item->mime_type, 'video/'));
        if ($standIn) {
            $coverUrl = $standIn->getUrl();
            $items = $items->reject(fn ($item) => $item->is($standIn))->values();
        }
    }
    $pictures = $items->map(fn ($item) => [
        'url' => $item->getUrl(),
        'video' => str_starts_with((string) $item->mime_type, 'video/'),
        'mime' => $item->mime_type,
    ])->values();
    $total = $pictures->count();

    // A result shows by what was typed: + or − is a change, a trailing % a percent.
    $resultType = function (string $value): string {
        if (preg_match('/^\s*[+\-−]/u', $value)) {
            return 'change';
        }

        return preg_match('/%\s*$/', $value) ? 'percent' : 'number';
    };
    $results = collect($project->resultRows());

    $skills = $project->talentTypes;
    $client = filled($project->client_name) ? $project->client_name : '';
    $kicker = collect([$client, $skills->first()?->name])->filter()->implode(' · ');

    // The team: confirmed on-platform people are co-owners; off-platform names are listed.
    $credits = $project->credits->filter(fn ($credit) => $credit->status->isPublic())->values();
    $coOwnerIds = $project->coOwnerCredits()->pluck('talent_id')->map('intval')->all();
    $coNames = $credits->filter(fn ($c) => $c->talent && in_array((int) $c->talent_id, $coOwnerIds, true))
        ->map(fn ($c) => $c->talent->display_name)->filter()->unique()->values();
    $withLine = $coNames->count() < 3
        ? $coNames->implode(' '.__('and').' ')
        : $coNames->slice(0, -1)->implode(', ').' '.__('and').' '.$coNames->last();

    // The opening line is one line of serif: the summary as plain text.
    $lead = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(
        str_replace(['</p>', '<br>', '<br/>', '<br />'], ' ', (string) \App\Support\Html\BriefSanitizer::clean((string) ($project->summary ?? '')))
    ), ENT_QUOTES | ENT_HTML5)));
    $body = $project->body ? \App\Support\Html\BriefSanitizer::clean((string) $project->body) : '';
    $hasBody = trim(strip_tags($body)) !== '';
    // Plain text written before the editor was rich keeps its line breaks.
    if ($hasBody && ! preg_match('/<(p|div|br|li)\b/i', $body)) {
        $body = nl2br(trim($body));
    }

    $profileUrl = url($talent->slug);
    // ", lead stylist · with Omar Saleh" after the owner's name on the cover.
    $byTail = (filled($project->role) ? ', '.mb_strtolower($project->role) : '').($withLine !== '' ? ' · '.__('with :names', ['names' => $withLine]) : '');
    $primary = $talent->talentTypes->sortByDesc(fn ($t) => (int) $t->pivot->is_primary)->first();
    $ownerLine = collect([$primary?->name, $talent->base_city])->filter()->implode(' · ');
    $liveHost = $project->url ? rtrim(preg_replace('#^https?://(www\.)?#', '', $project->url), '/') : '';

    $facts = collect([
        $client !== '' ? [__('Client'), e($client)] : null,
        filled($project->role) ? [__('Role'), e($project->role)] : null,
        $skills->isNotEmpty() ? [__('Skills'), e($skills->pluck('name')->implode(' · ')).($project->specialities->isNotEmpty() ? '<small>'.e($project->specialities->pluck('name')->implode(', ')).'</small>' : '')] : null,
        $project->url ? [__('See it live'), '<a href="'.e($project->url).'" target="_blank" rel="noopener">'.e($liveHost).' ↗</a>'] : null,
    ])->filter()->values();

    $next = $more->first();
    $others = $more->slice(1, 3)->values();
    $avatar = fn ($who) => $who?->avatar_url ? '<img src="'.e($who->avatar_url).'" alt="">' : e($initials($who?->display_name));
@endphp
<x-fama.layout :title="$project->title" :css="['project-page']">
    <x-slot:head>
        <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;1,6..72,400&display=swap">
    </x-slot:head>
    <x-slot:styles>
        <link rel="stylesheet" href="{{ \App\Support\FamaAsset::url('fama-ui/css/pages/project.css') }}">
    </x-slot:styles>

    <main class="ed is-short" id="page"
          x-data="famaProjectFollow({ id: {{ (int) $talent->id }}, following: @js($following) })">
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
                    <div class="by"><span class="av" aria-hidden="true">{!! $avatar($talent) !!}</span><span><a href="{{ $profileUrl }}"><b>{{ $talent->display_name }}</b></a>{{ $byTail }}</span></div>
                </div>
                <div class="acts">
                    <button class="btn btn-2" type="button" @click="share()">{{ __('Share') }}</button>
                    @if ($isOwner)
                        <a class="btn" href="{{ route('talent.projects.edit', $project) }}">{{ __('Edit project') }}</a>
                    @else
                        <button class="btn btn-2" type="button" @click="toggle()" :aria-pressed="following.toString()" aria-pressed="{{ $following ? 'true' : 'false' }}"
                                x-text="following ? @js(__('Following')) : @js(__('Follow'))">{{ $following ? __('Following') : __('Follow') }}</button>
                    @endif
                </div>
            </div>
        </header>

        <div class="grid"><article class="story short">
            <div class="s-intro">
                @if ($lead !== '')<p class="lead">{{ $lead }}</p>@else<span></span>@endif
                <dl class="s-facts">
                    @foreach ($facts as [$label, $value])
                        <div><dt>{{ $label }}</dt><dd>{!! $value !!}</dd></div>
                    @endforeach
                </dl>
            </div>

            @if ($total)
                <div class="sgrid">
                    @foreach ($pictures as $k => $pic)
                        @php $n = $two($k + 1).' / '.$two($total); @endphp
                        @if ($pic['video'])
                            <figure class="{{ $k === 0 ? 'big' : '' }} is-video" data-nolb>
                                <video class="ph-img" controls playsinline preload="metadata" aria-label="{{ __(':title, picture :i of :n', ['title' => $project->title, 'i' => $k + 1, 'n' => $total]) }}"><source src="{{ $pic['url'] }}" type="{{ $pic['mime'] }}"></video>
                                <figcaption><b>{{ $n }}</b></figcaption>
                            </figure>
                        @else
                            <figure class="{{ $k === 0 ? 'big' : '' }}">
                                <div class="ph-img" role="img" style="background-image:url('{{ $pic['url'] }}')" aria-label="{{ __(':title, picture :i of :n', ['title' => $project->title, 'i' => $k + 1, 'n' => $total]) }}"></div>
                                <figcaption><b>{{ $n }}</b></figcaption>
                            </figure>
                        @endif
                    @endforeach
                </div>
            @endif

            @if ($hasBody)
                <div class="txt">{!! $body !!}</div>
            @endif

            @if ($results->isNotEmpty())
                <div class="res">
                    @foreach ($results as $result)
                        @php
                            $kind = $resultType($result['value']);
                            $down = $kind === 'change' && preg_match('/^\s*[\-−]/u', $result['value']);
                            $pct = max(0, min(100, (float) preg_replace('/[^0-9.\-]/', '', $result['value'])));
                        @endphp
                        <div class="{{ $kind === 'change' ? ($down ? 'up down' : 'up') : '' }}" data-kind="{{ $kind }}">
                            <b>@if ($kind === 'change')<span class="ar"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg></span>@endif{{ $result['value'] }}</b>
                            <span>{{ $result['label'] }}</span>
                            @if ($kind === 'percent')<div class="bar" aria-hidden="true"><i style="width:{{ $pct }}%"></i></div>@endif
                            @if ($kind === 'change' && $result['compare'] !== '')<small>{{ $result['compare'] }}</small>@endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- The team: the owner first, then everyone confirmed. Only when there's someone besides the owner. --}}
            @if ($credits->isNotEmpty())
                <section class="ch" id="credits"><div class="ch-h"><span>{{ __('THE TEAM') }}</span><h2>{{ __('Credits') }}</h2></div>
                    <ul class="cred">
                        <li><span class="av">{!! $avatar($talent) !!}</span><b><a href="{{ $profileUrl }}">{{ $talent->display_name }}</a></b><span class="r">{{ $project->role }}</span><span class="st">{{ __('Owner') }}</span></li>
                        @foreach ($credits as $credit)
                            <li>
                                <span class="av">@if ($credit->talent){!! $avatar($credit->talent) !!}@else{{ $initials($credit->name) }}@endif</span>
                                <b>@if ($credit->talent)<a href="{{ url($credit->talent->slug) }}">{{ $credit->talent->display_name }}</a>@else{{ $credit->name }}@endif</b>
                                {{-- Role leads: skill says what someone can do, role says what they did HERE. --}}
                                <span class="r">{{ collect([$credit->role, $credit->talentType?->name])->filter()->implode(' · ') }}</span>
                                @if ($credit->talent && in_array((int) $credit->talent_id, $coOwnerIds, true))<span class="st">{{ __('Owner') }}</span>@else<span></span>@endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </article></div>

        <div class="grid"><section class="hire" id="hire" aria-labelledby="hireT" x-ref="hire">
            <span class="av hav" aria-hidden="true">{!! $avatar($talent) !!}</span>
            <div class="hwho"><p class="hk">{{ $isOwner && (int) auth('talent')->id() === (int) $talent->id ? __('Made by you') : __('Made by') }}</p><h2 id="hireT"><a href="{{ $profileUrl }}">{{ $talent->display_name }}</a></h2>
                <p class="hm">@if ($ownerLine !== ''){{ $ownerLine }}@endif @if ($talent->is_available)<span class="open"><i></i>{{ __('Open for work') }}</span>@endif</p></div>
            <div class="hacts">
                @if ($isOwner)
                    <a class="btn" href="{{ route('talent.projects.edit', $project) }}">{{ __('Edit project') }}</a>
                @else
                    <button class="btn btn-2" type="button" @click="toggle()" :aria-pressed="following.toString()" x-text="following ? @js(__('Following')) : @js(__('Follow'))">{{ $following ? __('Following') : __('Follow') }}</button>
                    <a class="btn" href="{{ $profileUrl }}">{{ __('View profile') }}</a>
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

        {{-- On a phone, the way to follow comes with you once the cover is gone, until the "Made by" band is on screen. --}}
        @unless ($isOwner)
            <div class="hirebar" :class="{ show: barShown }" :aria-hidden="(!barShown).toString()" aria-hidden="true">
                <span class="av" aria-hidden="true">{!! $avatar($talent) !!}</span>
                <span class="hb-t"><b>{{ $talent->display_name }}</b>@if ($talent->is_available)<span class="open"><i></i>{{ __('Open for work') }}</span>@endif</span>
                <button class="btn" type="button" :tabindex="barShown ? 0 : -1" tabindex="-1" @click="toggle()" x-text="following ? @js(__('Following')) : @js(__('Follow'))">{{ $following ? __('Following') : __('Follow') }}</button>
            </div>
        @endunless

        {{-- Full-screen pictures: every photo on the page, in order. --}}
        <dialog class="lb" x-ref="lb" aria-label="{{ __('Pictures') }}" @keydown.arrow-right="show(index + 1)" @keydown.arrow-left="show(index - 1)">
            <div class="lb-top"><span class="lb-n" x-text="counter"></span><button class="lb-x" type="button" @click="$refs.lb.close()" aria-label="{{ __('Close') }}">✕</button></div>
            <button class="lb-arr lb-prev" type="button" @click="show(index - 1)" aria-label="{{ __('Previous picture') }}">‹</button>
            <div class="lb-stage" @pointerdown="x0 = $event.clientX" @pointerup="swipe($event)"><div class="ph-img" role="img" :style="current ? { backgroundImage: current.bg } : {}" :aria-label="current ? current.cap : ''"></div></div>
            <button class="lb-arr lb-next" type="button" @click="show(index + 1)" aria-label="{{ __('Next picture') }}">›</button>
            <p class="lb-cap"></p>
        </dialog>
    </div>
    </main>

    <x-slot:scripts>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" defer></script>
        <script src="{{ \App\Support\FamaAsset::url('fama-ui/js/project-motion.js') }}" defer></script>
    </x-slot:scripts>
</x-fama.layout>
