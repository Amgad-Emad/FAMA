<x-admin-layout :title="__('Skills')">
    <div x-data="adminSkills()" class="space-y-6">
        <p class="text-sm text-muted">{{ __('Choose which eligible blocks each skill preselects for NEW talents, and in what order. Eligibility itself is governed by the Block catalog; existing profiles are untouched.') }}</p>

        <template x-if="loading"><div><x-admin.skeleton :rows="4" /></div></template>

        {{-- Types --}}
        <div x-show="!loading" class="space-y-4">
            <template x-for="type in types" :key="type.id">
                <x-ui.card>
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-display text-lg" x-text="type.name"></h3>
                        <span class="rounded-pill bg-elevated px-2 py-0.5 font-mono text-[10px] text-muted" x-text="$categoryLabel(type.category)"></span>
                    </div>

                    {{-- Ordered preselection (drag to reorder, × to remove) --}}
                    <p class="mb-1 text-xs font-medium uppercase tracking-wider text-subtle">{{ __('Preselected (in order)') }}</p>
                    <div class="mb-3 flex flex-wrap gap-2">
                        <template x-for="key in type.default_blocks" :key="key">
                            <span draggable="true" @dragstart="dragKey = key" @dragover.prevent @drop.prevent="dropOn(type, key)"
                                  :class="type.invalid_blocks.includes(key) ? 'border-danger bg-danger/10 text-danger' : 'border-accent bg-accent-weak text-accent-ink'"
                                  class="flex cursor-move items-center gap-1.5 rounded-pill border px-3 py-1.5 text-xs">
                                <span x-text="blockName(type, key)"></span>
                                <template x-if="type.invalid_blocks.includes(key)"><span class="font-medium">({{ __('no longer eligible') }})</span></template>
                                <button type="button" @click="removeBlock(type, key)" class="ms-0.5 opacity-70 hover:opacity-100" aria-label="{{ __('Remove') }}">×</button>
                            </span>
                        </template>
                        <template x-if="!type.default_blocks.length"><span class="text-xs text-muted">{{ __('No blocks preselected.') }}</span></template>
                    </div>

                    {{-- Eligible, not yet preselected --}}
                    <p class="mb-1 text-xs font-medium uppercase tracking-wider text-subtle">{{ __('Eligible blocks') }}</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="block in type.eligible_blocks.filter(b => !type.default_blocks.includes(b.key))" :key="block.key">
                            <button type="button" @click="addBlock(type, block.key)"
                                    class="rounded-pill border border-line px-3 py-1.5 text-xs text-muted hover:text-ink" x-text="block.name"></button>
                        </template>
                        <template x-if="type.eligible_blocks.length === type.default_blocks.filter(k => !type.invalid_blocks.includes(k)).length">
                            <span class="text-xs text-muted">{{ __('All eligible blocks are preselected.') }}</span>
                        </template>
                    </div>

                    <x-ui.button size="sm" variant="primary" class="mt-4" x-on:click="saveBlocks(type)">{{ __('Save blocks') }}</x-ui.button>
                </x-ui.card>
            </template>
        </div>

        {{-- Add skill --}}
        <x-ui.card>
            <h3 class="mb-3 font-display text-lg">{{ __('Add skill') }}</h3>
            <div class="grid gap-3 sm:grid-cols-3">
                <div><x-input-label :value="__('Name (EN)')" /><x-text-input class="mt-1 block w-full" x-model="newType.name.en" /></div>
                <div><x-input-label :value="__('Name (AR)')" /><x-text-input class="mt-1 block w-full" dir="rtl" x-model="newType.name.ar" /></div>
                <div>
                    <x-input-label :value="__('Category')" />
                    <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="newType.category">
                        @foreach (['model','crew','creative'] as $c)<option value="{{ $c }}">{{ __(ucfirst($c)) }}</option>@endforeach
                    </select>
                </div>
            </div>
            <template x-if="errors['name.en']"><p class="mt-1 text-xs text-danger" x-text="errors['name.en'][0]"></p></template>
            <x-ui.button variant="accent" class="mt-4" x-on:click="addType()">{{ __('Add skill') }}</x-ui.button>
        </x-ui.card>
    </div>
</x-admin-layout>
