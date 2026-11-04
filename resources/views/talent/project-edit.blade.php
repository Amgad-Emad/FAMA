{{--
    The project editor (fama-ui `project-edit.html`, WYSIWYG since 24 Oct 2026) — the project is edited on a
    page that looks like the published one: the same cover, colophon, type and pictures (project-page.css),
    plus what editing adds (project-editor.css). Every word types where it shows.

    The bar docks at the top of the page panel on wide screens (the site's side bar stays); on narrow
    screens the site header steps aside and the bar takes its place (x-teleport into `.fx-nav > .fx-in` +
    the `fx-editor` class on the header — see famaProjectEdit.init).

    Mapped onto the real project model (ADR-X / ADR-Y / ADR-AA) — a Fama project is a fixed set of fields,
    not the prototype's free-form blocks, so the blocks are fixed and appear in the page's order:
      cover        → the single-file `cover` collection (optional)
      title        → title.en (English-only in V1; a stored Arabic value rides along untouched)
      colophon     → client_name (hidden on the page when empty), role.en, skills (≥1 of the HOME owner's
                     own; the first is the home tab) + specialities (only from tagged skills), url
      opening line → summary.en (one line of text)
      Photos       → the ordered `media` collection (the order is the edit; the first leads)
      Text         → body.en (the full story)
      Results      → up to four {value, label, compare} rows
      Credits      → project_credits (on-Fama people confirm before they show; off-Fama names list at once)
      Settings     → occurred_on (recorded, never shown), Featured, status, Leave / Delete (co-owner rules)

    A NEW project is a draft in this page until Publish (POST /talent/projects); the staged cover, photos
    and credits are sent right after. From then on every change autosaves (PATCH, 700ms).

    Behaviour: `famaProjectEdit` (resources/js/ui/projects.js).
--}}
@php
    $strings = [
        'draft' => __('Draft'),
        'published' => __('Published'),
        'publish' => __('Publish'),
        'update' => __('Update'),
        'saving' => __('Saving…'),
        'saved' => __('Saved just now'),
        'notSaved' => __('Not published yet'),
        'saveFailed' => __('Not saved — :m'),
        'untitled' => __('Untitled project'),
        'yourRole' => __('Your role'),
        'needTitle' => __('Name the project'),
        'needSkill' => __('Pick at least one skill'),
        'needHead' => __('Before it goes on your profile'),
        'featOnly' => __('Your only project is always featured — it’s the cover of your profile.'),
        'featOnNew' => __('It becomes the cover of your profile when you publish.'),
        'featOnSaved' => __('This is the cover of your profile. Feature another project to change it.'),
        'featOff' => __('Put this project on the cover of your profile.'),
        'featDone' => __('Featured on your profile.'),
        'badUrl' => __('Enter a full link, like https://studio.com/work.'),
        'fixFields' => __('Check the highlighted fields.'),
        'notSavedFix' => __('Not saved — check the highlighted fields'),
        'published1' => __('Published to your profile.'),
        'publishedAsked1' => __('Published. :n is asked to confirm their credit.'),
        'publishedAskedN' => __('Published. :n people are asked to confirm their credit.'),
        'live' => __('Changes are live.'),
        'coverAdded' => __('Cover added'),
        'coverRemoved' => __('Cover removed'),
        'allSkills' => __('All your skills are on it.'),
        'allSpecs' => __('All the specialities of these skills are on it.'),
        'removeMediaTitle' => __('Remove this photo?'),
        'removeMediaBody' => __('It is deleted from this project for good.'),
        'removeOk' => __('Remove'),
        'removeCreditTitle' => __('Remove :n from the credits?'),
        'removeCreditBody' => __('Their name comes off this project, and off their profile if they had confirmed it.'),
        'blockRemoved' => __(':b removed'),
        'deleteBlock' => __('Delete'),
        'blockNotEmpty' => __('Remove what’s in it first.'),
        'uploading' => __('Uploading…'),
        'leave' => __('You have unsaved changes.'),
        'failed' => __('Something went wrong. Try again.'),
        'statusDraft' => __('Draft: only you can see it. Publish puts it on your profile and asks the people you credited to confirm.'),
        'statusLive' => __('Published: it’s on your profile. Changes save as you go.'),
        'leaveTitle' => __('Leave :p?'),
        'leaveBody' => __('You stop being an owner, and it comes off your profile. The project stays for its other owners. Nobody can delete a project that still has other owners.'),
        'leaveOk' => __('Leave project'),
        'delTitle' => __('Delete :p?'),
        'delBody' => __('You’re its only owner, so it comes off Fama for everyone, with its photos and credits. This can’t be undone.'),
        'delOk' => __('Delete project'),
        // What each block is, in the block menu: [label, hint]; groups: Write · Show · Prove.
        'groups' => ['write' => __('Write'), 'show' => __('Show'), 'prove' => __('Prove')],
        'blocks' => [
            'story' => [__('Text'), __('The full story: the brief, what you did, how it landed')],
            'gallery' => [__('Photos'), __('Your pictures and video, in order. The first one leads')],
            'results' => [__('Results'), __('Up to four numbers: 1.2M, 68%, +40%')],
            'credits' => [__('Credits'), __('People you made it with, asked to confirm')],
        ],
        'kinds' => ['lead' => __('Text'), 'story' => __('Text'), 'gallery' => __('Photos'), 'results' => __('Results'), 'credits' => __('Credits')],
        'gallery' => [
            'none' => __('Add pictures. The first one leads the page.'),
            'one' => __('On the page: full width.'),
            'many' => __('On the page: the first one wide, the rest in pairs, in this order.'),
            'title' => __('Photos · :n'),
        ],
        'creditStates' => [
            'accepted' => __('Confirmed'),
            'pending' => __('Waiting to confirm'),
            'declined' => __('Declined'),
            'listed' => __('Shown, not asked'),
            'listedDraft' => __('Shown once saved'),
            'sending' => __('Asked once saved'),
            'onPublish' => __('Asked when you publish'),
        ],
    ];
    $editUrlTemplate = route('talent.projects.edit', ['project' => '__ID__']);
    $isNew = ! $project;
    $initialName = $project ? ($project->getTranslation('title', 'en', false) ?: __('Untitled project')) : __('Untitled project');
    $me = auth('talent')->user();
    $meInitials = collect(preg_split('/\s+/', trim((string) ($me->display_name ?: $me->slug))))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
<x-fama.layout :title="$project ? __('Edit project') : __('New project')" :css="['project-page', 'project-editor']">
    <x-slot:head>
        <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;1,6..72,400&display=swap">
    </x-slot:head>
    <x-slot:styles>
        <link rel="stylesheet" href="{{ \App\Support\FamaAsset::url('fama-ui/css/pages/project-edit.css') }}">
    </x-slot:styles>

    <div class="pe-root" x-data="famaProjectEdit({
            project: @js($payload),
            skills: @js($skills),
            specialities: @js($specialities),
            allSkills: @js($allSkills),
            slug: @js($homeOwner->slug),
            live: @js((bool) $homeOwner->is_published),
            editUrl: @js($editUrlTemplate),
            projectsUrl: @js(route('talent.projects')),
            strings: @js($strings),
            projectCount: @js($projectCount),
            me: { name: @js($me->display_name ?: $me->slug), initials: @js($meInitials), avatar: @js($me->avatar_url) },
         })"
         @keydown.escape.window="onEscape()"
         @keydown.window="onKey($event)"
         @beforeunload.window="onBeforeUnload($event)">

        {{-- Narrow screens: the bar takes the site header's place. --}}
        <template x-if="!docked">
            <template x-teleport=".fx-nav > .fx-in">
                @include('talent.partials.project-edit-head')
            </template>
        </template>
        {{-- Wide screens: the bar docks at the top of the page panel. --}}
        <template x-if="docked">
            <div class="pe-bar pe-docked">@include('talent.partials.project-edit-head')</div>
        </template>

        <main class="ed" id="page" aria-label="{{ __('Project editor') }}" @focusin="onFocusIn($event)">

            {{-- Cover --}}
            <header class="cover pe-cover" :class="{ none: !coverUrl }">
                <template x-if="coverUrl"><div class="ph-img" :style="{ backgroundImage: 'url(' + coverUrl + ')' }"></div></template>
                <div class="pe-cover-acts">
                    <button type="button" class="btn" @click="$refs.coverInput.click()" :disabled="coverBusy"><svg class="pe-i" aria-hidden="true"><use href="#e-image"/></svg><span x-text="coverBusy ? strings.uploading : (coverUrl ? @js(__('Change cover')) : @js(__('Add a cover')))">{{ __('Add a cover') }}</span></button>
                    <button type="button" class="btn" x-show="coverUrl" x-cloak @click="removeCover()">{{ __('Remove') }}</button>
                </div>
                <div class="cap grid"><div>
                    <p class="kick"><span class="ytag" x-show="year" x-cloak x-text="year"></span><span x-text="kicker"></span><span class="pe-auto" x-text="kicker || year ? @js(__('from Client, Skills and Date')) : @js(__('Client, Skills and Date show here'))"></span></p>
                    <h1 contenteditable="true" spellcheck="true" role="textbox" id="peTitle" data-ph="{{ __('Name the project') }}" aria-label="{{ __('Project title') }}"
                        x-init="$el.textContent = form.title.en || ''; if (!id) $nextTick(() => $el.focus())"
                        :class="{ 'pe-bad': errors.title }" :aria-invalid="(!!errors.title).toString()"
                        @input="onTitle($el)" @keydown.enter.prevent="focusLead()" @paste="onPaste($event)"></h1>
                    <p class="pe-err" role="alert" x-show="errors.title" x-text="errors.title" x-cloak></p>
                    <div class="by"><span class="av" aria-hidden="true"><template x-if="me.avatar"><img :src="me.avatar" alt=""></template><template x-if="!me.avatar"><span x-text="me.initials"></span></template></span><span><b x-text="me.name"></b><span x-text="form.role.en ? ', ' + form.role.en.toLowerCase() : ''"></span></span></div>
                </div></div>
            </header>
            <input type="file" x-ref="coverInput" accept="image/jpeg,image/png,image/webp" style="display:none"
                   @change="pickCover($event.target.files[0]); $event.target.value = ''">

            {{-- Colophon --}}
            <div class="grid"><dl class="colo pe-colo">
                <div data-f="client"><dt>{{ __('Client') }}</dt>
                    <dd contenteditable="true" data-ph="{{ __('Add a client, or leave empty') }}" role="textbox" aria-label="{{ __('Client') }}"
                        x-init="$el.textContent = form.client_name || ''" @input="onPlain($el, 'client')" @keydown.enter.prevent @paste="onPaste($event)"></dd>
                    <p class="pe-err" role="alert" x-show="errors.client" x-text="errors.client" x-cloak></p></div>
                <div data-f="role"><dt>{{ __('Role') }}</dt>
                    <dd contenteditable="true" data-ph="{{ __('What did you do on it?') }}" role="textbox" aria-label="{{ __('Role') }}"
                        x-init="$el.textContent = form.role.en || ''" @input="onPlain($el, 'role')" @keydown.enter.prevent @paste="onPaste($event)"></dd>
                    <p class="pe-err" role="alert" x-show="errors.role" x-text="errors.role" x-cloak></p></div>
                <div data-f="skills"><dt>{{ __('Skills') }}</dt>
                    <dd>
                        <template x-for="s in selectedSkills" :key="'sk' + s.id">
                            <button type="button" class="pe-chip" @click="toggleSkill(s.id)" :title="homeSkillId === s.id ? @js(__('Home tab on your profile')) : ''" :aria-label="@js(__('Remove')) + ' ' + s.name"><span x-text="s.name"></span><svg class="pe-i" aria-hidden="true"><use href="#e-x"/></svg></button>
                        </template>
                        <button type="button" class="pe-chip add" data-act="skadd" @click.stop="openSkillMenu($el)"><svg class="pe-i" aria-hidden="true"><use href="#e-plus"/></svg>{{ __('Skill') }}</button>
                        <small x-show="selectedSpecialities.length" x-cloak>
                            <template x-for="sp in selectedSpecialities" :key="'sp' + sp.id">
                                <button type="button" class="pe-chip ghost" @click="toggleSpeciality(sp.id)" :aria-label="@js(__('Remove')) + ' ' + sp.name"><span x-text="sp.name"></span><svg class="pe-i" aria-hidden="true"><use href="#e-x"/></svg></button>
                            </template>
                        </small>
                        <button type="button" class="pe-chip add sm" data-act="specadd" x-show="form.skill_ids.length" x-cloak @click.stop="openSpecMenu($el)"><svg class="pe-i" aria-hidden="true"><use href="#e-plus"/></svg>{{ __('Speciality') }}</button>
                    </dd>
                    <p class="pe-err" role="alert" x-show="errors.skills || errors.specialities" x-text="errors.skills || errors.specialities" x-cloak></p></div>
                <div data-f="url"><dt>{{ __('See it live') }}</dt>
                    <dd><input class="pe-link" type="url" dir="ltr" maxlength="2048" x-model="form.url" @input="changed('url')" placeholder="{{ __('Paste a link') }}" aria-label="{{ __('Link to the live work') }}"></dd>
                    <p class="pe-err" role="alert" x-show="errors.url" x-text="errors.url" x-cloak></p></div>
            </dl></div>

            {{-- The story --}}
            <div class="grid"><article class="story pe-story" id="story">

                {{-- The opening line (summary) --}}
                <div class="pe-b pe-text" data-b="lead" :class="{ 'is-on': active === 'lead' }">
                    <div class="pe-tools" role="toolbar" aria-label="{{ __('Text block') }}"><span class="pe-kind" x-text="strings.kinds.lead"></span></div>
                    <p class="lead pe-lead" contenteditable="true" spellcheck="true" role="textbox" data-ph="{{ __('Open with one line about the project') }}" aria-label="{{ __('Open with one line about the project') }}"
                       x-init="$el.textContent = textOf(form.summary.en)" @input="onLead($el)" @keydown.enter.prevent="focusBlock('story')" @paste="onPaste($event)"></p>
                    <p class="pe-err" role="alert" x-show="errors.summary" x-text="errors.summary" x-cloak></p>
                </div>

                {{-- Photos (the ordered media) --}}
                <template x-if="shown.gallery">
                    <div class="pe-b pe-gallery" data-b="gallery" :class="{ 'is-on': active === 'gallery' }">
                        @include('talent.partials.project-edit-tools', ['block' => 'gallery'])
                        <div class="pe-gal">
                            <p class="pe-note"><svg class="pe-i" aria-hidden="true"><use href="#e-images"/></svg><span><b x-text="fmt(strings.gallery.title, { n: gallery.length })"></b><span x-text="galleryNote"></span></span></p>
                            <ol class="pe-strip">
                                <template x-for="(m, k) in gallery" :key="m.key">
                                    <li class="pe-ph" :class="{ 'pe-dragging': mediaDrag === m.key }" draggable="true"
                                        @dragstart="onMediaDragStart($event, m)" @dragover.prevent @drop.prevent="onMediaDrop(m)" @dragend="mediaDrag = null">
                                        <div class="pe-ph-img" :style="m.is_video ? {} : { backgroundImage: 'url(' + m.url + ')' }" role="img" :aria-label="@js(__('Picture')) + ' ' + (k + 1)">
                                            <template x-if="m.is_video"><video :src="m.url" muted playsinline preload="metadata"></video></template>
                                            <span class="pe-ph-n" x-text="String(k + 1).padStart(2, '0')"></span>
                                            <span class="pe-ph-acts">
                                                <button type="button" x-show="k > 0" @click="moveMedia(k, -1)" :aria-label="@js(__('Move picture :n earlier')).replace(':n', k + 1)"><svg class="pe-i" aria-hidden="true"><use href="#e-left"/></svg></button>
                                                <button type="button" x-show="k < gallery.length - 1" @click="moveMedia(k, 1)" :aria-label="@js(__('Move picture :n later')).replace(':n', k + 1)"><svg class="pe-i" aria-hidden="true"><use href="#e-right"/></svg></button>
                                                <button type="button" @click="removeMedia(m)" :aria-label="@js(__('Remove picture :n')).replace(':n', k + 1)"><svg class="pe-i" aria-hidden="true"><use href="#e-x"/></svg></button>
                                            </span>
                                        </div>
                                    </li>
                                </template>
                                <li class="pe-ph add"><button type="button" @click="$refs.mediaInput.click()" :disabled="uploading"><svg class="pe-i" aria-hidden="true"><use href="#e-plus"/></svg><b x-text="uploading ? strings.uploading : @js(__('Add pictures'))">{{ __('Add pictures') }}</b><small>{{ __('JPG, PNG, WebP, MP4, MOV or WebM') }}</small></button></li>
                            </ol>
                            <p class="pe-hint">{{ __('Up to 12 per upload, 50 MB each. Drag, or use the arrows, to set the order — the first one leads.') }}</p>
                        </div>
                        <input type="file" x-ref="mediaInput" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm" style="display:none"
                               @change="addMedia($event.target.files); $event.target.value = ''">
                    </div>
                </template>

                {{-- Text (the full story) --}}
                <template x-if="shown.story">
                    <div class="pe-b pe-text" data-b="story" :class="{ 'is-on': active === 'story' }">
                        @include('talent.partials.project-edit-tools', ['block' => 'story'])
                        <div class="txt pe-body" contenteditable="true" spellcheck="true" role="textbox" aria-multiline="true" aria-label="{{ __('The full story') }}"
                             data-ph="{{ __('Write, or type / to add a block') }}"
                             x-init="$el.innerHTML = form.body.en || ''" @input="onBody($el)" @paste="onPaste($event, true)"></div>
                        <p class="pe-err" role="alert" x-show="errors.story" x-text="errors.story" x-cloak></p>
                    </div>
                </template>

                {{-- Results --}}
                <template x-if="shown.results">
                    <div class="pe-b pe-results" data-b="results" :class="{ 'is-on': active === 'results' }">
                        @include('talent.partials.project-edit-tools', ['block' => 'results'])
                        <div class="res">
                            <template x-for="(r, k) in form.results" :key="r.key">
                                <div class="pe-item" :class="resultClass(r.value)">
                                    <b contenteditable="true" data-ph="1.2M" aria-label="{{ __('Number') }}" x-init="$el.textContent = r.value || ''" @input="r.value = $el.textContent; changed('results')" @keydown.enter.prevent></b>
                                    <span contenteditable="true" data-ph="{{ __('What it measures') }}" aria-label="{{ __('What it measures') }}" x-init="$el.textContent = r.label || ''" @input="r.label = $el.textContent; changed('results')" @keydown.enter.prevent></span>
                                    <template x-if="resultKind(r.value) === 'percent'"><div class="bar" aria-hidden="true"><i :style="{ width: pctOf(r.value) + '%' }"></i></div></template>
                                    <template x-if="resultKind(r.value) === 'change'"><small contenteditable="true" data-ph="{{ __('Compared with, e.g. last season') }}" aria-label="{{ __('Compared with') }}" x-init="$el.textContent = r.compare || ''" @input="r.compare = $el.textContent; changed('results')" @keydown.enter.prevent></small></template>
                                    <button type="button" class="pe-x" @click="removeResult(k)" aria-label="{{ __('Remove this number') }}"><svg class="pe-i" aria-hidden="true"><use href="#e-x"/></svg></button>
                                </div>
                            </template>
                            <button type="button" class="pe-add-tile" x-show="form.results.length < 4" @click="addResult()"><svg class="pe-i" aria-hidden="true"><use href="#e-plus"/></svg><b>{{ __('Add a number') }}</b><small>1.2M · 68% · +40%</small></button>
                        </div>
                        <p class="pe-hint">{{ __('Type a number the way it reads. A % gets a bar; a + or − shows a rise or a fall, with what it’s compared to.') }}</p>
                        <p class="pe-err" role="alert" x-show="errors.results" x-text="errors.results" x-cloak></p>
                    </div>
                </template>

                {{-- Credits (person + skill + role) --}}
                <template x-if="shown.credits">
                    <div class="pe-b pe-credits" data-b="credits" :class="{ 'is-on': active === 'credits' }">
                        @include('talent.partials.project-edit-tools', ['block' => 'credits'])
                        <ul class="cred">
                            <li class="pe-me"><span class="av"><template x-if="me.avatar"><img :src="me.avatar" alt=""></template><template x-if="!me.avatar"><span x-text="me.initials"></span></template></span><b x-text="me.name"></b><span class="r" x-text="form.role.en || strings.yourRole"></span><span class="st">{{ __('Owner') }}</span></li>
                            {{-- Saved credits (a credit is removed and re-added, never rewritten) --}}
                            <template x-for="c in credits" :key="'c' + c.id">
                                <li>
                                    <span class="av"><template x-if="c.talent?.avatar_url"><img :src="c.talent.avatar_url" alt=""></template><template x-if="!c.talent?.avatar_url"><span x-text="initials(c.name)"></span></template></span>
                                    <b x-text="c.name"></b>
                                    <span class="r" x-text="[c.role, c.skill?.name].filter(Boolean).join(' · ')"></span>
                                    <span class="st" :class="creditClass(c.status)" x-text="creditState(c)"></span>
                                    <button type="button" class="pe-x" @click="removeCredit(c)" :aria-label="@js(__('Remove')) + ' ' + c.name"><svg class="pe-i" aria-hidden="true"><use href="#e-x"/></svg></button>
                                </li>
                            </template>
                            {{-- New rows: saved as soon as they have a person, a skill and a role --}}
                            <template x-for="(d, k) in drafts" :key="d.key">
                                <li class="pe-draft" @focusout="onDraftBlur($event, d)">
                                    <span class="av"><template x-if="d.talent?.avatar_url"><img :src="d.talent.avatar_url" alt=""></template><template x-if="!d.talent?.avatar_url"><span x-text="initials(d.name || '?')"></span></template></span>
                                    <span class="pe-cname">
                                        <input x-model="d.name" maxlength="120" placeholder="{{ __('Their name, or search Fama') }}" aria-label="{{ __('Person') }}" autocomplete="off" @input="onDraftName(d)" :data-draft="d.key">
                                        <div class="pe-menu pe-people" role="listbox" x-show="d.results.length" x-cloak>
                                            <p>{{ __('On Fama') }}</p>
                                            <template x-for="p in d.results" :key="p.id">
                                                <button type="button" role="option" @mousedown.prevent="pickPerson(d, p)">
                                                    <span class="ico"><span class="av"><template x-if="p.avatar_url"><img :src="p.avatar_url" alt=""></template><template x-if="!p.avatar_url"><span x-text="initials(p.display_name)"></span></template></span></span>
                                                    <b x-text="p.display_name"></b><small dir="ltr" x-text="'@' + p.username"></small>
                                                </button>
                                            </template>
                                        </div>
                                    </span>
                                    <span class="r"><input x-model="d.role" maxlength="80" placeholder="{{ __('Role, e.g. Photographer') }}" aria-label="{{ __('Role') }}"></span>
                                    <span class="pe-cskill">
                                        <select x-model.number="d.talent_type_id" aria-label="{{ __('Skill') }}" @change="maybeCommit(d)">
                                            <option value="">{{ __('Skill…') }}</option>
                                            @foreach ($allSkills as $s)
                                                <option value="{{ $s['id'] }}">{{ $s['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <small x-show="d.name" x-cloak x-text="draftState(d)"></small>
                                    </span>
                                    <button type="button" class="pe-x" @click="drafts.splice(k, 1)" :aria-label="@js(__('Remove')) + ' ' + (d.name || @js(__('this person')))"><svg class="pe-i" aria-hidden="true"><use href="#e-x"/></svg></button>
                                </li>
                            </template>
                            <li class="pe-add-li"><button type="button" @click="addDraft()"><svg class="pe-i" aria-hidden="true"><use href="#e-plus"/></svg><b>{{ __('Credit someone') }}</b><small>{{ __('Anyone on Fama is asked to confirm; people not on Fama are listed right away') }}</small></button></li>
                        </ul>
                    </div>
                </template>

                {{-- The last + : "Add a block" (project-editor.css draws it) --}}
                <div class="pe-ins" x-show="addable.length"><button type="button" data-act="add" @click.stop="openAddMenu($el)" aria-label="{{ __('Add a block') }}"><svg class="pe-i" aria-hidden="true"><use href="#e-plus"/></svg></button></div>
            </article></div>
        </main>

        {{-- Settings: what visitors never see --}}
        <div class="pe-scrim" x-show="panelMounted" x-cloak :class="{ 'is-open': panelOpen }" @click="closeSettings()"></div>
        <aside class="pe-panel" role="dialog" aria-modal="true" aria-label="{{ __('Project settings') }}" x-show="panelMounted" x-cloak x-ref="panel"
               :class="{ 'is-open': panelOpen }" @keydown.tab="trapPanel($event)">
            <button class="x" type="button" @click="closeSettings()" aria-label="{{ __('Close settings') }}"><svg class="pe-i" aria-hidden="true"><use href="#e-x"/></svg></button>
            <h2>{{ __('Settings') }}</h2>
            <p class="lead2">{{ __('Visitors never see these.') }}</p>
            <div class="row"><label for="peDate">{{ __('Date') }}</label>
                <input id="peDate" type="month" :value="monthValue" @input="onMonth($event.target.value)" max="{{ now()->addYear()->format('Y-m') }}">
                <small>{{ __('Sorts your projects, and shows as the year tag.') }}</small>
                <p class="pe-err" role="alert" x-show="errors.date" x-text="errors.date" x-cloak></p></div>
            <div class="row" x-show="canFeature"><div class="tog"><div><label id="peFeatL">{{ __('Featured on your profile') }}</label><small x-text="featuredNote"></small></div>
                <button class="sw" role="switch" type="button" aria-labelledby="peFeatL" :aria-checked="featured.toString()"
                        :aria-disabled="(featuredLocked || (featured && !!id)).toString()" @click="toggleFeatured()"></button></div></div>
            <div class="row"><label>{{ __('Status') }}</label><small x-text="id ? strings.statusLive : strings.statusDraft"></small></div>
            <template x-if="id">
                <div class="row">
                    <template x-if="hasCoOwners"><button type="button" class="btn btn-2" @click="leaveProject()">{{ __('Leave project') }}</button></template>
                    <template x-if="!hasCoOwners"><button type="button" class="btn btn-2 pe-danger" @click="deleteProject()">{{ __('Delete project') }}</button></template>
                </div>
            </template>
        </aside>

        {{-- The block menu, the skill / speciality pickers and the publish checklist --}}
        <div class="pe-menu" role="menu" x-show="menu.open" x-cloak x-ref="menu" @click.outside="closeMenu()" @keydown="onMenuKey($event)"
             :style="{ position: 'fixed', top: menu.top + 'px', left: menu.left + 'px' }">
            <template x-for="(it, i) in menu.items" :key="it.key">
                <div style="display:contents">
                    <template x-if="it.header"><p x-text="it.label"></p></template>
                    <template x-if="!it.header">
                        <button type="button" role="menuitem" :class="[it.cls || '', { 'is-hl': i === menu.hl, 'pe-need': it.need }]" @click="pickMenu(it)" @mouseenter="menu.hl = i">
                            <template x-if="it.icon"><span class="ico"><svg class="pe-i" aria-hidden="true"><use :href="'#' + it.icon"/></svg></span></template>
                            <b x-text="it.label"></b>
                            <template x-if="it.sub"><small x-text="it.sub"></small></template>
                        </button>
                    </template>
                </div>
            </template>
        </div>
    </div>
</x-fama.layout>
