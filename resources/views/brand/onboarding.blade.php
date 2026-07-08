@php
    $initial = ['data' => [
        'name' => $brand->name,
        'description' => ['en' => $brand->getTranslation('description', 'en', false), 'ar' => $brand->getTranslation('description', 'ar', false)],
        'industry' => $brand->industry,
        'brand_stage' => $brand->brand_stage,
        'base_city' => $brand->base_city,
        'base_country' => $brand->base_country,
        'geographic_reach' => $brand->geographic_reach,
        'talent_type_ids' => $brand->creativeNeed?->talentTypes->pluck('id')->all() ?? [],
        'project_types' => $brand->creativeNeed?->projectTypes->pluck('project_type')->all() ?? [],
        'project_frequency' => $brand->creativeNeed?->project_frequency,
        'mood_tags' => $brand->aesthetic?->moodTags->pluck('tag')->all() ?? [],
        'brand_references' => $brand->aesthetic?->brand_references,
        'budget_tier' => $brand->creativeNeed?->budget_tier,
    ]];
    $steps = [__('Identity'), __('Location'), __('Creative needs'), __('Aesthetic'), __('Budget'), __('Finish')];
@endphp

<x-brand-layout :title="__('Welcome to Fama')">
    <div x-data="brandOnboarding(@js($initial))" class="mx-auto max-w-2xl">
        {{-- Progress --}}
        <ol class="mb-8 flex items-center justify-between gap-2 text-xs">
            @foreach ($steps as $i => $label)
                <li class="flex flex-1 flex-col items-center gap-1">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full border font-medium"
                          :class="step >= {{ $i + 1 }} ? 'border-accent bg-accent text-on-primary' : 'border-line text-muted'">{{ $i + 1 }}</span>
                    <span class="text-center text-muted">{{ $label }}</span>
                </li>
            @endforeach
        </ol>

        <div class="rounded-xl border border-line bg-surface p-6 sm:p-8">
            {{-- Step 1 — Identity --}}
            <section x-show="step === 1" class="space-y-4">
                <h2 class="font-display text-xl">{{ __('Tell us about your brand') }}</h2>
                <div>
                    <x-input-label :value="__('Brand name')" />
                    <x-text-input class="mt-1 block w-full" x-model="data.name" />
                    <template x-if="errors.name"><p class="mt-1 text-xs text-danger" x-text="errors.name[0]"></p></template>
                </div>
                <div>
                    <x-input-label :value="__('Short description (EN)')" />
                    <textarea class="mt-1 block w-full rounded-md border-line bg-bg text-ink" rows="2" x-model="data.description.en"></textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label :value="__('Industry')" />
                        <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="data.industry">
                            @foreach (['fashion','beauty','food_beverage','lifestyle','tech','other'] as $opt)
                                <option value="{{ $opt }}">{{ __(ucfirst(str_replace('_',' ',$opt))) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label :value="__('Stage')" />
                        <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="data.brand_stage">
                            @foreach (['new','growing','established'] as $opt)
                                <option value="{{ $opt }}">{{ __(ucfirst($opt)) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button @click="identity()" :disabled="saving" class="rounded-pill bg-primary px-5 py-2 text-sm text-on-primary hover:opacity-90 disabled:opacity-50">{{ __('Continue') }}</button>
                </div>
            </section>

            {{-- Step 2 — Location --}}
            <section x-show="step === 2" x-cloak class="space-y-4">
                <h2 class="font-display text-xl">{{ __('Where are you based?') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><x-input-label :value="__('City')" /><x-text-input class="mt-1 block w-full" x-model="data.base_city" /></div>
                    <div><x-input-label :value="__('Country')" /><x-text-input class="mt-1 block w-full" x-model="data.base_country" /></div>
                </div>
                <div>
                    <x-input-label :value="__('Geographic reach')" />
                    <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="data.geographic_reach">
                        <option value="same_city">{{ __('Same city') }}</option>
                        <option value="mena">{{ __('MENA region') }}</option>
                        <option value="international">{{ __('International') }}</option>
                    </select>
                </div>
                <div class="flex justify-between">
                    <button @click="step = 1" class="text-sm text-muted hover:text-ink">{{ __('Back') }}</button>
                    <button @click="location()" :disabled="saving" class="rounded-pill bg-primary px-5 py-2 text-sm text-on-primary disabled:opacity-50">{{ __('Continue') }}</button>
                </div>
            </section>

            {{-- Step 3 — Creative needs --}}
            <section x-show="step === 3" x-cloak class="space-y-4">
                <h2 class="font-display text-xl">{{ __('Who do you hire?') }}</h2>
                <div>
                    <x-input-label :value="__('Talent types')" />
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($talentTypes as $type)
                            <button type="button" @click="toggle('talent_type_ids', {{ $type->id }})"
                                    :class="data.talent_type_ids.includes({{ $type->id }}) ? 'border-accent bg-accent-weak text-accent-ink' : 'border-line text-muted'"
                                    class="rounded-pill border px-3 py-1.5 text-xs">{{ $type->getTranslation('name', app()->getLocale()) }}</button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <x-input-label :value="__('Project types')" />
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($projectTypes as $pt)
                            <button type="button" @click="toggle('project_types', '{{ $pt }}')"
                                    :class="data.project_types.includes('{{ $pt }}') ? 'border-accent bg-accent-weak text-accent-ink' : 'border-line text-muted'"
                                    class="rounded-pill border px-3 py-1.5 text-xs">{{ __(ucfirst(str_replace('_',' ',$pt))) }}</button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <x-input-label :value="__('How often?')" />
                    <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="data.project_frequency">
                        @foreach (['occasional','monthly','weekly','ongoing'] as $opt)
                            <option value="{{ $opt }}">{{ __(ucfirst($opt)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-between">
                    <button @click="step = 2" class="text-sm text-muted hover:text-ink">{{ __('Back') }}</button>
                    <button @click="needs()" :disabled="saving" class="rounded-pill bg-primary px-5 py-2 text-sm text-on-primary disabled:opacity-50">{{ __('Continue') }}</button>
                </div>
            </section>

            {{-- Step 4 — Aesthetic --}}
            <section x-show="step === 4" x-cloak class="space-y-4">
                <h2 class="font-display text-xl">{{ __('Your aesthetic') }}</h2>
                <div>
                    <x-input-label :value="__('Mood')" />
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($moods as $mood)
                            <button type="button" @click="toggle('mood_tags', '{{ $mood }}')"
                                    :class="data.mood_tags.includes('{{ $mood }}') ? 'border-accent bg-accent-weak text-accent-ink' : 'border-line text-muted'"
                                    class="rounded-pill border px-3 py-1.5 text-xs">{{ __(ucfirst($mood)) }}</button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <x-input-label :value="__('References (brands you admire)')" />
                    <textarea class="mt-1 block w-full rounded-md border-line bg-bg text-ink" rows="2" x-model="data.brand_references"></textarea>
                </div>
                <div class="flex justify-between">
                    <button @click="step = 3" class="text-sm text-muted hover:text-ink">{{ __('Back') }}</button>
                    <button @click="aesthetic()" :disabled="saving" class="rounded-pill bg-primary px-5 py-2 text-sm text-on-primary disabled:opacity-50">{{ __('Continue') }}</button>
                </div>
            </section>

            {{-- Step 5 — Budget --}}
            <section x-show="step === 5" x-cloak class="space-y-4">
                <h2 class="font-display text-xl">{{ __('Typical budget per project') }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (['under_500'=>'Under $500','500_2000'=>'$500 – $2,000','2000_10000'=>'$2,000 – $10,000','10000_plus'=>'$10,000+'] as $val => $label)
                        <button type="button" @click="data.budget_tier = '{{ $val }}'"
                                :class="data.budget_tier === '{{ $val }}' ? 'border-accent bg-accent-weak text-accent-ink' : 'border-line text-muted'"
                                class="rounded-lg border px-4 py-3 text-sm">{{ $label }}</button>
                    @endforeach
                </div>
                <div class="flex justify-between">
                    <button @click="step = 4" class="text-sm text-muted hover:text-ink">{{ __('Back') }}</button>
                    <button @click="budget()" :disabled="saving || !data.budget_tier" class="rounded-pill bg-primary px-5 py-2 text-sm text-on-primary disabled:opacity-50">{{ __('Continue') }}</button>
                </div>
            </section>

            {{-- Step 6 — Finish --}}
            <section x-show="step === 6" x-cloak class="space-y-4 text-center">
                <h2 class="font-display text-2xl">{{ __('You’re all set!') }}</h2>
                <p class="text-muted">{{ __('Finish to unlock your personalised talent feed.') }}</p>
                <button @click="complete()" :disabled="saving" class="rounded-pill bg-accent px-6 py-2.5 text-sm font-medium text-on-primary hover:opacity-90 disabled:opacity-50">{{ __('Enter Fama') }}</button>
            </section>
        </div>
    </div>
</x-brand-layout>
