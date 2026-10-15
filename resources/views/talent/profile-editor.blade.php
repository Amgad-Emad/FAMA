{{--
    Profile structure (GET /talent/profile) — the fama-ui "profile-structure.html"
    screen on real data. The old dashboard is folded in (completion steps while
    Private + the views card). EVERY edit — identity, availability, links,
    skills (order = tab order; the first opens first), specialities, sections,
    willing to explore, pricing — collects in a draft; the bottom bar saves it
    through the existing endpoints or discards it (toast with Undo), and leaving
    with unsaved changes warns. Only the photo uploads immediately. A Private
    profile shows no link anywhere on this page.
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
    $publish = ['is_published' => (bool) $talent->is_published, 'status' => $talent->status->getValue(), 'is_verified' => (bool) $talent->is_verified];
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

    $steps = [
        // `focus` = the field an Edit/Add link glides to on this page.
        ['key' => 'photo', 'label' => __('Profile photo'), 'help' => __('A clear photo of you. Brands are looking for a person.'), 'href' => '#core', 'focus' => 'fPhoto'],
        ['key' => 'name', 'label' => __('Display name'), 'help' => __('The name brands see in search results.'), 'href' => '#core', 'focus' => 'fDisplay'],
        ['key' => 'place', 'label' => __('City and country'), 'help' => __('Brands filter by where you are.'), 'href' => '#core', 'focus' => 'fCountry'],
        ['key' => 'bio', 'label' => __('A short bio'), 'help' => __('A line or two about your work.'), 'href' => '#core', 'focus' => 'fBio'],
        ['key' => 'skill', 'label' => __('A skill and a speciality'), 'help' => __('What brands search for.'), 'href' => '#skills'],
        ['key' => 'project', 'label' => __('One project'), 'help' => __('At least one project, with its photos or video.'), 'href' => route('talent.projects')],
    ];

    $strings = [
        'steps' => $steps,
        'sbClean' => __('All changes saved'), 'sbCleanN' => __('Your profile is up to date.'), 'sbSavedNow' => __('Saved just now'),
        'sbDirtyOne' => __('You have unsaved changes in 1 part'), 'sbDirtyMany' => __('You have unsaved changes in :n parts'),
        'sbDirtyN' => __('Save to update your profile, or discard to undo them.'),
        'sbBad' => __('Fix the highlighted field before saving'), 'sbBadN' => __('Your profile needs a display name and a valid username.'),
        'tSaved' => __('Changes saved'), 'saveFailed' => __('Some changes couldn’t be saved. Try again.'), 'tPhoto' => __('Profile photo updated'), 'tDiscarded' => __('Changes discarded'), 'tRemoved' => __(':name removed'), 'tAdded' => __(':name added'),
        'tMoved' => __(':name moved to :group'), 'tLive' => __('Your profile is Live'), 'tPrivate' => __('Your profile is Private'),
        'photo' => __('Profile photo'), 'photoType' => __('Use a JPG, PNG or WebP photo.'), 'photoBig' => __('That photo is over 5 MB. Choose a smaller one.'),
        'photoRmTitle' => __('Remove profile photo?'), 'photoRmHelp' => __('Your photo will be removed and replaced with your initials.'),
        'uploadFailed' => __('Upload failed'), 'remove' => __('Remove'),
        'copyOffN' => __('Complete your profile to go Live. Then you can share your link.'), 'copiedA' => __('Link copied to the clipboard'),
        'privTitle' => __('Make your profile Private?'), 'privHelp' => __('Only you will be able to see it, and your link stops working for everyone else until you go Live again.'), 'privOk' => __('Make private'),
        'travelOn' => __('Brands see you’ll take jobs outside :city.'), 'travelOff' => __('Brands see only that you’re based in :city.'), 'yourCity' => __('your city'),
        'website' => __('Website'), 'other' => __('Other'),
        'skMoved' => __(':name is now tab :n'),
        'addTitle' => __('Add a skill'), 'addHelp' => __('It gets its own tab, with default sections you can change.'), 'addOk' => __('Add skill'),
        'rmTitle' => __('Remove :name?'), 'rmHelp' => __('Its tab and sections come off your profile. The content inside them is kept, so you can add the skill back later.'), 'rmOk' => __('Remove skill'),
        'pw' => __('Profile-wide'), 'offA' => __(':name is hidden from your profile. It keeps its content.'),
        'cantMove' => __('That section can’t go there.'),
        'wtePickT' => __('Choose a skill to explore'), 'wtePickH' => __('Any skill, including one you have no work in yet.'), 'wtePickOk' => __('Choose skill'),
    ];
    $viewStrings = ['sum' => __(':n views in this period'), 'nViews' => __(':n views')];
    $brandBlock = $talent->profileBlocks->first(fn ($b) => $b->blockType->key === 'brand_collabs');
    $brandsUrl = route('talent.content', ['section' => $brandBlock?->talent_type_id ? optional($talent->talentTypes->firstWhere('id', $brandBlock->talent_type_id))->slug ?? 'universal' : 'universal']).'#block-brand_collabs';
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
        universalPosition: {{ (int) ($talent->meta['universal_position'] ?? 0) }},
        interests: @js($interests),
        collabs: @js($collabs),
        hasPricing: {{ \App\Support\Features::enabled('pricing') ? 'true' : 'false' }},
        rate: @js($rate),
        reviewsOn: {{ \App\Support\Features::enabled('reviews') ? 'true' : 'false' }},
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
      {{-- Live only: a Private profile shows no link at all — just its status. --}}
      <template x-if="isPublished">
        <button type="button" class="linkbtn" :class="{ copied }" @click="copyLink()" aria-describedby="copyNote" :aria-label="@js(__('Copy your profile link')) + ', ' + linkLabel">
          <span id="pubState" dir="ltr" x-text="linkLabel"></span>
          <span class="lb-ic"><svg class="icon" aria-hidden="true"><use :href="copied ? '#i-check' : '#i-copy'" href="#i-copy"/></svg><span id="copyTx" x-text="copied ? @js(__('Copied')) : @js(__('Copy'))"></span></span>
        </button>
      </template>
      <span class="vh" id="copyNote" aria-live="polite" x-text="note"></span>
      <span class="stat">
        <span class="pill" :class="isPublished ? 'p-live' : 'p-off'" x-text="isPublished ? @js(__('Live')) : @js(__('Private'))">{{ $talent->is_published ? __('Live') : __('Private') }}</span>
        <button type="button" class="tipbtn" aria-describedby="pubTip" aria-label="{{ __('What Live and Private mean') }}"><svg class="icon" aria-hidden="true"><use href="#i-info"/></svg></button>
        <span class="tip" role="tooltip" id="pubTip">
          <p :class="{ cur: isPublished }"><b>{{ __('Live') }}</b> {{ __('Anyone can open your profile, and brands find you in the Creatives Index.') }}</p>
          <p :class="{ cur: !isPublished }"><b>{{ __('Private') }}</b> {{ __('Only you can see it. Complete your profile to go Live, then share your link.') }}</p>
        </span>
      </span>
      <button type="button" class="btn btn-text pub-act" x-show="isPublished" x-cloak @click="setPublished(false)" :disabled="publishing">{{ __('Make private') }}</button>
    </div>
  </div>
  <div class="band" role="presentation"></div>
</section>

{{-- The dashboard lives here now: the steps to go Live while Private, and the views. --}}
<section class="vw" id="gate" aria-label="{{ __('Creatives Index') }}" x-show="!isPublished" x-cloak>
  <div class="vw-head">
    <div>
      <span class="vw-label">{{ __('Creatives Index') }}</span>
      <h2 class="vw-h" x-text="stepsLeft === 0 ? @js(__('Ready to go Live')) : (stepsLeft === 1 ? @js(__('1 more step to go Live')) : @js(__(':n more steps to go Live')).replace(':n', stepsLeft))"></h2>
      <p class="vw-lead">{{ __('Only you can see your profile for now. Complete these and it goes Live: anyone can open your link, and brands find you in the Creatives Index.') }}</p>
    </div>
    <span class="vw-only"><svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg>{{ __('Only you see this') }}</span>
  </div>
  {{-- Every step done: the six rows fold into one card about the last step — Fama's check. --}}
  <div class="vw-check" :class="{ ok: isVerified }" x-show="allStepsDone && !showSteps" x-cloak>
    <span class="vw-check-ic" aria-hidden="true">
      <svg class="icon" x-show="!isVerified"><use href="#i-award"/></svg>
      <svg class="icon" x-show="isVerified" x-cloak><use href="#i-check"/></svg>
    </span>
    <div class="vw-check-txt">
      <span class="vw-check-k" x-text="@js(__('All :n steps done')).replace(':n', gateSteps.length)"></span>
      <h3>{{ __('Checked by Fama') }}</h3>
      <p x-text="isVerified ? @js(__('Your profile has been checked. Go Live whenever you’re ready.')) : @js(__('Our team reviews every new profile once. You can keep editing meanwhile.'))"></p>
      <button type="button" class="vw-link" @click="showSteps = true">{{ __('Review your steps') }}<svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></button>
    </div>
  </div>
  <ul class="vw-todo" x-show="!allStepsDone || showSteps">
    <template x-for="s in gateSteps" :key="s.key">
      <li :class="{ ok: s.done }">
        <span class="tick"><svg class="icon" aria-hidden="true" x-show="s.done"><use href="#i-check"/></svg></span>
        <span class="txt"><b x-text="s.label"></b><p x-show="!s.done" x-text="s.help"></p></span>
        <a class="vw-link" :href="s.href" @click="goStep($event, s)"><span x-text="s.done ? @js(__('Edit')) : @js(__('Add'))"></span><span class="vh" x-text="' ' + s.label"></span><svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></a>
      </li>
    </template>
    {{-- App step (not in the prototype): Fama verifies every profile once before it can go Live (ADR-U). --}}
    <li :class="{ ok: isVerified }">
      <span class="tick"><svg class="icon" aria-hidden="true" x-show="isVerified"><use href="#i-check"/></svg></span>
      <span class="txt"><b>{{ __('Checked by Fama') }}</b><p x-show="!isVerified">{{ __('Our team reviews every new profile once. You can keep editing meanwhile.') }}</p></span>
    </li>
  </ul>
  <div class="vw-go">
    <button type="button" class="btn" @click="setPublished(true)" :disabled="publishing || !isVerified">{{ __('Go Live') }}</button>
  </div>
  <p class="vw-err" x-show="publishError" x-text="publishError"></p>
  <p class="vw-note" x-text="isVerified ? @js(__('You choose when it goes Live. You can make it Private again at any time.')) : @js(__('Go Live unlocks once our team has checked your profile.'))"></p>
</section>

<section class="vw" id="views" aria-label="{{ __('Profile views') }}" x-data="profileViews({ strings: @js($viewStrings) })">
  <div class="vw-head">
    <div>
      <span class="vw-label">{{ __('Profile views · all time') }}</span>
      <span class="vw-n">{{ number_format($views['total']) }}</span>
      <span class="vw-d"><b>{{ $deltaText }}</b> {{ __('vs last week') }}</span>
    </div>
    <span class="vw-only"><svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg>{{ __('Only you see this') }}</span>
  </div>
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
      <div class="vw-bars">
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
          <label class="f" :class="{ bad: nameBad }">
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
          <div class="f" :class="{ bad: errors.base_city }"><span id="lCity">{{ __('City') }}</span>
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

  {{-- ═══ Skills — the order of the profile tabs; the first opens first (no separate primary) ═══ --}}
  <section class="sec panel" id="skills" :class="{ changed: dirty.skills }">
    <div class="phead">
      <div class="ptitle"><div class="hrow"><h2 class="h3x">{{ __('Skills') }}</h2><span class="dirty" x-show="dirty.skills" x-cloak>{{ __('Unsaved') }}</span></div>
        <p class="pdesc">{{ __('Each skill is a tab on your profile, in this order. The first one opens first. Drag to reorder.') }}</p></div>
      <button class="btn btn-2" type="button" id="addSkill" @click="addSkill()" :disabled="!canAddSkill"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg><span>{{ __('Add skill') }}</span></button>
    </div>
    <div id="skillList">
      <template x-for="(s, i) in skills" :key="s.id">
        <div class="sk skill" :data-s="s.id"
             :class="{ 'is-new': s.id === newSkillId, dragging: skDrag === s.id, 'drop-before': skDropOn === s.id && !skDropAfter, 'drop-after': skDropOn === s.id && skDropAfter }"
             @dragover.prevent="skOver($event, s)" @drop.prevent="skDrop(s)">
          <div class="shead">
            <button type="button" class="grip" draggable="true" data-grip @dragstart="skStart($event, s)" @dragend="skEnd()"
                    @keydown.up.prevent="moveSkill(s.id, i - 1)" @keydown.down.prevent="moveSkill(s.id, i + 1)"
                    :aria-label="@js(__('Reorder :name. Use the arrow keys, or drag.')).replace(':name', skName(s))"><svg class="icon" aria-hidden="true"><use href="#i-grip"/></svg></button>
            <div class="stitle"><b><span x-text="skName(s)"></span><span class="order" x-show="i === 0">{{ __('Opens first') }}</span></b></div>
            <span class="skacts">
              <button type="button" class="iconbtn mv" data-mv="-1" @click="moveSkill(s.id, i - 1, '[data-mv=&quot;-1&quot;]:not(:disabled)')" :disabled="i === 0"
                      :aria-label="@js(__('Move :name up')).replace(':name', skName(s))"><svg class="icon" aria-hidden="true"><use href="#i-up"/></svg></button>
              <button type="button" class="iconbtn mv" data-mv="1" @click="moveSkill(s.id, i + 1, '[data-mv=&quot;1&quot;]:not(:disabled)')" :disabled="i === skills.length - 1"
                      :aria-label="@js(__('Move :name down')).replace(':name', skName(s))"><svg class="icon" aria-hidden="true"><use href="#i-down"/></svg></button>
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
      <div class="place" x-show="blocksInScope(null).length" style="margin-top:0">
        <span class="lbl" style="margin:0">{{ __('Show profile-wide sections') }}</span>
        <div class="seg" role="group" aria-label="{{ __('Show profile-wide sections') }}">
          <button type="button" :aria-pressed="(universalPosition === 0).toString()" @click="setPlace(false)">{{ __('Before your work') }}</button>
          <button type="button" :aria-pressed="(universalPosition > 0).toString()" @click="setPlace(true)">{{ __('After your work') }}</button>
        </div>
      </div>
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

  {{-- ═══ Experience — the talent's brand collabs (the app has no roles model;
       see the report). Read here, edited on the profile-content page. ═══ --}}
  <section class="sec panel" id="brands" :class="{ 'is-off': uniBlock('brand_collabs') && !uniBlock('brand_collabs').is_visible, changed: dirty.brands }">
    <div class="phead">
      <div class="ptitle"><div class="hrow"><h2 class="h3x">{{ __('Experience') }}</h2><span class="tag tag-hidden hidtag" x-show="uniBlock('brand_collabs') && !uniBlock('brand_collabs').is_visible" x-cloak>{{ __('Hidden from your profile') }}</span><span class="dirty" x-show="dirty.brands" x-cloak>{{ __('Unsaved') }}</span></div>
        <p class="pdesc">{{ __('Brands you\'ve worked with, with the project and year. It shows on your profile after your work.') }}</p></div>
      <template x-if="uniBlock('brand_collabs')">
        <label class="showsw"><span>{{ __('Show on profile') }}</span><button type="button" class="switch" role="switch" :aria-checked="uniBlock('brand_collabs').is_visible.toString()" @click="toggleVisible(uniBlock('brand_collabs'))" aria-label="{{ __('Show on profile') }}: {{ __('Experience') }}"><i></i></button></label>
      </template>
      <a class="btn btn-2" href="{{ $brandsUrl }}"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg><span>{{ __('Add brand') }}</span></a>
    </div>
    <div class="pbody" id="body-brands">
      <div id="exp">
        <ol class="xp" x-show="collabs.length">
          <template x-for="c in collabs" :key="c.id">
            <li class="xp-g one">
              <span class="xp-logo" aria-hidden="true"><template x-if="c.logo"><img :src="c.logo" alt=""></template><template x-if="!c.logo"><span x-text="initialsOf(c.name)"></span></template></span>
              <div class="xp-b"><div class="xp-top"><div>
                <h3 class="xp-t" x-text="c.name"></h3>
                <p class="xp-m" x-show="c.project" x-text="c.project"></p>
                <p class="xp-d" x-show="c.year" x-text="c.year"></p>
              </div><a class="iconbtn plain xp-ed" href="{{ $brandsUrl }}" :aria-label="@js(__('Edit :name')).replace(':name', c.name)"><svg class="icon" aria-hidden="true"><use href="#i-pen"/></svg></a></div></div>
            </li>
          </template>
        </ol>
        <p class="empty" x-show="!collabs.length">{{ __('Nothing here yet. Add the companies and brands you\'ve worked with.') }}</p>
      </div>
    </div>
  </section>

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

<div class="savebar off" id="savebar" :class="{ dirty: dirtyCount > 0, bad: dirtyCount > 0 && badField, off: !barShown }" :aria-hidden="(!barShown).toString()">
  <div class="in-bar">
    <p class="msg" role="status" aria-live="polite"><span class="dot" aria-hidden="true"></span><span><span x-text="sbTitle">{{ __('All changes saved') }}</span><small x-text="sbNote">{{ __('Your profile is up to date.') }}</small></span></p>
    <div class="acts" x-show="dirtyCount > 0" x-cloak>
      <button type="button" class="btn btn-2" @click="discardAll()">{{ __('Discard') }}</button>
      <button type="button" class="btn btn-save" @click="saveAll()" :disabled="saving" x-text="saving ? @js(__('Saving…')) : @js(__('Save changes'))">{{ __('Save changes') }}</button>
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
