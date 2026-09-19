{{-- Project composer — create/edit in one portalled dialog (nested in the
     talentProjects scope). Teleported to <body> so no transformed ancestor traps
     it; enter/leave is driven by :class + CSS (never x-transition on a
     teleported node — its leave never completes and the overlay eats clicks). --}}
<template x-teleport="body">
    <div x-show="modalOpen" x-cloak
         style="z-index: var(--z-modal);"
         class="fixed inset-0 flex items-end justify-center transition-opacity duration-200 ease-out motion-reduce:transition-none sm:items-center sm:p-6"
         :class="modalActive ? 'opacity-100' : 'opacity-0 pointer-events-none'"
         role="dialog" aria-modal="true" aria-labelledby="project-composer-title"
         @keydown.escape.window="requestClose()">

        {{-- Scrim --}}
        <div @click="requestClose()" class="absolute inset-0" style="background: var(--scrim);"></div>

        <div x-ref="dialog" tabindex="-1" @keydown="trapFocus($event)"
             class="relative flex max-h-[94vh] w-full flex-col rounded-t-2xl border border-line bg-surface shadow-e4 outline-none transition duration-200 ease-out motion-reduce:transition-none sm:max-h-[88vh] sm:max-w-3xl sm:rounded-2xl"
             :class="modalActive ? 'translate-y-0 opacity-100 sm:scale-100' : 'translate-y-6 opacity-0 sm:translate-y-2 sm:scale-95'">

            {{-- Pinned header --}}
            <div class="flex items-start justify-between gap-4 border-b border-line px-6 py-5 sm:px-7">
                <div class="min-w-0">
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-accent-ink" x-text="editing ? '{{ __('Edit project') }}' : '{{ __('New project') }}'"></p>
                    <h2 id="project-composer-title" class="mt-1 truncate font-display text-2xl leading-tight text-ink"
                        x-text="editing ? (editing.label || '{{ __('Untitled project') }}') : '{{ __('Add a piece of work') }}'"></h2>
                </div>
                <button type="button" @click="requestClose()" aria-label="{{ __('Close') }}"
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-pill text-lg text-subtle transition hover:bg-elevated hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">✕</button>
            </div>

            {{-- Scrolling body (the dialog scrolls, never the page) --}}
            <div class="flex-1 space-y-8 overflow-y-auto px-6 py-6 sm:px-7">

                {{-- Any envelope-level failure (a domain rule the server enforces) --}}
                <p x-show="errors._" x-cloak class="rounded-md border border-danger bg-danger/5 px-4 py-3 text-sm text-danger" x-text="errors._?.[0]"></p>

                {{-- 1 · Cover ------------------------------------------------------}}
                <section>
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Cover') }}</span>
                            <span class="rounded-pill border border-line px-2 py-0.5 font-mono text-[9px] uppercase tracking-wider text-subtle">{{ __('Optional') }}</span>
                        </div>
                        <button type="button" x-show="coverPreview" x-cloak @click="clearCover()"
                                class="text-xs text-subtle underline transition hover:text-danger">{{ __('Remove cover') }}</button>
                    </div>
                    <p class="mb-3 text-xs text-muted">{{ __('You can add a project without a cover — the card falls back to a tinted placeholder, and you can upload one any time.') }}</p>

                    <label class="group relative block aspect-video w-full cursor-pointer overflow-hidden rounded-lg border-2 border-dashed border-line-strong bg-elevated transition hover:border-accent focus-within:border-accent">
                        <template x-if="coverPreview">
                            <img :src="coverPreview" alt="" class="h-full w-full object-cover">
                        </template>
                        <span class="absolute inset-0 flex flex-col items-center justify-center gap-1.5 text-center transition"
                              :class="coverPreview ? 'bg-ink/50 opacity-0 group-hover:opacity-100' : ''">
                            <svg class="h-6 w-6" :class="coverPreview ? 'text-bg' : 'text-subtle'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4m0 0L8 8m4-4 4 4M20 16v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2"/></svg>
                            <span class="text-sm font-medium" :class="coverPreview ? 'text-bg' : 'text-ink'"
                                  x-text="coverBusy ? '{{ __('Uploading…') }}' : (coverPreview ? '{{ __('Replace cover') }}' : '{{ __('Upload a cover image') }}')"></span>
                            <span class="font-mono text-[10px] uppercase tracking-wider" :class="coverPreview ? 'text-bg/80' : 'text-subtle'">{{ __('JPG · PNG · WEBP · up to 10 MB') }}</span>
                        </span>
                        <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only"
                               @change="pickCover($event.target.files[0]); $event.target.value = ''">
                    </label>
                    <p x-show="errors.cover" x-cloak class="mt-1.5 text-xs text-danger" x-text="errors.cover?.[0]"></p>
                </section>

                {{-- 2 · Skills — THE requirement -----------------------------------}}
                <section>
                    <div class="mb-1 flex flex-wrap items-center gap-2">
                        <span class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Skills') }}</span>
                        <span class="rounded-pill bg-danger/10 px-2 py-0.5 font-mono text-[9px] uppercase tracking-wider text-danger">{{ __('Required') }}</span>
                        <span class="ms-auto font-mono text-[10px] text-subtle" x-text="`${form.skill_ids.length} {{ __('selected') }}`"></span>
                    </div>
                    <p class="mb-3 text-xs leading-relaxed text-muted">
                        {{ __('Pick every discipline this work belongs to. The first one you pick is its home tab on your public profile; the project also shows up under every other skill you tag.') }}
                    </p>

                    <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('Skills for this project') }}">
                        @foreach ($skills as $skill)
                            <button type="button" x-on:click="toggleSkill({{ $skill['id'] }})"
                                    :aria-pressed="hasSkill({{ $skill['id'] }}) ? 'true' : 'false'"
                                    class="inline-flex items-center gap-2 rounded-pill border px-4 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                                    :class="hasSkill({{ $skill['id'] }})
                                        ? 'border-transparent bg-accent text-on-accent shadow-sm'
                                        : 'border-line bg-elevated text-ink hover:border-line-strong'">
                                <x-skill-icon :icon="$skill['icon']" class="h-4 w-4 shrink-0" />
                                <span>{{ $skill['name'] }}</span>
                                <span x-show="homeSkillId === {{ $skill['id'] }}" x-cloak
                                      class="rounded-pill bg-on-accent/20 px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-wider">{{ __('Home') }}</span>
                                <svg x-show="hasSkill({{ $skill['id'] }})" x-cloak class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            </button>
                        @endforeach
                    </div>
                    <p x-show="errors.skill_ids || errors['skill_ids.0']" x-cloak class="mt-2 text-xs text-danger"
                       x-text="(errors.skill_ids || errors['skill_ids.0'])?.[0]"></p>

                    {{--
                        Specialities (ADR-Y) — revealed BY the skills tagged above,
                        because a speciality only exists under a skill. Un-tagging a
                        skill takes its picks with it (pruneSpecialities in the
                        composer mirrors what the service does server-side).
                        Optional: a project that does not fit a named speciality
                        should not be blocked from being logged.
                    --}}
                    <template x-if="specialityGroups().length > 0">
                        <div class="mt-5 border-t border-line pt-4">
                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                <span class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Specialities') }}</span>
                                <span class="rounded-pill border border-line px-2 py-0.5 font-mono text-[9px] uppercase tracking-wider text-subtle">{{ __('Optional') }}</span>
                                <span class="ms-auto font-mono text-[10px] text-subtle" x-text="`${form.speciality_ids.length} {{ __('selected') }}`"></span>
                            </div>
                            <p class="mb-3 text-xs leading-relaxed text-muted">
                                {{ __('What kind of subject this work was. This is what lets a brand searching for it find you.') }}
                            </p>

                            <template x-for="group in specialityGroups()" :key="group.id">
                                <div class="mb-3">
                                    <div class="mb-1.5 font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="group.name"></div>
                                    <div class="flex flex-wrap gap-2" role="group" :aria-label="group.name">
                                        <template x-for="speciality in group.options" :key="speciality.id">
                                            <button type="button" x-on:click="toggleSpeciality(speciality.id)"
                                                    :aria-pressed="hasSpeciality(speciality.id) ? 'true' : 'false'"
                                                    class="rounded-pill border px-3 py-1.5 text-xs font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                                    :class="hasSpeciality(speciality.id)
                                                        ? 'border-transparent bg-accent text-on-accent'
                                                        : 'border-line bg-elevated text-muted hover:text-ink'">
                                                <span x-text="speciality.name"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </section>

                <hr class="border-line">

                {{-- 3 · The facts --------------------------------------------------}}
                <section class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="pc-title" class="mb-1.5 block text-sm font-medium text-ink">
                            {{ __('Title') }} <span class="text-danger">*</span>
                        </label>
                        <input id="pc-title" x-model="form.title.en" @input="touch()" maxlength="255"
                               placeholder="{{ __('e.g. Ramadan campaign film') }}"
                               class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                        <p x-show="errors['title.en']" x-cloak class="mt-1 text-xs text-danger" x-text="errors['title.en']?.[0]"></p>
                    </div>

                    {{-- Arabic input hidden while the app is English-only (restore for the AR pass).
                         `form.title.ar` stays in the payload, so a stored Arabic title is never wiped.
                    <div class="sm:col-span-2" dir="rtl">
                        <label for="pc-title-ar" class="mb-1.5 block text-sm font-medium text-ink">{{ __('Title (Arabic)') }}</label>
                        <input id="pc-title-ar" x-model="form.title.ar" @input="touch()" maxlength="255"
                               class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </div>
                    --}}

                    <div>
                        <label for="pc-client" class="mb-1.5 block text-sm font-medium text-ink">{{ __('Client') }}</label>
                        <input id="pc-client" x-model="form.client_name" @input="touch()" maxlength="255"
                               placeholder="{{ __('Leave blank for self-initiated work') }}"
                               placeholder="{{ __('Who it was for') }}"
                               class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </div>

                    <div>
                        <label for="pc-role" class="mb-1.5 block text-sm font-medium text-ink">{{ __('Your role') }}</label>
                        <input id="pc-role" x-model="form.role.en" @input="touch()" maxlength="255"
                               placeholder="{{ __('e.g. Director of photography') }}"
                               class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </div>

                    {{-- DATE (ADR-AA) — recorded, NOT displayed. It orders your
                         work by recency and makes "shot recently" answerable; it
                         never appears on the project page, so an older project is
                         not discounted for its age. The label says so, because a
                         date field people assume is public gets left blank. --}}
                    <div>
                        <label for="pc-date" class="mb-1.5 block text-sm font-medium text-ink">{{ __('When was it made?') }}</label>
                        <input id="pc-date" type="date" x-model="form.occurred_on" @input="touch()"
                               max="{{ now()->addYear()->toDateString() }}"
                               class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                        <p class="mt-1 text-xs text-subtle">{{ __('Not shown on your profile — it only orders your work.') }}</p>
                        <p x-show="errors.occurred_on" x-cloak class="mt-1 text-xs text-danger" x-text="errors.occurred_on?.[0]"></p>
                    </div>

                    <div>
                        <label for="pc-url" class="mb-1.5 block text-sm font-medium text-ink">{{ __('Link') }}</label>
                        <input id="pc-url" type="url" x-model="form.url" @input="touch()"
                               placeholder="https://…"
                               class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                        <p x-show="errors.url" x-cloak class="mt-1 text-xs text-danger" x-text="errors.url?.[0]"></p>
                    </div>
                </section>

                <hr class="border-line">

                {{-- 4 · The story (rich text, sanitized server-side) ---------------}}
                <section class="space-y-5">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Summary') }}</label>
                        <p class="mb-2 text-xs text-muted">{{ __('One or two lines — this is what shows on the card.') }}</p>
                        @include('talent.partials.rich-input', ['model' => 'form.summary.en', 'minHeight' => '80px'])
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-ink">{{ __('The full story') }}</label>
                        <p class="mb-2 text-xs text-muted">{{ __('The brief, what you did, how it landed.') }}</p>
                        @include('talent.partials.rich-input', ['model' => 'form.body.en', 'minHeight' => '160px'])
                    </div>
                </section>

                <hr class="border-line">

                {{-- 5 · Results ----------------------------------------------------}}
                <section>
                    <div class="mb-1 flex items-center justify-between gap-3">
                        <span class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Results') }}</span>
                        <button type="button" x-on:click="addResult()" :disabled="form.results.length >= 6"
                                class="rounded-pill border border-line-strong px-3 py-1 text-xs font-medium text-ink transition hover:bg-elevated disabled:opacity-40">
                            {{ __('Add a metric') }}
                        </button>
                    </div>
                    <p class="mb-3 text-xs text-muted">{{ __('Optional numbers that make the work concrete — reach, views, sell-through.') }}</p>

                    <div class="space-y-2">
                        <template x-for="(row, index) in form.results" :key="index">
                            <div class="flex items-center gap-2">
                                <input x-model="row.label" @input="touch()" maxlength="60" placeholder="{{ __('Reach') }}"
                                       class="w-2/5 rounded-md border-line bg-elevated text-sm text-ink shadow-sm focus:border-accent focus:ring-accent">
                                <input x-model="row.value" @input="touch()" maxlength="60" placeholder="{{ __('2.4M') }}"
                                       class="flex-1 rounded-md border-line bg-elevated text-sm text-ink shadow-sm focus:border-accent focus:ring-accent">
                                <button type="button" x-on:click="removeResult(index)" aria-label="{{ __('Remove metric') }}"
                                        class="grid h-9 w-9 shrink-0 place-items-center rounded-pill text-subtle transition hover:text-danger focus:outline-none focus-visible:ring-2 focus-visible:ring-danger">✕</button>
                            </div>
                        </template>
                        <p x-show="form.results.length === 0" x-cloak class="text-xs text-subtle">{{ __('No metrics on this project.') }}</p>
                    </div>
                </section>

                {{-- THE WORK + COLLABORATORS (ADR-AA) — only on a project that
                     already exists: both attach to a project id, and a composer
                     that pretended otherwise would have to stage uploads and
                     credit invitations against nothing. --}}
                <template x-if="editing">
                    <div class="space-y-8">
                        <hr class="border-line">

                        {{-- Media gallery. The ORDER is the edit. --}}
                        <section>
                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                <span class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('The work') }}</span>
                                <span class="ms-auto font-mono text-[10px] text-subtle" x-text="`${media.length} {{ __('files') }}`"></span>
                            </div>
                            <p class="mb-3 text-xs leading-relaxed text-muted">
                                {{ __('Images and video, in the order people will see them. Drag to reorder — the first one is doing the most work, so choose it.') }}
                            </p>

                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                <template x-for="(item, index) in media" :key="item.id">
                                    <div draggable="true"
                                         @dragstart="mediaDragId = item.id"
                                         @dragover.prevent
                                         @drop.prevent="dropMedia(item)"
                                         @dragend="mediaDragId = null"
                                         class="group relative aspect-square cursor-move overflow-hidden rounded-lg border border-line bg-elevated transition"
                                         :class="mediaDragId === item.id ? 'opacity-40' : ''">
                                        <template x-if="item.is_video">
                                            <video :src="item.url" muted class="h-full w-full object-cover"></video>
                                        </template>
                                        <template x-if="!item.is_video">
                                            <img :src="item.url" alt="" class="h-full w-full object-cover">
                                        </template>
                                        <span x-show="index === 0" x-cloak
                                              class="absolute start-1.5 top-1.5 rounded-pill bg-accent px-2 py-0.5 font-mono text-[9px] uppercase tracking-wider text-on-accent">{{ __('Lead') }}</span>
                                        <button type="button" @click="removeMedia(item)"
                                                class="absolute end-1.5 top-1.5 grid h-6 w-6 place-items-center rounded-full bg-ink/70 text-xs text-bg opacity-0 transition group-hover:opacity-100 focus:opacity-100"
                                                aria-label="{{ __('Remove') }}">&times;</button>
                                    </div>
                                </template>

                                <label class="flex aspect-square cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border border-dashed border-line text-center text-xs text-subtle transition hover:border-accent hover:text-accent">
                                    <span class="text-lg" aria-hidden="true">+</span>
                                    <span x-show="!uploadingMedia">{{ __('Add files') }}</span>
                                    <span x-show="uploadingMedia" x-cloak>{{ __('Uploading…') }}</span>
                                    <input type="file" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm"
                                           class="sr-only" @change="uploadMedia($event.target.files); $event.target.value = ''">
                                </label>
                            </div>
                            <p x-show="mediaError" x-cloak class="mt-2 text-xs text-danger" x-text="mediaError"></p>
                        </section>

                        <hr class="border-line">

                        {{-- Collaborators. person + skill + role = one credit. --}}
                        <section>
                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                <span class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Collaborators') }}</span>
                            </div>
                            <p class="mb-3 text-xs leading-relaxed text-muted">
                                {{ __('Everyone who worked on it, and what each of them did. Crediting someone on Fama adds this project to their profile too — so it waits for them to confirm it.') }}
                            </p>

                            <ul class="mb-3 space-y-2">
                                <template x-for="credit in credits" :key="credit.id">
                                    <li class="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-elevated p-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2 text-sm text-ink">
                                                <span x-text="credit.name"></span>
                                                <span class="font-mono text-[11px] uppercase tracking-wider text-subtle" x-text="credit.role"></span>
                                            </div>
                                            <div class="mt-0.5 flex flex-wrap items-center gap-2">
                                                <span class="font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="credit.skill?.name"></span>
                                                {{-- The confirmation state, said plainly. --}}
                                                <span x-show="credit.status === 'pending'" x-cloak
                                                      class="rounded-pill bg-gold-weak px-2 py-0.5 font-mono text-[9px] uppercase tracking-wider text-gold">{{ __('Awaiting them') }}</span>
                                                <span x-show="credit.status === 'accepted'" x-cloak
                                                      class="rounded-pill bg-success-weak px-2 py-0.5 font-mono text-[9px] uppercase tracking-wider text-success">{{ __('Confirmed') }}</span>
                                                <span x-show="credit.status === 'declined'" x-cloak
                                                      class="rounded-pill border border-line px-2 py-0.5 font-mono text-[9px] uppercase tracking-wider text-subtle">{{ __('Declined') }}</span>
                                            </div>
                                        </div>
                                        <button type="button" @click="removeCredit(credit)"
                                                class="rounded-md px-2 py-1 text-subtle transition hover:text-danger focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                                aria-label="{{ __('Remove') }}">&times;</button>
                                    </li>
                                </template>
                            </ul>

                            <div class="grid gap-2 sm:grid-cols-4">
                                <input x-model="newCredit.name" type="text" maxlength="120"
                                       placeholder="{{ __('Name') }}"
                                       class="rounded-md border-line bg-elevated text-sm text-ink shadow-sm focus:border-accent focus:ring-accent">
                                <select x-model.number="newCredit.talent_type_id"
                                        class="rounded-md border-line bg-elevated text-sm text-ink">
                                    <option value="">{{ __('Skill…') }}</option>
                                    @foreach ($allSkills as $skill)
                                        <option value="{{ $skill['id'] }}">{{ $skill['name'] }}</option>
                                    @endforeach
                                </select>
                                <input x-model="newCredit.role" type="text" maxlength="80"
                                       placeholder="{{ __('What they did') }}"
                                       class="rounded-md border-line bg-elevated text-sm text-ink shadow-sm focus:border-accent focus:ring-accent">
                                <x-ui.button size="sm" x-on:click="addCredit()"
                                             x-bind:disabled="savingCredit || !newCredit.name || !newCredit.talent_type_id || !newCredit.role">
                                    {{ __('Add credit') }}
                                </x-ui.button>
                            </div>
                            <p class="mt-1.5 text-xs text-subtle">{{ __('Type a name to credit someone who is not on Fama — that credit is display-only and stays on this project.') }}</p>
                            <p x-show="creditError" x-cloak class="mt-2 text-xs text-danger" x-text="creditError"></p>
                        </section>
                    </div>
                </template>
            </div>

            {{-- Pinned footer --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-surface px-6 py-4 sm:px-7">
                <p class="min-w-0 text-xs text-muted" x-text="saveHint"></p>
                <div class="flex shrink-0 items-center gap-3">
                    <button type="button" @click="requestClose()"
                            class="rounded-pill px-4 py-2.5 text-sm font-medium text-ink transition hover:bg-elevated focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                        {{ __('Cancel') }}
                    </button>
                    <x-ui.button variant="accent" x-on:click="save()" x-bind:disabled="!canSave">
                        <span x-text="saving ? '{{ __('Saving…') }}' : (editing ? '{{ __('Save changes') }}' : '{{ __('Add project') }}')"></span>
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
</template>
