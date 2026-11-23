{{--
    Public talent profile — GET /{slug} (fama-ui "preview.html" on real data).
    The hero answers a brand's questions in the order they ask them (24 Oct
    2026): who is this, what do they do and where, are they free, which crafts,
    in their words, who have they worked for, how much work is here, then what
    to do (Follow / Recognise / share) — beside the FEATURED PROJECT cover
    (FeaturedProjectService). Then the skill tabs (ADR-R: the active tab renders
    here, the rest load lazily through `profileTabs`), then what is true for
    every skill ONCE, after the tabs ("About {name}": the profile-wide sections
    + point of view). The owner sees the Preview strip (and can preview while
    Private); guests get the sign-in prompt for Follow / Recognise. Behaviour:
    `publicProfile` (resources/js/ui/profile.js) + `profileTabs` / `talentFollow`
    (dashboard.js); motion: public/fama-ui/js/profile-motion.js (GSAP).
--}}
@php
    $skills = $talent->talentTypes
        ->sortBy(fn ($t) => [(int) $t->pivot->position, (int) $t->id])
        ->values();
    $primaryType = $skills->first(); // the first skill opens first (no separate primary)
    $avatar = $talent->avatar_url;
    $name = $talent->display_name ?: $talent->slug;
    $initials = collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $place = collect([$talent->base_city, $talent->base_country])->filter()->implode(', ');

    $viewer = auth('talent')->user();
    $isOwner = $viewer && (int) $viewer->getKey() === (int) $talent->id;
    // A visitor with no account at all: Follow / Recognise ask them to sign in.
    $isGuest = ! auth('talent')->check() && ! auth('brand')->check();
    $isLive = (bool) $talent->is_published;

    // The featured project on the cover: the talent's pick (FeaturedProjectService).
    $owned = $talent->projects->sortBy('position')->values();
    $featured = app(\App\Services\FeaturedProjectService::class)->resolve($talent, $owned);

    // Reviews are off on the public page for now (features.reviews) —
    // Recognitions replace them. Their block + CTA come back with the flag.
    $reviewsOn = \App\Support\Features::enabled('reviews');

    // Pricing rate (ADR-N) — only when the whole rate is set AND the flag is on.
    $hasRate = \App\Support\Features::enabled('pricing')
        && filled($talent->rate_amount) && filled($talent->rate_unit) && filled($talent->rate_currency);
    $rateUnitLabels = ['project' => __('project'), 'day' => __('day'), 'hour' => __('hour')];
    $rateAmount = null;
    if ($hasRate) {
        $amount = (float) $talent->rate_amount;
        $rateAmount = number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2);
    }

    // Optional external link (an external portfolio/booking URL).
    $externalUrl = ($talent->booking_type === 'external' && filled($talent->booking_value)) ? $talent->booking_value : null;

    // Blocks (ADR-Q/R): universal (talent_type_id NULL) + one tab per skill that
    // has sections. `hero` is retired; reviews hide while the flag is off.
    // Projects always show while a scope has work (App\Support\ProfileSections).
    $blocksFor = fn ($typeId) => \App\Support\ProfileSections::forScope($talent, $typeId);
    $universalBlocks = $blocksFor(null);
    $tabSkills = $skills->filter(fn ($s) => $blocksFor($s->id)->isNotEmpty())->values();

    $activeSkill = $tabSkills->firstWhere('slug', request('skill')) ?? $tabSkills->first();
    $tabUrls = $tabSkills->mapWithKeys(fn ($s) => [$s->slug => route('talent.tab', ['slug' => $talent->slug, 'skill' => $s->slug])]);

    $socialLabels = [
        'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'x' => 'X',
        'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'behance' => 'Behance',
        'vimeo' => 'Vimeo', 'website' => __('Website'), 'other' => __('Link'),
    ];

    // "Worked with …" + the Brands number: the distinct brands the talent lists
    // (brand collabs — the app's Experience), first three named, the rest counted.
    $firms = $talent->brandCollabs->sortBy('position')
        ->map(fn ($c) => trim((string) $c->brand_name))->filter()->unique()->values();

    // The featured project's name and a year the talent typed into its title
    // ("Name — 2026"); the recorded date itself stays private (ADR-AA).
    $featuredTitle = $featured ? \App\Support\ProjectTitle::forProject($featured) : null;

    $strings = [
        'copied' => __('Link copied'), 'yourLink' => __('Your link'), 'shareTitle' => __(':name on Fama'),
        'shareOff' => __('Complete your profile to go Live. Then you can share your link.'),
        'ownNote' => __('This is your own profile. Visitors use this button.'),
    ];
    $blockVars = ['talent' => $talent, 'isOwner' => $isOwner];

    // Search + share (ADR-AO): who they are, what they do, where — then their own words.
    $profileUrl = route('talent.public', ['slug' => $talent->slug]);
    $skillNames = $skills->pluck('name')->implode(', ');
    $seoLine = trim($name.($skillNames !== '' ? ' — '.$skillNames : '').($place !== '' ? ' '.__('in :place', ['place' => $place]) : '').'.');
    $seo = \App\Support\Seo::make()
        ->title($name.($primaryType ? ' — '.$primaryType->name : ''))
        ->description($seoLine.' '.($talent->worldview ?: $talent->bio ?: __('See their projects and the people they made them with on Fama.')))
        ->canonical($profileUrl)
        ->image($featured?->lead_image_url ?: $avatar)
        ->type('profile')
        ->noindex(! $isLive || ! empty($staffPreview))
        ->schema([
            '@type' => 'ProfilePage',
            'url' => $profileUrl,
            'mainEntity' => [
                '@type' => 'Person',
                'name' => $name,
                'alternateName' => '@'.$talent->slug,
                'url' => $profileUrl,
                'image' => $avatar,
                'jobTitle' => $skillNames,
                'description' => $talent->worldview ?: $talent->bio,
                'address' => $place !== '' ? ['@type' => 'PostalAddress', 'addressLocality' => $talent->base_city, 'addressCountry' => $talent->base_country] : null,
                'sameAs' => $talent->socialHandles->pluck('url')->filter()->values()->all(),
                'knowsAbout' => $skills->pluck('name')->all(),
            ],
        ]);
@endphp

{{-- Staff preview (moderation's View panel): no visitor nav — no Log in / Feed chrome inside the admin. --}}
<x-fama.layout :title="$name" :seo="$seo" :css="['fields', 'exp']" :mode="! empty($staffPreview) ? 'bare' : null">
    <x-slot:styles>
        <link rel="stylesheet" href="{{ \App\Support\FamaAsset::url('fama-ui/css/pages/preview.css') }}">
    </x-slot:styles>

    @if (! empty($staffPreview))
        <x-slot:hero>
            <div class="pv-strip" id="strip"><div class="wrap"><span>{{ $isLive ? __('Staff preview — this is the Live profile, as visitors see it.') : __('Staff preview — this profile is private. Visitors can’t see it yet.') }}</span></div></div>
        </x-slot:hero>
    @elseif ($isOwner)
        <x-slot:hero>
            <div class="pv-strip" id="strip"><div class="wrap"><span>{{ $isLive ? __('This is how everyone sees your profile.') : __("Your profile is private. This is how people will see it once it's public.") }}</span><a class="btn" href="{{ route('talent.profile.edit') }}"><svg class="icon" aria-hidden="true"><use href="#i-pen"/></svg><span>{{ __('Edit profile') }}</span></a></div></div>
        </x-slot:hero>
    @endif

<main class="wrap" id="page"
      x-data="publicProfile({ strings: @js($strings), url: @js(url($talent->slug)), name: @js($name), own: {{ $isOwner ? 'true' : 'false' }}, guest: {{ $isGuest ? 'true' : 'false' }}, live: {{ $isLive ? 'true' : 'false' }} })"
      @click="onClick($event)">

  <section class="ct-top">
    <div class="ct-id">
      <span class="ct-av" aria-hidden="true" @if ($avatar) style="background-image:url('{{ $avatar }}');color:transparent" @endif>{{ $initials }}@if ($talent->is_available)<i class="ct-on"></i>@endif</span>
      <h1 dir="auto">{{ $name }}</h1>
      <p class="ct-who" dir="auto">{{ $talent->headline ?: $primaryType?->name }}@if ($place)<span class="ct-place"><svg class="icon" aria-hidden="true"><use href="#i-pin"/></svg>{{ $place }}</span>@endif</p>
      <div class="ct-tags">
        <div class="ct-chips">
          {{-- Availability is information, never a gate (ADR-L/Z) — the yellow
               signal; the last-changed date rides on the chip so a stale switch
               never passes for current. --}}
          <span @class(['on' => $talent->is_available]) @if ($talent->availability_changed_at) title="{{ __('Changed :when', ['when' => $talent->availability_changed_at->diffForHumans()]) }}" @endif>{{ $talent->is_available ? __('Open for work') : __('Not open for work') }}</span>
          @if ($talent->will_travel)<span>{{ __('Will travel') }}</span>@endif
          @if ($hasRate)<span>{{ __('From') }} {{ $talent->rate_currency }} {{ $rateAmount }} / {{ $rateUnitLabels[$talent->rate_unit] ?? $talent->rate_unit }}</span>@endif
          @if ($externalUrl)<span><a href="{{ $externalUrl }}" target="_blank" rel="noopener nofollow" style="color:inherit;text-decoration:none">{{ preg_replace('#^https?://(www\.)?#i', '', rtrim($externalUrl, '/')) }}</a></span>@endif
        </div>
        @if ($skills->isNotEmpty())
          {{-- Each craft opens its tab and brings the work into view (profileTabs.goSkill). --}}
          <ul class="ct-skills" aria-label="{{ __('Skills') }}">
            @foreach ($skills as $skill)
              <li><a href="?skill={{ $skill->slug }}" data-goskill="{{ $skill->slug }}" @click.prevent="$dispatch('fama-goskill', @js($skill->slug))">{{ $skill->name }}</a></li>
            @endforeach
          </ul>
        @endif
      </div>
      @if ($talent->bio)<p class="ct-bio" dir="auto">{{ $talent->bio }}</p>@endif
      {{-- 13 Nov 2026 review: the point of view is part of the person's details, so it moved up into the hero. --}}
      @if (filled($talent->worldview))<div class="ct-pov"><h2>{{ __('Point of view') }}</h2><p dir="auto">“{{ $talent->worldview }}”</p></div>@endif
      @if ($firms->isNotEmpty())
        <p class="ct-with"><span>{{ __('Worked with') }}</span> {{ $firms->take(3)->implode(', ') }}@if ($firms->count() > 3) {{ __('and :n more', ['n' => $firms->count() - 3]) }}@endif</p>
      @endif
      {{-- No stats row (Projects · Brands · Recognitions) — removed 2026-11-23. --}}
      <div class="ct-acts">
        {{-- Follow: your own profile explains itself; a guest is asked to sign in;
             anyone signed in (talent or brand) follows for real. --}}
        <div class="guestpop">
          @if ($isOwner)
            <button type="button" class="btn" @click="ownNote()" aria-pressed="false"><svg class="icon" aria-hidden="true"><use href="#a-user-plus"/></svg><span>{{ __('Follow') }}</span></button>
          @elseif ($isGuest)
            <button type="button" class="btn" @click="signIn('follow')" aria-pressed="false"><svg class="icon" aria-hidden="true"><use href="#a-user-plus"/></svg><span>{{ __('Follow') }}</span></button>
          @elseif ($canFollow ?? true)
            <div x-data="talentFollow({
                    id: {{ $talent->id }},
                    following: {{ ($following ?? false) ? 'true' : 'false' }},
                    confirm: {
                        title: @js(__('Unfollow :name?', ['name' => $name])),
                        message: @js(__('You will stop seeing their new posts in your feed.')),
                        confirmLabel: @js(__('Unfollow')),
                    },
                 })" style="display:contents">
              <button type="button" class="btn" :class="{ 'btn-2': following }" @click="toggle()" :disabled="busy" :aria-pressed="following ? 'true' : 'false'">
                <svg class="icon" aria-hidden="true"><use :href="following ? '#a-user-check' : '#a-user-plus'" href="#a-user-plus"/></svg>
                <span x-text="following ? @js(__('Following')) : @js(__('Follow'))">{{ ($following ?? false) ? __('Following') : __('Follow') }}</span>
              </button>
            </div>
          @endif
        </div>
        <a class="btn btn-2" href="{{ route('talent.recognition.create', ['slug' => $talent->slug]) }}"
           @if ($isOwner) @click.prevent="ownNote()" @elseif ($isGuest) @click.prevent="signIn('recognise')" @endif><svg class="icon" aria-hidden="true"><use href="#i-award"/></svg><span>{{ __('Recognise') }}</span></a>
        @if ($reviewsOn)
          <a class="btn btn-2" href="{{ route('talent.review.create', ['slug' => $talent->slug]) }}">{{ __('Leave a review') }}</a>
        @endif
        @feature('messaging')
          <a class="btn btn-2" href="{{ route('brand.talents.message', ['talent' => $talent->slug]) }}">{{ __('Message') }}</a>
        @endfeature
        {{-- Share. A Private profile (only its owner or staff can open it) shows the button
             locked, as the prototype does (9 Oct 2026, ADR-AH): greyed, and a press explains. --}}
        <button type="button" class="btn btn-2 ionly" @click="share()" aria-label="{{ __('Share profile') }}"
                title="{{ $isLive ? __('Share profile') : __('Your link can be shared once your profile is Live') }}"
                @unless ($isLive) aria-disabled="true" style="opacity:.45;cursor:not-allowed" @endunless><svg class="icon" aria-hidden="true"><use href="#i-link"/></svg><span class="vh">{{ __('Share profile') }}</span></button>
        @if ($talent->socialHandles->isNotEmpty())
          <ul class="ct-links">
            @foreach ($talent->socialHandles as $social)
              @php $label = in_array($social->platform, ['website', 'other'], true) ? preg_replace('#^https?://(www\.)?#i', '', rtrim((string) $social->url, '/')) : ($socialLabels[$social->platform] ?? $social->platform); @endphp
              <li><a href="{{ $social->url }}" rel="me noopener nofollow" target="_blank" aria-label="{{ $label }}" title="{{ $label }}">@include('talent.partials.social-icon', ['platform' => $social->platform])</a></li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
    @if ($featured)
      {{-- The featured project: the talent's pick, else the automatic one. It is
           chosen in Projects / Profile structure, never from the profile itself. --}}
      <div class="ct-coverwrap">
        @if ($featuredTitle['year'] !== '')<span class="ptag">{{ $featuredTitle['year'] }}</span>@endif
        <a class="ct-cover{{ $featured->lead_image_url ? '' : ' noimg' }}" href="{{ route('talent.work', ['slug' => $talent->slug, 'project' => $featured->id]) }}" @if ($featured->lead_image_url) style="background-image:url('{{ $featured->lead_image_url }}')" @endif>
          <span class="ct-feat"><span class="ct-badge"><svg viewBox="10 80 760 690" aria-hidden="true"><polygon points="314.63 635.59 16.74 747.68 217.29 533.15 86.97 370.29 403.26 356.02 701.63 87.21 636.51 355.76 760 356.91 574.68 534.04 422.33 756.79 314.63 635.59"/></svg>{{ __('Featured project') }}</span>
            <span class="ct-ft"><b>{{ $featuredTitle['name'] }}</b>@if (filled($featured->client_name))<small>{{ $featured->client_name }}</small>@endif</span><span class="ct-go" aria-hidden="true"><svg class="icon"><use href="#i-arrow"/></svg></span></span>
        </a>
      </div>
    @else
      <div class="ct-cover empty" aria-hidden="true"></div>
    @endif
  </section>

  @if ($tabSkills->isNotEmpty())
    <div id="skillArea" x-data="profileTabs({ active: @js($activeSkill?->slug), tabs: @js($tabSkills->pluck('slug')), labels: @js($tabSkills->pluck('name', 'slug')), urls: @js($tabUrls) })"
         @fama-goskill.window="goSkill($event.detail)">
      @if ($tabSkills->count() > 1)
        {{-- Scrolls with the page (30 Oct 2026); the open tab is filled in orchid
             and carries the FAMA star; names in the display face. --}}
        <nav class="stabs" aria-label="{{ __('Skills') }}"><div class="row" role="tablist" aria-label="{{ __('Skills') }}" x-ref="tablist">
          @foreach ($tabSkills as $skill)
            @php $isActive = $skill->is($activeSkill); $n = $talent->portfolioProjects($skill->id)->count(); @endphp
            <a role="tab" id="skill-tab-{{ $skill->slug }}" href="?skill={{ $skill->slug }}" aria-controls="skill-tabpanel" data-skill="{{ $skill->slug }}"
               @click.prevent="show('{{ $skill->slug }}')" @keydown="onTabKey($event, {{ $loop->index }})"
               :aria-selected="active === '{{ $skill->slug }}' ? 'true' : 'false'"
               :tabindex="active === '{{ $skill->slug }}' ? 0 : -1"
               @if ($isActive) aria-selected="true" @else aria-selected="false" tabindex="-1" @endif><svg class="tstar" viewBox="10 80 760 690" aria-hidden="true"><polygon points="314.63 635.59 16.74 747.68 217.29 533.15 86.97 370.29 403.26 356.02 701.63 87.21 636.51 355.76 760 356.91 574.68 534.04 422.33 756.79 314.63 635.59"/></svg>{{ $skill->name }}<span class="n"><span class="vh">, </span>{{ $n }}<span class="vh"> {{ trans_choice('{1} project|[0,*] projects', $n) }}</span></span></a>
          @endforeach
        </div></nav>
        <h2 class="vh" x-text="labels[active]">{{ $activeSkill->name }}</h2>
        <div id="skillBody">
          <div id="skill-tabpanel" role="tabpanel" x-ref="panel" :aria-labelledby="'skill-tab-' + active" aria-labelledby="skill-tab-{{ $activeSkill->slug }}" :aria-busy="loading">
            @include('talent.partials.skill-blocks', $blockVars + ['skill' => $activeSkill, 'blocks' => $blocksFor($activeSkill->id)])
          </div>
        </div>
      @else
        <div id="skillBody">
          <div x-ref="panel">
            @include('talent.partials.skill-blocks', $blockVars + ['skill' => $activeSkill, 'blocks' => $blocksFor($activeSkill->id)])
          </div>
        </div>
      @endif
    </div>
  @endif

  {{-- What is true for every skill shows ONCE, after the tabs (24 Oct 2026) —
       the profile-wide sections under "About {name}" (the point of view moved
       up into the hero, 13 Nov 2026). The
       partial renders nothing when none of it has content for this viewer. --}}
  @include('talent.partials.universal-blocks', $blockVars + ['blocks' => $universalBlocks, 'name' => $name])

  @if ($universalBlocks->isEmpty() && $tabSkills->isEmpty())
    <section class="notlive"><h2 class="h1">{{ __('This profile is still being set up.') }}</h2><p>{{ __('Check back soon.') }}</p>
      <a class="btn" style="margin-top:var(--s5)" href="{{ route('feed') }}">{{ __('Browse the feed') }}</a></section>
  @endif

  {{-- Sign in to follow / recognise (guests). The follow is captured and finished after login. --}}
  @if ($isGuest)
    <dialog class="dlg signdlg" x-ref="signDlg" data-follow-id="{{ $talent->id }}" @close="sign.open = false" @click.self="closeSign()">
      <form method="dialog">
        <div class="dh"><h2 x-text="sign.act === 'follow' ? @js(__('Sign in to follow')) : @js(__('Sign in to recognise'))"></h2>
          <button type="button" class="iconbtn plain" @click="closeSign()" aria-label="{{ __('Close') }}"><svg class="icon" aria-hidden="true"><use href="#i-x"/></svg></button></div>
        <p x-text="sign.act === 'follow' ? @js(__('Log in or join Fama to follow :name. We’ll finish the follow as soon as you’re in.', ['name' => $name])) : @js(__('Log in or join Fama to recognise :name.', ['name' => $name]))"></p>
        <div class="acts">
          <a class="btn" :href="sign.act === 'follow' ? @js(route('login', ['role' => 'talent'])) : @js(route('talent.recognition.create', ['slug' => $talent->slug]))" @click="loginFor($event, $el.href)"><svg class="icon" aria-hidden="true"><use href="#a-log-in"/></svg><span>{{ __('Log in') }}</span></a>
          <a class="btn btn-2" href="{{ route('register') }}"><svg class="icon" aria-hidden="true"><use href="#a-user-plus"/></svg><span>{{ __('Join as talent') }}</span></a>
        </div>
      </form>
    </dialog>
  @endif

  {{-- Lightbox: every picture in a section, previous / next, arrow keys, a count. --}}
  <dialog class="lb" id="lb" x-ref="lb" aria-label="{{ __('Picture viewer') }}" @close="lbClosed()" @click.self="$refs.lb.close()"
          @keydown.arrow-right="step(1)" @keydown.arrow-left="step(-1)">
    <button type="button" class="iconbtn x" @click="$refs.lb.close()" aria-label="{{ __('Close') }}"><svg class="icon"><use href="#i-x"/></svg></button>
    <div class="lb-stage">
      <button type="button" class="iconbtn nav" @click="step(-1)" x-show="lb.items.length > 1" aria-label="{{ __('Previous') }}"><svg class="icon flip" style="transform:scaleX(-1)"><use href="#i-arrow"/></svg></button>
      <div class="ph" :style="lbItem[0] ? `background:var(--line) url('${lbItem[0]}') center/contain no-repeat` : ''"><svg class="icon" style="width:48px;height:48px" x-show="!lbItem[0]"><use href="#i-image"/></svg></div>
      <button type="button" class="iconbtn nav" x-ref="lbNext" @click="step(1)" x-show="lb.items.length > 1" aria-label="{{ __('Next') }}"><svg class="icon"><use href="#i-arrow"/></svg></button>
    </div>
    <p><span x-text="lbItem[1]"></span> <span class="lb-n" x-text="lb.items.length > 1 ? @js(__(':i of :n')).replace(':i', lb.i + 1).replace(':n', lb.items.length) : ''"></span></p>
  </dialog>
</main>

    {{-- Profile motion (24 Oct 2026): GSAP 3.12.5 + ScrollTrigger from cdnjs, as
         the prototype loads them. Everything is visible without JS and under
         reduced motion — GSAP only animates from a hidden start it sets itself. --}}
    <x-slot:scripts>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
        <script src="{{ \App\Support\FamaAsset::url('fama-ui/js/profile-motion.js') }}"></script>
    </x-slot:scripts>
</x-fama.layout>
