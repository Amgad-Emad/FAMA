{{-- The "New post" dialog (port of feed.html's #np): Post or Share-a-project,
     posting-as identity, a textarea where @ credits someone and # tags a skill,
     link chips, the 2,200 counter, media tiles (drag to reorder, alt text,
     remove), the project picker, and a footer that says what's missing. Edit
     mode (15-minute window) changes text + skills only — media is locked.
     Opened by the window events open-composer {mode} / edit-post {post}.
     Behaviour = feedComposer (ui/feed.js). Talent-only (ADR-F1). --}}
@php
    $me = $currentActor;
    $meName = $me?->feedDisplayName() ?? '';
    $meInitials = collect(preg_split('/\s+/', trim($meName)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $cfg = [
        'catalog' => $composer['catalog'],
        'projects' => $composer['projects'],
        'published' => $composer['published'],
        'limits' => [
            'body' => (int) config('feed.limits.body_max_chars'),
            'media' => (int) config('feed.limits.media_per_post'),
            'imageKb' => (int) config('feed.limits.image_max_kb'),
            'videoKb' => (int) config('feed.limits.video_max_kb'),
        ],
    ];
@endphp

<div x-data="feedComposer(@js($cfg))" @open-composer.window="open($event.detail ?? {})" @edit-post.window="openEdit($event.detail)">
    <dialog class="dlg wide" id="np" x-ref="dlg" @cancel.prevent="requestClose()">
        <form class="np" id="npf" novalidate @submit.prevent="submit()"
              @dragover.prevent @drop.prevent="onFileDrop($event)">
            <div class="dh">
                <h2 x-text="editing ? @js(__('Edit post')) : @js(__('New post'))">{{ __('New post') }}</h2>
                <button type="button" class="iconbtn plain" @click="requestClose()" aria-label="{{ __('Cancel') }}"><svg class="icon" aria-hidden="true"><use href="#i-x"/></svg></button>
            </div>
            <div class="seg" role="group" style="margin-top:var(--s4)" x-show="!editing">
                <button type="button" :aria-pressed="(mode === 'post').toString()" @click="mode = 'post'">{{ __('Post') }}</button>
                <button type="button" :aria-pressed="(mode === 'share').toString()" @click="mode = 'share'">{{ __('Share a project') }}</button>
            </div>
            <div class="who">
                <span class="av">@if ($me?->feedAvatarUrl())<img src="{{ $me->feedAvatarUrl() }}" alt="">@else{{ $meInitials }}@endif</span>
                <span><b>{{ $meName }}</b><span class="meta" dir="ltr">{{ '@'.$me?->feedUsername() }}</span></span>
            </div>

            <div style="display:contents" x-data="famaMention({ catalog })" x-modelable="value" x-model="body">
                <label class="vh" for="npText" x-text="placeholder()"></label>
                <textarea class="ta" :class="{ short: mode === 'share' }" id="npText" x-ref="field" maxlength="2400"
                          :placeholder="placeholder()" x-model="value"
                          @input="onInput($event)" @keydown="onKeydown($event)" @blur="onBlur()" @click="onInput($event)"
                          role="combobox" aria-autocomplete="list" :aria-expanded="open.toString()" :aria-controls="listId"></textarea>
                <x-feed.mention-field />
            </div>
            <div class="row">
                <div class="chips" id="lchips">
                    <template x-for="chip in chips" :key="chip.url"><span class="chip"><svg class="icon sm" aria-hidden="true"><use href="#i-link"/></svg><span x-text="chip.label"></span></span></template>
                </div>
                <span class="meta" id="cnt" x-text="counter()"></span>
            </div>

            <div x-show="mode === 'post'">
                <p class="locked" x-show="editing" x-cloak>{{ __("Media can't be changed after publishing. You can edit the text and skills for 15 minutes.") }}</p>
                <div x-show="!editing">
                    <button type="button" class="drop" style="margin-top:var(--s4)" @click="$refs.file.click()" x-show="media.length < limits.media">
                        <svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg>{{ __('Add photos or video') }}<small>{{ __('Up to 10. Drag to reorder.') }}</small>
                    </button>
                    <input type="file" x-ref="file" class="vh" tabindex="-1" aria-hidden="true" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime" @change="addFiles($event.target.files); $event.target.value = ''">
                    <div class="mgrid">
                        <template x-for="(m, i) in media" :key="m.key">
                            <div class="mtile" draggable="true" @dragstart="dragFrom = i; $event.dataTransfer.setData('text/plain', '')" @dragover.prevent @drop.prevent.stop="dropOn(i)">
                                <div class="ph">
                                    <img x-show="m.preview" :src="m.preview" alt="">
                                    <svg class="icon" aria-hidden="true" x-show="!m.preview"><use href="#i-video"/></svg>
                                    <button type="button" class="iconbtn" @click="removeMedia(i)" aria-label="{{ __('Remove') }}"><svg class="icon sm" aria-hidden="true"><use href="#i-x"/></svg></button>
                                </div>
                                <input class="in" maxlength="160" x-model="m.alt" placeholder="{{ __('Describe this image') }}" :aria-label="@js(__('Alt text')) + ' ' + (i + 1)">
                            </div>
                        </template>
                    </div>
                    <p class="err" role="alert" x-show="mediaError" x-cloak x-text="mediaError"></p>
                </div>
            </div>

            <div x-show="mode === 'share'" x-cloak>
                <p class="lbl" style="margin-top:var(--s5)">{{ __('Pick one of your projects') }}</p>
                <p class="locked" x-show="!published">{{ __('Your profile must be published to share a project.') }}</p>
                <ul class="projpick" x-show="projects.length">
                    <template x-for="p in projects" :key="p.id">
                        <li><label><input type="radio" name="proj" :value="p.id" :checked="selectedProject === p.id" @change="selectedProject = p.id"><span><b style="font-weight:600;display:block" x-text="p.title"></b><span class="meta" x-text="[p.client, p.year].filter(Boolean).join(' · ')"></span></span></label></li>
                    </template>
                </ul>
                <p class="locked" x-show="!projects.length">{{ __('No projects yet. Add one from Projects first.') }}</p>
            </div>

            <p class="err" role="alert" x-show="error" x-cloak x-text="error" style="margin-top:var(--s3)"></p>
            <div class="foot">
                <span class="need" id="need" role="status" x-text="need()"></span>
                <button type="button" class="btn btn-2" @click="requestClose()">{{ __('Cancel') }}</button>
                <button class="btn" id="pub" :aria-disabled="(need() !== '' || submitting) ? 'true' : null" aria-describedby="need" :title="need()"><svg class="icon" aria-hidden="true"><use href="#a-send"/></svg>{{ __('Publish') }}</button>
            </div>
        </form>
    </dialog>
</div>
