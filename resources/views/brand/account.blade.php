@php
    $initial = [
        'form' => [
            'slug' => $brand->slug,
            'founded_year' => $brand->founded_year,
            'company_size' => $brand->company_size,
            'website' => $brand->website,
            'phone' => $brand->phone,
        ],
        'published' => (bool) $brand->is_published,
    ];
@endphp

<x-brand-layout :title="__('Account')">
    <div x-data="brandAccount(@js($initial))" class="space-y-6">
        {{-- Publish toggle --}}
        <section class="flex items-center justify-between rounded-xl border border-line bg-surface p-6">
            <div>
                <h3 class="font-display text-lg">{{ __('Visibility') }}</h3>
                <p class="text-sm text-muted" x-text="published ? '{{ __('Your profile is visible to talent.') }}' : '{{ __('Your profile is hidden.') }}'"></p>
            </div>
            <button @click="togglePublish()" :disabled="publishing"
                    :class="published ? 'bg-ok text-white' : 'bg-primary text-on-primary'"
                    class="rounded-pill px-4 py-2 text-sm disabled:opacity-50"
                    x-text="published ? '{{ __('Unpublish') }}' : '{{ __('Publish') }}'"></button>
        </section>

        {{-- Settings --}}
        <section class="rounded-xl border border-line bg-surface p-6">
            <h3 class="mb-4 font-display text-lg">{{ __('Settings') }}</h3>
            <div class="space-y-4">
                <div>
                    <x-input-label :value="__('Profile slug')" />
                    <x-text-input class="mt-1 block w-full" x-model="form.slug" placeholder="my-brand" />
                    <template x-if="errors.slug"><p class="mt-1 text-xs text-danger" x-text="errors.slug[0]"></p></template>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><x-input-label :value="__('Founded year')" /><x-text-input type="number" class="mt-1 block w-full" x-model="form.founded_year" /></div>
                    <div>
                        <x-input-label :value="__('Company size')" />
                        <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="form.company_size">
                            <option :value="null">—</option>
                            @foreach (['solo','small','medium','large','enterprise'] as $opt)<option value="{{ $opt }}">{{ __(ucfirst($opt)) }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div><x-input-label :value="__('Website')" /><x-text-input class="mt-1 block w-full" x-model="form.website" placeholder="https://" /></div>
                <div><x-input-label :value="__('Phone')" /><x-text-input class="mt-1 block w-full" x-model="form.phone" /></div>
            </div>
            <div class="mt-4 flex items-center gap-3">
                <button @click="save()" :disabled="saving" class="rounded-pill bg-primary px-5 py-2 text-sm text-on-primary disabled:opacity-50">{{ __('Save') }}</button>
                <span x-show="saved" x-cloak class="text-xs text-ok">{{ __('Saved') }}</span>
            </div>
        </section>
    </div>
</x-brand-layout>
