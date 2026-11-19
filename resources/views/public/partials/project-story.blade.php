{{--
    A project's story as the public page draws it (ADR-AN) — the prototype's
    project.html renderers (R.*), fed by App\Support\ProjectStory, which already
    decided the layout. Params: $pieces, $title (project title).
    Behaviour (before/after slider, look tags, video player, viewer):
    famaProjectPage in resources/js/ui/projects.js; motion: project-motion.js.
--}}
@php
    $two = fn (int $n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
    $kindOf = fn (string $v) => preg_match('/^\s*[+\-−]/u', $v) ? 'change' : (preg_match('/%\s*$/', $v) ? 'percent' : 'number');
@endphp
@foreach ($pieces as $p)
    @switch($p['k'])
        @case('open')
            <section class="ch">
            @break
        @case('close')
            </section>
            @break
        @case('chapter')
            <div class="ch-h"><span>{{ $p['n'] }}</span><h2>{{ $p['title'] }}</h2></div>
            @break
        @case('txt')
            <p class="txt">{!! nl2br(e($p['text']), false) !!}</p>
            @break
        @case('quote')
            <blockquote class="quote">{{ $p['text'] }}@if ($p['by'] !== '')<cite>{{ $p['by'] }}</cite>@endif</blockquote>
            @break
        @case('figure')
            @include('public.partials.project-fig', ['fig' => $p['fig'], 'cls' => $p['cls']])
            @break
        @case('pair')
            <div class="pair">@foreach ($p['figs'] as $fig)@include('public.partials.project-fig', ['fig' => $fig, 'cls' => ''])@endforeach</div>
            @break
        @case('spread')
            <div class="spread">@include('public.partials.project-fig', ['fig' => $p['fig'], 'cls' => ''])<div class="side"><p class="txt">{!! nl2br(e($p['text']), false) !!}</p></div></div>
            @break
        @case('cases')
            <div class="cases" data-cases data-start="{{ $p['start'] }}"><div class="cs-stage">
                @if ($p['chapter'])
                    <div class="cs-intro"><span class="cs-k">{{ $p['chapter']['k'] }}</span>@if ($p['chapter']['intro'] !== '')<p class="txt">{{ $p['chapter']['intro'] }}</p>@endif</div>
                @endif
                <h2 class="cs-title">{{ $p['title'] }}</h2>
                <span class="cs-count" aria-hidden="true"><b>{{ $two($p['start']) }}</b> / {{ $two($p['total']) }}</span>
                <div class="cs-col">@foreach ($p['figs'] as $fig)@include('public.partials.project-fig', ['fig' => $fig, 'cls' => ''])@endforeach</div>
                <ol class="cs-list" aria-hidden="true">@foreach ($p['figs'] as $k => $fig)<li @if ($k === 0) class="on" @endif>{{ $fig['cap'] ?: __('Picture').' '.$two($p['start'] + $k) }}</li>@endforeach</ol>
            </div></div>
            @break
        @case('ba')
            <figure class="ba" data-nolb><div class="ba-box" style="--p:50%">
                <div class="ph-img ba-after" style="background-image:url('{{ $p['after'] }}')"></div>
                <div class="ph-img ba-before real" style="background-image:url('{{ $p['before'] }}')"></div>
                <span class="ba-tag l">{{ $p['lb'] }}</span><span class="ba-tag r">{{ $p['la'] }}</span>
                <div class="ba-line" role="slider" tabindex="0" aria-label="{{ __('Compare :a with :b', ['a' => $p['lb'], 'b' => $p['la']]) }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="50"><span class="ba-knob" aria-hidden="true">‹ ›</span></div>
            </div><figcaption><b>{{ $p['lb'] }} / {{ $p['la'] }}</b>{{ $p['cap'] }}</figcaption></figure>
            @break
        @case('look')
            <div class="look"><figure class="look-img" data-nolb><div class="ph-img" style="background-image:url('{{ $p['url'] }}')"></div>
                @foreach ($p['items'] as $k => $x)<button type="button" class="dot" style="left:{{ $x['x'] }}%;top:{{ $x['y'] }}%" data-tag="{{ $k + 1 }}" aria-label="{{ $k + 1 }}. {{ $x['n'] }}">{{ $k + 1 }}</button>@endforeach
            </figure>
                <div class="look-list"><small>{{ mb_strtoupper(($p['title'] !== '' ? $p['title'].' · ' : '').__('What’s in it')) }}</small><ol>
                    @foreach ($p['items'] as $k => $x)<li data-tag="{{ $k + 1 }}"><b>{{ $x['n'] }}</b><span>{{ $x['d'] }}</span></li>@endforeach
                </ol></div></div>
            @break
        @case('video')
            <figure class="video" data-video data-nolb>
                <button type="button" class="vposter" data-player="{{ $p['player'] }}" data-href="{{ $p['url'] }}" aria-label="{{ __('Play the video') }}{{ $p['cap'] !== '' ? ': '.$p['cap'] : '' }}">
                    <span class="ph-img" @if ($p['poster']) style="background-image:url('{{ $p['poster'] }}')" @endif></span>
                    <span class="vplay" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 5.5v13l11-6.5z"/></svg></span>
                </button>
                <figcaption><b>{{ __('Film') }}</b>{{ collect([$p['cap'], $p['platform']])->filter()->implode(' · ') }}</figcaption>
            </figure>
            @break
        @case('embeds')
            <div class="embeds" style="--n:{{ count($p['posts']) }}"><p class="emb-h"><small>{{ __('ALSO POSTED') }}</small>{{ __('Where the work went out') }}</p>
                @foreach ($p['posts'] as $post)
                    <figure class="emb" data-nolb><a href="{{ $post['url'] }}" target="_blank" rel="noopener" class="emb-card"><span class="emb-top"><span class="av">{{ $post['initials'] }}</span><span><b>{{ $post['who'] }}</b><i>{{ $post['platform'] === 'Instagram' ? __('Reel').' · ' : '' }}{{ $post['platform'] }}</i></span></span><span class="ph-img" @if ($post['poster']) style="background-image:url('{{ $post['poster'] }}')" @endif></span><span class="emb-play" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 5.5v13l11-6.5z"/></svg></span></a>
                        <figcaption><a href="{{ $post['url'] }}" target="_blank" rel="noopener">{{ __('View on :p', ['p' => $post['platform']]) }} ↗</a></figcaption></figure>
                @endforeach
            </div>
            @break
        @case('results')
            <div class="res">
                @foreach ($p['rows'] as $row)
                    @php
                        $kind = $kindOf($row['value']);
                        $down = $kind === 'change' && preg_match('/^\s*[\-−]/u', $row['value']);
                        $pct = max(0, min(100, (float) preg_replace('/[^0-9.\-]/', '', $row['value'])));
                    @endphp
                    <div class="{{ $kind === 'change' ? ($down ? 'up down' : 'up') : '' }}" data-kind="{{ $kind }}">
                        <b>@if ($kind === 'change')<span class="ar"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg></span>@endif{{ $row['value'] }}</b>
                        <span>{{ $row['label'] }}</span>
                        @if ($kind === 'percent')<div class="bar" aria-hidden="true"><i style="width:{{ $pct }}%"></i></div>@endif
                        @if ($kind === 'change' && $row['compare'] !== '')<small>{{ $row['compare'] }}</small>@endif
                    </div>
                @endforeach
            </div>
            @break
    @endswitch
@endforeach
