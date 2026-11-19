{{--
    The project editor (fama-ui `project-edit.html`, WYSIWYG since 24 Oct 2026) — the project is edited on a
    page that looks like the published one: the same cover, colophon, type and pictures (project-page.css),
    plus what editing adds (project-editor.css). Every word types where it shows.

    The bar docks at the top of the page panel on wide screens (the site's side bar stays); on narrow
    screens the site header steps aside and the bar takes its place (x-teleport into `.fx-nav > .fx-in` +
    the `fx-editor` class on the header — see famaProjectEdit.init).

    Mapped onto the real project model (ADR-X / ADR-Y / ADR-AA / ADR-AN):
      cover        → the single-file `cover` collection (optional)
      title        → title.en (English-only in V1; a stored Arabic value rides along untouched)
      colophon     → client_name (+ client_confidential), role.en, skills (≥1 of the HOME owner's own; the
                     first is the home tab) + specialities (only from tagged skills), url
      story        → `projects.blocks`: free-form blocks (text · heading · quote · image · photos ·
                     before/after · look · video · social post · results · credits), any order, any number
                     (App\Support\ProjectBlocks). Pictures are ids in the `media` collection; Results rows
                     stay on `projects.results` and credits on `project_credits` — their blocks mark WHERE.
                     summary/body are written from the blocks by the service.
      Settings     → occurred_on (recorded; shows as the year tag), confidential client, Featured, status,
                     Leave / Delete (co-owner rules)

    A NEW project is a draft in this page until Publish (POST /talent/projects); its staged cover, pictures
    and credits are sent right after, then the blocks are saved again with the pictures' ids. From then on
    every change autosaves (PATCH, 700ms) and a picked picture uploads at once (`place=0`).

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
        'blockNotEmpty' => __('Remove the people in it first.'),
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
        // The block menu (prototype MENU): [label, hint, icon]; groups Write · Show · Prove.
        'groups' => ['write' => __('Write'), 'show' => __('Show'), 'prove' => __('Prove')],
        'blocks' => [
            'text' => [__('Text'), __('A paragraph. Type / in an empty line for this menu'), 'e-text'],
            'heading' => [__('Heading'), __('Starts a new chapter on the page'), 'e-heading'],
            'quote' => [__('Quote'), __('Set large, with who said it'), 'e-quote'],
            'image' => [__('Image'), __('Full width. Text right after it sits beside it'), 'e-image'],
            'gallery' => [__('Photos'), __('2 sit side by side; 4 or more become the scrolling gallery'), 'e-images'],
            'beforeafter' => [__('Before / After'), __('One frame twice, with a line to drag'), 'e-split'],
            'look' => [__('Look with tags'), __('Numbered pieces on a photo'), 'e-tags'],
            'video' => [__('Video'), __('A YouTube or Vimeo link'), 'e-video'],
            'embed' => [__('Social post'), __('A post from Instagram, TikTok, Behance or YouTube Shorts'), 'e-reel'],
            'results' => [__('Results'), __('Up to four numbers: 1.2M, 68%, +40%'), 'e-chart'],
            'credits' => [__('Credits'), __('People you made it with, asked to confirm'), 'e-users'],
        ],
        'kinds' => ['text' => __('Text'), 'heading' => __('Heading'), 'quote' => __('Quote'), 'image' => __('Image'), 'gallery' => __('Photos'),
            'beforeafter' => __('Before / After'), 'look' => __('Look'), 'video' => __('Video'), 'embed' => __('Social post'),
            'results' => __('Results'), 'credits' => __('Credits')],
        'gallery' => [
            'none' => __('Add pictures. 4 or more become the scrolling gallery.'),
            'one' => __('On the page: full width.'),
            'two' => __('On the page: side by side.'),
            'three' => __('On the page: one wide, then two side by side.'),
            'many' => __('On the page: the scrolling gallery, in this order, under the chapter name.'),
            'title' => __('Photos · :n'),
        ],
        'chapter' => __('CHAPTER'),
        'addBelow' => __('Add a block below'),
        'turnInto' => __('Turn into :k'),
        'duplicate' => __('Duplicate'),
        'deleted' => __(':b deleted'),
        'oneOnly' => __('A project has one :b block.'),
        'paras' => __(':n paragraphs added'),
        'pieceRemoved' => __('Piece removed'),
        'picRemoved' => __('Picture removed'),
        'numberRemoved' => __('Number removed'),
        'lookFull' => __('A look holds up to 8 pieces.'),
        'needVisual' => __('Add a cover, or some pictures or a video'),
        'pickImage' => __('Add an image'),
        'replaceImage' => __('Replace the image'),
        'pickLook' => __('The look’s photo'),
        'replaceLook' => __('Replace the look’s photo'),
        'pickBefore' => __('The before picture'),
        'pickAfter' => __('The after picture'),
        'pickMany' => __('Add pictures'),
        'replacePic' => __('Replace picture :n'),
        'addPic1' => __('Add 1 picture'),
        'addPicN' => __('Add :n pictures'),
        'ndaWarn1' => __(':c is still named in :n block. Change those words so the page doesn’t give it away.'),
        'ndaWarnN' => __(':c is still named in :n blocks. Change those words so the page doesn’t give it away.'),
        'confidential' => __('Confidential client'),
        'badVideo' => __('Paste a YouTube or Vimeo link.'),
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
                        @input="onTitle($el)" @keydown.enter.prevent="focusStory()" @paste="onPaste($event)"></h1>
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
                    <span class="pe-nda" x-show="form.client_confidential" x-cloak><svg class="pe-i" aria-hidden="true"><use href="#e-lock"/></svg>{{ __('Hidden on the page: Confidential') }}</span>
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
                    <span class="pe-nda" x-show="form.client_confidential" x-cloak><svg class="pe-i" aria-hidden="true"><use href="#e-lock"/></svg>{{ __('Hidden on the page: Confidential') }}</span>
                    <p class="pe-err" role="alert" x-show="errors.url" x-text="errors.url" x-cloak></p></div>
            </dl></div>

            {{-- The story: free-form blocks (ADR-AN) --}}
            <div class="grid"><article class="story pe-story" id="story">
                @include('talent.partials.project-edit-story')
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
            <div class="row"><div class="tog"><div><label id="peNdaL">{{ __('Keep the client confidential') }}</label><small>{{ __('The page says “Confidential client” and hides the link. For work under an NDA.') }}</small></div>
                <button class="sw" role="switch" type="button" aria-labelledby="peNdaL" :aria-checked="(!!form.client_confidential).toString()" @click="form.client_confidential = !form.client_confidential; changed('client')"></button></div>
                <p class="pe-warn" x-show="ndaHits" x-cloak x-text="fmt(ndaHits === 1 ? strings.ndaWarn1 : strings.ndaWarnN, { c: (form.client_name || '').trim(), n: ndaHits })"></p></div>
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

        {{-- Pictures: upload from the device, or pick one already on this project (prototype pickPicture) --}}
        <dialog class="pe-picker" x-ref="picker" aria-labelledby="pePkT" @close="onPickerClose()">
            <form method="dialog">
                <div class="pe-pk-h"><h2 id="pePkT" x-text="picker.title"></h2><button class="x" value="cancel" aria-label="{{ __('Close') }}"><svg class="pe-i" aria-hidden="true"><use href="#e-x"/></svg></button></div>
                <label class="pe-up"><svg class="pe-i" aria-hidden="true"><use href="#e-upload"/></svg><span><b>{{ __('Upload from your device') }}</b><small x-text="picker.many ? @js(__('JPG, PNG or WebP, several at once')) + (picker.video ? ' · MP4, MOV, WebM' : '') : @js(__('JPG, PNG or WebP'))"></small></span>
                    <input type="file" x-ref="pickerFile" :multiple="picker.many" :accept="picker.video ? 'image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm' : 'image/jpeg,image/png,image/webp'" @change="onPickerFiles($event.target.files); $event.target.value = ''"></label>
                <template x-if="pool.length">
                    <div>
                        <p class="pe-pk-k">{{ __('Or pick from this project’s pictures') }}</p>
                        <div class="pe-pk-grid">
                            <template x-for="(m, k) in pool" :key="'pk' + m.id">
                                <button type="button" class="pe-pk" :aria-pressed="picker.chosen.includes(m.id).toString()" :style="{ backgroundImage: 'url(' + m.url + ')' }"
                                        :aria-label="@js(__('Picture')) + ' ' + (k + 1)" @click="onPoolPick(m)" x-text="picker.many && picker.chosen.includes(m.id) ? picker.chosen.indexOf(m.id) + 1 : ''"></button>
                            </template>
                        </div>
                    </div>
                </template>
                <template x-if="picker.many">
                    <div class="pe-pk-foot"><button class="btn btn-2" value="cancel">{{ __('Cancel') }}</button>
                        <button class="btn" value="ok" :disabled="!picker.chosen.length" x-text="picker.chosen.length ? fmt(picker.chosen.length === 1 ? strings.addPic1 : strings.addPicN, { n: picker.chosen.length }) : @js(__('Add pictures'))"></button></div>
                </template>
            </form>
        </dialog>

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
