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
        'moods' => $moods,
        'platforms' => $platforms,
    ];
@endphp

<x-brand-layout :title="__('Profile editor')">
    <div x-data="brandProfile(@js($initial))" class="space-y-8">
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
                        <button @click="removeImage(image.id)" class="absolute end-1 top-1 rounded-full bg-black/60 px-2 py-0.5 text-xs text-white opacity-0 group-hover:opacity-100">✕</button>
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
                        <button @click="removeHandle(handle.id)" class="text-xs text-danger">{{ __('Remove') }}</button>
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
