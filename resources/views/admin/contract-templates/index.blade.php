<x-admin-layout :title="__('Contract templates')">
    <div x-data="adminContractTemplates()" class="space-y-6">
        <div class="flex items-center justify-between">
            <p class="text-sm text-muted">{{ __('Author the contract schema — standard terms + clauses — that flows render into a contract document. Edits affect future contracts only.') }}</p>
        </div>

        {{-- Create --}}
        <x-ui.card class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
            <div>
                <x-input-label :value="__('New template name')" />
                <x-text-input class="mt-1 block w-full" x-model="form.name" placeholder="{{ __('Fama Standard') }}" />
                <template x-if="errors.name"><p class="mt-1 text-xs text-danger" x-text="errors.name[0]"></p></template>
            </div>
            <x-ui.button variant="accent" x-on:click="create()" x-bind:disabled="creating">{{ __('Create template') }}</x-ui.button>
        </x-ui.card>

        {{-- List --}}
        <template x-if="loading"><div class="grid gap-3 sm:grid-cols-2"><x-admin.skeleton :rows="2" /><x-admin.skeleton :rows="2" /></div></template>
        <div x-show="!loading" class="grid gap-3 sm:grid-cols-2">
            <template x-for="t in templates" :key="t.id">
                <a :href="window.fama.localizeUrl(`/admin/contract-templates/${t.id}`)" class="block rounded-xl border border-line bg-surface p-5 transition hover:border-line-strong">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-display text-lg text-ink" x-text="t.name"></h3>
                        <div class="flex items-center gap-1.5">
                            <template x-if="t.is_default"><span class="rounded-pill bg-accent-weak px-2 py-0.5 text-xs text-accent-ink">{{ __('default') }}</span></template>
                            <span :class="$pill(t.is_active ? 'active' : 'archived')" x-text="t.is_active ? '{{ __('active') }}' : '{{ __('inactive') }}'"></span>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-muted">
                        <span x-text="t.deposit_pct + '% {{ __('down') }}'"></span> ·
                        <span x-text="(t.clauses?.length || 0) + ' {{ __('clauses') }}'"></span>
                        <span x-show="t.flows_count" class="text-subtle">· <span x-text="t.flows_count"></span> {{ __('flows') }}</span>
                    </p>
                </a>
            </template>
            <template x-if="!templates.length"><p class="col-span-full rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('No templates yet. Create your first contract schema above.') }}</p></template>
        </div>
        <x-admin.pagination />
    </div>
</x-admin-layout>
