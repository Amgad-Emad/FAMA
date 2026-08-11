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

<x-talent-layout :title="__('Profile')">
    <div x-data="profileEditor({
        core: @js($core),
        avatarUrl: @js($talent->avatar_url),
        displayName: @js($talent->display_name),
        blocks: @js($blocks),
        catalog: @js($catalog),
        universalLabel: @js(__('Universal · profile-level')),
        skills: @js($skills),
        availableSkills: @js($availableSkills),
        publish: @js($publish),
        rate: @js($rate),
    })" class="space-y-10">

        {{-- Publishing (moved from the old Account tab) --}}
        <x-ui.section :title="__('Publishing')" :eyebrow="__('Profile visibility')">
            {{-- Awaiting verification: an admin must approve before going public. --}}
            <div x-show="!isVerified && !isPublished" x-cloak
                 class="mb-3 flex items-start gap-3 rounded-xl border border-warn/40 bg-warn/10 p-4">
                <span class="mt-0.5 text-warn">⏳</span>
                <div>
                    <p class="font-medium text-ink">{{ __('Awaiting verification') }}</p>
                    <p class="mt-0.5 text-sm text-muted">{{ __('Our team is reviewing your profile. You can keep editing now — the Publish button unlocks once your account is verified.') }}</p>
                </div>
            </div>
            <x-ui.card class="flex flex-wrap items-center justify-between gap-4">
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
            </x-ui.card>
        </x-ui.section>

        {{-- Core fields (identity + username) --}}
        <x-ui.section :title="__('Core details')" :eyebrow="__('Your identity')">
            <x-ui.card class="space-y-6">
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

        {{-- Pricing rate (ADR-N — replaces the removed rate card) --}}
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

        {{-- Blocks — the universal/profile-level section is always shown; each
             skill's section renders only after that skill is SELECTED in the
             pill bar (nothing shows until a skill is picked). --}}
        <x-ui.section :title="__('Blocks')" :eyebrow="__('Per skill · drag to reorder within a section')">
            <div class="space-y-6">
                <template x-for="group in universalGroups" :key="group.key">
                    @include('talent.partials.block-scope-card')
                </template>

                <div x-show="skills.length > 0" x-cloak class="space-y-3">
                    <p class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Skill sections') }}</p>
                    <div class="flex flex-wrap gap-1.5" role="group" aria-label="{{ __('Skill sections') }}">
                        <template x-for="g in skillGroups" :key="g.key">
                            <button type="button"
                                    @click="activeSkillTab = activeSkillTab === g.typeId ? null : g.typeId"
                                    :aria-pressed="activeSkillTab === g.typeId ? 'true' : 'false'"
                                    class="flex items-center gap-1.5 rounded-pill border px-3.5 py-1.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                    :class="activeSkillTab === g.typeId ? 'border-accent bg-accent text-on-accent' : 'border-line text-muted hover:border-line-strong hover:text-ink'">
                                <span x-text="g.label"></span>
                                <span x-show="g.is_primary" x-cloak aria-hidden="true">★</span>
                            </button>
                        </template>
                    </div>
                    <p x-show="activeSkillGroups.length === 0" x-cloak class="rounded-lg border border-dashed border-line p-6 text-center text-sm text-subtle">{{ __('Select a skill above to manage its blocks.') }}</p>
                    <template x-for="group in activeSkillGroups" :key="group.key">
                        @include('talent.partials.block-scope-card')
                    </template>
                </div>

                <p x-show="skills.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-6 text-center text-sm text-subtle">{{ __('Add a skill below to create its tab and blocks.') }}</p>
            </div>
        </x-ui.section>

        {{-- Skills (below the blocks; adding one creates its section above) --}}
        <x-ui.section :title="__('Skills')" :eyebrow="__('Drag to reorder · star to set primary')">
            <div class="space-y-4">
                <div class="space-y-2">
                    <template x-for="type in skills" :key="type.id">
                        <div draggable="true"
                             @dragstart="skillDragId = type.id" @dragover.prevent @drop.prevent="onSkillDrop(type)"
                             class="flex items-center gap-3 rounded-lg border border-line bg-surface p-3">
                            <span class="cursor-grab text-subtle">⋮⋮</span>
                            <div class="flex-1">
                                <span class="font-medium text-ink" x-text="t(type.name)"></span>
                                {{-- Category display label (Modeling / Crew / Creative), not the raw enum. --}}
                                <span class="ms-2 font-mono text-[10px] uppercase tracking-wider text-subtle"
                                      x-text="({ model: '{{ __('Modeling') }}', crew: '{{ __('Crew') }}', creative: '{{ __('Creative') }}' })[type.category] || type.category"></span>
                            </div>
                            <button @click="makePrimarySkill(type)" class="text-lg"
                                    :class="type.is_primary ? 'text-gold' : 'text-subtle hover:text-gold'"
                                    :title="type.is_primary ? '{{ __('Primary') }}' : '{{ __('Make primary') }}'"
                                    x-text="type.is_primary ? '★' : '☆'"></button>
                            {{-- Removing a skill deletes its tab's blocks (content is kept) — confirm first. --}}
                            <button @click="$confirm({ title: '{{ __('Remove this skill?') }}', message: '{{ __('Delete this tab’s blocks? (your content is kept)') }}', confirmLabel: '{{ __('Remove') }}' }).then(ok => ok && removeSkill(type))"
                                    class="text-subtle hover:text-danger" title="{{ __('Remove skill') }}">✕</button>
                        </div>
                    </template>
                    <p x-show="skills.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-6 text-center text-sm text-subtle">{{ __('Add your first skill below.') }}</p>
                </div>

                <x-ui.card class="flex flex-wrap items-end gap-3">
                    <div class="flex-1">
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Skill') }}</label>
                        <select x-model="addSkillId" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                            <option value="">{{ __('Choose…') }}</option>
                            <template x-for="type in availableSkills" :key="type.id">
                                <option :value="type.id" x-text="t(type.name)"></option>
                            </template>
                        </select>
                    </div>
                    <x-ui.button variant="accent" x-on:click="addSkill()" x-bind:disabled="!addSkillId">{{ __('Add skill') }}</x-ui.button>
                </x-ui.card>
            </div>
        </x-ui.section>
    </div>
</x-talent-layout>
