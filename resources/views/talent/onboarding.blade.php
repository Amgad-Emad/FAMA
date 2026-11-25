{{--
    The guided onboarding (GET /talent/welcome/{step?}, ADR-AR) — replaces the
    big Go Live / Stand out card that sat on Profile. Five screens, ordered for a
    COMPLETE portfolio rather than for the quickest Go Live:

      1 You         — display name · headline · country + city · photo (name, headline, place, photo)
      2 What you do — skills + the specialities inside each        (skill)
      3 Your story  — bio · point of view · links (every platform)  (bio, links; POV is optional)
      4 Your work   — projects: one = Creatives Index, three = a style (show, projects3)
      5 Go Live     — every step, ticked; Go Live / your link      (brands links out to Profile)

    The step map is ProfileCompletionService::ONBOARDING (THE home); ticks come from
    SAVED data (GET /talent/profile/state), never the typed draft. The page writes
    nothing of its own: identity → PATCH /talent/profile, photo → POST
    /talent/profile/avatar, skills → /talent/profile/skills*, links → PUT
    /talent/profile/social, Go Live → PATCH /talent/profile/publish. Projects open
    the real project editor (?from=welcome — its way back returns here).
    Switching screens never reloads (history.pushState; back/forward work).
    A live preview card ("How brands see you") fills in beside the form.

    Behaviour: `famaOnboarding` (resources/js/ui/onboarding.js).
--}}
@php
    $screenMeta = [
        'you' => ['n' => 1, 'label' => __('You')],
        'craft' => ['n' => 2, 'label' => __('What you do')],
        'story' => ['n' => 3, 'label' => __('Your story')],
        'work' => ['n' => 4, 'label' => __('Your work')],
        'live' => ['n' => 5, 'label' => __('Go Live')],
    ];
    // Every step's label, in onboarding order (the Go Live screen's checklist + the preview meter).
    $stepLabels = [
        'name' => __('Display name'), 'place' => __('Your city'), 'photo' => __('A profile photo'),
        'skill' => __('A skill and a speciality'),
        'show' => __('Your first project'), 'projects3' => __('Three projects'),
        'headline' => __('A headline'), 'bio' => __('A short bio'), 'links' => __('Your social links or website'),
        'brands' => __('Experience'),
    ];
    $core = [
        'display_name' => (string) $talent->display_name,
        'base_city' => (string) $talent->base_city,
        'base_country' => (string) $talent->base_country,
        'headline' => (string) $talent->getTranslation('headline', 'en', false),
        'bio' => (string) $talent->getTranslation('bio', 'en', false),
        'worldview' => (string) $talent->getTranslation('worldview', 'en', false),
    ];
    $strings = [
        'saved' => __('Saved'),
        'saveFailed' => __('Couldn’t save that. Try again.'),
        'photoType' => __('Use a JPG, PNG or WebP photo.'),
        'photoBig' => __('That photo is over 5 MB. Choose a smaller one.'),
        'photoAdded' => __('Profile photo added'),
        'photoFailed' => __('Your photo didn’t upload. Check your connection and try again.'),
        'photoRmTitle' => __('Remove profile photo?'),
        'photoRmHelp' => __('Your photo will be removed and replaced with your initials.'),
        'remove' => __('Remove'),
        'skillAdded' => __(':name added'),
        'skillRmTitle' => __('Remove :name?'),
        'skillRmHelp' => __('Its tab comes off your profile. Projects you tagged with it keep their other skills.'),
        'needName' => __('Add your display name to carry on.'),
        'needCity' => __('Add your city to carry on.'),
        'needSkill' => __('Pick a skill and at least one speciality.'),
        'skip' => __('Skip for now'),
        'continue' => __('Continue'),
        'finish' => __('Go to your profile'),
        'live' => __('Your profile is Live'),
        'copied' => __('Link copied'),
        'nowSaved' => __('Saved'),
        'website' => __('Website'),
        'stDraft' => __('Draft'), 'stPrivate' => __('Private'), 'stLive' => __('Live'),
        // The Go Live screen (by stage, ADR-AQ) and the preview meter.
        'tDraft' => __('Almost there'), 'tPrivate' => __('Ready when you are'),
        'tLive' => __('You’re Live'), 'tLiveListed' => __('You’re Live, and in the Creatives Index'),
        'lDraft1' => __('One more step and you can go Live. Everything else makes you easier to pick.'),
        'lDraftN' => __(':n more steps and you can go Live. Everything else makes you easier to pick.'),
        'lPrivate' => __('Your profile is Private: only you can see it. Go Live and your link works for anyone you share it with.'),
        'lLive' => __('Anyone with your link can open your profile. Keep adding work: every project is another way brands find you.'),
        'mShow' => __('Your first project puts you in the Creatives Index.'),
        'mThree' => __('Three projects show a style.'),
        'mDone' => __('Complete. Nice work.'),
        'mMore' => __('Every step makes you easier to pick.'),
    ];
    $onboardingUrl = route('talent.onboarding');
    $newProjectUrl = route('talent.projects.new', ['from' => 'welcome']);
    $host = parse_url(url('/'), PHP_URL_HOST) ?: 'fama.so';
@endphp

<x-fama.layout :title="__('Set up your profile')" mode="bare" :footer="false" :css="['fields']">
    <x-slot:styles>
        <link rel="stylesheet" href="{{ \App\Support\FamaAsset::url('fama-ui/css/pages/onboarding.css') }}">
    </x-slot:styles>

<div class="ob" x-data="famaOnboarding({
        step: @js($step),
        order: @js(array_keys($screens)),
        screens: @js($screens),
        state: @js($state),
        core: @js($core),
        avatarUrl: @js($talent->getFirstMedia('avatar') ? $talent->avatar_url : null),
        skills: @js($skills),
        projects: @js($projects),
        projectCount: {{ (int) $projectCount }},
        social: @js($social),
        countries: @js($countries),
        preview: @js($preview),
        slug: @js((string) $talent->slug),
        host: @js($host),
        baseUrl: @js($onboardingUrl),
        strings: @js($strings),
     })">

  <header class="ob-top">
    <a class="ob-logo" href="{{ route('talent.profile.edit') }}" aria-label="{{ __('Fama — your profile') }}"><img src="{{ asset('fama-ui/img/fama-logo-tight.svg') }}" alt=""></a>
    <nav class="ob-rail" aria-label="{{ __('Setup steps') }}">
      <ol>
        @foreach ($screenMeta as $key => $meta)
          <li>
            <a href="{{ $onboardingUrl.'/'.$key }}" @click.prevent="goTo(@js($key))"
               :aria-current="step === @js($key) ? 'step' : null"
               :class="{ done: screenDone(@js($key)), now: step === @js($key) }">
              <span class="ob-rn" aria-hidden="true"><svg class="icon sm" x-show="screenDone(@js($key))" x-cloak><use href="#i-check"/></svg><span x-show="!screenDone(@js($key))">{{ $meta['n'] }}</span></span>
              <span class="ob-rl">{{ $meta['label'] }}</span>
              <span class="vh" x-show="screenDone(@js($key))">{{ __(', done') }}</span>
            </a>
          </li>
        @endforeach
      </ol>
    </nav>
    <a class="ob-later" href="{{ route('talent.profile.edit') }}">{{ __('Finish later') }}</a>
  </header>

  <div class="ob-body">
    <main class="ob-main" id="obMain" tabindex="-1">

      {{-- ============ 1 · You ============ --}}
      <section class="ob-step" x-show="step === 'you'" @if ($step !== 'you') x-cloak @endif aria-labelledby="obH-you">
        <p class="ob-kick">{{ __('Step :n of 5', ['n' => 1]) }}</p>
        <h1 class="ob-h" id="obH-you">{{ __('Let’s start with you') }}</h1>
        <p class="ob-lead">{{ __('Your name, the line under it and your city are what brands search by. A photo gets your profile opened more often.') }}</p>

        <div class="ob-photo">
          <span class="ob-av" :class="{ has: avatarUrl }" :style="avatarUrl ? `background-image:url(${avatarUrl})` : ''" aria-hidden="true" x-text="avatarUrl ? '' : initials"></span>
          <div>
            <p class="ob-ptitle">{{ __('Profile photo') }} <small>{{ __('Recommended') }}</small></p>
            <p class="help">{{ __('A clear photo of you. JPG, PNG or WebP, up to 5 MB.') }}</p>
            <div class="ob-pact">
              <label class="btn btn-2" :class="{ 'is-busy': uploading }">
                <svg class="icon sm" aria-hidden="true"><use href="#i-upload"/></svg>
                <span x-text="avatarUrl ? @js(__('Change photo')) : @js(__('Upload a photo'))">{{ __('Upload a photo') }}</span>
                <input type="file" class="vh" accept="image/jpeg,image/png,image/webp" x-ref="avatarInput" @change="uploadAvatar($event.target.files)" :disabled="uploading">
              </label>
              <button type="button" class="btn btn-text" x-show="avatarUrl" x-cloak @click="removeAvatar()" :disabled="uploading">{{ __('Remove') }}</button>
            </div>
            <p class="err" x-show="avatarError" x-cloak x-text="avatarError" role="alert"></p>
          </div>
        </div>

        <div class="ob-fields">
          <label class="f" :class="{ bad: errors.display_name }">
            <span>{{ __('Display name') }} <small>{{ __('The name brands see first') }}</small></span>
            <input class="in" id="obName" type="text" maxlength="255" autocomplete="name" x-model="core.display_name" @input="errors.display_name = null" placeholder="{{ __('e.g. Lina Haddad') }}">
            <p class="err" x-show="errors.display_name" x-cloak x-text="errors.display_name"></p>
          </label>
          <label class="f" :class="{ bad: errors['headline.en'] }">
            <span>{{ __('Headline') }} <small x-text="count(core.headline, 255)"></small></span>
            <input class="in" type="text" maxlength="255" x-model="core.headline" placeholder="{{ __('e.g. Art director & stylist') }}">
            <p class="help">{{ __('One line under your name in search.') }}</p>
            <p class="err" x-show="errors['headline.en']" x-cloak x-text="errors['headline.en']"></p>
          </label>
          <div class="fgrid">
            <div class="f" :class="{ bad: errors.base_country }">
              <span id="obLCountry">{{ __('Country') }}</span>
              <x-fama.combo id="obCountry" model="core.base_country" items="countryOptions" :flags="true" icon="globe" aria-labelledby="obLCountry"
                            :placeholder="__('Choose a country')" :search-placeholder="__('Search countries')" @combo-pick="onCountryPicked($event.detail)" />
              <p class="err" x-show="errors.base_country" x-cloak x-text="errors.base_country"></p>
            </div>
            <div class="f" :class="{ bad: errors.base_city }">
              <span id="obLCity">{{ __('City') }}</span>
              <x-fama.combo id="obCity" model="core.base_city" items="cityOptions" :allow-custom="true" icon="pin" aria-labelledby="obLCity"
                            :placeholder="__('Choose a city')" :search-placeholder="__('Search or type a city')" />
              <p class="err" x-show="errors.base_city" x-cloak x-text="errors.base_city"></p>
            </div>
          </div>
          {{-- Availability (ADR-Z): information, never a gate. Saves on flip, like Profile. --}}
          <div class="ob-now">
            <div class="ob-tog">
              <div><b>{{ __('Open for work') }}</b><small>{{ __('Brands see a yellow “Open for work” tag and a green dot on your photo.') }}</small></div>
              <button type="button" class="switch" role="switch" :aria-checked="now.is_available.toString()" @click="toggleNow('is_available')" :disabled="nowBusy" aria-label="{{ __('Open for work') }}"><i></i></button>
            </div>
            <div class="ob-tog">
              <div><b>{{ __('Will travel') }}</b><small>{{ __('You take jobs outside your city.') }}</small></div>
              <button type="button" class="switch" role="switch" :aria-checked="now.will_travel.toString()" @click="toggleNow('will_travel')" :disabled="nowBusy" aria-label="{{ __('Will travel') }}"><i></i></button>
            </div>
          </div>
        </div>
      </section>

      {{-- ============ 2 · What you do ============ --}}
      <section class="ob-step" x-show="step === 'craft'" @if ($step !== 'craft') x-cloak @endif aria-labelledby="obH-craft">
        <p class="ob-kick">{{ __('Step :n of 5', ['n' => 2]) }}</p>
        <h1 class="ob-h" id="obH-craft">{{ __('What do you do?') }}</h1>
        <p class="ob-lead">{{ __('Pick your skills, then the kind of work inside each. Brands search by both. Each skill gets its own tab on your profile; the first one opens first.') }}</p>

        <div class="ob-mine" x-show="linked.length" x-cloak>
          <template x-for="(sk, i) in linked" :key="sk.id">
            <div class="ob-skill" :class="{ ok: picks(sk.id).length }">
              <div class="ob-skh">
                <span class="ob-ski" x-html="iconFor(sk.icon)" aria-hidden="true"></span>
                <b x-text="tr(sk.name)"></b>
                <span class="tag" x-show="i === 0">{{ __('Opens first') }}</span>
                <span class="ob-skc" x-text="picks(sk.id).length ? fmt(@js(__(':n picked')), { n: picks(sk.id).length }) : @js(__('Pick at least one'))"></span>
                <button type="button" class="iconbtn plain" @click="removeSkill(sk)" :aria-label="fmt(@js(__('Remove :name')), { name: tr(sk.name) })" :disabled="busy"><svg class="icon sm" aria-hidden="true"><use href="#i-x"/></svg></button>
              </div>
              <div class="chips" role="group" :aria-label="fmt(@js(__('Specialities in :name')), { name: tr(sk.name) })">
                <template x-for="op in options(sk.id)" :key="op.id">
                  <button type="button" class="chip" :aria-pressed="picks(sk.id).includes(op.id).toString()" @click="toggleSpeciality(sk.id, op.id)" x-text="tr(op.name)"></button>
                </template>
              </div>
            </div>
          </template>
        </div>

        <div class="ob-pick">
          <div class="ob-pickh">
            <p class="ob-ptitle" x-text="linked.length ? @js(__('Add another skill')) : @js(__('Choose your main skill'))">{{ __('Choose your main skill') }}</p>
            <label class="ob-search">
              <svg class="icon sm" aria-hidden="true"><use href="#i-search"/></svg>
              <span class="vh">{{ __('Search skills') }}</span>
              <input class="in" type="search" x-model="skillQuery" placeholder="{{ __('Search 41 skills') }}">
            </label>
          </div>
          <div class="ob-skgrid">
            <template x-for="sk in availableFiltered" :key="sk.id">
              <button type="button" class="ob-skb" @click="addSkill(sk)" :disabled="busy">
                <span class="ob-ski" x-html="iconFor(sk.icon)" aria-hidden="true"></span>
                <span x-text="tr(sk.name)"></span>
                <svg class="icon sm ob-plus" aria-hidden="true"><use href="#i-plus"/></svg>
              </button>
            </template>
            <p class="meta" x-show="!availableFiltered.length" x-cloak>{{ __('No skill matches that. Try a broader word.') }}</p>
          </div>
        </div>
      </section>

      {{-- ============ 3 · Your work ============ --}}
      <section class="ob-step" x-show="step === 'work'" @if ($step !== 'work') x-cloak @endif aria-labelledby="obH-work">
        <p class="ob-kick">{{ __('Step :n of 5', ['n' => $screenMeta['work']['n']]) }}</p>
        <h1 class="ob-h" id="obH-work">{{ __('Show your work') }}</h1>
        <p class="ob-lead">{{ __('Projects are the heart of a Fama profile. One puts you in the Creatives Index, where brands search. Three show a style.') }}</p>

        <div class="ob-goal" role="group" aria-label="{{ __('Projects added') }}">
          <template x-for="n in 3" :key="n">
            <span class="ob-gs" :class="{ on: projectCount >= n }">
              <i aria-hidden="true"></i>
              <span x-text="n === 1 ? @js(__('Creatives Index')) : (n === 3 ? @js(__('A style')) : '')"></span>
            </span>
          </template>
          <b class="ob-gn" x-text="fmt(@js(__(':n of 3')), { n: Math.min(projectCount, 3) })"></b>
        </div>

        <div class="ob-projects">
          <template x-for="p in projects" :key="p.id">
            <a class="ob-proj" :href="p.edit">
              <span class="ob-pimg" :style="p.image ? `background-image:url(${p.image})` : ''" aria-hidden="true"><span x-show="!p.image" x-text="(p.title || '?').slice(0, 1)"></span></span>
              <span class="ob-pt" x-text="p.title"></span>
              <span class="ob-pe">{{ __('Edit') }} <svg class="icon sm flip" aria-hidden="true"><use href="#i-arrow"/></svg></span>
            </a>
          </template>
          <template x-for="n in emptySlots" :key="'e' + n">
            <a class="ob-proj ob-add" href="{{ $newProjectUrl }}" :class="{ first: n === 1 }">
              <span class="ob-pimg" aria-hidden="true"><svg class="icon"><use href="#i-plus"/></svg></span>
              <span class="ob-pt" x-text="projects.length + n === 1 ? @js(__('Add your first project')) : fmt(@js(__('Project :n')), { n: projects.length + n })"></span>
              <span class="ob-pe" x-show="n === 1">{{ __('Start') }} <svg class="icon sm flip" aria-hidden="true"><use href="#i-arrow"/></svg></span>
            </a>
          </template>
        </div>
        <a class="link ob-more" href="{{ $newProjectUrl }}" x-show="projects.length >= 3" x-cloak><svg class="icon sm" aria-hidden="true"><use href="#i-plus"/></svg>{{ __('Add another project') }}</a>

        <div class="ob-tips">
          <p class="ob-ptitle">{{ __('What makes a project land') }}</p>
          <ul>
            <li><b>{{ __('A strong first picture.') }}</b> {{ __('It becomes the cover brands see in search.') }}</li>
            <li><b>{{ __('Six to twelve pictures,') }}</b> {{ __('in the order you would show them in person.') }}</li>
            <li><b>{{ __('The people you made it with.') }}</b> {{ __('Credits link your work to theirs, and it shows up on their profiles too.') }}</li>
          </ul>
        </div>
      </section>

      {{-- ============ 4 · Your story ============ --}}
      <section class="ob-step" x-show="step === 'story'" @if ($step !== 'story') x-cloak @endif aria-labelledby="obH-story">
        <p class="ob-kick">{{ __('Step :n of 5', ['n' => $screenMeta['story']['n']]) }}</p>
        <h1 class="ob-h" id="obH-story">{{ __('Put it in your words') }}</h1>
        <p class="ob-lead">{{ __('A few lines on how you work, your point of view, and where else your work lives.') }}</p>

        <div class="ob-fields">
          <label class="f" :class="{ bad: errors['bio.en'] }">
            <span>{{ __('Bio') }} <small x-text="count(core.bio, 5000)"></small></span>
            <textarea class="ta" maxlength="5000" rows="5" x-model="core.bio" placeholder="{{ __('Where you trained, what you are known for, who you have worked with.') }}"></textarea>
            <p class="err" x-show="errors['bio.en']" x-cloak x-text="errors['bio.en']"></p>
          </label>
          {{-- Point of view (ADR-Z): taste, not a second bio — shown under the bio with the side line. --}}
          <label class="f" :class="{ bad: errors['worldview.en'] }">
            <span>{{ __('Point of view') }} <small x-text="count(core.worldview, 2000)"></small></span>
            <textarea class="ta" maxlength="2000" rows="3" x-model="core.worldview" placeholder="{{ __('What kind of work are you drawn to? What would you turn down?') }}"></textarea>
            <p class="help">{{ __('Your taste, in a line or two. It sits under your bio with a side line.') }}</p>
            <p class="err" x-show="errors['worldview.en']" x-cloak x-text="errors['worldview.en']"></p>
          </label>
          {{-- Every platform the profile shows (social.js PLATFORMS minus the free-form 'other'):
               icon · fixed address · username. A div, not a span: `.f > span` is ds.css's LABEL row. --}}
          <div class="f ob-links-h"><span>{{ __('Where else your work lives') }} <small>{{ __('Add any that apply') }}</small></span></div>
          <div class="ob-links">
            <template x-for="p in linkFields" :key="p.id">
              <label class="f" :class="{ bad: linkError === p.id, filled: !!links[p.id] }">
                <span class="vh" x-text="p.name"></span>
                <div class="ob-pre" dir="ltr">
                  <span class="ob-pre-ic" x-html="socialIcon(p.id)" :title="p.name"></span>
                  <span class="ob-pre-t" x-show="p.prefix" x-text="p.prefix"></span>
                  <input class="in" :type="p.prefix ? 'text' : 'url'" :inputmode="p.prefix ? 'text' : 'url'" autocapitalize="off" spellcheck="false"
                         x-model="links[p.id]" :placeholder="p.prefix ? @js(__('username')) : 'studio.com'" :aria-label="p.name">
                </div>
              </label>
            </template>
          </div>
          <p class="err" x-show="linkErrorText" x-cloak x-text="linkErrorText" role="alert"></p>
        </div>
      </section>

      {{-- ============ 5 · Go Live ============ --}}
      <section class="ob-step" x-show="step === 'live'" @if ($step !== 'live') x-cloak @endif aria-labelledby="obH-live">
        <p class="ob-kick">{{ __('Step :n of 5', ['n' => 5]) }}</p>
        <h1 class="ob-h" id="obH-live" x-text="liveTitle">{{ __('Go Live') }}</h1>
        <p class="ob-lead" x-text="liveLead"></p>

        <div class="ob-golive" x-show="stage !== 'draft'" x-cloak>
          <span class="ob-link" :class="{ off: stage !== 'live' }" dir="ltr">
            <svg class="icon sm" aria-hidden="true"><use :href="stage === 'live' ? '#i-link' : '#i-lock'" href="#i-lock"/></svg>
            <span x-text="host + '/' + slug"></span>
          </span>
          <button type="button" class="btn" x-show="stage === 'private'" @click="goLive()" :disabled="busy">{{ __('Go Live') }} <svg class="icon sm flip" aria-hidden="true"><use href="#i-arrow"/></svg></button>
          <button type="button" class="btn btn-2" x-show="stage === 'live'" @click="copyLink()"><svg class="icon sm" aria-hidden="true"><use :href="copied ? '#i-check' : '#i-copy'" href="#i-copy"/></svg><span x-text="copied ? @js(__('Copied')) : @js(__('Copy link'))"></span></button>
          <a class="btn btn-2" x-show="stage === 'live'" href="{{ url($talent->slug) }}" target="_blank" rel="noopener">{{ __('See your profile') }}</a>
        </div>
        <p class="err" x-show="publishError" x-cloak x-text="publishError" role="alert"></p>

        <ol class="ob-check">
          @foreach ($screens as $screen => $keys)
            @foreach ($keys as $key)
              <li :class="{ ok: done(@js($key)), need: essential.includes(@js($key)) && !done(@js($key)) }">
                <span class="ob-tick" aria-hidden="true"><svg class="icon sm" x-show="done(@js($key))"><use href="#i-check"/></svg></span>
                <span class="ob-ct">{{ $stepLabels[$key] }}
                  @if (in_array($key, \App\Services\ProfileCompletionService::ONBOARDING_ESSENTIAL, true))<small x-show="!done(@js($key))">{{ $key === 'show' ? __('Gets you into the Creatives Index') : __('Needed to go Live') }}</small>@endif
                </span>
                <span class="vh" x-text="done(@js($key)) ? @js(__('done')) : @js(__('not done yet'))"></span>
                @if ($key === 'brands')
                  <a class="link" href="{{ route('talent.profile.edit') }}#brands" x-show="!done('brands')">{{ __('Add on your profile') }} <svg class="icon sm flip" aria-hidden="true"><use href="#i-arrow"/></svg></a>
                @else
                  <button type="button" class="link" x-show="!done(@js($key))" @click="goTo(@js($screen))">{{ __('Add') }} <svg class="icon sm flip" aria-hidden="true"><use href="#i-arrow"/></svg></button>
                @endif
              </li>
            @endforeach
          @endforeach
        </ol>
      </section>

      <footer class="ob-foot">
        <button type="button" class="btn btn-text" x-show="prevStep" x-cloak @click="back()">
          <svg class="icon sm ob-back-ic" aria-hidden="true"><use href="#i-arrow"/></svg>{{ __('Back') }}
        </button>
        <span class="ob-hint" role="status" x-text="hint"></span>
        <a class="btn" x-show="step === 'live'" x-cloak href="{{ route('talent.profile.edit') }}">{{ __('Go to your profile') }}</a>
        {{-- Skipping is allowed but never the loud choice: "Skip for now" is the quiet button. --}}
        <button type="button" class="btn" :class="{ 'btn-2': nextLabel === S.skip }" x-show="step !== 'live'" @click="next()" :disabled="busy">
          <span x-text="nextLabel">{{ __('Continue') }}</span>
          <svg class="icon sm flip" aria-hidden="true"><use href="#i-arrow"/></svg>
        </button>
      </footer>
    </main>

    {{-- THE LIVE PREVIEW: the real public profile (desktop layout, no side bar) in a browser frame.
         The draft is posted in as the talent types; the part the current screen edits is lit and
         scrolled into view (previewFocus in famaOnboarding → public/fama-ui/js/profile-embed.js). --}}
    <aside class="ob-side" aria-label="{{ __('Live preview of your profile') }}">
      <button type="button" class="ob-pvtoggle" @click="pvOpen = !pvOpen" :aria-expanded="pvOpen.toString()" aria-controls="obPreview">
        <span class="ob-av xs" :class="{ has: avatarUrl }" :style="avatarUrl ? `background-image:url(${avatarUrl})` : ''" aria-hidden="true" x-text="avatarUrl ? '' : initials"></span>
        <span class="ob-pvt"><b>{{ __('Live preview') }}</b><span x-text="fmt(@js(__(':n of :m done')), { n: doneCount, m: totalCount })"></span></span>
        <span class="ob-pvc" x-text="pvOpen ? @js(__('Hide')) : @js(__('Show'))"></span>
      </button>

      <div class="obp" id="obPreview" :class="{ open: pvOpen }">
        {{-- the frame: a quiet browser bar with the profile's own address and its state --}}
        <div class="obp-bar">
          <span class="obp-dots" aria-hidden="true"><i></i><i></i><i></i></span>
          <span class="obp-url" dir="ltr"><svg class="icon sm" aria-hidden="true"><use :href="stage === 'live' ? '#i-link' : '#i-lock'" href="#i-lock"/></svg><span x-text="host + '/' + slug"></span></span>
          <span class="obp-state" :class="'is-' + stage" x-text="stageLabel"></span>
        </div>

        {{-- THE REAL PAGE: the public profile in embed mode (no nav/strip/footer), rendered at
             desktop width and scaled to fit (fitPreview). The draft is posted into it as you type;
             it reloads after anything saves (photo, skills, links, projects, Go Live). --}}
        <div class="obp-view" x-ref="pvBox">
          <iframe class="obp-frame" x-ref="pv" src="{{ url($talent->slug) }}?embed=1" title="{{ __('Your public profile') }}"
                  tabindex="-1" loading="eager" @load="onPreviewLoad()"></iframe>
          <span class="obp-loading" x-show="pvLoading" x-transition.opacity aria-hidden="true"></span>
        </div>

        <div class="obp-meter">
          <span class="ob-mbar" role="progressbar" :aria-valuenow="doneCount" aria-valuemin="0" :aria-valuemax="totalCount" aria-label="{{ __('Portfolio complete') }}"><i :style="`width:${Math.round(doneCount / totalCount * 100)}%`"></i></span>
          <p><b x-text="fmt(@js(__(':n of :m')), { n: doneCount, m: totalCount })"></b> <span x-text="meterNote"></span></p>
        </div>
      </div>
    </aside>
  </div>
</div>

</x-fama.layout>
