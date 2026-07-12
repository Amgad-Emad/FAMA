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
                <a :href="`/brand/campaigns/${campaign.id}`" class="block rounded-xl border border-line bg-surface p-5 hover:border-line-strong">
                    <div class="flex items-center justify-between">
                        <h3 class="font-display text-lg" x-text="campaign.title"></h3>
                        <span class="rounded-pill bg-elevated px-2 py-0.5 text-xs text-muted" x-text="campaign.status"></span>
                    </div>
                    <p class="mt-1 text-xs text-muted"><span x-text="campaign.deals_count || 0"></span> {{ __('deals') }} · <span x-text="campaign.is_public ? '{{ __('Public') }}' : '{{ __('Private') }}'"></span></p>
                </a>
            </template>
            <template x-if="!campaigns.length"><p class="col-span-full rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('No campaigns yet.') }}</p></template>
        </div>
    </div>
</x-brand-layout>
