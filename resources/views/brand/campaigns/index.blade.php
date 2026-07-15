@php
    $initial = ['types' => $talentTypes->map(fn ($t) => ['id' => $t->id, 'name' => $t->getTranslation('name', app()->getLocale())])->values()];
@endphp

<x-brand-layout :title="__('Campaigns')">
    <div x-data="brandCampaigns(@js($initial))" class="space-y-6">
        <div class="flex items-center justify-between">
            <p class="text-sm text-muted">{{ __('Group your projects and the deals that run under them.') }}</p>
            <button @click="showForm = !showForm" class="rounded-pill bg-accent px-4 py-2 text-sm text-on-primary hover:opacity-90">{{ __('New campaign') }}</button>
        </div>

        {{-- Create form --}}
        <div x-show="showForm" x-cloak class="rounded-xl border border-line bg-surface p-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><x-input-label :value="__('Title')" /><x-text-input class="mt-1 block w-full" x-model="form.title" /><template x-if="errors.title"><p class="mt-1 text-xs text-danger" x-text="errors.title[0]"></p></template></div>
                <div><x-input-label :value="__('Type')" /><select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="form.type"><option value="campaign">{{ __('Campaign') }}</option><option value="shoot">{{ __('Shoot') }}</option></select></div>
                <div class="flex items-end gap-2"><label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="form.is_public" class="rounded border-line">{{ __('List publicly') }}</label></div>
                <div><x-input-label :value="__('Budget min')" /><x-text-input type="number" class="mt-1 block w-full" x-model="form.budget_min" /></div>
                <div><x-input-label :value="__('Budget max')" /><x-text-input type="number" class="mt-1 block w-full" x-model="form.budget_max" /></div>
            </div>
            <div class="mt-4">
                <div class="flex items-center justify-between"><x-input-label :value="__('Roles')" /><button @click="addRole()" class="text-xs text-accent">+ {{ __('Add role') }}</button></div>
                <template x-for="(role, i) in form.roles" :key="i">
                    <div class="mt-2 flex items-center gap-2">
                        <select class="flex-1 rounded-md border-line bg-bg text-sm" x-model.number="role.talent_type_id">
                            <template x-for="type in types" :key="type.id"><option :value="type.id" x-text="type.name"></option></template>
                        </select>
                        <x-text-input type="number" min="1" class="w-20" x-model.number="role.quantity" />
                        <button @click="removeRole(i)" class="text-xs text-danger">✕</button>
                    </div>
                </template>
            </div>
            <div class="mt-4 flex gap-2">
                <button @click="create()" :disabled="creating" class="rounded-pill bg-primary px-5 py-2 text-sm text-on-primary disabled:opacity-50">{{ __('Create') }}</button>
                <button @click="showForm = false" class="rounded-pill border border-line px-4 py-2 text-sm text-muted">{{ __('Cancel') }}</button>
            </div>
        </div>

        {{-- List --}}
        <div x-show="loading" class="py-10 text-center text-sm text-muted">{{ __('Loading…') }}</div>
        <div x-show="!loading" class="grid gap-3 sm:grid-cols-2">
            <template x-for="campaign in campaigns" :key="campaign.id">
                <div class="rounded-xl border border-line bg-surface p-5 shadow-e1 transition hover:border-line-strong">
                    <div class="flex items-start justify-between gap-3">
                        <a :href="`/brand/campaigns/${campaign.id}`" class="min-w-0 font-display text-lg text-ink transition hover:text-accent-ink" x-text="campaign.title"></a>
                        <span class="shrink-0 rounded-pill px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                              :class="campaign.status === 'completed' ? 'bg-success-weak text-success' : (campaign.status === 'open' ? 'bg-accent-weak text-accent-ink' : (campaign.status === 'cancelled' ? 'bg-danger-weak text-danger' : 'bg-elevated text-muted'))"
                              x-text="campaign.status.replaceAll('_', ' ')"></span>
                    </div>
                    <p class="mt-1 text-xs text-muted"><span x-text="campaign.deals_count || 0"></span> {{ __('deals') }} · <span x-text="campaign.is_public ? '{{ __('Public') }}' : '{{ __('Private') }}'"></span></p>
                    {{-- Edit only while the campaign is still editable (not completed/cancelled). --}}
                    <div class="mt-4 flex items-center gap-2">
                        <a :href="`/brand/campaigns/${campaign.id}`" class="rounded-pill border border-line-strong px-3 py-1.5 text-xs font-medium text-muted transition hover:border-accent hover:text-ink">{{ __('View') }}</a>
                        <template x-if="!['completed', 'cancelled'].includes(campaign.status)">
                            <a :href="`/brand/campaigns/${campaign.id}`" class="inline-flex items-center gap-1.5 rounded-pill bg-accent px-3 py-1.5 text-xs font-medium text-on-accent transition hover:opacity-90">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
                                {{ __('Edit') }}
                            </a>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="!campaigns.length"><p class="col-span-full rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('No campaigns yet.') }}</p></template>
        </div>
    </div>
</x-brand-layout>
