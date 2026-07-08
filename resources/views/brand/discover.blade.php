<x-brand-layout :title="__('Discover talent')">
    <div x-data="brandDiscover()" class="space-y-6">
        <p class="text-sm text-muted">{{ __('Talent matched to your creative needs and reach.') }}</p>

        <div x-show="loading && !talents.length" class="py-10 text-center text-sm text-muted">{{ __('Loading…') }}</div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="talent in talents" :key="talent.id">
                <div class="flex flex-col overflow-hidden rounded-xl border border-line bg-surface">
                    <div class="h-40 bg-elevated bg-cover bg-center" :style="talent.avatar_url ? `background-image:url(${talent.avatar_url})` : ''"></div>
                    <div class="flex flex-1 flex-col p-4">
                        <a :href="`/${talent.slug}`" target="_blank" class="font-display text-lg hover:text-accent" x-text="talent.display_name"></a>
                        <p class="text-xs text-muted" x-text="talent.primary_type ? talent.primary_type.name : ''"></p>
                        <p class="mt-1 line-clamp-2 text-sm text-muted" x-text="talent.headline"></p>
                        <p class="mt-1 text-xs text-muted" x-text="[talent.city, talent.country].filter(Boolean).join(', ')"></p>
                        <div class="mt-auto flex gap-2 pt-3">
                            <button @click="save(talent.id)" :disabled="saved[talent.id]" class="flex-1 rounded-pill border border-line-strong px-3 py-1.5 text-xs text-muted hover:text-ink disabled:opacity-50" x-text="saved[talent.id] ? '{{ __('Saved') }}' : '{{ __('Save') }}'"></button>
                            <button @click="brief(talent.id)" class="flex-1 rounded-pill bg-accent px-3 py-1.5 text-xs text-on-primary hover:opacity-90">{{ __('Send brief') }}</button>
                        </div>
                    </div>
                </div>
            </template>
            <template x-if="!loading && !talents.length"><p class="col-span-full rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('No matches yet — tune your creative needs.') }}</p></template>
        </div>

        <div x-show="hasMore" class="text-center">
            <button @click="more()" :disabled="loading" class="rounded-pill border border-line px-5 py-2 text-sm text-muted hover:text-ink disabled:opacity-50">{{ __('Load more') }}</button>
        </div>
    </div>
</x-brand-layout>
