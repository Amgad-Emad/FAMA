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

                    {{--
                        The controlled speciality vocabulary (ADR-Y). Talents pick
                        from this list and can never type into it, so every entry
                        is born here. Retiring an entry hides it from every picker
                        while the talents and projects already claiming it keep
                        rendering — it is deliberately not a delete.
                    --}}
                    <div class="mt-6 border-t border-line pt-4">
                        <p class="mb-1 text-xs font-medium uppercase tracking-wider text-subtle">{{ __('Specialities') }}</p>
                        <p class="mb-3 text-xs text-muted">{{ __('Four to six is the target (seven where the range genuinely justifies it). Every entry answers one question: what kind of subject.') }}</p>

                        <div class="mb-3 flex flex-wrap gap-2">
                            <template x-for="speciality in type.specialities" :key="speciality.id">
                                <span class="flex items-center gap-1.5 rounded-pill border px-3 py-1.5 text-xs"
                                      :class="speciality.is_active
                                          ? (speciality.is_regional ? 'border-accent bg-accent-weak text-accent-ink' : 'border-line text-ink')
                                          : 'border-dashed border-line text-subtle line-through'">
                                    <span x-text="speciality.name.en || speciality.slug"></span>
                                    <template x-if="speciality.is_regional">
                                        <span class="font-mono text-[9px] uppercase tracking-wider">{{ __('Regional') }}</span>
                                    </template>
                                    <button type="button" @click="toggleSpeciality(speciality)"
                                            class="ms-0.5 opacity-70 hover:opacity-100"
                                            :aria-label="speciality.is_active ? '{{ __('Retire') }}' : '{{ __('Reinstate') }}'"
                                            x-text="speciality.is_active ? '×' : '↺'"></button>
                                </span>
                            </template>
                            <template x-if="!type.specialities.length">
                                <span class="text-xs text-muted">{{ __('No specialities yet.') }}</span>
                            </template>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <x-text-input class="w-56" x-model="specialityDraft(type.id).name"
                                          @keydown.enter.prevent="addSpeciality(type)"
                                          placeholder="{{ __('Add a speciality…') }}" />
                            <label class="flex items-center gap-1.5 text-xs text-muted">
                                <input type="checkbox" class="rounded border-line" x-model="specialityDraft(type.id).is_regional">
                                {{ __('Regional') }}
                            </label>
                            <x-ui.button size="sm" x-on:click="addSpeciality(type)">{{ __('Add') }}</x-ui.button>
                        </div>
                    </div>
                </x-ui.card>
            </template>
        </div>

        {{-- Add skill --}}
        <x-ui.card>
            <h3 class="mb-3 font-display text-lg">{{ __('Add skill') }}</h3>
            <div class="grid gap-3 sm:grid-cols-3">
                <div><x-input-label :value="__('Name')" /><x-text-input class="mt-1 block w-full" x-model="newType.name.en" /></div>
                {{-- Arabic input hidden while the app is English-only (restore for the AR pass).
                <div><x-input-label :value="__('Name (AR)')" /><x-text-input class="mt-1 block w-full" dir="rtl" x-model="newType.name.ar" /></div>
                --}}
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
