@php
    $core = [
        'display_name' => $talent->display_name,
        'headline' => array_merge(['en' => '', 'ar' => ''], $talent->getTranslations('headline')),
        'bio' => array_merge(['en' => '', 'ar' => ''], $talent->getTranslations('bio')),
        'worldview' => array_merge(['en' => '', 'ar' => ''], $talent->getTranslations('worldview')),
        'slug' => $talent->slug,
        'base_city' => $talent->base_city,
        'base_country' => $talent->base_country,
    ];
    $publish = ['is_published' => (bool) $talent->is_published, 'status' => $talent->status->getValue(), 'is_verified' => (bool) $talent->is_verified];
    $rate = ['rate_unit' => $talent->rate_unit, 'rate_amount' => $talent->rate_amount, 'rate_currency' => $talent->rate_currency];
@endphp

<x-talent-layout :title="__('Profile structure')">
    <div x-data="profileEditor({
        core: @js($core),
        avatarUrl: @js($talent->avatar_url),
        displayName: @js($talent->display_name),
        blocks: @js($blocks),
        catalog: @js($catalog),
        universalLabel: @js(__('Universal · profile-level')),
        universalPosition: {{ (int) ($talent->meta['universal_position'] ?? 0) }},
        skills: @js($skills),
        availableSkills: @js($availableSkills),
        specialities: @js($specialities),
        now: @js($now),
        social: @js($social),
        interests: @js($interests),
        interestSkills: @js($interestSkills),
        contentKeys: @js($contentKeys),
        publish: @js($publish),
        rate: @js($rate),
    })" class="space-y-10">

        {{-- Core details — identity + username, with the publishing status/toggle
             merged in as the card's first row (the old standalone Publishing
             section is folded in here). --}}
        <x-ui.section :title="__('Core details')" :eyebrow="__('Your identity')">
            {{-- Awaiting verification: an admin must approve before going public. --}}
            <div x-show="!isVerified && !isPublished" x-cloak
                 class="mb-3 flex items-start gap-3 rounded-xl border border-warn/40 bg-warn/10 p-4">
                <span class="mt-0.5 text-warn">⏳</span>
                <div>
                    <p class="font-medium text-ink">{{ __('Awaiting verification') }}</p>
                    <p class="mt-0.5 text-sm text-muted">{{ __('Our team is reviewing your profile. You can keep editing now — the Publish button unlocks once your account is verified.') }}</p>
                </div>
            </div>
            <x-ui.card class="space-y-6">
                {{-- Publishing (merged): status + publish/unpublish. --}}
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-line pb-6">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-pill" :class="isPublished ? 'bg-success' : (isVerified ? 'bg-warn' : 'bg-muted')"></span>
                            <span class="font-display text-xl text-ink" x-text="isPublished ? '{{ __('Live') }}' : ('{{ __('Not live') }}' + ' · ' + status)"></span>
                            <template x-if="isVerified">
                                <span class="rounded-pill bg-accent-weak px-2 py-0.5 text-xs text-accent-ink">{{ __('verified') }}</span>
                            </template>
                        </div>
                        <p x-show="publishError" x-cloak class="mt-1 text-xs text-danger" x-text="publishError"></p>
                    </div>
                    <x-ui.button variant="accent" x-on:click="togglePublish()" x-bind:disabled="publishing || (!isVerified && !isPublished)"
                                 x-bind:title="(!isVerified && !isPublished) ? '{{ __('Your account must be verified before you can publish.') }}' : ''">
                        <span x-text="isPublished ? '{{ __('Unpublish') }}' : '{{ __('Publish') }}'"></span>
                    </x-ui.button>
                </div>

                {{-- Profile image (avatar) — uploads to the `avatar` collection, Ajax, no reload. --}}
                <div class="flex flex-col items-center gap-5 border-b border-line pb-6 sm:flex-row sm:items-center">
                    <span class="inline-flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-pill border border-line-strong bg-elevated shadow-e1">
                        <template x-if="avatarUrl">
                            <img :src="avatarUrl" alt="{{ __('Profile image') }}" class="h-full w-full object-cover">
                        </template>
                        <template x-if="!avatarUrl">
                            <span class="flex h-full w-full items-center justify-center font-display text-3xl text-accent-ink"
                                  style="background: linear-gradient(135deg, var(--accent-weak), var(--gold-weak));"
                                  x-text="avatarInitials"></span>
                        </template>
                    </span>

                    <div class="space-y-2 text-center sm:text-start">
                        <div class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Profile image') }}</div>
                        <input x-ref="avatarInput" type="file" accept="image/png,image/jpeg,image/jpg,image/webp" class="hidden"
                               @change="uploadAvatar($event.target.files)">
                        <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                            <x-ui.button variant="outline" size="sm" x-on:click="$refs.avatarInput.click()" x-bind:disabled="uploadingAvatar">
                                <span x-show="!uploadingAvatar" x-text="avatarUrl ? '{{ __('Change photo') }}' : '{{ __('Upload photo') }}'"></span>
                                <span x-show="uploadingAvatar" x-cloak>{{ __('Uploading…') }}</span>
                            </x-ui.button>
                            <button type="button" x-show="avatarUrl" x-cloak @click="$confirm({ title: '{{ __('Remove profile photo?') }}', message: '{{ __('Your photo will be removed and replaced with your initials.') }}', confirmLabel: '{{ __('Remove') }}' }).then(ok => ok && removeAvatar())" x-bind:disabled="uploadingAvatar"
                                    class="text-sm text-muted underline underline-offset-2 transition hover:text-danger focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">{{ __('Remove') }}</button>
                        </div>
                        <p class="text-xs text-subtle">{{ __('JPG, PNG or WebP · up to 5 MB · square works best.') }}</p>
                        <p x-show="avatarError" x-cloak class="text-xs text-danger" x-text="avatarError"></p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Display name') }}</label>
                        <input x-model="core.display_name" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                        <p x-show="errors.display_name" x-cloak class="mt-1 text-xs text-danger" x-text="errors.display_name?.[0]"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Username') }}</label>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-sm text-subtle">fama.com/</span>
                            <input x-model="core.slug" type="text" class="w-full rounded-md border-line bg-elevated font-mono text-sm text-ink shadow-sm focus:border-accent focus:ring-accent">
                        </div>
                        <p x-show="errors.slug" x-cloak class="mt-1 text-xs text-danger" x-text="errors.slug?.[0]"></p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Headline') }}</label>
                        <input x-model="core.headline.en" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </div>
                    {{-- Arabic input hidden while the app is English-only (restore for the AR pass).
                    <div dir="rtl">
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Headline (AR)') }}</label>
                        <input x-model="core.headline.ar" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </div>
                    --}}
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Bio') }}</label>
                        <textarea x-model="core.bio.en" rows="3" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                    </div>
                    {{-- Arabic input hidden while the app is English-only (restore for the AR pass).
                    <div class="sm:col-span-2" dir="rtl">
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Bio (AR)') }}</label>
                        <textarea x-model="core.bio.ar" rows="3" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                    </div>
                    --}}
                    {{-- Worldview (ADR-Z). PROMPTED, not an empty box: an unprompted
                         second textarea next to Bio just gets a second bio typed into it. --}}
                    <div class="sm:col-span-2">
                        <label for="pe-worldview" class="mb-1 block text-sm font-medium text-ink">{{ __('Point of view') }}</label>
                        <textarea id="pe-worldview" x-model="core.worldview.en" rows="3"
                                  placeholder="{{ __('What kind of work are you drawn to? What do you turn down? Who do you want to work with?') }}"
                                  class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                        <p class="mt-1 text-xs text-subtle">{{ __('Your taste, not your track record — the bio above covers that.') }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Base city') }}</label>
                        <input x-model="core.base_city" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Base country') }}</label>
                        <input x-model="core.base_country" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <x-ui.button variant="accent" x-on:click="saveCore()" x-bind:disabled="savingCore">
                        <span x-show="!savingCore">{{ __('Save details') }}</span>
                        <span x-show="savingCore" x-cloak>{{ __('Saving…') }}</span>
                    </x-ui.button>
                    <span x-show="coreSaved" x-cloak class="text-sm text-success">{{ __('Saved') }}</span>
                </div>
            </x-ui.card>
        </x-ui.section>

        {{-- ═══ NOW (ADR-Z) ═══ The time-sensitive strip. Both toggles save on
             flip — there is no Save button, because a toggle you have to confirm
             is one people forget to confirm, and this is the part of the page
             that goes stale fastest. --}}
        <x-ui.section :title="__('Now')" :eyebrow="__('What a brand checks first')">
            <x-ui.card class="space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-ink">{{ __('Taking work') }}</div>
                        <p class="mt-0.5 text-xs leading-relaxed text-muted">
                            {{ __('Shown on your profile with the date you last changed it, so people know it is current.') }}
                        </p>
                        <p class="mt-1 font-mono text-[11px] text-subtle" x-show="now.availability_changed_for_humans" x-cloak>
                            {{ __('Last changed') }} <span x-text="now.availability_changed_for_humans"></span>
                        </p>
                    </div>
                    <button type="button" role="switch" @click="toggleAvailability()"
                            :aria-checked="now.is_available ? 'true' : 'false'"
                            :aria-label="now.is_available ? '{{ __('Taking work') }}' : '{{ __('Not taking work') }}'"
                            class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                            :class="now.is_available ? 'bg-accent' : 'bg-line-strong'">
                        <span class="absolute top-0.5 h-5 w-5 rounded-full bg-surface transition-all"
                              :class="now.is_available ? 'start-[1.375rem]' : 'start-0.5'"></span>
                    </button>
                </div>

                <hr class="border-line">

                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-ink">{{ __('Will travel') }}</div>
                        <p class="mt-0.5 text-xs leading-relaxed text-muted">
                            {{ __('Whether you will work outside your city at all. Which city and on whose budget is a conversation — this just says you are open to it.') }}
                        </p>
                    </div>
                    <button type="button" role="switch" @click="toggleTravel()"
                            :aria-checked="now.will_travel ? 'true' : 'false'"
                            aria-label="{{ __('Will travel') }}"
                            class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                            :class="now.will_travel ? 'bg-accent' : 'bg-line-strong'">
                        <span class="absolute top-0.5 h-5 w-5 rounded-full bg-surface transition-all"
                              :class="now.will_travel ? 'start-[1.375rem]' : 'start-0.5'"></span>
                    </button>
                </div>
            </x-ui.card>
        </x-ui.section>

        {{-- ═══ LINKS (ADR-Z, About) ═══ Instagram and anywhere else the work lives. --}}
        <x-ui.section :title="__('Links')" :eyebrow="__('Where else your work lives')">
            <x-ui.card class="space-y-4">
                <template x-for="(link, index) in social" :key="index">
                    <div class="flex flex-wrap items-end gap-2">
                        <div class="w-36">
                            <label class="mb-1 block text-xs font-medium text-muted">{{ __('Platform') }}</label>
                            <select x-model="link.platform" class="w-full rounded-md border-line bg-elevated text-sm text-ink">
                                @foreach (['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'x' => 'X', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'behance' => 'Behance', 'vimeo' => 'Vimeo', 'website' => __('Website'), 'other' => __('Other')] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="min-w-[8rem] flex-1">
                            <label class="mb-1 block text-xs font-medium text-muted">{{ __('Handle') }}</label>
                            <input x-model="link.handle" type="text" maxlength="120"
                                   class="w-full rounded-md border-line bg-elevated text-sm text-ink shadow-sm focus:border-accent focus:ring-accent">
                        </div>
                        <div class="min-w-[12rem] flex-1">
                            <label class="mb-1 block text-xs font-medium text-muted">{{ __('URL') }}</label>
                            <input x-model="link.url" type="url"
                                   class="w-full rounded-md border-line bg-elevated text-sm text-ink shadow-sm focus:border-accent focus:ring-accent">
                        </div>
                        <button type="button" @click="removeSocial(index)"
                                class="rounded-md px-2 py-2 text-subtle transition hover:text-danger focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                aria-label="{{ __('Remove') }}">&times;</button>
                    </div>
                </template>

                <p x-show="social.length === 0" x-cloak class="rounded-lg border border-dashed border-line p-4 text-center text-xs text-subtle">
                    {{ __('No links yet.') }}
                </p>
                <p x-show="socialErrors._" x-cloak class="text-xs text-danger" x-text="socialErrors._?.[0]"></p>

                <div class="flex items-center gap-3">
                    <x-ui.button size="sm" x-on:click="addSocial()">{{ __('Add a link') }}</x-ui.button>
                    <x-ui.button size="sm" variant="accent" x-on:click="saveSocial()" x-bind:disabled="savingSocial">
                        <span x-show="!savingSocial">{{ __('Save links') }}</span>
                        <span x-show="savingSocial" x-cloak>{{ __('Saving…') }}</span>
                    </x-ui.button>
                    <span x-show="socialSaved" x-cloak class="text-sm text-success">{{ __('Saved') }}</span>
                </div>
            </x-ui.card>
        </x-ui.section>

        {{-- ═══ WILLING TO EXPLORE (ADR-Z) ═══ The one section that asks for
             something instead of proving something. Structured (skill + kind of
             work) so brands can filter it; as free text nobody acts on it. --}}
        <x-ui.section :title="__('Willing to explore')" :eyebrow="__('Where you want to go next')">
            <x-ui.card class="space-y-4">
                <p class="text-xs leading-relaxed text-muted">
                    {{ __('Directions you want to move toward. These do not have to be skills you already list — that is the point.') }}
                </p>

                <ul class="space-y-2">
                    <template x-for="interest in interests" :key="interest.id">
                        <li class="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-elevated p-3">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-accent" aria-hidden="true"></span>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm text-ink">
                                    <span x-text="interest.skill?.name"></span>
                                    <template x-if="interest.speciality">
                                        <span><span class="text-subtle">·</span> <span class="text-muted" x-text="interest.speciality.name"></span></span>
                                    </template>
                                </div>
                                <p class="mt-0.5 text-xs text-subtle" x-show="interest.note" x-cloak x-text="interest.note"></p>
                            </div>
                            <button type="button"
                                    @click="$confirm({ title: '{{ __('Remove this direction?') }}', message: '{{ __('It will no longer show on your profile.') }}', confirmLabel: '{{ __('Remove') }}', tone: 'danger' }).then(ok => ok && removeInterest(interest))"
                                    class="rounded-md px-2 py-1 text-subtle transition hover:text-danger focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                    aria-label="{{ __('Remove') }}">&times;</button>
                        </li>
                    </template>
                </ul>

                <p x-show="interests.length === 0" x-cloak class="rounded-lg border border-dashed border-line p-4 text-center text-xs text-subtle">
                    {{ __('Nothing listed yet.') }}
                </p>

                <div class="flex flex-wrap items-end gap-2 border-t border-line pt-4">
                    <div class="min-w-[10rem] flex-1">
                        <label for="pe-interest-skill" class="mb-1 block text-xs font-medium text-muted">{{ __('Skill') }}</label>
                        <select id="pe-interest-skill" x-model.number="newInterest.talent_type_id"
                                @change="newInterest.speciality_id = ''"
                                class="w-full rounded-md border-line bg-elevated text-sm text-ink">
                            <option value="">{{ __('Pick a skill…') }}</option>
                            <template x-for="skill in interestSkills" :key="skill.id">
                                <option :value="skill.id" x-text="skill.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="min-w-[10rem] flex-1">
                        <label for="pe-interest-spec" class="mb-1 block text-xs font-medium text-muted">{{ __('Kind of work') }} <span class="text-subtle">({{ __('optional') }})</span></label>
                        <select id="pe-interest-spec" x-model="newInterest.speciality_id"
                                :disabled="interestSpecialities().length === 0"
                                class="w-full rounded-md border-line bg-elevated text-sm text-ink disabled:opacity-50">
                            <option value="">{{ __('Any') }}</option>
                            <template x-for="speciality in interestSpecialities()" :key="speciality.id">
                                <option :value="speciality.id" x-text="speciality.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="min-w-[12rem] flex-1">
                        <label for="pe-interest-note" class="mb-1 block text-xs font-medium text-muted">{{ __('Note') }} <span class="text-subtle">({{ __('optional') }})</span></label>
                        <input id="pe-interest-note" x-model="newInterest.note" type="text" maxlength="160"
                               class="w-full rounded-md border-line bg-elevated text-sm text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </div>
                    <x-ui.button size="sm" variant="accent" x-on:click="addInterest()" x-bind:disabled="savingInterest || !newInterest.talent_type_id">
                        {{ __('Add') }}
                    </x-ui.button>
                </div>
                <p x-show="interestErrors._" x-cloak class="text-xs text-danger" x-text="interestErrors._?.[0]"></p>
            </x-ui.card>
        </x-ui.section>

        {{-- Pricing rate (ADR-N — replaces the removed rate card). Hidden in V1. --}}
        @feature('pricing')
        <x-ui.section :title="__('Pricing rate')" :eyebrow="__('An indicative rate shown on your profile')">
            <x-ui.card class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Amount') }}</label>
                        <input x-model="rate.rate_amount" type="number" min="0" step="0.01" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                        <p x-show="rateErrors.rate_amount" x-cloak class="mt-1 text-xs text-danger" x-text="rateErrors.rate_amount?.[0]"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Currency') }}</label>
                        <input x-model="rate.rate_currency" type="text" maxlength="3" placeholder="EGP" class="w-full rounded-md border-line bg-elevated font-mono uppercase text-ink shadow-sm focus:border-accent focus:ring-accent">
                        <p x-show="rateErrors.rate_currency" x-cloak class="mt-1 text-xs text-danger" x-text="rateErrors.rate_currency?.[0]"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Per') }}</label>
                        <select x-model="rate.rate_unit" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                            <option value="">{{ __('Choose…') }}</option>
                            <option value="project">{{ __('Project') }}</option>
                            <option value="day">{{ __('Day') }}</option>
                            <option value="hour">{{ __('Hour') }}</option>
                        </select>
                        <p x-show="rateErrors.rate_unit" x-cloak class="mt-1 text-xs text-danger" x-text="rateErrors.rate_unit?.[0]"></p>
                    </div>
                </div>
                <p class="text-xs text-subtle">{{ __('Fill all three fields, or leave them blank to hide your rate.') }}</p>
                <div class="flex items-center gap-3">
                    <x-ui.button variant="accent" x-on:click="saveRate()" x-bind:disabled="savingRate">{{ __('Save rate') }}</x-ui.button>
                    <button type="button" x-on:click="clearRate()" class="text-sm text-muted underline hover:text-ink">{{ __('Clear rate') }}</button>
                    <span x-show="rateSaved" x-cloak class="text-sm text-success">{{ __('Saved') }}</span>
                </div>
            </x-ui.card>
        </x-ui.section>
        @endfeature

        {{-- Blocks — ONE orderable list of section pills: the universal/profile-
             level section sits AMONG the skill sections and drags like them (its
             slot decides whether its blocks render above or below the skill tabs
             on the public profile). A section's card shows when selected. --}}
        <x-ui.section :title="__('Blocks')" :eyebrow="__('Sections · drag to reorder your profile')">
            <div class="space-y-6">
                <div class="space-y-3">
                    <p class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Sections') }} · {{ __('Drag to reorder · star to set primary') }}</p>
                    <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="{{ __('Sections') }}">
                        <template x-for="g in sectionPills" :key="g.key">
                            <div draggable="true"
                                 @dragstart="onRowDragStart($event, 'section', g.key)"
                                 @dragover.prevent="onRowDragOver($event, g.key)"
                                 @drop.prevent="onRowDrop('section', g)"
                                 @dragend="onRowDragEnd()"
                                 class="flex select-none items-center rounded-pill border transition-all duration-150"
                                 :class="[
                                     g.typeId !== null && expanded[g.key] ? 'border-accent bg-accent text-on-accent' : 'border-line text-muted hover:border-line-strong',
                                     dragging?.key === g.key ? 'scale-[.97] opacity-40' : '',
                                     dragOverKey === g.key ? 'ring-2 ring-accent' : '',
                                 ]">
                                <button type="button"
                                        @click="g.typeId !== null && toggleExpanded(g.key)"
                                        :aria-pressed="g.typeId !== null && expanded[g.key] ? 'true' : 'false'"
                                        class="flex cursor-grab items-center gap-1.5 rounded-s-pill ps-3.5 py-1.5 text-sm font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                        :class="[g.typeId !== null && expanded[g.key] ? '' : 'hover:text-ink', g.typeId === null ? 'rounded-e-pill pe-3.5' : 'pe-1']">
                                    <span x-text="g.label"></span>
                                </button>
                                <template x-if="g.typeId !== null">
                                    <button type="button" @click.stop="makePrimarySkill({ id: g.typeId })"
                                            class="px-1 py-1.5 text-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                            :class="g.is_primary ? (expanded[g.key] ? 'text-on-accent' : 'text-gold') : 'opacity-60 hover:opacity-100'"
                                            :title="g.is_primary ? '{{ __('Primary') }}' : '{{ __('Make primary') }}'"
                                            :aria-label="g.is_primary ? '{{ __('Primary') }}' : '{{ __('Make primary') }}'"
                                            x-text="g.is_primary ? '★' : '☆'"></button>
                                </template>
                                {{-- Removing a skill deletes its tab's blocks (content is kept) — confirm first. --}}
                                <template x-if="g.typeId !== null">
                                    <button type="button"
                                            @click.stop="$confirm({ title: '{{ __('Remove this skill?') }}', message: '{{ __('Delete this tab’s blocks? (your content is kept)') }}', confirmLabel: '{{ __('Remove') }}' }).then(ok => ok && removeSkill({ id: g.typeId }))"
                                            class="rounded-e-pill py-1.5 pe-2.5 ps-1 text-xs opacity-60 transition hover:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                            :aria-label="'{{ __('Remove skill') }}: ' + g.label" title="{{ __('Remove skill') }}">✕</button>
                                </template>
                            </div>
                        </template>

                        {{-- Add skill: reveals the picker beside the pills. --}}
                        <button type="button" @click="showAddSkill = !showAddSkill" x-show="availableSkills.length > 0"
                                :aria-expanded="showAddSkill ? 'true' : 'false'"
                                class="flex items-center gap-1.5 rounded-pill bg-accent px-3.5 py-1.5 text-sm font-semibold text-on-accent shadow-e1 transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-bg">
                            <span aria-hidden="true">+</span> {{ __('Add skill') }}
                        </button>
                    </div>
                    <p class="text-xs text-subtle">{{ __('The universal section shows above your skill tabs — drag it after a skill to show it below them.') }}</p>

                    <div x-show="showAddSkill" x-cloak class="flex flex-wrap items-center gap-2">
                        <select x-model="addSkillId" class="rounded-md border-line bg-elevated text-sm text-ink shadow-sm focus:border-accent focus:ring-accent">
                            <option value="">{{ __('Choose…') }}</option>
                            <template x-for="type in availableSkills" :key="type.id">
                                <option :value="type.id" x-text="t(type.name)"></option>
                            </template>
                        </select>
                        <x-ui.button variant="accent" size="sm" x-on:click="addSkill()" x-bind:disabled="!addSkillId">{{ __('Add skill') }}</x-ui.button>
                    </div>

                    <p x-show="skills.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-6 text-center text-sm text-subtle">{{ __('Add your first skill to create its tab and blocks.') }}</p>
                    {{-- The ONE unified card: universal block rows + each skill as a
                         SINGLE collapsed row (expand to manage its blocks), in the
                         true section order (universal_position). --}}
                    <x-ui.card class="space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="font-display text-lg text-ink">{{ __('Universal · profile-level') }}</span>
                            <span class="font-mono text-[10px] uppercase tracking-wider text-subtle"
                                  x-text="universalPosition === 0 ? '{{ __('Above the tabs') }}' : '{{ __('Below the tabs') }}'"></span>
                        </div>

                        <div class="space-y-2">
                            <template x-for="entry in unifiedRows" :key="entry.key">
                                <div>
                                    {{-- A universal / profile-level block row --}}
                                    <template x-if="entry.kind === 'block'">
                                        <div>
                                            <template x-for="block in [entry.block]" :key="block.id">
                                                @include('talent.partials.block-row')
                                            </template>
                                        </div>
                                    </template>

                                    {{-- A skill section, treated as ONE block row (expandable) --}}
                                    <template x-if="entry.kind === 'skill'">
                                        <div class="rounded-lg border border-line bg-elevated transition-all duration-150"
                                             :class="[
                                                 dragging?.key === entry.key ? 'scale-[.99] opacity-40' : '',
                                                 dragOverKey === entry.key ? 'border-transparent ring-2 ring-accent' : '',
                                                 denyKey === entry.key ? 'border-transparent ring-2 ring-danger' : '',
                                             ]">
                                            <div draggable="true"
                                                 @dragstart="onRowDragStart($event, 'section', entry.key)"
                                                 @dragover.prevent="onRowDragOver($event, entry.key)"
                                                 @drop.prevent="onRowDrop('section', entry.group)"
                                                 @dragend="onRowDragEnd()"
                                                 class="flex select-none flex-wrap items-center gap-3 p-3">
                                                <span class="cursor-grab text-subtle" title="{{ __('Drag') }}">⋮⋮</span>
                                                <div class="min-w-0 flex-1">
                                                    <div class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Skill section') }}</div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-ink" x-text="entry.group.label"></span>
                                                        <span x-show="entry.group.is_primary" x-cloak class="rounded-pill bg-gold-weak px-2 py-0.5 text-[10px] font-medium text-gold">{{ __('Primary') }}</span>
                                                    </div>
                                                </div>
                                                <span class="font-mono text-[11px] text-subtle" x-text="blocksInScope(entry.group.typeId).length + ' {{ __('blocks') }}'"></span>
                                                <button type="button" @click="toggleExpanded(entry.key)"
                                                        :aria-expanded="expanded[entry.key] ? 'true' : 'false'"
                                                        class="flex items-center gap-1.5 rounded-pill border border-line-strong px-3 py-1 text-xs font-medium text-muted transition hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                                                    <span x-text="expanded[entry.key] ? '{{ __('Collapse') }}' : '{{ __('Manage blocks') }}'"></span>
                                                    <svg class="h-3 w-3 transition-transform" :class="expanded[entry.key] ? 'rotate-180' : ''" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                </button>
                                            </div>
                                            <div x-show="expanded[entry.key]" x-cloak class="space-y-2 border-t border-line p-3">
                                                {{--
                                                    Specialities (ADR-Y) — the second and final level of the skills
                                                    tree. A CONTROLLED list: these pills are the only options and
                                                    there is deliberately no free-text field, because an invented
                                                    entry would never match on the project side and the filter
                                                    would stop working.
                                                --}}
                                                <template x-if="specialityOptions(entry.group.typeId).length > 0">
                                                    <div class="rounded-lg border border-line bg-surface p-3">
                                                        <div class="flex items-baseline justify-between gap-3">
                                                            <div class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Specialities') }}</div>
                                                            <span class="text-[11px] text-subtle"
                                                                  x-show="savingSpecialities === entry.group.typeId" x-cloak>{{ __('Saving…') }}</span>
                                                        </div>
                                                        <p class="mt-1 text-xs text-subtle">{{ __('What kind of subject you shoot. Pick as many as apply.') }}</p>
                                                        <div class="mt-2.5 flex flex-wrap gap-2" role="group" aria-label="{{ __('Specialities') }}">
                                                            <template x-for="speciality in specialityOptions(entry.group.typeId)" :key="speciality.id">
                                                                <button type="button"
                                                                        @click="toggleSpeciality(entry.group.typeId, speciality.id)"
                                                                        :aria-pressed="specialityPicked(entry.group.typeId, speciality.id) ? 'true' : 'false'"
                                                                        class="rounded-pill border px-3 py-1.5 text-xs font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                                                        :class="specialityPicked(entry.group.typeId, speciality.id)
                                                                            ? 'border-transparent bg-accent text-on-accent'
                                                                            : 'border-line-strong text-muted hover:text-ink'">
                                                                    <span x-text="speciality.name.en || speciality.slug"></span>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>

                                                <template x-for="block in blocksInScope(entry.group.typeId)" :key="block.id">
                                                    @include('talent.partials.block-row')
                                                </template>
                                                <p x-show="blocksInScope(entry.group.typeId).length === 0" x-cloak class="rounded-lg border border-dashed border-line p-4 text-center text-xs text-subtle">{{ __('No blocks here yet.') }}</p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <p x-show="unifiedRows.length === 0" x-cloak class="rounded-lg border border-dashed border-line p-4 text-center text-xs text-subtle">{{ __('No blocks here yet.') }}</p>
                        </div>
                    </x-ui.card>
                </div>
            </div>
        </x-ui.section>
    </div>
</x-talent-layout>
