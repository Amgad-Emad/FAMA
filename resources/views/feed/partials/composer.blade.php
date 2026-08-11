{{-- The New post modal, rebuilt (5AI). Portalled to <body> on the z-scale
     (backdrop covers topbar + rails), header/footer pinned with a scrolling
     body, posting-as identity in the header, an Instrument 17px input (an
     input is not display type — Fraunces stays in the feed), a full-width
     dropzone whose preview uses the REAL media-grid geometry (5AE) with
     per-tile remove/reorder/ALT, the live credits line above the footer, and
     a PUBLISH button that says what's missing instead of sulking. The body is
     a <textarea> by deliberate 5N decision (native caret/RTL). --}}
@props(['composer'])

<div x-data="feedComposer({
        skills: @js($composer['skills']),
        projects: @js($composer['projects']),
        username: @js($composer['username']),
        displayName: @js($currentActor?->feedDisplayName()),
        avatarUrl: @js($currentActor?->feedAvatarUrl()),
        limits: @js([
            'body' => (int) config('feed.limits.body_max_chars'),
            'media' => (int) config('feed.limits.media_per_post'),
            'imageKb' => (int) config('feed.limits.image_max_kb'),
            'videoKb' => (int) config('feed.limits.video_max_kb'),
        ]),
     })" class="feed-col">

    {{-- Trigger — an obvious "create a post" box (avatar + prompt + a filled
         CTA). The FAB is the second entry point (5AC). --}}
    <button type="button" @click="open()"
            class="frame-shadow composer-trigger w-full text-start" x-ref="trigger" aria-label="{{ __('Create a post') }}">
        <span class="frame flex items-center gap-3 px-4 py-3.5 sm:px-5">
            {{-- posting-as avatar --}}
            @if ($currentActor?->feedAvatarUrl())
                <img src="{{ $currentActor->feedAvatarUrl() }}" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover">
            @else
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full" style="background: var(--dl-surface-sunk); font-family: var(--dl-font-display);">{{ str($currentActor?->feedDisplayName() ?? '+')->substr(0, 1) }}</span>
            @endif

            <span class="composer-field min-w-0 flex-1">
                <span class="block t-body text-ink">{{ __('Create a post') }}</span>
                <span class="block truncate t-meta">{{ __('Share a note, a set, or a Project…') }}</span>
            </span>

            {{-- filled CTA so it clearly reads as an action --}}
            <span class="composer-trigger-cta inline-flex shrink-0 items-center gap-1.5">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                <span class="hidden sm:inline">{{ __('POST') }}</span>
            </span>
        </span>
    </button>

    {{-- Panel --}}
    <template x-teleport="body">
        <div x-show="isOpen" x-cloak class="fixed inset-0" style="z-index: var(--z-modal-backdrop);"
             @keydown.escape.window="requestClose()"
             role="dialog" aria-modal="true" aria-label="{{ __('New post') }}">
            <div class="absolute inset-0" style="background: color-mix(in srgb, var(--dl-ink, #1A1712) 40%, transparent); backdrop-filter: blur(8px);" @click="requestClose()"></div>

            <div data-surface="daylight"
                 class="absolute inset-x-0 bottom-0 flex max-h-[85vh] flex-col rounded-t-2xl
                        sm:inset-auto sm:start-1/2 sm:top-1/2 sm:w-full sm:max-w-[640px] sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-2xl"
                 style="background: var(--dl-surface); box-shadow: var(--dl-shadow-lifted); z-index: var(--z-modal);"
                 @click.stop
                 @dragover.prevent @drop.prevent="mode === 'post' && addFiles($event.dataTransfer.files)">

                {{-- Pinned header: title + posting-as identity --}}
                <div class="flex items-start justify-between border-b border-seam p-6 pb-4 sm:px-8">
                    <div>
                        <h2 class="t-display">{{ __('New post') }}</h2>
                        <p class="mt-2 flex items-center gap-2">
                            <img x-show="avatarUrl" :src="avatarUrl" alt="" class="comment-avatar">
                            <span x-show="!avatarUrl" class="comment-avatar flex items-center justify-center bg-ground-sunk t-meta" x-text="(displayName ?? '?').slice(0, 1)"></span>
                            <span class="comment-author" x-text="displayName"></span>
                            <bdi class="comment-handle" x-text="'@' + username"></bdi>
                        </p>
                    </div>
                    <button type="button" class="action-btn" @click="requestClose()" aria-label="{{ __('Close') }}">✕</button>
                </div>

                {{-- Scrolling body --}}
                <div class="min-h-0 flex-1 overflow-y-auto p-6 sm:px-8">
                    {{-- Mode toggle --}}
                    <div class="flex gap-2" role="group" aria-label="{{ __('Post kind') }}">
                        <button type="button" class="dl-chip" :class="{ 'is-active': mode === 'post' }"
                                @click="mode = 'post'">{{ __('POST') }}</button>
                        <button type="button" class="dl-chip" :class="{ 'is-active': mode === 'share' }"
                                @click="mode = 'share'">{{ __('SHARE A PROJECT') }}</button>
                    </div>

                    {{-- Project picker (share mode) --}}
                    <div x-show="mode === 'share'" x-cloak class="mt-4">
                        <template x-if="projects.length === 0">
                            <p class="t-body text-muted">{{ __('No Projects yet — add one from your dashboard first.') }}</p>
                        </template>
                        <div class="flex gap-3 overflow-x-auto pb-2">
                            <template x-for="project in projects" :key="project.id">
                                <button type="button" class="frame-shadow w-40 flex-none text-start"
                                        @click="selectedProject = (selectedProject === project.id ? null : project.id)">
                                    <span class="frame block" :class="{ 'outline outline-2 outline-daylight': selectedProject === project.id }">
                                        <img x-show="project.cover" :src="project.cover" alt="" class="block aspect-[4/3] w-full object-cover">
                                        <span class="block p-3">
                                            <span class="block truncate" style="font-family: var(--dl-font-display);" x-text="project.title"></span>
                                            <span class="t-meta" x-text="project.year ?? ''"></span>
                                        </span>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Body — an input, so the UI face at 17px (5AI) --}}
                    <div class="mt-4">
                        <x-feed.mention-field x-model="body" x-ref="bodyField" :rows="3" :max-rows="10"
                                              field-class="t-comment"
                                              @mention-picked="mentions.push($event.detail.actor)"
                                              @mention-input="onBodyChanged()"
                                              :placeholder="__('What are you working on? Type @ to credit someone.')" />
                    </div>

                    {{-- Counter: appears at 1900, DANGER at the limit. NEVER add a
                         string :style here — it would rewrite the style attribute
                         and wipe the display:none that x-show manages. --}}
                    <p class="t-meta mt-1 text-end" x-show="body.length >= 1900" x-cloak
                       :class="body.length >= limits.body ? 'text-danger' : ''"
                       x-text="body.length + ' / ' + limits.body"></p>

                    {{-- Link chips (instant from parse) --}}
                    <div x-show="chips.length > 0" x-cloak class="mt-2 flex flex-wrap gap-2">
                        <template x-for="chip in chips" :key="chip.url">
                            <span class="feed-chip">
                                <span x-text="'[' + chip.provider.slice(0, 2) + ']'"></span>
                                <bdi x-text="chip.handle ? '@' + chip.handle : chip.url.replace(/^https?:\/\//, '').slice(0, 32)"></bdi>
                            </span>
                        </template>
                    </div>

                    {{-- Media (post mode): dropzone + the REAL grid geometry --}}
                    <div x-show="mode === 'post'" x-cloak class="mt-4">
                        <label x-show="media.length === 0"
                               class="flex w-full cursor-pointer flex-col items-center gap-2 rounded-md border-2 border-dashed border-seam px-6 py-10 hover:border-ink-faint hover:bg-ground-sunk">
                            <span class="t-display" aria-hidden="true">+</span>
                            <span class="t-body text-muted">{{ __('Drag images here or browse') }}</span>
                            <input type="file" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime"
                                   class="hidden" @change="addFiles($event.target.files); $event.target.value = ''">
                        </label>

                        <div x-show="media.length > 0" x-cloak>
                            {{-- The 5AE geometry, exactly as it will publish --}}
                            <div class="media-grid" :class="media.length === 1 ? 'media-grid--single' : (media.length === 2 ? 'media-grid--two' : 'media-grid--half')">
                                <template x-for="(item, index) in media.slice(0, media.length >= 4 ? 3 : media.length)" :key="item.key">
                                    <div class="media-tile" :class="{ 'media-tile--lead': media.length > 2 && index === 0 }">
                                        <img :src="item.preview" alt="" class="media-img">
                                        <span x-show="media.length > 3 && index === 2" class="media-more" x-text="'+' + (media.length - 3)"></span>
                                        <span class="absolute flex gap-1" style="inset-block-start: 4px; inset-inline-end: 4px;">
                                            <button type="button" x-show="index > 0" @click="reorder(index, index - 1)" class="dl-chip" aria-label="{{ __('Move earlier') }}">‹</button>
                                            <button type="button" x-show="index < media.length - 1" @click="reorder(index, index + 1)" class="dl-chip" aria-label="{{ __('Move later') }}">›</button>
                                            <button type="button" @click="toggleAlt(index)" class="dl-chip" :class="{ 'is-active': item.altOpen }" aria-label="{{ __('Alt text') }}">{{ __('ALT') }}</button>
                                            <button type="button" @click="removeMedia(index)" class="dl-chip" aria-label="{{ __('Remove') }}">✕</button>
                                        </span>
                                    </div>
                                </template>
                            </div>
                            <template x-for="(item, index) in media" :key="'alt-' + item.key">
                                <input x-show="item.altOpen" x-cloak type="text" x-model="item.alt" maxlength="160"
                                       class="well t-body mt-2 w-full p-2"
                                       :placeholder="'{{ __('Describe image') }} ' + (index + 1)">
                            </template>
                            <label class="dl-chip mt-2 inline-flex cursor-pointer" x-show="media.length < limits.media">
                                + {{ __('ADD MORE') }}
                                <input type="file" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime"
                                       class="hidden" @change="addFiles($event.target.files); $event.target.value = ''">
                            </label>
                        </div>
                        <p class="t-meta mt-2 text-danger" x-show="mediaError" x-cloak x-text="mediaError"></p>
                    </div>

                    {{-- Skills --}}
                    <div class="mt-5" x-show="skills.length > 0">
                        <p class="t-meta">{{ __('SKILLS') }}</p>
                        <p class="t-body mb-2 text-muted">{{ __('Tag the Skills you used on this.') }}</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="skill in skills" :key="skill.id">
                                <button type="button" class="dl-chip"
                                        :aria-pressed="selectedSkills.includes(skill.id) ? 'true' : 'false'"
                                        @click="toggleSkill(skill.id)" x-text="skill.name"></button>
                            </template>
                        </div>
                    </div>

                    {{-- The live credits preview — the composer's signature moment --}}
                    <div class="mt-5" x-show="creditsPreview().length > 0" x-cloak>
                        <hr class="seam mb-2">
                        <div class="credits" aria-live="polite">
                            <template x-for="(credit, index) in creditsPreview()" :key="index">
                                <p><span x-text="credit.label"></span> — <bdi class="credit-handle" x-text="'@' + credit.handle"></bdi></p>
                            </template>
                        </div>
                    </div>

                    <p class="t-body mt-3 text-danger" x-show="error" x-cloak x-text="error"></p>
                </div>

                {{-- Pinned footer --}}
                <div class="flex items-center justify-end gap-3 border-t border-seam p-4 sm:px-8">
                    <button type="button" class="dl-btn" @click="requestClose()">{{ __('CLOSE') }}</button>
                    <span class="relative" @mouseenter="hintVisible = !canSubmit()" @mouseleave="hintVisible = false">
                        <span x-show="hintVisible && publishHint()" x-cloak
                              class="absolute bottom-full end-0 mb-2 whitespace-nowrap rounded-md border border-seam px-3 py-1.5 t-meta"
                              style="background: var(--dl-ground);"
                              x-text="publishHint()"></span>
                        <button type="button" @click="submit()"
                                class="dl-btn" :class="{ 'is-ready': canSubmit() && !submitting, 'is-busy': submitting }"
                                :aria-disabled="(!canSubmit() || submitting) ? 'true' : 'false'"
                                :style="(!canSubmit() && !submitting) ? 'background: color-mix(in srgb, var(--dl-ink) 40%, transparent); color: var(--dl-surface);' : ''">
                            <span x-show="submitting" class="dl-spinner" aria-hidden="true"></span>
                            {{ __('PUBLISH') }}
                        </button>
                    </span>
                </div>
            </div>
        </div>
    </template>
</div>
