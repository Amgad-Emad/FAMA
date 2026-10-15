{{--
    The project editor (fama-ui `project-edit.html`) — a project is edited in
    place, on a page that looks like the published project: cover, title, a few
    properties, then blocks of content. No pop-up.

    Mapped onto the real project model (ADR-X / ADR-Y / ADR-AA):
      cover        → the single-file `cover` collection (optional)
      title        → title.en (English-only in V1; a stored Arabic value rides
                     along untouched in the payload)
      properties   → client_name (hidden on the page when empty), role.en,
                     occurred_on (recorded, never shown), skills (≥1 of the
                     talent's own; the first is the home tab), specialities (only
                     from tagged skills), url
      blocks       → Text = summary.en, the full story = body.en, Gallery = the
                     ordered `media` collection (the order is the edit), Results =
                     the results map, Credits = project_credits (on-Fama people
                     confirm before they show; off-Fama names list immediately)

    A NEW project is a draft in this page until Publish (POST /talent/projects);
    staged cover/media/credits are sent right after. From then on every change
    autosaves (PATCH) — "Saves as you go".

    Behaviour: `famaProjectEdit` (resources/js/ui/projects.js).
--}}
@php
    $strings = [
        'draft' => __('Draft'),
        'published' => __('Published'),
        'publish' => __('Publish'),
        'update' => __('Update'),
        'saving' => __('Saving…'),
        'saved' => __('Saved'),
        'notSaved' => __('Not published yet'),
        'saveFailed' => __('Couldn’t save — :m'),
        'needTitle' => __('Add a title first.'),
        'needSkill' => __('Pick at least one skill.'),
        'published1' => __('Published to your profile.'),
        'publishedAsked1' => __('Published. :n is asked to confirm their credit.'),
        'publishedAskedN' => __('Published. :n people are asked to confirm their credit.'),
        'live' => __('Changes are live.'),
        'coverAdded' => __('Cover added'),
        'coverRemoved' => __('Cover removed'),
        'allSpecs' => __('All the specialities of these skills are added.'),
        'removeMediaTitle' => __('Remove this photo?'),
        'removeMediaBody' => __('It is deleted from this project for good.'),
        'removeOk' => __('Remove'),
        'removeCreditTitle' => __('Remove :n from the credits?'),
        'removeCreditBody' => __('Their name comes off this project, and off their profile if they had confirmed it.'),
        'creditAdded' => __('Credit added.'),
        'creditSent' => __('Credit sent — it appears once they confirm it.'),
        'uploading' => __('Uploading…'),
        'leave' => __('You have unsaved changes.'),
        'failed' => __('Something went wrong. Try again.'),
        // The + / "/" block menu: [label, hint].
        'blocks' => [
            'story' => [__('Text'), __('The full story: the brief, what you did')],
            'gallery' => [__('Gallery'), __('A grid of photos and video')],
            'results' => [__('Results'), __('Numbers the work moved')],
            'credits' => [__('Credits'), __('The people you made it with')],
        ],
        'creditStates' => [
            'accepted' => __('Confirmed'),
            'pending' => __('Waiting for them to confirm'),
            'declined' => __('Declined'),
            'listed' => __('Not on Fama — listed on the project'),
            'listedDraft' => __('Not on Fama — listed once saved'),
            'sending' => __('Asked to confirm once saved'),
            'onPublish' => __('Asked to confirm when you publish'),
        ],
    ];
    $editUrlTemplate = route('talent.projects.edit', ['project' => '__ID__']);
@endphp
<x-fama.layout :title="$project ? __('Edit project') : __('New project')">
    <x-slot:styles>
        <link rel="stylesheet" href="{{ asset('fama-ui/css/pages/project-edit.css') }}">
        <style>
            /* App additions on top of the prototype sheet. */
            .grid3 .tile{cursor:grab;overflow:hidden}
            .grid3 .tile.dragging{opacity:.4}
            .grid3 .tile video{width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block}
            .grid3 .tile .x{z-index:1}
            .person{position:relative;grid-template-columns:40px repeat(3,minmax(0,1fr)) 28px}
            .person select{border:0;background:none;font:inherit;outline:none;min-width:0;padding:var(--s1);color:inherit}
            .person select:focus{box-shadow:inset 0 -1px 0 var(--ink)}
            .person .menu{top:100%;inset-inline-start:40px}
            .av img{width:100%;height:100%;border-radius:inherit;object-fit:cover;display:block}
            .txt a{color:var(--purple-dk);text-decoration:underline}
            @media (max-width:760px){.person{grid-template-columns:32px minmax(0,1fr) 28px}.person input+input,.person select{grid-column:2}}
        </style>
    </x-slot:styles>

    <div x-data="famaProjectEdit({
            project: @js($payload),
            skills: @js($skills),
            specialities: @js($specialities),
            allSkills: @js($allSkills),
            slug: @js($homeOwner->slug),
            live: @js((bool) $homeOwner->is_published),
            editUrl: @js($editUrlTemplate),
            strings: @js($strings),
         })"
         @keydown.escape.window="closeMenu()"
         @beforeunload.window="onBeforeUnload($event)">

        <div class="ed-bar"><div class="in">
            <a class="back" href="{{ route('talent.projects') }}"><svg class="icon" aria-hidden="true"><use href="#e-arrow"/></svg>{{ __('Projects') }}</a>
            <span class="pill" :class="id ? 'p-live' : 'p-off'" id="status" x-text="id ? strings.published : strings.draft">{{ $project ? __('Published') : __('Draft') }}</span>
            <span class="saved" id="saved" role="status" x-text="savedText">{{ $project ? __('Saved') : __('Not published yet') }}</span>
            <a class="btn btn-2" id="previewBtn" :href="previewUrl" x-show="previewUrl" x-cloak target="_blank" rel="noopener"><svg class="icon" aria-hidden="true"><use href="#a-eye"/></svg>{{ __('Preview') }}</a>
            <button class="btn" id="publishBtn" type="button" @click="publish()" :disabled="publishing"><svg class="icon" aria-hidden="true"><use href="#a-send"/></svg><span x-text="id ? strings.update : strings.publish">{{ $project ? __('Update') : __('Publish') }}</span></button>
        </div></div>

        <main class="canvas" id="canvas" aria-label="{{ __('Project editor') }}">

            {{-- Cover (optional) --}}
            <div class="cover" :class="coverUrl ? 'has' : 'empty'" :style="coverUrl ? { backgroundImage: 'url(' + coverUrl + ')' } : {}">
                <template x-if="coverUrl">
                    <div class="cbtns">
                        <button class="btn btn-2" type="button" @click="$refs.coverInput.click()"><svg class="icon" aria-hidden="true"><use href="#e-upload"/></svg><span x-text="coverBusy ? strings.uploading : @js(__('Change cover'))"></span></button>
                        <button class="btn btn-2" type="button" @click="removeCover()">{{ __('Remove') }}</button>
                    </div>
                </template>
                <template x-if="!coverUrl">
                    <button class="addc" type="button" @click="$refs.coverInput.click()"><svg class="icon" aria-hidden="true"><use href="#e-image"/></svg><span x-text="coverBusy ? strings.uploading : @js(__('Add a cover'))">{{ __('Add a cover') }}</span></button>
                </template>
            </div>
            <input type="file" x-ref="coverInput" accept="image/jpeg,image/png,image/webp" style="display:none"
                   @change="pickCover($event.target.files[0]); $event.target.value = ''">

            {{-- Title --}}
            <h1 class="title" contenteditable="true" id="title" data-ph="{{ __('Untitled project') }}" role="textbox" aria-label="{{ __('Project title') }}"
                x-init="$el.textContent = form.title.en || ''; if (!id) $nextTick(() => $el.focus())"
                @input="onTitle($el)" @keydown.enter.prevent="focusFirstBlock()"></h1>

            {{-- Properties --}}
            <div class="props">
                <div class="prop"><span class="k"><svg class="icon" aria-hidden="true"><use href="#e-brief"/></svg>{{ __('Client') }}</span>
                    <input class="v" maxlength="255" x-model="form.client_name" @input="changed()" placeholder="{{ __('Add a client, or leave empty') }}" aria-label="{{ __('Client') }}"></div>
                <div class="prop"><span class="k"><svg class="icon" aria-hidden="true"><use href="#e-user"/></svg>{{ __('Your role') }}</span>
                    <input class="v" maxlength="255" x-model="form.role.en" @input="changed()" placeholder="{{ __('What did you do on it?') }}" aria-label="{{ __('Your role') }}"></div>
                <div class="prop"><span class="k"><svg class="icon" aria-hidden="true"><use href="#e-cal"/></svg>{{ __('Date') }}</span>
                    <span class="v"><input class="vin" type="month" :value="monthValue" @input="onMonth($event.target.value)" max="{{ now()->addYear()->format('Y-m') }}" aria-label="{{ __('Date') }}"><span class="hint">{{ __('Only for sorting, never shown') }}</span></span></div>
                <div class="prop"><span class="k"><svg class="icon" aria-hidden="true"><use href="#e-tag"/></svg>{{ __('Skills') }}</span>
                    <div class="v" role="group" aria-label="{{ __('Skills') }}">
                        @foreach ($skills as $skill)
                            <button class="sel-chip" type="button" @click="toggleSkill({{ $skill['id'] }})" :aria-pressed="hasSkill({{ $skill['id'] }}).toString()" aria-pressed="false"
                                    :title="homeSkillId === {{ $skill['id'] }} ? @js(__('Home tab on your profile')) : ''"><span class="pin" aria-hidden="true" x-show="homeSkillId === {{ $skill['id'] }}" x-cloak></span>{{ $skill['name'] }}</button>
                        @endforeach
                    </div></div>
                <div class="prop"><span class="k"><svg class="icon" aria-hidden="true"><use href="#e-tag"/></svg>{{ __('Specialities') }}</span>
                    <div class="v" role="group" aria-label="{{ __('Specialities') }}">
                        <template x-if="form.skill_ids.length">
                            <span style="display:contents">
                                <template x-for="sp in selectedSpecialities" :key="sp.id">
                                    <button class="sel-chip" type="button" aria-pressed="true" @click="toggleSpeciality(sp.id)" :aria-label="@js(__('Remove')) + ' ' + sp.name"><span x-text="sp.name"></span> <svg class="icon" aria-hidden="true"><use href="#e-x"/></svg></button>
                                </template>
                                <button class="sel-chip" type="button" data-act="addspec" @click.stop="openSpecMenu($el)"><svg class="icon" aria-hidden="true"><use href="#e-plus"/></svg>{{ __('Add') }}</button>
                            </span>
                        </template>
                        <template x-if="!form.skill_ids.length"><span class="hint">{{ __('Pick a skill first') }}</span></template>
                    </div></div>
                <div class="prop"><span class="k"><svg class="icon" aria-hidden="true"><use href="#e-link"/></svg>{{ __('Link') }}</span>
                    <input class="v" type="url" dir="ltr" maxlength="2048" x-model="form.url" @input="changed()" placeholder="{{ __('Add a link to the live work') }}" aria-label="{{ __('Link') }}"></div>
            </div>

            <hr class="divider">

            <div class="blocks" id="blocks">
                {{-- Text — the summary (shows first on the project page) --}}
                <div class="blk" data-b="summary">
                    <div class="gutter"><button class="gbtn" type="button" data-act="add" @click.stop="openAddMenu($el)" aria-label="{{ __('Add a block below') }}"><svg class="icon" aria-hidden="true"><use href="#e-plus"/></svg></button></div>
                    <div class="txt" contenteditable="true" role="textbox" aria-multiline="true" aria-label="{{ __('Text') }}"
                         data-ph="{{ __('Write about the project, or type / to add a block') }}"
                         x-init="$el.innerHTML = form.summary.en || ''" @input="onRich($el, 'summary')"></div>
                </div>

                {{-- The full story (body) --}}
                <template x-if="shown.story">
                    <div class="blk" data-b="story">
                        <div class="gutter"><button class="gbtn" type="button" data-act="add" @click.stop="openAddMenu($el)" aria-label="{{ __('Add a block below') }}"><svg class="icon" aria-hidden="true"><use href="#e-plus"/></svg></button></div>
                        <div class="txt" contenteditable="true" role="textbox" aria-multiline="true" aria-label="{{ __('The full story') }}"
                             data-ph="{{ __('Tell the story: the brief, what you did, how it landed') }}"
                             x-init="$el.innerHTML = form.body.en || ''" @input="onRich($el, 'body')"></div>
                    </div>
                </template>

                {{-- Gallery — the work, in order (the first frame leads) --}}
                <template x-if="shown.gallery">
                    <div class="blk" data-b="gallery">
                        <div class="gutter"><button class="gbtn" type="button" data-act="add" @click.stop="openAddMenu($el)" aria-label="{{ __('Add a block below') }}"><svg class="icon" aria-hidden="true"><use href="#e-plus"/></svg></button></div>
                        <div class="grid3">
                            <template x-for="(m, k) in gallery" :key="m.key">
                                <div class="tile" :class="{ has: !m.is_video, dragging: mediaDrag === m.key }" role="img" :aria-label="@js(__('Photo')) + ' ' + (k + 1)"
                                     :style="m.is_video ? {} : { backgroundImage: 'url(' + m.url + ')' }"
                                     draggable="true" @dragstart="onMediaDragStart($event, m)" @dragover.prevent @drop.prevent="onMediaDrop(m)" @dragend="mediaDrag = null">
                                    <template x-if="m.is_video"><video :src="m.url" muted playsinline preload="metadata"></video></template>
                                    <button class="gbtn x" type="button" @click="removeMedia(m)" :aria-label="@js(__('Remove photo')) + ' ' + (k + 1)"><svg class="icon" aria-hidden="true"><use href="#e-x"/></svg></button>
                                </div>
                            </template>
                            <button class="addt" type="button" @click="$refs.mediaInput.click()" :disabled="uploading"><svg class="icon" aria-hidden="true"><use href="#e-plus"/></svg><span x-text="uploading ? strings.uploading : @js(__('Add photos'))">{{ __('Add photos') }}</span></button>
                        </div>
                        <p class="rhint">{{ __('Up to 12 per upload, 50 MB each. JPG, PNG, WebP, MP4, MOV, WebM. Drag to order — the first one leads.') }}</p>
                        <input type="file" x-ref="mediaInput" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm" style="display:none"
                               @change="addMedia($event.target.files); $event.target.value = ''">
                    </div>
                </template>

                {{-- Results --}}
                <template x-if="shown.results">
                    <div class="blk" data-b="results">
                        <div class="gutter"><button class="gbtn" type="button" data-act="add" @click.stop="openAddMenu($el)" aria-label="{{ __('Add a block below') }}"><svg class="icon" aria-hidden="true"><use href="#e-plus"/></svg></button></div>
                        <p class="blk-l">{{ __('Results') }}</p>
                        <div class="results">
                            <template x-for="(r, k) in form.results" :key="k">
                                <div class="metric" :class="metricClass(r.value)">
                                    <div class="nrow"><span class="ar" aria-hidden="true"><svg class="icon"><use href="#e-trend"/></svg></span>
                                        <div class="n" contenteditable="true" data-ph="1.2M" aria-label="{{ __('Number') }}" x-init="$el.textContent = r.value || ''" @input="r.value = $el.textContent; changed()"></div></div>
                                    <div class="l" contenteditable="true" data-ph="{{ __('Reach') }}" aria-label="{{ __('What it measures') }}" x-init="$el.textContent = r.label || ''" @input="r.label = $el.textContent; changed()"></div>
                                    <span class="bar" aria-hidden="true"><i :style="{ width: pctOf(r.value) + '%' }"></i></span>
                                    <div class="cmp" contenteditable="true" data-ph="{{ __('Compared with, e.g. last season') }}" aria-label="{{ __('Compared with') }}" x-init="$el.textContent = r.compare || ''" @input="r.compare = $el.textContent; changed()"></div>
                                </div>
                            </template>
                            <button class="metric add" type="button" x-show="form.results.length < 4" @click="addResult()"><svg class="icon" aria-hidden="true"><use href="#e-plus"/></svg> {{ __('Add a number') }}</button>
                        </div>
                        <p class="rhint">{{ __('Up to four. Type the number the way it reads: 1.2M, 68%, +40%. A percent gets a bar; a + or − shows a rise or a fall, with what it’s compared to.') }}</p>
                    </div>
                </template>

                {{-- Credits (person + skill + role) --}}
                <template x-if="shown.credits">
                    <div class="blk" data-b="credits">
                        <div class="gutter"><button class="gbtn" type="button" data-act="add" @click.stop="openAddMenu($el)" aria-label="{{ __('Add a block below') }}"><svg class="icon" aria-hidden="true"><use href="#e-plus"/></svg></button></div>
                        <p class="blk-l">{{ __('Credits') }}</p>
                        <div class="people">
                            {{-- Saved credits (read-only: a credit is removed and re-added, never rewritten) --}}
                            <template x-for="c in credits" :key="'c' + c.id">
                                <div class="person">
                                    <span class="av"><template x-if="c.talent?.avatar_url"><img :src="c.talent.avatar_url" alt=""></template><template x-if="!c.talent?.avatar_url"><span x-text="initials(c.name)"></span></template></span>
                                    <input :value="c.name" readonly aria-label="{{ __('Person') }}">
                                    <input :value="c.skill?.name || ''" readonly aria-label="{{ __('Skill') }}">
                                    <input :value="c.role" readonly aria-label="{{ __('Role') }}">
                                    <button class="gbtn" type="button" @click="removeCredit(c)" :aria-label="@js(__('Remove')) + ' ' + c.name"><svg class="icon" aria-hidden="true"><use href="#e-x"/></svg></button>
                                    <span class="st" :class="c.status === 'accepted' ? 'ok' : 'wait'" x-text="creditState(c)"></span>
                                </div>
                            </template>
                            {{-- New rows: saved as soon as they have a person, a skill and a role --}}
                            <template x-for="(d, k) in drafts" :key="d.key">
                                <div class="person" @focusout="onDraftBlur($event, d)">
                                    <span class="av"><template x-if="d.talent?.avatar_url"><img :src="d.talent.avatar_url" alt=""></template><template x-if="!d.talent?.avatar_url"><span x-text="initials(d.name || '?')"></span></template></span>
                                    <input x-model="d.name" maxlength="120" placeholder="{{ __('Name, or search Fama') }}" aria-label="{{ __('Person') }}" autocomplete="off" @input="onDraftName(d)" :data-draft="d.key">
                                    <select x-model.number="d.talent_type_id" aria-label="{{ __('Skill') }}" @change="maybeCommit(d)">
                                        <option value="">{{ __('Skill…') }}</option>
                                        @foreach ($allSkills as $s)
                                            <option value="{{ $s['id'] }}">{{ $s['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <input x-model="d.role" maxlength="80" placeholder="{{ __('Role, e.g. Photographer') }}" aria-label="{{ __('Role') }}">
                                    <button class="gbtn" type="button" @click="drafts.splice(k, 1)" :aria-label="@js(__('Remove')) + ' ' + (d.name || @js(__('person')))"><svg class="icon" aria-hidden="true"><use href="#e-x"/></svg></button>
                                    <span class="st wait" x-show="d.name" x-text="draftState(d)"></span>
                                    <ul class="menu" role="listbox" x-show="d.results.length" x-cloak>
                                        <li class="h">{{ __('On Fama') }}</li>
                                        <template x-for="p in d.results" :key="p.id">
                                            <li><button type="button" role="option" @mousedown.prevent="pickPerson(d, p)">
                                                <span class="ic"><span class="av" style="width:30px;height:30px;font-size:11px"><template x-if="p.avatar_url"><img :src="p.avatar_url" alt=""></template><template x-if="!p.avatar_url"><span x-text="initials(p.display_name)"></span></template></span></span>
                                                <span><b x-text="p.display_name"></b><small dir="ltr" x-text="'@' + p.username"></small></span>
                                            </button></li>
                                        </template>
                                    </ul>
                                </div>
                            </template>
                        </div>
                        <button class="addrow" type="button" @click="addDraft()"><svg class="icon" aria-hidden="true"><use href="#e-plus"/></svg>{{ __('Credit someone') }}</button>
                        <p class="rhint">{{ __('Anyone on Fama gets a request, and their name shows once they confirm. People not on Fama are listed right away.') }}</p>
                    </div>
                </template>
            </div>

            <button class="addrow addblock" type="button" data-act="add" x-show="addable.length" @click.stop="openAddMenu($el)"><svg class="icon" aria-hidden="true"><use href="#e-plus"/></svg>{{ __('Add a block') }}</button>
        </main>

        {{-- The + / "/" menu, and the specialities picker --}}
        <ul class="menu" role="menu" x-show="menu.open" x-cloak x-ref="menu" @click.outside="closeMenu()" @keydown="onMenuKey($event)"
            :style="{ position: 'fixed', top: menu.top + 'px', left: menu.left + 'px' }">
            <template x-for="(it, i) in menu.items" :key="it.key">
                <li>
                    <template x-if="it.header"><span class="h" style="display:block" x-text="it.label"></span></template>
                    <template x-if="!it.header">
                        <button type="button" role="menuitem" :class="{ on: i === menu.firstIndex }" @click="pickMenu(it)">
                            <template x-if="it.icon"><span class="ic"><svg class="icon" aria-hidden="true"><use :href="'#' + it.icon"/></svg></span></template>
                            <span><b x-text="it.label"></b><template x-if="it.sub"><small x-text="it.sub"></small></template></span>
                        </button>
                    </template>
                </li>
            </template>
        </ul>
    </div>
</x-fama.layout>
