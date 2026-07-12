@php
    $core = [
        'display_name' => $talent->display_name,
        'headline' => array_merge(['en' => '', 'ar' => ''], $talent->getTranslations('headline')),
        'bio' => array_merge(['en' => '', 'ar' => ''], $talent->getTranslations('bio')),
        'slug' => $talent->slug,
        'base_city' => $talent->base_city,
        'base_country' => $talent->base_country,
        'booking_type' => $talent->booking_type,
        'booking_value' => $talent->booking_value,
    ];
    $publish = ['is_published' => (bool) $talent->is_published, 'status' => $talent->status->getValue()];
    $rate = ['rate_unit' => $talent->rate_unit, 'rate_amount' => $talent->rate_amount, 'rate_currency' => $talent->rate_currency];
@endphp

<x-talent-layout :title="__('Profile')">
    <div x-data="profileEditor({
        core: @js($core),
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
            <x-ui.card class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-pill" :class="isPublished ? 'bg-success' : 'bg-warn'"></span>
                        <span class="font-display text-xl text-ink" x-text="isPublished ? '{{ __('Live') }}' : ('{{ __('Not live') }}' + ' · ' + status)"></span>
                    </div>
                    <p x-show="publishError" x-cloak class="mt-1 text-xs text-danger" x-text="publishError"></p>
                </div>
                <x-ui.button variant="accent" x-on:click="togglePublish()" x-bind:disabled="publishing">
                    <span x-text="isPublished ? '{{ __('Unpublish') }}' : '{{ __('Publish') }}'"></span>
                </x-ui.button>
            </x-ui.card>
        </x-ui.section>

        {{-- Core fields (identity + username) --}}
        <x-ui.section :title="__('Core details')" :eyebrow="__('Your identity')">
            <x-ui.card class="space-y-6">
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
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Headline (EN)') }}</label>
                        <input x-model="core.headline.en" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </div>
                    <div dir="rtl">
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Headline (AR)') }}</label>
                        <input x-model="core.headline.ar" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Bio (EN)') }}</label>
                        <textarea x-model="core.bio.en" rows="3" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                    </div>
                    <div class="sm:col-span-2" dir="rtl">
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Bio (AR)') }}</label>
                        <textarea x-model="core.bio.ar" rows="3" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Base city') }}</label>
                        <input x-model="core.base_city" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Base country') }}</label>
                        <input x-model="core.base_country" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Booking method') }}</label>
                        <select x-model="core.booking_type" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                            <option value="email">{{ __('Email') }}</option>
                            <option value="calendar">{{ __('Calendar') }}</option>
                            <option value="form">{{ __('Form') }}</option>
                            <option value="external">{{ __('External link') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Booking value') }}</label>
                        <input x-model="core.booking_value" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
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

        {{-- Skills (folded into the profile from the old standalone tab) --}}
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
                            <template x-if="confirmRemoveSkillId !== type.id">
                                <button @click="requestRemoveSkill(type)" class="text-subtle hover:text-danger" title="{{ __('Remove skill') }}">✕</button>
                            </template>
                            <template x-if="confirmRemoveSkillId === type.id">
                                <span class="flex items-center gap-2">
                                    <span class="text-[11px] text-danger">{{ __('Delete this tab’s blocks? (content kept)') }}</span>
                                    <button @click="removeSkill(type)" class="rounded-pill bg-danger px-2 py-0.5 text-[11px] font-medium text-white">{{ __('Remove') }}</button>
                                    <button @click="cancelRemoveSkill()" class="text-[11px] text-muted underline">{{ __('Cancel') }}</button>
                                </span>
                            </template>
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

        {{-- Blocks — organised by scope: the universal section + one tab per skill (ADR-Q) --}}
        <x-ui.section :title="__('Blocks')" :eyebrow="__('Per skill · drag to reorder within a section')">
            <div class="space-y-6">
                <template x-for="group in scopeGroups" :key="group.key">
                    <x-ui.card class="space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="font-display text-lg text-ink" x-text="group.label"></span>
                            <span x-show="group.is_primary" x-cloak class="rounded-pill bg-gold-weak px-2 py-0.5 text-[10px] font-medium text-gold">{{ __('Primary') }}</span>
                            <span x-show="group.typeId === null" x-cloak class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Above the tabs') }}</span>
                        </div>

                        {{-- Blocks in this scope --}}
                        <div class="space-y-2">
                            <template x-for="block in blocksInScope(group.typeId)" :key="block.id">
                                <div draggable="true"
                                     @dragstart="dragId = block.id"
                                     @dragover.prevent
                                     @drop.prevent="onDrop(block)"
                                     class="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-elevated p-3"
                                     :class="block.is_visible ? '' : 'opacity-60'">
                                    <span class="cursor-grab text-subtle" title="{{ __('Drag') }}">⋮⋮</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="block.block_type.key"></div>
                                        <input type="text" :value="t(block.title) || t(block.block_type.name)"
                                               @change="block.title = { ...(block.title||{}), en: $event.target.value }; saveTitle(block)"
                                               class="w-full border-0 bg-transparent p-0 text-ink focus:ring-0">
                                    </div>
                                    <template x-if="block.block_type.content_source === 'table'">
                                        <a :href="'/talent/content/' + block.block_type.key" class="rounded-pill border border-line-strong px-3 py-1 text-xs text-muted hover:text-ink">{{ __('Edit content') }}</a>
                                    </template>
                                    {{-- Move between scopes (only shows eligible targets) --}}
                                    <template x-if="moveTargets(block).length">
                                        <select @change="moveBlock(block, $event.target.value); $event.target.value=''"
                                                class="rounded-md border-line bg-surface py-1 text-xs text-muted focus:border-accent focus:ring-accent">
                                            <option value="">{{ __('Move to…') }}</option>
                                            <template x-for="g in moveTargets(block)" :key="g.key">
                                                <option :value="g.typeId === null ? '' : g.typeId" x-text="g.label"></option>
                                            </template>
                                        </select>
                                    </template>
                                    <button @click="toggleVisible(block)" class="rounded-pill px-3 py-1 text-xs font-medium"
                                            :class="block.is_visible ? 'bg-success-weak text-success' : 'bg-surface text-muted border border-line'">
                                        <span x-text="block.is_visible ? '{{ __('Visible') }}' : '{{ __('Hidden') }}'"></span>
                                    </button>
                                    <button @click="removeBlock(block)" class="text-subtle hover:text-danger" title="{{ __('Remove') }}">✕</button>
                                </div>
                            </template>
                            <p x-show="blocksInScope(group.typeId).length === 0" x-cloak class="rounded-lg border border-dashed border-line p-4 text-center text-xs text-subtle">{{ __('No blocks here yet.') }}</p>
                        </div>

                        {{-- Add a block into this scope --}}
                        <div class="flex flex-wrap items-end gap-2 border-t border-line pt-3">
                            <select x-model="newBlock[group.key]" class="flex-1 rounded-md border-line bg-elevated text-sm text-ink shadow-sm focus:border-accent focus:ring-accent">
                                <option value="">{{ __('Add a block…') }}</option>
                                <template x-for="bt in pickerForScope(group)" :key="bt.id">
                                    <option :value="bt.id" x-text="t(bt.name)"></option>
                                </template>
                            </select>
                            <x-ui.button variant="accent" size="sm" x-on:click="addBlock(group)" x-bind:disabled="!newBlock[group.key]">{{ __('Add') }}</x-ui.button>
                        </div>
                    </x-ui.card>
                </template>

                <p x-show="skills.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-6 text-center text-sm text-subtle">{{ __('Add a skill above to create its tab and blocks.') }}</p>
            </div>
        </x-ui.section>
    </div>
</x-talent-layout>
