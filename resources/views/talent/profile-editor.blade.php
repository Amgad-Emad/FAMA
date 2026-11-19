{{--
    Profile structure (GET /talent/profile) — the fama-ui "profile-structure.html"
    screen on real data. The old dashboard is folded in: the profile path (Go Live
    then Stand out — ADR-AC, drawn by ./profile-gate.js from SAVED data, with the
    tracker floating under the nav) and the views card (the chart opens on request). EVERY edit — identity, availability, links,
    skills (order = tab order; the first opens first), specialities, sections,
    willing to explore, pricing — collects in a draft; the bottom bar saves it
    through the existing endpoints or discards it (toast with Undo), and leaving
    with unsaved changes warns. Only the photo uploads immediately. A profile goes
    Live by itself once its Go Live steps are SAVED; a Private profile shows no
    link anywhere on this page (product rule).
    Behaviour: `profileStructure` (resources/js/ui/profile-structure*.js) + `profileViews`.
--}}
@php
    $core = [
        'display_name' => (string) $talent->display_name,
        'headline' => array_merge(['en' => '', 'ar' => ''], $talent->getTranslations('headline')),
        'bio' => array_merge(['en' => '', 'ar' => ''], $talent->getTranslations('bio')),
        'worldview' => array_merge(['en' => '', 'ar' => ''], $talent->getTranslations('worldview')),
        'slug' => (string) $talent->slug,
        'base_city' => (string) $talent->base_city,
        'base_country' => (string) $talent->base_country,
    ];
    $publish = ['is_published' => (bool) $talent->is_published, 'status' => $talent->status->getValue()];
    $rate = [
        'rate_unit' => (string) ($talent->rate_unit ?? ''),
        'rate_amount' => $talent->rate_amount !== null ? (string) $talent->rate_amount : '',
        'rate_currency' => (string) ($talent->rate_currency ?? ''),
    ];
    $host = parse_url(url('/'), PHP_URL_HOST) ?: 'fama.so';
    $initials = collect(preg_split('/\s+/', trim((string) ($talent->display_name ?: $talent->slug))))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

    // Views card head: lifetime total + rolling week-over-week change.
    $weekly = $views['weekly'];
    $deltaUp = $weekly['delta'] >= 0;
    $deltaText = $weekly['delta_pct'] !== null
        ? ($deltaUp ? '▲' : '▼').' '.number_format(abs($weekly['delta_pct'])).'%'
        : ($weekly['current_week'] > 0 ? '▲ '.number_format($weekly['current_week']) : '–');

    // The profile path (ADR-AC — prototype profile.js FAMA_INDEX_REQ / _REC).
    // Order = ProfileCompletionService::GO_LIVE / STAND_OUT. Steps on this page
    // land on their field (profile-gate.js `go` map); the rest link away.
    $goLive = [
        ['key' => 'name', 'label' => __('Display name'), 'help' => __('The name brands see in search results.'), 'href' => '#core'],
        ['key' => 'skill', 'label' => __('A skill and a speciality'), 'help' => __('What brands search for.'), 'href' => '#skills'],
        ['key' => 'place', 'label' => __('Your city'), 'help' => __('Brands filter by where you are.'), 'href' => '#core'],
    ];
    $standOut = [
        ['key' => 'show', 'label' => __('Something to show'), 'help' => __('A project, three photos or a showreel. This puts you in the Creatives Index.'), 'href' => route('talent.projects')],
        ['key' => 'projects3', 'label' => __('Three projects'), 'help' => __('One project shows you can do it. Three show a style.'), 'href' => route('talent.projects')],
        ['key' => 'headline', 'label' => __('A headline'), 'help' => __('One line under your name in search, like “Art director & stylist”.'), 'href' => '#core'],
        ['key' => 'photo', 'label' => __('A profile photo'), 'help' => __('Profiles with a photo get opened more.'), 'href' => '#core'],
        ['key' => 'brands', 'label' => __('Experience'), 'help' => __('The brands and teams you have worked with.'), 'href' => '#brands'],
        ['key' => 'links', 'label' => __('Your Instagram or website'), 'help' => __('Brands look at where else your work lives.'), 'href' => '#social'],
        ['key' => 'bio', 'label' => __('A short bio'), 'help' => __('A few lines on how you work.'), 'href' => '#core'],
    ];
    $gate = [
        'barLabel' => __('Profile steps'), 'doneSuffix' => __(', done'), 'ofN' => __(':n of :m'),
        'trackA' => __('Go Live :a done, Stand out :b done'),
        'grpLive' => __('1 · Go Live'), 'grpStand' => __('2 · Stand out'),
        'inIndex' => __('In the Creatives Index'), 'getsIndex' => __('Gets you into the Creatives Index'),
        'edit' => __('Edit'), 'add' => __('Add'),
        'standOut' => __('Stand out'), 'oneToIndex' => __('1 step to the Creatives Index'), 'nextColon' => __('Next:'),
        'allSteps' => __('All steps'), 'fold' => __('Fold steps'), 'yourProfile' => __('Your profile'),
        'moreOne' => __(':n more step to go Live'), 'moreMany' => __(':n more steps to go Live'),
        'liveStand' => __('You’re Live. Now stand out'), 'liveNext' => __('You’re Live. Next, the Creatives Index'),
        'leadLive' => __('Two parts. Go Live takes a minute, and then your link works. Stand out starts with something to show, which puts you in the Creatives Index where brands search. The rest gets you picked.'),
        'leadListed' => __('Your link works and brands find you in the Creatives Index. Fama recommends these next: they are what gets a profile picked.'),
        'leadNotListed' => __('Your link works and you can share it. Add something to show and brands find you in the Creatives Index too.'),
        'onlyYou' => __('Only you see this'),
        'p1Title' => __('Go Live'), 'p1Sub' => __('Your link works and you can share it.'),
        'p2Title' => __('Stand out'), 'p2Sub' => __('Not needed to go Live. Something to show gets you into the Creatives Index; the rest gets you picked.'),
        'readyH' => __('Your profile is ready'), 'readySub' => __('Live, and standing out. Nice work.'), 'readySr' => __('Your profile is ready.'),
        'headWent' => __('You’re Live. Your link works'), 'headListed' => __('You’re in the Creatives Index'), 'headNice' => __('Nice. :n to go'),
        'headLast' => __('Last step to go Live'), 'headMore' => __(':n more steps to go Live'), 'headGetListed' => __('Next: the Creatives Index'), 'headStand' => __('Stand out · :n to go'),
        'shortWent' => __('You’re Live'), 'shortListed' => __('In the Index!'), 'shortNice' => __('Nice!'), 'shortLive' => __(':n to go Live'), 'shortGet' => __('Get listed'), 'shortStand' => __('Stand out'),
        'sub1' => __('Part 1 of 2 · Go Live'), 'sub2Listed' => __('Part 2 of 2 · Recommended by Fama'), 'sub2Not' => __('Part 2 of 2 · Live, not in the Creatives Index yet'),
        'nextL' => __('Next: '), 'pop1' => __('1 · Go Live'), 'pop2' => __('2 · Stand out'),
        'srWent' => __('You are Live. Your link works.'), 'srListed' => __('You are in the Creatives Index. Brands can find you now.'), 'srN' => __(':n of :m done'),
    ];

    $strings = [
        'goLive' => $goLive, 'standOut' => $standOut, 'gate' => $gate,
        'partNames' => ['core' => __('Core details'), 'now' => __('Availability'), 'social' => __('Social links'), 'skills' => __('Skills'), 'sections' => __('Sections'), 'explore' => __('Willing to explore'), 'brands' => __('Experience')] + (\App\Support\Features::enabled('pricing') ? ['pricing' => __('Pricing rate')] : []),
        'sbClean' => __('All changes saved'), 'sbCleanN' => __('Your profile is up to date.'), 'sbSavedNow' => __('Saved just now'),
        'sbDirtyOneIn' => __('Unsaved changes in :name'), 'sbDirtyManyIn' => __('Unsaved changes in :n sections'), 'sbShort' => __('Unsaved changes'),
        'sbDirtyN' => __('Save to update your profile, or discard to undo them.'),
        'sbBad' => __('Fix the highlighted field before saving'), 'sbBadN' => __('Your profile needs a display name and a valid username.'),
        'sbBadName' => __('Add a display name to save'), 'sbBadNameN' => __('Your profile needs one, and brands see it first.'),
        'sbNoNameN' => __('Add it back to save. Your profile stays as it is until then.'),
        'sbBadUser' => __('Fix your username to save'), 'sbBadUserN' => __('Use letters, numbers and dashes only.'),
        'sbRisk' => __('Saving this takes your profile out of Live'), 'sbRiskN' => __('Your city is empty. Add it back to stay Live.'),
        'tSaved' => __('Changes saved'), 'saveFailed' => __('Some changes couldn’t be saved. Try again.'), 'tPhoto' => __('Profile photo updated'), 'tDiscarded' => __('Changes discarded'), 'tRemoved' => __(':name removed'), 'tAdded' => __(':name added'),
        'tMoved' => __(':name moved to :group'), 'tLive' => __('Your profile is Live'), 'tPrivate' => __('Your profile is Private'),
        'photo' => __('Profile photo'), 'photoType' => __('Use a JPG, PNG or WebP photo.'), 'photoBig' => __('That photo is over 5 MB. Choose a smaller one.'),
        'photoRmTitle' => __('Remove profile photo?'), 'photoRmHelp' => __('Your photo will be removed and replaced with your initials.'),
        'uploadFailed' => __('Upload failed'), 'remove' => __('Remove'),
        'copiedA' => __('Link copied to the clipboard'),
        'travelOn' => __('Brands see you’ll take jobs outside :city.'), 'travelOff' => __('Brands see only that you’re based in :city.'), 'yourCity' => __('your city'),
        'website' => __('Website'), 'other' => __('Other'),
        'skPrimary' => __(':name is now your primary skill'),
        'fxCount' => __(':n of :m added'),
        'roleAdded' => __(':r added'),
        'roleUpdated' => __(':r updated'),
        'roleRemoved' => __(':r removed'),
        'roleNeedY' => __('Add the year you started.'),
        'roleOrder' => __("The end date can't be before the start date."),
        'copyOffN' => __('Finish Go Live first. Then you can share your link.'),
        'fxCleared' => __(':name cleared'),
        'addTitle' => __('Add a skill'), 'addHelp' => __('It gets its own tab, with default sections you can change.'), 'addOk' => __('Add skill'),
        'rmTitle' => __('Remove :name?'), 'rmHelp' => __('Its tab and sections come off your profile. The content inside them is kept, so you can add the skill back later.'), 'rmOk' => __('Remove skill'),
        'pw' => __('Profile-wide'), 'offA' => __(':name is hidden from your profile. It keeps its content.'),
        'cantMove' => __('That section can’t go there.'),
        'wtePickT' => __('Choose a skill to explore'), 'wtePickH' => __('Any skill, including one you have no work in yet.'), 'wtePickOk' => __('Choose skill'),
    ];
    $viewStrings = ['sum' => __(':n views in this period'), 'nViews' => __(':n views')];
@endphp

<x-fama.layout :title="__('Profile structure')" mode="talent" :css="['fields', 'views', 'exp']">
    <x-slot:styles>
        <link rel="stylesheet" href="{{ \App\Support\FamaAsset::url('fama-ui/css/pages/profile-structure.css') }}">
    </x-slot:styles>

<div x-data="profileStructure({
        strings: @js($strings),
        host: @js($host),
        core: @js($core),
        avatarUrl: @js($talent->avatar_url),
        publish: @js($publish),
        completion: @js($completion),
        now: @js($now),
        social: @js($social),
        skills: @js($skills),
        availableSkills: @js($availableSkills),
        specialities: @js($specialities),
        interestSkills: @js($interestSkills),
        blocks: @js($blocks),
        catalog: @js($catalog),
        contentKeys: @js($contentKeys),
        countries: @js(\App\Support\Locations::countries()),
        interests: @js($interests),
        experience: @js($experience),
        hasPricing: {{ \App\Support\Features::enabled('pricing') ? 'true' : 'false' }},
        rate: @js($rate),
        reviewsOn: {{ \App\Support\Features::enabled('reviews') ? 'true' : 'false' }},
        practice: @js($practice),
    })">

<main class="wrap">

<section class="hero soft">
  <div class="body">
    <span class="ph" :class="{ has: avatarUrl }" :style="avatarUrl ? `background-image:url(${avatarUrl})` : ''" aria-hidden="true" x-text="avatarUrl ? '' : initials">{{ $talent->avatar_url ? '' : $initials }}</span>
    <div>
      <span class="hello">{{ __('Your profile') }}</span>
      <h1 x-text="savedName || '—'">{{ $talent->display_name ?: '—' }}</h1>
    </div>
    <div class="pub">
      {{-- The profile link. Private: shown LOCKED — dashed, a lock, "Locked"; a press says to
           finish Go Live (prototype 9 Oct 2026, ADR-AH — supersedes the "no link" rule). --}}
      <button type="button" class="linkbtn" id="copyLink" :class="{ copied, off: !isPublished }" @click="copyLink()" aria-describedby="copyNote"
              :aria-disabled="(!isPublished).toString()" :title="isPublished ? '' : @js(__('Your link can be shared once your profile is Live'))"
              :aria-label="isPublished ? @js(__('Copy your profile link')) + ', ' + linkLabel : @js(__('Your link can be shared once your profile is Live'))">
        <span id="pubState" dir="ltr" x-text="linkLabel"></span>
        <span class="lb-ic"><svg class="icon" aria-hidden="true"><use :href="!isPublished ? '#i-lock' : (copied ? '#i-check' : '#i-copy')" href="#i-copy"/></svg><span id="copyTx" x-text="!isPublished ? @js(__('Locked')) : (copied ? @js(__('Copied')) : @js(__('Copy')))"></span></span>
      </button>
      <span class="vh" id="copyNote" aria-live="polite" x-text="note"></span>
      <span class="stat">
        <span class="pill" :class="isPublished ? 'p-live' : 'p-off'" x-text="isPublished ? @js(__('Live')) : @js(__('Private'))">{{ $talent->is_published ? __('Live') : __('Private') }}</span>
        <span class="pill p-idx" x-show="isListed" x-cloak>{{ __('In the Index') }}</span>
        <button type="button" class="tipbtn" aria-describedby="pubTip" aria-label="{{ __('What Live and Private mean') }}"><svg class="icon" aria-hidden="true"><use href="#i-info"/></svg></button>
        {{-- Three states, and every line that is true is lit. --}}
        <span class="tip" role="tooltip" id="pubTip">
          <p :class="{ cur: !isPublished }"><b>{{ __('Private') }}</b> {{ __('Only you can see it. Finish Go Live and your link works.') }}</p>
          <p :class="{ cur: isPublished }"><b>{{ __('Live') }}</b> {{ __('Anyone with your link can open your profile.') }}</p>
          <p :class="{ cur: isListed }"><b>{{ __('In the Creatives Index') }}</b> <span x-text="isListed ? @js(__('Brands find you when they search.')) : @js(__('Brands find you when they search. Add something to show to get there.'))"></span></p>
        </span>
      </span>
      {{-- App states the prototype has no screen for: a profile HELD private (Unpublished) goes
           Live by hand; a suspended one can't. Every other profile goes Live by itself. --}}
      <button type="button" class="btn btn-text pub-act" x-show="isHeld && path.can_go_live" x-cloak @click="setPublished(true)" :disabled="publishing">{{ __('Go Live') }}</button>
      <p class="vw-err pub-note" x-show="publishError || isSuspended" x-cloak x-text="publishError || @js(__('Fama has taken your profile down. Contact support to restore it.'))"></p>
    </div>
  </div>
  <div class="band" role="presentation"></div>
</section>

{{-- The dashboard lives here: the path (Go Live, then Stand out — drawn by profile-gate.js
     from SAVED data; folds to one line once Live, gone once everything is done) and the views. --}}
<section class="vw" id="gate" aria-label="{{ __('Creatives Index') }}" x-ref="gate" hidden></section>

<section class="vw" id="views" aria-label="{{ __('Profile views') }}" x-data="profileViews({ strings: @js($viewStrings) })">
  {{-- 24 Oct 2026: the card is a heading, the number and one line; the chart opens on request. --}}
  <div class="vw-head">
    <div>
      <h2 class="vw-label">{{ __('Profile views · all time') }}</h2>
      <span class="vw-n">{{ number_format($views['total']) }}</span>
      <span class="vw-d">{{ __('This week') }} <b>{{ $deltaText }}</b> {{ __('on last week') }}</span>
    </div>
    <div class="vw-hr">
      <span class="vw-only"><svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg>{{ __('Only you see this') }}</span>
      <button type="button" class="vw-link vw-open" @click="toggle()" :aria-expanded="open.toString()" aria-controls="vwMore"><span x-text="open ? @js(__('Hide chart')) : @js(__('See chart'))">{{ __('See chart') }}</span><svg class="icon" aria-hidden="true"><use href="#i-down"/></svg></button>
    </div>
  </div>
  {{-- 4 Nov 2026: the chart eases open/closed (grid-rows 0fr→1fr — no fixed height) and its bars
       rise on every open; inert while closed so nothing inside takes focus. --}}
  <div class="vw-more" id="vwMore" :class="{ 'is-open': open }" :inert="!open" :aria-hidden="(!open).toString()">
   <div class="vw-more-in">
    <div class="vw-sh">
      <p class="vw-sum" x-text="sumText"></p>
      <div class="vw-seg" role="group" aria-label="{{ __('Period') }}">
        @foreach (['7d' => __('7 days'), '30d' => __('30 days'), '90d' => __('90 days'), 'all' => __('All time')] as $r => $label)
          <button type="button" @click="load('{{ $r }}')" :aria-pressed="(range === '{{ $r }}').toString()">{{ $label }}</button>
        @endforeach
      </div>
    </div>
    <div class="vw-plot" :class="{ 'is-loading': loading }">
      <div class="vw-yax" aria-hidden="true"><template x-for="(tk, i) in yTicks" :key="i"><span x-text="tk"></span></template></div>
      <div class="vw-area" x-ref="area">
        <div class="vw-grid"><i></i><i></i><i></i><i></i></div>
        <div class="vw-bars" :class="{ 'is-snap': snap }">
          <template x-for="(b, i) in series" :key="b.date">
            <button type="button" :aria-label="b.label + ': ' + b.views.toLocaleString('en-GB') + ' ' + @js(__('views'))"
                    @mouseenter="tip($event, i)" @focus="tip($event, i)" @mouseleave="untip()" @blur="untip()"><span :style="'height:' + (grown ? height(b.views) : 0) + '%;transition-delay:' + delay(i) + 'ms'"></span></button>
          </template>
        </div>
        <div class="vw-tip" x-ref="tip" role="status"></div>
      </div>
      <div class="vw-xax" aria-hidden="true"><template x-for="i in xPicks" :key="i"><span x-text="series[i] ? series[i].label : ''"></span></template></div>
    </div>
    <p class="vw-err" x-show="error" x-cloak>{{ __('Couldn’t load your views. Try again in a moment.') }}</p>
   </div>
  </div>
</section>

<div class="layout">

  {{-- ═══ Core details ═══ --}}
  <section class="sec panel" id="core" :class="{ changed: dirty.core }">
    <div class="phead">
      <div class="ptitle"><div class="hrow"><h2 class="h3x">{{ __('Core details') }}</h2><span class="dirty" x-show="dirty.core" x-cloak>{{ __('Unsaved') }}</span></div>
        <p class="pdesc">{{ __('Your name, your address on Fama, and how you introduce yourself.') }}</p></div>
    </div>
    <div class="pbody" id="body-core">
      <div class="form">
        <div class="f">
          <span>{{ __('Profile photo') }}</span>
          <div class="photo">
            <span class="ph" :class="{ has: avatarUrl }" :style="avatarUrl ? `background-image:url(${avatarUrl})` : ''" aria-hidden="true" x-text="avatarUrl ? '' : initials"></span>
            <div>
              <div class="acts">
                <button class="btn btn-2" type="button" id="fPhoto" @click="$refs.avatarInput.click()" :disabled="uploadingAvatar">
                  <svg class="icon" aria-hidden="true"><use href="#i-upload"/></svg>
                  <span x-text="uploadingAvatar ? @js(__('Uploading…')) : (avatarUrl ? @js(__('Change photo')) : @js(__('Upload photo')))">{{ __('Upload photo') }}</span>
                </button>
                <button class="btn btn-text" type="button" x-show="avatarUrl" x-cloak @click="removeAvatar()" :disabled="uploadingAvatar">{{ __('Remove') }}</button>
                <input type="file" x-ref="avatarInput" accept="image/jpeg,image/png,image/webp" hidden @change="uploadAvatar($event.target.files)">
              </div>
              <p class="help" :class="{ err: avatarError }" x-text="avatarError || @js(__('JPG, PNG or WebP, up to 5 MB. Shown in a circle.'))">{{ __('JPG, PNG or WebP, up to 5 MB. Shown in a circle.') }}</p>
            </div>
          </div>
        </div>
        <div class="two f">
          <label class="f" :class="{ bad: nameBad, risk: liveRisk.includes('display') }">
            <span><span>{{ __('Display name') }}</span><small>{{ __('Needed to go Live') }}</small></span>
            <input class="in" id="fDisplay" maxlength="255" autocomplete="name" x-model="core.display_name" @input="errors.display_name = null">
            <p class="help" :class="{ err: nameBad }" x-text="errors.display_name ? errors.display_name[0] : (nameBad ? @js(__('Add a display name. Your profile needs one to go Live.')) : @js(__('The name people see on your profile.')))"></p>
          </label>
          <label class="f" :class="{ bad: userBad }">
            <span>{{ __('Username') }}</span>
            <span class="url"><b>{{ $host }}/</b><input class="in" id="fUser" maxlength="60" autocomplete="off" spellcheck="false" dir="ltr" x-model="core.slug" @input="errors.slug = null"></span>
            <p class="help" :class="{ err: userBad }" x-text="errors.slug ? errors.slug[0] : (userBad ? @js(__('Use only letters, numbers and dashes.')) : @js(__('Letters, numbers and dashes. It becomes your profile address.')))"></p>
          </label>
        </div>
        <label class="f"><span><span>{{ __('Headline') }}</span><small>{{ __('Optional') }}</small></span><input class="in" id="fHeadline" maxlength="255" x-model="core.headline.en"></label>
        <label class="f"><span><span>{{ __('Bio') }}</span><small x-text="count(core.bio.en, 5000)"></small></span><textarea class="ta" id="fBio" maxlength="5000" x-model="core.bio.en"></textarea><p class="help">{{ __('Your background and track record.') }}</p></label>
        <div class="f">
          <label for="fPov" class="lbl"><span>{{ __('Point of view') }}</span><small x-text="count(core.worldview.en, 2000)"></small></label>
          <textarea class="ta" id="fPov" maxlength="2000" x-model="core.worldview.en"></textarea>
          <p class="help">{{ __('Your taste, in your words. Not a second bio.') }}</p>
          <div class="prompts">
            @foreach ([__('What do you notice that others miss?'), __('A reference you keep going back to'), __('Work you won’t take, and why')] as $prompt)
              <button type="button" class="chip" @click="addPrompt(@js($prompt))">{{ $prompt }}</button>
            @endforeach
          </div>
        </div>
        <div class="two f">
          {{-- Country first: it decides the City list. --}}
          <div class="f" :class="{ bad: errors.base_country }"><span id="lCountry">{{ __('Country') }}</span>
            <x-fama.combo id="fCountry" model="core.base_country" items="countryOptions" :flags="true" icon="globe" aria-labelledby="lCountry"
                          :label="__('Country')" :placeholder="__('Choose a country')" :search-placeholder="__('Search countries')"
                          @combo-pick="onCountryPicked($event.detail)" />
            <p class="help err" x-show="errors.base_country" x-cloak x-text="errors.base_country?.[0]"></p></div>
          <div class="f" :class="{ bad: errors.base_city, risk: liveRisk.includes('city') }"><span id="lCity">{{ __('City') }}</span>
            <x-fama.combo id="fCity" model="core.base_city" items="cityOptions" :allow-custom="true" icon="pin"
                          :label="__('City')" :placeholder="__('Choose a city')" :search-placeholder="__('Search cities')"
                          disabled="!countryCode" x-effect="loadCities(countryCode)" />
            <p class="help" x-show="!countryCode" x-cloak>{{ __('Choose a country first.') }}</p>
            <p class="help err" x-show="errors.base_city" x-cloak x-text="errors.base_city?.[0]"></p></div>
        </div>
      </div>
    </div>
  </section>

  {{-- ═══ Availability ═══ --}}
  <section class="sec panel" id="now" :class="{ changed: dirty.now }">
    <div class="phead">
      <div class="ptitle"><div class="hrow"><h2 class="h3x">{{ __('Availability') }}</h2><span class="dirty" x-show="dirty.now" x-cloak>{{ __('Unsaved') }}</span></div>
        <p class="pdesc">{{ __('Tell brands if they can book you right now. It shows under your name on your profile.') }}</p></div>
    </div>
    <div class="pbody" id="body-now">
      <div class="nowprev" aria-live="polite">
        <span class="nowprev-l">{{ __('On your profile') }}</span>
        <span class="nowchips"><span :class="{ on: now.is_available }" x-text="now.is_available ? @js(__('Open for work')) : @js(__('Not open for work'))"></span><span x-show="now.will_travel" x-cloak>{{ __('Will travel') }}</span></span>
      </div>
      <div class="nowlist">
        <div class="nowrow">
          <div><b id="lTaking">{{ __('Open for work') }}</b><p class="nowdesc" id="dTaking" x-text="now.is_available ? @js(__('Brands see you’re open for work, and can send you a job now.')) : @js(__('Brands see you’re not open for work right now. Your profile stays up.'))"></p></div>
          <button type="button" class="switch" role="switch" :aria-checked="now.is_available.toString()" aria-labelledby="lTaking" aria-describedby="dTaking" @click="flipNow('is_available')"><i></i></button>
        </div>
        <div class="nowrow">
          <div><b id="lTravel">{{ __('Happy to travel for work') }}</b><p class="nowdesc" id="dTravel" x-text="travelText(now.will_travel)"></p></div>
          <button type="button" class="switch" role="switch" :aria-checked="now.will_travel.toString()" aria-labelledby="lTravel" aria-describedby="dTravel" @click="flipNow('will_travel')"><i></i></button>
        </div>
      </div>
    </div>
  </section>

  {{-- ═══ Social links ═══ --}}
  <section class="sec panel" id="social" :class="{ changed: dirty.social }">
    <div class="phead">
      <div class="ptitle"><div class="hrow"><h2 class="h3x">{{ __('Social links') }}</h2><span class="dirty" x-show="dirty.social" x-cloak>{{ __('Unsaved') }}</span></div>
        <p class="pdesc">{{ __('Choose the platform, then type your username. Your profile shows its icon and links to your page. Up to nine.') }}</p></div>
    </div>
    <div class="pbody" id="body-social">
      <div class="pin-card">
        <ul class="links" id="links">
          <template x-for="(l, i) in links" :key="l._k">
            <li>
              <button type="button" class="platbtn" @click.stop="togglePlat(i)" aria-haspopup="listbox" :aria-expanded="(platOpen === i).toString()"
                      :aria-label="@js(__('Account type: :platform. Change')).replace(':platform', platName(l.platform))">
                <span class="ic" x-html="socialIcon(l.platform)"></span><svg class="icon caret" aria-hidden="true"><use href="#i-chev"/></svg>
              </button>
              <span class="url" :class="{ web: isWeb(l.platform) }"><b x-show="!isWeb(l.platform)" x-text="prefix(l.platform)"></b><input class="in" :data-link="i" :value="l.handle" @input="onLinkInput(i, $event)"
                     :placeholder="isWeb(l.platform) ? @js(__('Link to social profile')) : 'username'"
                     :aria-label="isWeb(l.platform) ? @js(__('Link to social profile')) : @js(__('Your :platform username')).replace(':platform', platName(l.platform))"
                     dir="ltr" autocomplete="off" autocapitalize="none" spellcheck="false"></span>
              <button type="button" class="iconbtn plain rm" @click="removeLink(i)" :aria-label="@js(__('Remove :platform link')).replace(':platform', platName(l.platform))"><svg class="icon" aria-hidden="true"><use href="#i-trash"/></svg></button>
              <div class="platmenu" role="listbox" aria-label="{{ __('Account type') }}" x-show="platOpen === i" x-cloak @keydown="platKey($event)">
                <p class="pm-h">{{ __('Account type') }}</p>
                <template x-for="p in platforms" :key="p">
                  <button type="button" role="option" class="pm-o" :aria-selected="(p === l.platform).toString()" :aria-disabled="(usedBy(i).includes(p)).toString()"
                          @click="!usedBy(i).includes(p) && setPlat(i, p)">
                    <span class="pm-ck" aria-hidden="true"><svg class="icon" x-show="p === l.platform"><use href="#i-check"/></svg></span>
                    <span class="ic" x-html="socialIcon(p)"></span><span class="pm-n" x-text="platName(p)"></span><span class="pm-x" x-show="usedBy(i).includes(p)">{{ __('Added') }}</span>
                  </button>
                </template>
              </div>
            </li>
          </template>
          <li class="newrow" x-show="showNewRow" x-cloak>
            <button type="button" class="platbtn" @click.stop="togglePlat('new')" aria-haspopup="listbox" :aria-expanded="(platOpen === 'new').toString()"
                    :aria-label="@js(__('Account type: :platform. Change')).replace(':platform', platName(newRow.platform))">
              <span class="ic" x-html="socialIcon(newRow.platform)"></span><svg class="icon caret" aria-hidden="true"><use href="#i-chev"/></svg>
            </button>
            <span class="url" :class="{ web: isWeb(newRow.platform) }"><b x-show="!isWeb(newRow.platform)" x-text="prefix(newRow.platform)"></b><input class="in" data-link="new" value="" @input="onLinkInput('new', $event); $event.target.value = ''"
                   :placeholder="isWeb(newRow.platform) ? @js(__('Link to social profile')) : 'username'" aria-label="{{ __('Link to social profile') }}"
                   dir="ltr" autocomplete="off" autocapitalize="none" spellcheck="false"></span>
            <span></span>
            <div class="platmenu" role="listbox" aria-label="{{ __('Account type') }}" x-show="platOpen === 'new'" x-cloak @keydown="platKey($event)">
              <p class="pm-h">{{ __('Account type') }}</p>
              <template x-for="p in platforms" :key="p">
                <button type="button" role="option" class="pm-o" :aria-selected="(p === newRow.platform).toString()" :aria-disabled="(usedBy(-1).includes(p)).toString()"
                        @click="!usedBy(-1).includes(p) && setPlat('new', p)">
                  <span class="pm-ck" aria-hidden="true"><svg class="icon" x-show="p === newRow.platform"><use href="#i-check"/></svg></span>
                  <span class="ic" x-html="socialIcon(p)"></span><span class="pm-n" x-text="platName(p)"></span><span class="pm-x" x-show="usedBy(-1).includes(p)">{{ __('Added') }}</span>
                </button>
              </template>
            </div>
          </li>
        </ul>
        <button class="btn btn-2" type="button" x-show="!linksFull && !showNewRow" x-cloak @click="showNew = true; focusLink('new')" style="margin-top:var(--s3)"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg><span>{{ __('Add another link') }}</span></button>
        <p class="help" x-show="linksFull" x-cloak>{{ __('You\'ve added nine. Remove one to add another.') }}</p>
        <p class="help err" x-show="socialError" x-cloak x-text="socialError"></p>
      </div>
    </div>
  </section>

  {{-- ═══ Skills — the order of the profile tabs. 13 Nov 2026 review: no reordering (drag and arrows
       are gone); one primary skill, which opens first. "Make primary" moves a skill to the front
       (is_primary stays derived from position 0 — SkillsService::syncPrimaryFromOrder). ═══ --}}
  <section class="sec panel" id="skills" :class="{ changed: dirty.skills }">
    <div class="phead">
      <div class="ptitle"><div class="hrow"><h2 class="h3x">{{ __('Skills') }}</h2><span class="dirty" x-show="dirty.skills" x-cloak>{{ __('Unsaved') }}</span></div>
        <p class="pdesc">{{ __('Each skill is a tab on your profile. Your primary skill opens first.') }}</p></div>
      <button class="btn btn-2" type="button" id="addSkill" @click="addSkill()" :disabled="!canAddSkill"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg><span>{{ __('Add skill') }}</span></button>
    </div>
    <div id="skillList">
      <template x-for="(s, i) in skills" :key="s.id">
        <div class="sk skill" :data-s="s.id" :class="{ 'is-new': s.id === newSkillId }">
          <div class="shead">
            <div class="stitle"><b><span x-text="skName(s)"></span><span class="order" x-show="i === 0">{{ __('Primary') }}</span></b></div>
            <span class="skacts">
              <button type="button" class="btn btn-2 skprim" x-show="i > 0" @click="makePrimary(s)"
                      :aria-label="@js(__('Make :name your primary skill')).replace(':name', skName(s))"><svg class="icon" aria-hidden="true"><use href="#i-star"/></svg><span>{{ __('Make primary') }}</span></button>
              <button type="button" class="btn btn-2 skrm" @click="removeSkill(s)" :disabled="skills.length < 2"
                      :aria-label="@js(__('Remove :name')).replace(':name', skName(s))"><svg class="icon" aria-hidden="true"><use href="#i-trash"/></svg><span>{{ __('Remove') }}</span></button>
            </span>
          </div>
          <div class="sbody">
            <p class="specs-l" :id="'sl-' + s.id">{{ __('Specialities') }}</p>
            <div class="specs" role="group" :aria-labelledby="'sl-' + s.id">
              <template x-for="sp in specialityOptions(s.id)" :key="sp.id">
                <button type="button" class="chip" :aria-pressed="specialityPicked(s.id, sp.id).toString()" @click="toggleSpeciality(s.id, sp.id)" x-text="tr(sp.name) || sp.slug"></button>
              </template>
            </div>
            {{-- About your practice (ADR-AG): the skill's custom fields, true of every job. Values live
                 once per profile, so a field two skills share is the same field in both. --}}
            <template x-if="fxFor(s).length">
              <details class="fxd" open>
                <summary><svg class="icon" aria-hidden="true"><use href="#i-chev"/></svg>{{ __('About your practice') }} <small x-text="fxCount(s)"></small></summary>
                <div class="fx-fields" :data-s="s.slug" x-init="mountFields($el, s)"></div>
              </details>
            </template>
          </div>
        </div>
      </template>
      <p class="empty" x-show="!skills.length" x-cloak style="padding-inline:var(--s6)">{{ __('Add your first skill to create its tab and sections.') }}</p>
    </div>
  </section>

  {{-- ═══ Sections — the blocks on each tab (app feature; built from the
       prototype's own section-row pattern: name → content, Move to, Visible). ═══ --}}
  <section class="sec panel" id="sections" :class="{ changed: dirty.sections }">
    <div class="phead">
      <div class="ptitle"><div class="hrow"><h2 class="h3x">{{ __('Sections') }}</h2><span class="dirty" x-show="dirty.sections" x-cloak>{{ __('Unsaved') }}</span></div>
        <p class="pdesc">{{ __('What shows on each tab of your profile. Choose a section to fill it in, drag to reorder, or switch it off to hide it. Hidden sections keep their content.') }}</p></div>
    </div>
    <div class="pbody">
      {{-- No "Before / After your work" control: every profile shows its profile-wide
           sections after the skill tabs (prototype place:'below'). --}}
      <template x-for="g in scopeGroups" :key="g.key">
        <div class="group">
          <h3><button type="button" class="ghead" @click="toggleGroup(g.key)" :aria-expanded="(!!openGroups[g.key]).toString()">
            <span x-text="g.label"></span>
            <span class="meta" x-text="g.typeId === null ? @js(__('Shows on every tab')) : (blocksInScope(g.typeId).length === 1 ? @js(__('1 section')) : @js(__(':n sections')).replace(':n', blocksInScope(g.typeId).length))"></span>
            <svg class="icon" aria-hidden="true"><use href="#i-chev"/></svg>
          </button></h3>
          <ul class="rows" x-show="openGroups[g.key]" :class="{ 'drop-ok': dropOn === g.key && !dropDeny, 'drop-no': dropOn === g.key && dropDeny }"
              @dragover.prevent="groupOver(g)" @dragleave.self="dropOn = null" @drop.prevent="groupDrop(g)">
            <template x-for="b in blocksInScope(g.typeId)" :key="b.id">
              <li class="row" :class="{ off: !b.is_visible, dragging: dragBlock === b.id }" @drop.prevent.stop="rowDrop(b)">
                <span class="rname">
                  <button type="button" class="grip" draggable="true" @dragstart="rowStart($event, b)" @dragend="rowEnd()"
                          :aria-label="@js(__('Drag to reorder :name')).replace(':name', blockName(b))"><svg class="icon" aria-hidden="true"><use href="#i-grip"/></svg></button>
                  <template x-if="hasContentEditor(b)">
                    <a class="name" :href="contentUrl(b)"><span x-text="blockName(b)"></span><svg class="icon" aria-hidden="true"><use href="#i-pen"/></svg></a>
                  </template>
                  <template x-if="!hasContentEditor(b)">
                    <span class="name" x-text="blockName(b)"></span>
                  </template>
                  <span class="tag tag-hidden" x-show="!b.is_visible">{{ __('Hidden') }}</span>
                </span>
                <span class="rmeta">
                  <select class="sel" x-show="moveTargets(b).length" @change="moveBlock(b, $event.target.value); $event.target.value = ''"
                          :aria-label="@js(__('Move :name to another tab')).replace(':name', blockName(b))">
                    <option value="">{{ __('Move to…') }}</option>
                    <template x-for="t in moveTargets(b)" :key="t.key"><option :value="t.typeId === null ? 'u' : t.typeId" x-text="t.label"></option></template>
                  </select>
                </span>
                <button type="button" class="switch" role="switch" :aria-checked="b.is_visible.toString()" @click="toggleVisible(b)"
                        :aria-label="@js(__('Show :name on your profile')).replace(':name', blockName(b))"><i></i></button>
              </li>
            </template>
            <li class="empty" x-show="!blocksInScope(g.typeId).length" x-text="g.isNew ? @js(__('Its sections appear once you save.')) : @js(__('No sections here. Drag one in, or use Move to.'))"></li>
          </ul>
        </div>
      </template>
    </div>
  </section>

  {{-- ═══ Willing to explore ═══ --}}
  <section class="sec panel" id="explore" :class="{ 'is-off': uniBlock('interests') && !uniBlock('interests').is_visible, changed: dirty.explore }">
    <div class="phead">
      <div class="ptitle"><div class="hrow"><h2 class="h3x">{{ __('Willing to explore') }}</h2><span class="tag tag-hidden hidtag" x-show="uniBlock('interests') && !uniBlock('interests').is_visible" x-cloak>{{ __('Hidden from your profile') }}</span><span class="dirty" x-show="dirty.explore" x-cloak>{{ __('Unsaved') }}</span></div>
        <p class="pdesc">{{ __('Skills you\'d like to be asked about, even ones you don\'t hold yet. Up to six.') }}</p></div>
      <template x-if="uniBlock('interests')">
        <label class="showsw"><span>{{ __('Show on profile') }}</span><button type="button" class="switch" role="switch" :aria-checked="uniBlock('interests').is_visible.toString()" @click="toggleVisible(uniBlock('interests'))" aria-label="{{ __('Show on profile') }}: {{ __('Willing to explore') }}"><i></i></button></label>
      </template>
    </div>
    <div class="pbody" id="body-explore">
      <div class="pin-card">
        <ul class="wte" id="wte">
          <template x-for="(w, i) in interests" :key="w.id">
            <li>
              <div><span class="lbl" :id="'wl-' + i">{{ __('Skill') }}</span><button type="button" class="sel pickbtn" @click="pickInterestSkill(w)" aria-haspopup="dialog" :aria-labelledby="'wl-' + i + ' wp-' + i"><span :id="'wp-' + i" x-text="allSkillName(w.talent_type_id)"></span><svg class="icon" aria-hidden="true"><use href="#i-search"/></svg></button></div>
              <label><span class="lbl"><span>{{ __('Speciality') }}</span><small>{{ __('Optional') }}</small></span>
                <select class="sel" @change="w.speciality_id = $event.target.value ? Number($event.target.value) : null">
                  <option value="">{{ __('Any speciality') }}</option>
                  <template x-for="sp in skillSpecs(w.talent_type_id)" :key="sp.id"><option :value="sp.id" :selected="sp.id === w.speciality_id" x-text="sp.name"></option></template>
                </select></label>
              <button type="button" class="iconbtn" @click="removeInterest(w)" aria-label="{{ __('Remove this entry') }}" style="margin-top:34px"><svg class="icon" aria-hidden="true"><use href="#i-trash"/></svg></button>
              <label class="note"><span class="lbl"><span>{{ __('Note') }}</span><small x-text="(w.note || '').length + ' / 160'"></small></span>
                <input class="in" maxlength="160" x-model="w.note" placeholder="{{ __('What you\'d like to try, in a line') }}"></label>
            </li>
          </template>
        </ul>
        <button class="btn btn-2" type="button" @click="addInterest()" :disabled="interests.length >= 6" style="margin-top:var(--s4)"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg><span>{{ __('Add another') }}</span></button>
      </div>
    </div>
  </section>

  {{-- ═══ Experience — LinkedIn-style roles (ADR-AK, prototype FAMA_EXP): title, company, employment,
       place, dates, highlights, skills. Part of the draft; the dialog only edits the draft. ═══ --}}
  <section class="sec panel" id="brands" :class="{ 'is-off': uniBlock('brand_collabs') && !uniBlock('brand_collabs').is_visible, changed: dirty.brands }">
    <div class="phead">
      <div class="ptitle"><div class="hrow"><h2 class="h3x">{{ __('Experience') }}</h2><span class="tag tag-hidden hidtag" x-show="uniBlock('brand_collabs') && !uniBlock('brand_collabs').is_visible" x-cloak>{{ __('Hidden from your profile') }}</span><span class="dirty" x-show="dirty.brands" x-cloak>{{ __('Unsaved') }}</span></div>
        <p class="pdesc">{{ __('Your roles at companies and brands, newest first. It shows on your profile after your work.') }}</p></div>
      <template x-if="uniBlock('brand_collabs')">
        <label class="showsw"><span>{{ __('Show on profile') }}</span><button type="button" class="switch" role="switch" :aria-checked="uniBlock('brand_collabs').is_visible.toString()" @click="toggleVisible(uniBlock('brand_collabs'))" aria-label="{{ __('Show on profile') }}: {{ __('Experience') }}"><i></i></button></label>
      </template>
      <button class="btn btn-2" type="button" id="addBrand" @click="openRole(null)"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg><span>{{ __('Add role') }}</span></button>
    </div>
    <div class="pbody" id="body-brands">
      <div id="exp" @click="onExpClick($event)">
        <div x-show="roles.some((r) => r.n || r.t)" x-html="xpHtml()"></div>
        <p class="empty" x-show="!roles.some((r) => r.n || r.t)">{{ __('Nothing here yet. Add the companies and brands you\'ve worked with, and your roles there.') }}</p>
      </div>
    </div>
  </section>

  <dialog id="dlgRole" x-ref="roleDlg" aria-labelledby="roleT" @close="$nextTick(() => (roleAt == null ? document.getElementById('addBrand') : document.querySelector(`#exp [data-edit-role='${roleAt}']`))?.focus())">
    <form method="dialog" class="dlg role" novalidate @submit.prevent="saveRole()">
      <div class="role-h"><h2 id="roleT" x-text="roleAt == null ? @js(__('Add a role')) : @js(__('Edit role'))"></h2><button type="button" class="iconbtn plain" @click="$refs.roleDlg.close()" aria-label="{{ __('Close') }}"><svg class="icon"><use href="#i-x"/></svg></button></div>
      <div class="role-b">
        <div class="two"><label class="f" :class="{ bad: roleErrors.t }"><span>{{ __('Job title') }}</span><input class="in" x-ref="roleTitle" x-model="roleForm.t" maxlength="80" placeholder="{{ __('Lead stylist') }}"><p class="help err" x-show="roleErrors.t" x-cloak>{{ __('Add this to save the role.') }}</p></label>
          <label class="f" :class="{ bad: roleErrors.n }"><span>{{ __('Company or brand') }}</span><input class="in" x-ref="roleOrg" x-model="roleForm.n" maxlength="80" placeholder="Maison Nour"><p class="help err" x-show="roleErrors.n" x-cloak>{{ __('Add this to save the role.') }}</p></label></div>
        <div class="three"><label class="f"><span>{{ __('Employment type') }}</span><select class="sel" x-model="roleForm.et"><option value="">{{ __('Please select') }}</option><template x-for="o in xpEmployment" :key="o"><option :value="o" x-text="o" :selected="roleForm.et === o"></option></template></select></label>
          <label class="f"><span>{{ __('Location') }}</span><input class="in" x-model="roleForm.loc" maxlength="60" placeholder="{{ __('City') }}"></label>
          <label class="f"><span>{{ __('Location type') }}</span><select class="sel" x-model="roleForm.lt"><option value="">{{ __('Please select') }}</option><template x-for="o in xpLocationTypes" :key="o"><option :value="o" x-text="o" :selected="roleForm.lt === o"></option></template></select></label></div>
        <label class="chk"><input type="checkbox" x-model="roleForm.cur"><span>{{ __('I currently work here') }}</span></label>
        <div class="two"><div class="f"><span class="lbl">{{ __('Start date') }}</span><div class="my">
            <select class="sel" x-model.number="roleForm.sm" aria-label="{{ __('Start month') }}"><option value="0">{{ __('Month') }}</option><template x-for="(m, j) in xpMonths" :key="m"><option :value="j + 1" x-text="m" :selected="+roleForm.sm === j + 1"></option></template></select>
            <select class="sel" x-ref="roleSy" x-model.number="roleForm.sy" aria-label="{{ __('Start year') }}"><option value="0">{{ __('Year') }}</option><template x-for="y in roleYears" :key="y"><option :value="y" x-text="y" :selected="+roleForm.sy === y"></option></template></select></div>
            <p class="help err" x-show="roleErrors.start" x-cloak x-text="roleErrors.start"></p></div>
          <div class="f" x-show="!roleForm.cur"><span class="lbl">{{ __('End date') }}</span><div class="my">
            <select class="sel" x-model.number="roleForm.em" aria-label="{{ __('End month') }}"><option value="0">{{ __('Month') }}</option><template x-for="(m, j) in xpMonths" :key="m"><option :value="j + 1" x-text="m" :selected="+roleForm.em === j + 1"></option></template></select>
            <select class="sel" x-model.number="roleForm.ey" aria-label="{{ __('End year') }}"><option value="0">{{ __('Year') }}</option><template x-for="y in roleYears" :key="y"><option :value="y" x-text="y" :selected="+roleForm.ey === y"></option></template></select></div></div></div>
        <label class="f"><span><span>{{ __('Highlights') }}</span><small x-text="`${(roleForm.hl || '').length.toLocaleString('en')} / 2,000`"></small></span><textarea class="ta" x-model="roleForm.hl" maxlength="2000"></textarea><p class="help">{{ __('One per line: projects, problems you solved, or results you got.') }}</p></label>
        <div class="f"><span class="lbl" id="rSkL">{{ __('Skills') }}</span><p class="help" style="margin:0 0 var(--s2)">{{ __('Which of your skills this role used.') }}</p>
          <div class="specs" role="group" aria-labelledby="rSkL"><template x-for="s in skills" :key="s.id"><button type="button" class="chip" :aria-pressed="(roleForm.sk || []).includes(s.id).toString()" @click="toggleRoleSkill(s.id)" x-text="skName(s)"></button></template></div></div>
      </div>
      <div class="role-f"><button type="button" class="btn btn-text del" x-show="roleAt != null" @click="deleteRole()">{{ __('Delete role') }}</button><span></span><button type="button" class="btn btn-2" @click="$refs.roleDlg.close()">{{ __('Cancel') }}</button><button type="submit" class="btn" x-text="roleAt == null ? @js(__('Add role')) : @js(__('Update role'))"></button></div>
    </form>
  </dialog>

  {{-- ═══ Pricing rate (ADR-N; hidden in V1 — no prototype design, same panel pattern) ═══ --}}
  @feature('pricing')
  <section class="sec panel" id="pricing" :class="{ changed: dirty.pricing }">
    <div class="phead">
      <div class="ptitle"><div class="hrow"><h2 class="h3x">{{ __('Pricing rate') }}</h2><span class="dirty" x-show="dirty.pricing" x-cloak>{{ __('Unsaved') }}</span></div>
        <p class="pdesc">{{ __('An indicative rate shown on your profile. Fill all three, or leave them blank to hide it.') }}</p></div>
    </div>
    <div class="pbody">
      <div class="three">
        <label class="f" :class="{ bad: rateErrors.rate_amount }"><span>{{ __('Amount') }}</span><input class="in" type="number" min="0" step="0.01" x-model="rate.rate_amount"><p class="help err" x-show="rateErrors.rate_amount" x-cloak x-text="rateErrors.rate_amount?.[0]"></p></label>
        <label class="f" :class="{ bad: rateErrors.rate_currency }"><span>{{ __('Currency') }}</span><input class="in" maxlength="3" placeholder="EGP" x-model="rate.rate_currency"><p class="help err" x-show="rateErrors.rate_currency" x-cloak x-text="rateErrors.rate_currency?.[0]"></p></label>
        <label class="f" :class="{ bad: rateErrors.rate_unit }"><span>{{ __('Per') }}</span>
          <select class="sel" x-model="rate.rate_unit"><option value="">{{ __('Choose…') }}</option><option value="project">{{ __('Project') }}</option><option value="day">{{ __('Day') }}</option><option value="hour">{{ __('Hour') }}</option></select>
          <p class="help err" x-show="rateErrors.rate_unit" x-cloak x-text="rateErrors.rate_unit?.[0]"></p></label>
      </div>
      <div class="pdone"><button type="button" class="btn btn-text" @click="rate = { rate_unit: '', rate_amount: '', rate_currency: '' }">{{ __('Clear rate') }}</button></div>
    </div>
  </section>
  @endfeature

</div>
</main>

{{-- 24 Oct 2026: the bar only shows while there is something to save, and for a moment after a save;
     ringed yellow when saving the draft would take the profile out of Live. --}}
<div class="savebar is-away" id="savebar" :class="{ dirty: dirtyCount > 0, bad: dirtyCount > 0 && badField, risk: atRisk, 'is-away': !barShown }" :aria-hidden="(!barShown).toString()">
  <div class="in-bar">
    <p class="msg" role="status" aria-live="polite"><span class="dot" aria-hidden="true"></span><span><span id="sbTitle" x-text="sbTitle">{{ __('All changes saved') }}</span><small x-text="sbNote">{{ __('Your profile is up to date.') }}</small></span></p>
    <div class="acts" x-show="dirtyCount > 0" x-cloak>
      <button type="button" class="btn btn-2" id="sbDiscard" @click="discardAll()">{{ __('Discard') }}</button>
      <button type="button" class="btn btn-save" id="sbSave" @click="saveAll()" :disabled="saving" x-text="saving ? @js(__('Saving…')) : (narrow ? @js(__('Save')) : @js(__('Save changes')))">{{ __('Save changes') }}</button>
    </div>
  </div>
</div>

{{-- One skill picker for the page: add a skill, or choose one to explore. --}}
<dialog id="dlgAdd" x-ref="dlgAdd" @close="picker.resolve && closePicker(null)">
  <form method="dialog" class="dlg" @submit.prevent="closePicker(currentPick)">
    <h2 x-text="picker.title"></h2>
    <p x-text="picker.help"></p>
    <input class="in" type="search" style="margin-top:var(--s4)" placeholder="{{ __('Search skills or specialities') }}" x-ref="pickSearch" x-model="picker.q">
    <ul class="picklist">
      <template x-for="o in pickerList" :key="o.id">
        <li><label><input type="radio" name="newskill" :value="o.id" :checked="o.id === currentPick" @change="picker.current = o.id"><span><span x-text="o.name"></span><small class="via" x-show="o.via" x-text="o.via"></small></span></label></li>
      </template>
      <li class="empty" style="padding:var(--s4)" x-show="!pickerList.length">{{ __('No skill matches that. Try another word.') }}</li>
    </ul>
    <div class="acts">
      <button type="button" class="btn btn-2" @click="closePicker(null)">{{ __('Cancel') }}</button>
      <button type="submit" class="btn" :disabled="!pickerList.length" x-text="picker.ok"></button>
    </div>
  </form>
</dialog>
</div>

    <x-slot:scripts>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var hero = document.querySelector('.hero.soft');
                if (hero && window.famaStar) window.famaStar(hero, hero.querySelector('.body'), @js(asset('fama-ui/img/banners/profile-structure.jpg')), { text: 470 });
            });
        </script>
    </x-slot:scripts>
</x-fama.layout>
