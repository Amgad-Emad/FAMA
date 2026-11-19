{{--
    The project's story as free-form blocks (ADR-AN) — port of fama-ui project-editor.js `blockBody()` /
    `storyHTML()`. Every word types where it shows; every picture has its own Replace; lists (pieces, numbers,
    people) add and remove in place. Each block carries the "+" that inserts a block ABOVE it; the last "+"
    ("Add a block") is the story's last child, as project-editor.css expects.
    Scope: famaProjectEdit. Pictures resolve through picUrl(m, s): a saved media id, or a file staged
    in the page (before Publish, or while it uploads).
--}}
<template x-for="(b, i) in blocks" :key="b.key">
    <div class="pe-b" :class="['pe-' + b.t, b.t === 'heading' ? 'ch' : '', active === b.key ? 'is-on' : '', dragFrom === i ? 'pe-dragging' : '']" :data-i="i" :data-key="b.key">
        <div class="pe-ins pe-ins-above"><button type="button" data-act="add" tabindex="-1" @click.stop="openAddMenu($el, i)" aria-label="{{ __('Add a block here') }}"><svg class="pe-i" aria-hidden="true"><use href="#e-plus"/></svg></button></div>
        @include('talent.partials.project-edit-tools')

        {{-- Text: one paragraph; the first before any heading is the opening line --}}
        <template x-if="b.t === 'text'">
            <p contenteditable="true" spellcheck="true" role="textbox" data-f="v"
               :class="leadKey === b.key ? 'lead pe-lead' : 'txt'"
               :data-ph="leadKey === b.key ? @js(__('Open with one line about the project')) : @js(__('Write, or type / to add a block'))"
               :aria-label="leadKey === b.key ? @js(__('Open with one line about the project')) : @js(__('Text'))"
               x-init="$el.textContent = b.v || ''" @input="onText($el, b, i)" @keydown="onTextKey($event, b, i)" @paste="onPaste($event, b, i)"></p>
        </template>

        {{-- Heading: starts a chapter --}}
        <template x-if="b.t === 'heading'">
            <div class="ch-h"><span x-text="chapterOf(i)"></span>
                <h2 contenteditable="true" spellcheck="true" role="textbox" data-f="v" data-ph="{{ __('Name this chapter') }}" aria-label="{{ __('Name this chapter') }}"
                    x-init="$el.textContent = b.v || ''" @input="b.v = plainOf($el); changed('blocks')" @keydown="onTextKey($event, b, i)" @paste="onPaste($event)"></h2></div>
        </template>

        {{-- Quote --}}
        <template x-if="b.t === 'quote'">
            <blockquote class="quote">
                <span class="pe-q" contenteditable="true" role="textbox" data-f="v" data-ph="{{ __('What was said') }}" aria-label="{{ __('What was said') }}"
                      x-init="$el.textContent = b.v || ''" @input="b.v = plainOf($el); changed('blocks')" @keydown="onTextKey($event, b, i)" @paste="onPaste($event)"></span>
                <cite contenteditable="true" role="textbox" data-ph="{{ __('Who said it, and their role') }}" aria-label="{{ __('Who said it, and their role') }}"
                      x-init="$el.textContent = b.by || ''" @input="b.by = plainOf($el); changed('blocks')" @keydown.enter.prevent @paste="onPaste($event)"></cite>
            </blockquote>
        </template>

        {{-- Image: full width; text right after it sits beside it on the page --}}
        <template x-if="b.t === 'image' && (!picUrl(b.m, b.s))">
            <button type="button" class="pe-drop" @click="pick(b, 'm')"><svg class="pe-i" aria-hidden="true"><use href="#e-image"/></svg><b>{{ __('Add an image') }}</b><small>{{ __('Pick one of yours, or upload from your device') }}</small></button>
        </template>
        <template x-if="b.t === 'image' && (picUrl(b.m, b.s))">
            <figure class="full"><div class="pe-frame"><div class="ph-img" :style="bg(b.m, b.s)"></div>
                <button type="button" class="pe-pic" @click="pick(b, 'm')"><svg class="pe-i" aria-hidden="true"><use href="#e-image"/></svg><span x-text="busy(b.s) ? strings.uploading : @js(__('Replace'))"></span></button></div>
                <figcaption><span contenteditable="true" role="textbox" data-ph="{{ __('Add a caption') }}" aria-label="{{ __('Caption') }}"
                      x-init="$el.textContent = b.cap || ''" @input="b.cap = plainOf($el); changed('blocks')" @keydown.enter.prevent @paste="onPaste($event)"></span></figcaption></figure>
        </template>

        {{-- Photos: 2 side by side; 3 = one wide + a pair; 4+ = the scrolling gallery --}}
        <template x-if="b.t === 'gallery'">
            <div class="pe-gal">
                <p class="pe-note"><svg class="pe-i" aria-hidden="true"><use href="#e-images"/></svg><span><b x-text="fmt(strings.gallery.title, { n: b.items.length })"></b><span x-text="galleryNote(b.items.length)"></span></span></p>
                <ol class="pe-strip">
                    <template x-for="(it, k) in b.items" :key="it.key">
                        <li class="pe-ph">
                            <div class="pe-ph-img" :style="isVideo(it.m, it.s) ? {} : bg(it.m, it.s)" role="img" :aria-label="@js(__('Picture')) + ' ' + (k + 1)">
                                <template x-if="isVideo(it.m, it.s)"><video :src="picUrl(it.m, it.s)" muted playsinline preload="metadata"></video></template>
                                <span class="pe-ph-n" x-text="busy(it.s) ? '…' : String(k + 1).padStart(2, '0')"></span>
                                <span class="pe-ph-acts">
                                    <button type="button" x-show="k > 0" @click="moveItem(b, k, -1)" :aria-label="@js(__('Move picture :n earlier')).replace(':n', k + 1)"><svg class="pe-i" aria-hidden="true"><use href="#e-left"/></svg></button>
                                    <button type="button" x-show="k < b.items.length - 1" @click="moveItem(b, k, 1)" :aria-label="@js(__('Move picture :n later')).replace(':n', k + 1)"><svg class="pe-i" aria-hidden="true"><use href="#e-right"/></svg></button>
                                    <button type="button" @click="pick(it, 'm', { gallery: true })" :aria-label="@js(__('Replace picture :n')).replace(':n', k + 1)"><svg class="pe-i" aria-hidden="true"><use href="#e-image"/></svg></button>
                                    <button type="button" @click="removeItem(b, k)" :aria-label="@js(__('Remove picture :n')).replace(':n', k + 1)"><svg class="pe-i" aria-hidden="true"><use href="#e-x"/></svg></button>
                                </span>
                            </div>
                            <p class="pe-ph-cap" contenteditable="true" role="textbox" data-ph="{{ __('Caption') }}" :aria-label="@js(__('Caption')) + ' ' + (k + 1)"
                               x-init="$el.textContent = it.cap || ''" @input="it.cap = plainOf($el); changed('blocks')" @keydown.enter.prevent @paste="onPaste($event)"></p>
                        </li>
                    </template>
                    <li class="pe-ph add" x-show="b.items.length < 24"><button type="button" @click="pickMany(b)"><svg class="pe-i" aria-hidden="true"><use href="#e-plus"/></svg><b>{{ __('Add pictures') }}</b><small>{{ __('Pick or upload') }}</small></button></li>
                </ol>
            </div>
        </template>

        {{-- Before / After: one frame twice, with a line to drag --}}
        <template x-if="b.t === 'beforeafter' && (!picUrl(b.ma, b.sa) && !picUrl(b.mb, b.sb))">
            <button type="button" class="pe-drop" @click="pickBeforeAfter(b)"><svg class="pe-i" aria-hidden="true"><use href="#e-split"/></svg><b>{{ __('Add the before and after') }}</b><small>{{ __('The same frame twice works best') }}</small></button>
        </template>
        <template x-if="b.t === 'beforeafter' && (picUrl(b.ma, b.sa) || picUrl(b.mb, b.sb))">
            <figure class="ba"><div class="ba-box" style="--p:50%" @pointerdown="onBaDown($event)">
                <div class="ph-img ba-after" :style="bg(b.ma, b.sa) || bg(b.mb, b.sb)"></div>
                <div class="ph-img ba-before real" :style="bg(b.mb, b.sb) || bg(b.ma, b.sa)"></div>
                <span class="ba-tag l" contenteditable="true" role="textbox" data-ph="{{ __('Before') }}" aria-label="{{ __('Before') }}" x-init="$el.textContent = b.lb || ''" @input="b.lb = plainOf($el); changed('blocks')" @keydown.enter.prevent @paste="onPaste($event)"></span>
                <span class="ba-tag r" contenteditable="true" role="textbox" data-ph="{{ __('After') }}" aria-label="{{ __('After') }}" x-init="$el.textContent = b.la || ''" @input="b.la = plainOf($el); changed('blocks')" @keydown.enter.prevent @paste="onPaste($event)"></span>
                <div class="ba-line" aria-hidden="true"><span class="ba-knob">‹ ›</span></div>
                <span class="pe-ba-acts">
                    <button type="button" class="pe-pic" @click="pick(b, 'mb')"><svg class="pe-i" aria-hidden="true"><use href="#e-image"/></svg>{{ __('Before') }}</button>
                    <button type="button" class="pe-pic" @click="pick(b, 'ma')"><svg class="pe-i" aria-hidden="true"><use href="#e-image"/></svg>{{ __('After') }}</button>
                </span></div>
                <figcaption><span contenteditable="true" role="textbox" data-ph="{{ __('Say what changed') }}" aria-label="{{ __('Caption') }}" x-init="$el.textContent = b.cap || ''" @input="b.cap = plainOf($el); changed('blocks')" @keydown.enter.prevent @paste="onPaste($event)"></span></figcaption></figure>
        </template>

        {{-- Look with tags: numbered pieces on a photo --}}
        <template x-if="b.t === 'look' && (!picUrl(b.m, b.s))">
            <button type="button" class="pe-drop" @click="pick(b, 'm')"><svg class="pe-i" aria-hidden="true"><use href="#e-tags"/></svg><b>{{ __('Add the look’s photo') }}</b><small>{{ __('Then click on each piece to tag it') }}</small></button>
        </template>
        <template x-if="b.t === 'look' && (picUrl(b.m, b.s))">
            <div class="look pe-look" @pointerover="lightPiece($event)">
                <figure class="look-img" data-look @pointerdown="onLookDown($event, b)">
                    <div class="ph-img" :style="bg(b.m, b.s)"></div>
                    <template x-for="(x, k) in b.items" :key="x.key">
                        <button type="button" class="dot" :data-dot="k" :style="{ left: x.x + '%', top: x.y + '%' }" @keydown="onDotKey($event, x)"
                                :aria-label="(k + 1) + '. ' + (x.n || @js(__('piece'))) + '. ' + @js(__('Arrow keys move it'))" x-text="k + 1"></button>
                    </template>
                    <button type="button" class="pe-pic" @click="pick(b, 'm')"><svg class="pe-i" aria-hidden="true"><use href="#e-image"/></svg>{{ __('Replace photo') }}</button>
                    <p class="pe-look-tip" x-text="touch ? @js(__('Tap the photo to add a piece · drag a number onto its piece')) : @js(__('Click the photo to add a piece · drag a number onto its piece'))"></p>
                </figure>
                <div class="look-list">
                    <div class="pe-look-h"><span class="pe-look-name" contenteditable="true" role="textbox" data-ph="{{ __('Name the look') }}" aria-label="{{ __('Name the look') }}" x-init="$el.textContent = b.title || ''" @input="b.title = plainOf($el); changed('blocks')" @keydown.enter.prevent @paste="onPaste($event)"></span><span>{{ __('What’s in it') }}</span></div>
                    <ol>
                        <template x-for="(x, k) in b.items" :key="x.key">
                            <li :data-tag="k + 1">
                                <b contenteditable="true" role="textbox" :data-piece="x.key" data-ph="{{ __('Piece, e.g. Overshirt') }}" aria-label="{{ __('Piece') }}" x-init="$el.textContent = x.n || ''" @input="x.n = plainOf($el); changed('blocks')" @keydown.enter.prevent @paste="onPaste($event)"></b>
                                <span contenteditable="true" role="textbox" data-ph="{{ __('Brand · material, colour') }}" aria-label="{{ __('Brand · material, colour') }}" x-init="$el.textContent = x.d || ''" @input="x.d = plainOf($el); changed('blocks')" @keydown.enter.prevent @paste="onPaste($event)"></span>
                                <span class="pe-row-acts">
                                    <button type="button" x-show="k > 0" @click="movePiece(b, k)" aria-label="{{ __('Move up') }}"><svg class="pe-i" aria-hidden="true"><use href="#e-up"/></svg></button>
                                    <button type="button" @click="removePiece(b, k)" :aria-label="@js(__('Remove')) + ' ' + (x.n || @js(__('this piece')))"><svg class="pe-i" aria-hidden="true"><use href="#e-x"/></svg></button>
                                </span>
                            </li>
                        </template>
                    </ol>
                    <button type="button" class="pe-add-row" x-show="b.items.length < 8" @click="addPiece(b)"><svg class="pe-i" aria-hidden="true"><use href="#e-plus"/></svg>{{ __('Add a piece') }}<small>{{ __('or click on the photo') }}</small></button>
                </div>
            </div>
        </template>

        {{-- Video: a YouTube or Vimeo link --}}
        <template x-if="b.t === 'video'">
            <figure class="video">
                <label class="pe-url"><svg class="pe-i" aria-hidden="true"><use href="#e-video"/></svg>
                    <input type="url" dir="ltr" maxlength="2048" x-model.lazy="b.url" @change="changed('blocks')" placeholder="{{ __('Paste a YouTube or Vimeo link') }}" aria-label="{{ __('Video link') }}">
                    <small x-show="videoHost(b.url)" x-cloak x-text="videoHost(b.url)"></small></label>
                <figcaption><b>{{ __('Film') }}</b><span contenteditable="true" role="textbox" data-ph="{{ __('What it is') }}" aria-label="{{ __('What it is') }}" x-init="$el.textContent = b.cap || ''" @input="b.cap = plainOf($el); changed('blocks')" @keydown.enter.prevent @paste="onPaste($event)"></span></figcaption>
            </figure>
        </template>

        {{-- Social post: Instagram, TikTok, Behance or YouTube Shorts --}}
        <template x-if="b.t === 'embed'">
            <div class="pe-emb">
                <template x-if="b.url"><div class="emb-card" aria-hidden="true"><span class="emb-top"><span class="av" x-text="initials(handleOf(b.url).replace(/[._-]/g, ' '))"></span><span><b x-text="handleOf(b.url)"></b><i x-text="platformOf(b.url) || @js(__('Link'))"></i></span></span><span class="ph-img"></span></div></template>
                <div class="pe-emb-side"><p class="pe-emb-k" x-text="firstEmbed(i) ? @js(__('Also posted · posts next to each other show side by side')) : @js(__('Social post'))"></p>
                    <label class="pe-url"><svg class="pe-i" aria-hidden="true"><use href="#e-link"/></svg>
                        <input type="url" dir="ltr" maxlength="2048" x-model.lazy="b.url" @change="changed('blocks')" placeholder="{{ __('Paste an Instagram, TikTok, Behance or YouTube Shorts link') }}" aria-label="{{ __('Post link') }}">
                        <small x-show="platformOf(b.url)" x-cloak x-text="platformOf(b.url)"></small></label></div>
            </div>
        </template>

        {{-- Results: up to four numbers (the project's rows) --}}
        <template x-if="b.t === 'results'">
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
        </template>
        <template x-if="b.t === 'results'">
            <p class="pe-hint">{{ __('Type a number the way it reads. A % gets a bar; a + or − shows a rise or a fall, with what it’s compared to.') }}</p>
        </template>
        <template x-if="b.t === 'results' && errors.results">
            <p class="pe-err" role="alert" x-text="errors.results"></p>
        </template>

        {{-- Credits (person + skill + role): on the page they join the "Made by" band --}}
        <template x-if="b.t === 'credits'">
            <ul class="cred">
                <li class="pe-me"><span class="av"><template x-if="me.avatar"><img :src="me.avatar" alt=""></template><template x-if="!me.avatar"><span x-text="me.initials"></span></template></span><b x-text="me.name"></b><span class="r" x-text="form.role.en || strings.yourRole"></span><span class="st">{{ __('Owner') }}</span></li>
                <template x-for="c in credits" :key="'c' + c.id">
                    <li>
                        <span class="av"><template x-if="c.talent?.avatar_url"><img :src="c.talent.avatar_url" alt=""></template><template x-if="!c.talent?.avatar_url"><span x-text="initials(c.name)"></span></template></span>
                        <b x-text="c.name"></b>
                        <span class="r" x-text="[c.role, c.skill?.name].filter(Boolean).join(' · ')"></span>
                        <span class="st" :class="creditClass(c.status)" x-text="creditState(c)"></span>
                        <button type="button" class="pe-x" @click="removeCredit(c)" :aria-label="@js(__('Remove')) + ' ' + c.name"><svg class="pe-i" aria-hidden="true"><use href="#e-x"/></svg></button>
                    </li>
                </template>
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
        </template>
    </div>
</template>

{{-- The last + : "Add a block" (project-editor.css draws it) --}}
<div class="pe-ins"><button type="button" data-act="add" @click.stop="openAddMenu($el, blocks.length)" aria-label="{{ __('Add a block') }}"><svg class="pe-i" aria-hidden="true"><use href="#e-plus"/></svg></button></div>
