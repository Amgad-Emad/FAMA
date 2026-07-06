<x-talent-layout :title="__('Affiliations & press')">
    {{-- Agency affiliations --}}
    <div x-data="crudList({ endpoint: '/talent/affiliations', blank: { agency_name: '', agency_url: '', representation_type: 'freelance', region: '' } })" class="space-y-6">
        <x-ui.section :title="__('Agency affiliations')" :eyebrow="__('Representation')">
            <x-ui.card class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Agency name') }}</label>
                    <input x-model="form.agency_name" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    <p x-show="errors.agency_name" x-cloak class="mt-1 text-xs text-danger" x-text="errors.agency_name?.[0]"></p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Type') }}</label>
                    <select x-model="form.representation_type" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                        <option value="exclusive">{{ __('Exclusive') }}</option>
                        <option value="non_exclusive">{{ __('Non-exclusive') }}</option>
                        <option value="mother_agency">{{ __('Mother agency') }}</option>
                        <option value="freelance">{{ __('Freelance') }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Region') }}</label>
                    <input x-model="form.region" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Agency URL') }}</label>
                    <input x-model="form.agency_url" type="url" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                </div>
                <div class="sm:col-span-2">
                    <x-ui.button variant="accent" x-on:click="create()" x-bind:disabled="saving">{{ __('Add affiliation') }}</x-ui.button>
                </div>
            </x-ui.card>

            <div class="mt-3 space-y-2" x-show="!loading">
                <template x-for="a in items" :key="a.id">
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-line bg-surface p-3">
                        <div>
                            <span class="font-medium text-ink" x-text="a.agency_name"></span>
                            <span class="ms-2 font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="a.representation_type + (a.region ? ' · ' + a.region : '')"></span>
                            <span x-show="!a.is_current" class="ms-2 rounded-pill bg-surface px-2 py-0.5 text-[10px] text-muted">{{ __('Past') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button x-show="a.is_current" @click="act(a.id, 'end')" class="rounded-pill border border-line px-3 py-1 text-xs text-muted hover:text-ink">{{ __('Mark past') }}</button>
                            <button @click="remove(a.id)" class="text-subtle hover:text-danger">✕</button>
                        </div>
                    </div>
                </template>
                <p x-show="items.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-6 text-center text-sm text-subtle">{{ __('No affiliations yet.') }}</p>
            </div>
        </x-ui.section>
    </div>

    {{-- Press --}}
    <div x-data="crudList({ endpoint: '/talent/press', dataUrl: '/talent/press/data', blank: { publication: '', title: '', url: '', published_date: '' } })" class="mt-10 space-y-6">
        <x-ui.section :title="__('Press features')" :eyebrow="__('Media mentions')">
            <x-ui.card class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Publication') }}</label>
                    <input x-model="form.publication" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    <p x-show="errors.publication" x-cloak class="mt-1 text-xs text-danger" x-text="errors.publication?.[0]"></p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Title') }}</label>
                    <input x-model="form.title" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    <p x-show="errors.title" x-cloak class="mt-1 text-xs text-danger" x-text="errors.title?.[0]"></p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('URL') }}</label>
                    <input x-model="form.url" type="url" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Published date') }}</label>
                    <input x-model="form.published_date" type="date" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                </div>
                <div class="sm:col-span-2">
                    <x-ui.button variant="accent" x-on:click="create()" x-bind:disabled="saving">{{ __('Add press feature') }}</x-ui.button>
                </div>
            </x-ui.card>

            <div class="mt-3 space-y-2" x-show="!loading">
                <template x-for="p in items" :key="p.id">
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-line bg-surface p-3">
                        <div>
                            <span class="font-medium text-ink" x-text="p.title"></span>
                            <span class="ms-2 font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="p.publication"></span>
                        </div>
                        <button @click="remove(p.id)" class="text-subtle hover:text-danger">✕</button>
                    </div>
                </template>
                <p x-show="items.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-6 text-center text-sm text-subtle">{{ __('No press yet.') }}</p>
            </div>
        </x-ui.section>
    </div>
</x-talent-layout>
