@php
    $core = [
        'display_name' => $talent->display_name,
        'headline' => array_merge(['en' => '', 'ar' => ''], $talent->getTranslations('headline')),
        'bio' => array_merge(['en' => '', 'ar' => ''], $talent->getTranslations('bio')),
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
