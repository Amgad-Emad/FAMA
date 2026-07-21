@php
    $initial = [
        'core' => [
            'name' => $brand->name,
            'description' => ['en' => $brand->getTranslation('description', 'en', false), 'ar' => $brand->getTranslation('description', 'ar', false)],
            'industry' => $brand->industry,
            'brand_stage' => $brand->brand_stage,
            'base_city' => $brand->base_city,
            'base_country' => $brand->base_country,
            'geographic_reach' => $brand->geographic_reach,
            'website' => $brand->website,
            'logo_url' => $brand->logo_url,
            'cover_image_url' => $brand->cover_image_url,
        ],
        'aesthetic' => [
            'brand_references' => $brand->aesthetic?->brand_references,
            'mood_tags' => $brand->aesthetic?->moodTags->pluck('tag')->all() ?? [],
        ],
        // Account settings — folded in from the old Account tab.
        'account' => [
            'slug' => $brand->slug,
            'founded_year' => $brand->founded_year,
            'company_size' => $brand->company_size,
            'phone' => $brand->phone,
        ],
        // Creative needs — folded in from the old Creative needs tab.
        'needs' => [
            'talent_type_ids' => $brand->creativeNeed?->talentTypes->pluck('id')->all() ?? [],
            'project_types' => $brand->creativeNeed?->projectTypes->pluck('project_type')->all() ?? [],
            'project_frequency' => $brand->creativeNeed?->project_frequency,
            'budget_tier' => $brand->creativeNeed?->budget_tier,
        ],
        'published' => (bool) $brand->is_published,
        'moods' => $moods,
        'platforms' => $platforms,
    ];
@endphp

<x-brand-layout :title="__('Profile editor')">
    <div x-data="brandProfile(@js($initial))" class="space-y-8">
        {{-- Publishing (folded in from the old Account tab) --}}
        <section class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-line bg-surface p-6 shadow-e1">
            <div>
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-pill" :class="published ? 'bg-success' : 'bg-warn'"></span>
                    <h3 class="font-display text-lg text-ink">{{ __('Visibility') }}</h3>
                </div>
                <p class="mt-0.5 text-sm text-muted" x-text="published ? '{{ __('Your profile is visible to talent.') }}' : '{{ __('Your profile is hidden.') }}'"></p>
            </div>
            <x-ui.button variant="accent" x-on:click="togglePublish()" x-bind:disabled="publishing">
                <span x-text="published ? '{{ __('Unpublish') }}' : '{{ __('Publish') }}'"></span>
            </x-ui.button>
        </section>

        {{-- Cover + logo --}}
        <div class="overflow-hidden rounded-xl border border-line bg-surface">
            <div class="relative h-40 bg-elevated bg-cover bg-center" :style="coverUrl ? `background-image:url(${coverUrl})` : ''">
                <label class="absolute end-3 top-3 cursor-pointer rounded-pill bg-bg/80 px-3 py-1 text-xs backdrop-blur">
                    {{ __('Change cover') }}<input type="file" accept="image/*" class="hidden" @change="upload($event, 'cover', 'coverUrl')">
                </label>
            </div>
            <div class="flex items-center gap-4 p-4">
                <div class="h-16 w-16 shrink-0 rounded-lg border border-line bg-elevated bg-cover bg-center" :style="logoUrl ? `background-image:url(${logoUrl})` : ''"></div>
                <label class="cursor-pointer rounded-pill border border-line-strong px-3 py-1.5 text-xs text-muted hover:text-ink">
                    {{ __('Upload logo') }}<input type="file" accept="image/*" class="hidden" @change="upload($event, 'logo', 'logoUrl')">
                </label>
            </div>
        </div>

        {{-- Core fields --}}
        <section class="rounded-xl border border-line bg-surface p-6">
            <h3 class="mb-4 font-display text-lg">{{ __('Brand details') }}</h3>
            <div class="space-y-4">
                <div><x-input-label :value="__('Name')" /><x-text-input class="mt-1 block w-full" x-model="core.name" /></div>
                <div><x-input-label :value="__('Description (EN)')" /><textarea rows="2" class="mt-1 block w-full rounded-md border-line bg-bg" x-model="core.description.en"></textarea></div>
                <div><x-input-label :value="__('Description (AR)')" /><textarea rows="2" dir="rtl" class="mt-1 block w-full rounded-md border-line bg-bg" x-model="core.description.ar"></textarea></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><x-input-label :value="__('City')" /><x-text-input class="mt-1 block w-full" x-model="core.base_city" /></div>
                    <div><x-input-label :value="__('Country')" /><x-text-input class="mt-1 block w-full" x-model="core.base_country" /></div>
                </div>
                <div><x-input-label :value="__('Website')" /><x-text-input class="mt-1 block w-full" x-model="core.website" placeholder="https://" /></div>
            </div>
            <div class="mt-4 flex items-center gap-3">
                <button @click="saveCore()" :disabled="savingCore" class="rounded-pill bg-primary px-5 py-2 text-sm text-on-primary disabled:opacity-50">{{ __('Save') }}</button>
                <span x-show="coreSaved" x-cloak class="text-xs text-ok">{{ __('Saved') }}</span>
            </div>
        </section>

        {{-- Settings (folded in from the old Account tab): username + company facts --}}
        <section class="rounded-xl border border-line bg-surface p-6">
            <h3 class="mb-4 font-display text-lg">{{ __('Settings') }}</h3>
            <div class="space-y-4">
                <div>
                    <x-input-label :value="__('Profile slug')" />
                    <div class="mt-1 flex items-center gap-2">
                        <span class="font-mono text-sm text-subtle">fama.com/brands/</span>
                        <x-text-input class="block w-full font-mono text-sm" x-model="account.slug" placeholder="my-brand" />
                    </div>
                    <template x-if="accountErrors.slug"><p class="mt-1 text-xs text-danger" x-text="accountErrors.slug[0]"></p></template>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><x-input-label :value="__('Founded year')" /><x-text-input type="number" class="mt-1 block w-full" x-model="account.founded_year" /></div>
                    <div>
                        <x-input-label :value="__('Company size')" />
                        <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="account.company_size">
                            <option :value="null">—</option>
                            @foreach (['solo', 'small', 'medium', 'large', 'enterprise'] as $opt)<option value="{{ $opt }}">{{ __(ucfirst($opt)) }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div><x-input-label :value="__('Phone')" /><x-text-input class="mt-1 block w-full" x-model="account.phone" /></div>
            </div>
            <div class="mt-4 flex items-center gap-3">
                <button @click="saveAccount()" :disabled="savingAccount" class="rounded-pill bg-primary px-5 py-2 text-sm text-on-primary disabled:opacity-50">{{ __('Save') }}</button>
                <span x-show="accountSaved" x-cloak class="text-xs text-ok">{{ __('Saved') }}</span>
            </div>
        </section>

        {{-- Aesthetic --}}
        <section class="rounded-xl border border-line bg-surface p-6">
            <h3 class="mb-4 font-display text-lg">{{ __('Aesthetic') }}</h3>
            <x-input-label :value="__('Mood')" />
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($moods as $mood)
                    <button type="button" @click="toggleMood('{{ $mood }}')"
                            :class="aesthetic.mood_tags.includes('{{ $mood }}') ? 'border-accent bg-accent-weak text-accent-ink' : 'border-line text-muted'"
                            class="rounded-pill border px-3 py-1.5 text-xs">{{ __(ucfirst($mood)) }}</button>
                @endforeach
            </div>
            <div class="mt-4"><x-input-label :value="__('References')" /><textarea rows="2" class="mt-1 block w-full rounded-md border-line bg-bg" x-model="aesthetic.brand_references"></textarea></div>
            <button @click="saveAesthetic()" :disabled="savingAesthetic" class="mt-4 rounded-pill bg-primary px-5 py-2 text-sm text-on-primary disabled:opacity-50">{{ __('Save aesthetic') }}</button>
        </section>

        {{-- Creative needs (folded in from the old Creative needs tab) --}}
        <section class="rounded-xl border border-line bg-surface p-6">
            <h3 class="font-display text-lg">{{ __('Creative needs') }}</h3>
            <p class="mb-4 mt-1 text-sm text-muted">{{ __('These preferences shape your discovery feed.') }}</p>

            <x-input-label :value="__('Talent types')" />
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($talentTypes as $type)
                    <button type="button" @click="toggleNeed('talent_type_ids', {{ $type->id }})"
                            :class="needs.talent_type_ids.includes({{ $type->id }}) ? 'border-accent bg-accent-weak text-accent-ink' : 'border-line text-muted'"
                            class="rounded-pill border px-3 py-1.5 text-xs">{{ $type->getTranslation('name', app()->getLocale()) }}</button>
                @endforeach
            </div>

            <div class="mt-5"><x-input-label :value="__('Project types')" /></div>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($projectTypes as $pt)
                    <button type="button" @click="toggleNeed('project_types', '{{ $pt }}')"
                            :class="needs.project_types.includes('{{ $pt }}') ? 'border-accent bg-accent-weak text-accent-ink' : 'border-line text-muted'"
                            class="rounded-pill border px-3 py-1.5 text-xs">{{ __(ucfirst(str_replace('_', ' ', $pt))) }}</button>
                @endforeach
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label :value="__('Frequency')" />
                    <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="needs.project_frequency">
                        <option :value="null">—</option>
                        @foreach (['occasional', 'monthly', 'weekly', 'ongoing'] as $opt)<option value="{{ $opt }}">{{ __(ucfirst($opt)) }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <x-input-label :value="__('Budget tier')" />
                    <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="needs.budget_tier">
                        <option :value="null">—</option>
                        @foreach (['under_500' => 'Under $500', '500_2000' => '$500–2k', '2000_10000' => '$2k–10k', '10000_plus' => '$10k+'] as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                    </select>
                </div>
            </div>

            <div class="mt-5 flex items-center gap-3">
                <button @click="saveNeeds()" :disabled="savingNeeds" class="rounded-pill bg-primary px-5 py-2 text-sm text-on-primary disabled:opacity-50">{{ __('Save preferences') }}</button>
                <span x-show="needsSaved" x-cloak class="text-xs text-ok">{{ __('Saved') }}</span>
            </div>
        </section>

        {{-- Image gallery --}}
        <section class="rounded-xl border border-line bg-surface p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-display text-lg">{{ __('Gallery') }}</h3>
                <label class="cursor-pointer rounded-pill border border-line-strong px-3 py-1.5 text-xs text-muted hover:text-ink">
                    {{ __('Add image') }}<input type="file" accept="image/*" class="hidden" @change="addImage($event)">
                </label>
            </div>
            <div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
                <template x-for="image in images" :key="image.id">
                    <div class="group relative aspect-square overflow-hidden rounded-lg border border-line">
                        <img :src="image.thumbnail_url || image.image_url" class="h-full w-full object-cover" alt="">
                        <button @click="$confirm({ title: '{{ __('Remove this image?') }}', message: '{{ __('The image will be removed from your gallery.') }}', confirmLabel: '{{ __('Remove') }}' }).then(ok => ok && removeImage(image.id))" class="absolute end-1 top-1 rounded-full bg-black/60 px-2 py-0.5 text-xs text-white opacity-0 group-hover:opacity-100">✕</button>
                    </div>
                </template>
                <template x-if="!images.length"><p class="col-span-full py-6 text-center text-sm text-muted">{{ __('No images yet.') }}</p></template>
            </div>
        </section>

        {{-- Social handles --}}
        <section class="rounded-xl border border-line bg-surface p-6">
            <h3 class="mb-4 font-display text-lg">{{ __('Social handles') }}</h3>
            <div class="space-y-2">
                <template x-for="handle in handles" :key="handle.id">
                    <div class="flex items-center justify-between rounded-md border border-line px-3 py-2 text-sm">
                        <span><span class="font-medium" x-text="handle.platform"></span> · <span class="text-muted" x-text="handle.handle"></span></span>
                        <button @click="$confirm({ title: '{{ __('Remove this social link?') }}', message: '{{ __('This social handle will be removed from your profile.') }}', confirmLabel: '{{ __('Remove') }}' }).then(ok => ok && removeHandle(handle.id))" class="text-xs text-danger">{{ __('Remove') }}</button>
                    </div>
                </template>
            </div>
            <div class="mt-4 flex flex-wrap items-end gap-2">
                <select class="rounded-md border-line bg-bg text-sm" x-model="newHandle.platform">
                    @foreach ($platforms as $p)<option value="{{ $p }}">{{ ucfirst($p) }}</option>@endforeach
                </select>
                <x-text-input class="flex-1" x-model="newHandle.handle" :placeholder="__('@handle')" />
                <button @click="addHandle()" class="rounded-pill bg-primary px-4 py-2 text-sm text-on-primary">{{ __('Add') }}</button>
            </div>
        </section>
    </div>
</x-brand-layout>
