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
        'hero_image_url' => $talent->hero_image_url,
    ];
@endphp

<x-talent-layout :title="__('Profile editor')">
    <div x-data="profileEditor({ core: @js($core), blocks: @js($blocks), picker: @js($picker) })" class="space-y-10">

        {{-- Hero + core fields --}}
        <x-ui.section :title="__('Core details')" :eyebrow="__('Your identity')">
            <x-ui.card class="space-y-6">
                {{-- Hero uploader --}}
                <div>
                    <label class="mb-2 block text-sm font-medium text-ink">{{ __('Hero image') }}</label>
                    <div class="relative flex h-40 items-center justify-center overflow-hidden rounded-lg border border-line"
                         style="background-color:var(--surface);background-image:repeating-linear-gradient(135deg, var(--line) 0 1px, transparent 1px 16px);">
                        <template x-if="heroUrl"><img :src="heroUrl" alt="" class="absolute inset-0 h-full w-full object-cover"></template>
                        <div x-show="heroUploading" x-cloak class="absolute inset-0 grid place-items-center bg-black/30 text-xs font-mono uppercase tracking-wider text-white">{{ __('Uploading…') }}</div>
                        <label class="relative z-10 cursor-pointer rounded-pill bg-primary px-4 py-2 text-xs font-medium text-on-primary hover:opacity-90">
                            {{ __('Upload') }}
                            <input type="file" accept="image/*" class="hidden" @change="uploadHero($event)">
                        </label>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Display name') }}</label>
                        <input x-model="core.display_name" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                        <p x-show="errors.display_name" x-cloak class="mt-1 text-xs text-danger" x-text="errors.display_name?.[0]"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-ink">{{ __('Public slug') }}</label>
                        <input x-model="core.slug" type="text" class="w-full rounded-md border-line bg-elevated font-mono text-sm text-ink shadow-sm focus:border-accent focus:ring-accent">
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

        {{-- Blocks + picker --}}
        <div class="grid gap-8 lg:grid-cols-3">
            <x-ui.section class="lg:col-span-2" :title="__('Your blocks')" :eyebrow="__('Drag to reorder')">
                <div class="space-y-2">
                    <template x-for="block in blocks" :key="block.id">
                        <div draggable="true"
                             @dragstart="dragId = block.id"
                             @dragover.prevent
                             @drop.prevent="onDrop(block)"
                             class="flex items-center gap-3 rounded-lg border border-line bg-surface p-3"
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
                            <button @click="toggleVisible(block)" class="rounded-pill px-3 py-1 text-xs font-medium"
                                    :class="block.is_visible ? 'bg-success-weak text-success' : 'bg-surface text-muted border border-line'">
                                <span x-text="block.is_visible ? '{{ __('Visible') }}' : '{{ __('Hidden') }}'"></span>
                            </button>
                            <button @click="removeBlock(block)" class="text-subtle hover:text-danger" title="{{ __('Remove') }}">✕</button>
                        </div>
                    </template>
                    <p x-show="blocks.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-6 text-center text-sm text-subtle">{{ __('No blocks yet — add some from the picker.') }}</p>
                </div>
            </x-ui.section>

            <x-ui.section :title="__('Add a block')" :eyebrow="__('Block picker')">
                <div class="space-y-2">
                    <template x-for="bt in picker" :key="bt.id">
                        <button @click="addBlock(bt.id)" class="flex w-full items-center justify-between gap-3 rounded-md border border-line bg-surface p-3 text-start transition hover:border-line-strong">
                            <span>
                                <span class="block text-sm font-medium text-ink" x-text="t(bt.name)"></span>
                                <span class="block font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="bt.availability"></span>
                            </span>
                            <span class="text-accent-ink">+</span>
                        </button>
                    </template>
                    <p x-show="picker.length === 0" x-cloak class="rounded-md border border-line bg-surface p-6 text-center text-sm text-subtle">{{ __('Nothing left to add.') }}</p>
                </div>
            </x-ui.section>
        </div>
    </div>
</x-talent-layout>
