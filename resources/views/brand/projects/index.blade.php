@php
    $initial = ['types' => $talentTypes->map(fn ($t) => ['id' => $t->id, 'name' => $t->getTranslation('name', app()->getLocale())])->values()];
@endphp

<x-brand-layout :title="__('Projects')">
    <div x-data="brandProjects(@js($initial))" class="space-y-6">
        <div class="flex items-center justify-between">
            <p class="text-sm text-muted">{{ __('Group your projects and the contracts that run under them.') }}</p>
            <button @click="showForm = !showForm" class="rounded-pill bg-accent px-4 py-2 text-sm text-on-primary hover:opacity-90">{{ __('New project') }}</button>
        </div>

        {{-- Create form — premium, sectioned (mirrors the project edit form) --}}
        <div x-show="showForm" x-cloak class="overflow-hidden rounded-2xl border border-line bg-elevated/40 shadow-e1">
            {{-- Header --}}
            <div class="flex items-start gap-3 border-b border-line px-6 py-5 sm:px-7">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-accent-weak text-accent-ink">
                    <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <h3 class="font-display text-lg text-ink">{{ __('New project') }}</h3>
                    <p class="text-xs text-muted">{{ __('Describe the project and the one role you need.') }}</p>
                </div>
            </div>

            <div class="space-y-5 p-6 sm:p-7">
                {{-- Basics --}}
                <div class="rounded-xl border border-line bg-surface p-5">
                    <div class="mb-4 font-mono text-[10px] uppercase tracking-[0.16em] text-subtle">{{ __('Basics') }}</div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label :value="__('Title')" /><x-text-input class="mt-1 block w-full" x-model="form.title" placeholder="{{ __('e.g. Autumn Menu Launch') }}" />
                            <template x-if="errors.title"><p class="mt-1 text-xs text-danger" x-text="errors.title[0]"></p></template>
                        </div>
                        <div><x-input-label :value="__('Type')" /><select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="form.type"><option value="campaign">{{ __('Campaign') }}</option><option value="shoot">{{ __('Shoot') }}</option></select></div>
                        {{-- One role, one position — a single discipline. --}}
                        <div>
                            <x-input-label :value="__('Role')" />
                            <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model.number="form.talent_type_id">
                                <option :value="null">{{ __('Select a discipline…') }}</option>
                                <template x-for="type in types" :key="type.id"><option :value="type.id" x-text="type.name"></option></template>
                            </select>
                            <template x-if="errors.talent_type_id"><p class="mt-1 text-xs text-danger" x-text="errors.talent_type_id[0]"></p></template>
                        </div>
                        {{-- Brief (translatable) --}}
                        <div class="sm:col-span-2">
                            <x-input-label :value="__('Brief (EN)')" />
                            <textarea rows="2" class="mt-1 block w-full rounded-md border-line bg-bg text-sm" x-model="form.description.en" placeholder="{{ __('A short summary of the project…') }}"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label :value="__('Brief (AR)')" />
                            <textarea rows="2" dir="rtl" class="mt-1 block w-full rounded-md border-line bg-bg text-sm" x-model="form.description.ar" placeholder="{{ __('A short summary of the project…') }}"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Budget --}}
                <div class="rounded-xl border border-line bg-surface p-5">
                    <div class="mb-4 font-mono text-[10px] uppercase tracking-[0.16em] text-subtle">{{ __('Budget') }}</div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><x-input-label :value="__('Budget min')" /><x-text-input type="number" min="0" class="mt-1 block w-full" x-model.number="form.budget_min" /></div>
                        <div>
                            <x-input-label :value="__('Budget max')" /><x-text-input type="number" min="0" class="mt-1 block w-full" x-model.number="form.budget_max" />
                            <template x-if="errors.budget_max"><p class="mt-1 text-xs text-danger" x-text="errors.budget_max[0]"></p></template>
                        </div>
                    </div>
                    <label class="mt-4 flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-line bg-bg px-3.5 py-2.5 transition hover:border-line-strong">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-ink">{{ __('Show budget publicly') }}</span>
                            <span class="block text-xs text-muted">{{ __('Off — only you can see the budget.') }}</span>
                        </span>
                        <input type="checkbox" x-model="form.budget_is_public" class="h-4 w-4 shrink-0 rounded border-line text-accent focus:ring-accent">
                    </label>
                </div>

                {{-- Visibility --}}
                <div class="rounded-xl border border-line bg-surface p-5">
                    <div class="mb-3 font-mono text-[10px] uppercase tracking-[0.16em] text-subtle">{{ __('Visibility') }}</div>
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-line bg-bg px-3.5 py-2.5 transition hover:border-line-strong">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-ink">{{ __('List publicly') }}</span>
                            <span class="block text-xs text-muted">{{ __('Show this project on your public profile.') }}</span>
                        </span>
                        <input type="checkbox" x-model="form.is_public" class="h-4 w-4 shrink-0 rounded border-line text-accent focus:ring-accent">
                    </label>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center gap-2 border-t border-line px-6 py-5 sm:px-7">
                <button @click="create()" :disabled="creating" class="rounded-pill bg-accent px-5 py-2 text-sm font-semibold text-on-accent transition hover:opacity-90 disabled:opacity-50">{{ __('Create project') }}</button>
                <button @click="showForm = false" class="rounded-pill border border-line px-4 py-2 text-sm text-muted transition hover:text-ink">{{ __('Cancel') }}</button>
                <template x-if="errors._"><p class="text-xs text-danger" x-text="errors._[0]"></p></template>
            </div>
        </div>

        {{-- List --}}
        <div x-show="loading" class="py-10 text-center text-sm text-muted">{{ __('Loading…') }}</div>
        <div x-show="!loading" class="grid gap-3 sm:grid-cols-2">
            <template x-for="campaign in campaigns" :key="campaign.id">
                <div class="rounded-xl border border-line bg-surface p-5 shadow-e1 transition hover:border-line-strong">
                    <div class="flex items-start justify-between gap-3">
                        <a :href="`/brand/projects/${campaign.id}`" class="min-w-0 font-display text-lg text-ink transition hover:text-accent-ink" x-text="campaign.title"></a>
                        <span class="shrink-0 rounded-pill px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                              :class="campaign.status === 'completed' ? 'bg-success-weak text-success' : (campaign.status === 'open' ? 'bg-accent-weak text-accent-ink' : (campaign.status === 'cancelled' ? 'bg-danger-weak text-danger' : 'bg-elevated text-muted'))"
                              x-text="campaign.status.replaceAll('_', ' ')"></span>
                    </div>
                    <p class="mt-1 text-xs text-muted"><span x-text="campaign.contracts_count || 0"></span> {{ __('contracts') }} · <span x-text="campaign.is_public ? '{{ __('Public') }}' : '{{ __('Private') }}'"></span></p>
                    {{-- Edit only while the campaign is still editable (not completed/cancelled). --}}
                    <div class="mt-4 flex items-center gap-2">
                        <a :href="`/brand/projects/${campaign.id}`" class="rounded-pill border border-line-strong px-3 py-1.5 text-xs font-medium text-muted transition hover:border-accent hover:text-ink">{{ __('View') }}</a>
                        <template x-if="!['completed', 'cancelled'].includes(campaign.status)">
                            <a :href="`/brand/projects/${campaign.id}`" class="inline-flex items-center gap-1.5 rounded-pill bg-accent px-3 py-1.5 text-xs font-medium text-on-accent transition hover:opacity-90">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
                                {{ __('Edit') }}
                            </a>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="!campaigns.length"><p class="col-span-full rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('No projects yet.') }}</p></template>
        </div>
    </div>
</x-brand-layout>
