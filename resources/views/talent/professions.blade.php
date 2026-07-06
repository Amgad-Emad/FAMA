<x-talent-layout :title="__('Professions')">
    <div x-data="professionsManager({ linked: @js($linked), available: @js($available) })" class="space-y-8">

        <x-ui.section :title="__('Your professions')" :eyebrow="__('Drag to reorder · star to set primary')">
            <div class="space-y-2">
                <template x-for="type in linked" :key="type.id">
                    <div draggable="true"
                         @dragstart="dragId = type.id" @dragover.prevent @drop.prevent="onDrop(type)"
                         class="flex items-center gap-3 rounded-lg border border-line bg-surface p-3">
                        <span class="cursor-grab text-subtle">⋮⋮</span>
                        <div class="flex-1">
                            <span class="font-medium text-ink" x-text="t(type.name)"></span>
                            <span class="ms-2 font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="type.category"></span>
                        </div>
                        <button @click="makePrimary(type)" class="text-lg"
                                :class="type.is_primary ? 'text-gold' : 'text-subtle hover:text-gold'"
                                :title="type.is_primary ? '{{ __('Primary') }}' : '{{ __('Make primary') }}'"
                                x-text="type.is_primary ? '★' : '☆'"></button>
                        <button @click="remove(type)" class="text-subtle hover:text-danger" title="{{ __('Remove') }}">✕</button>
                    </div>
                </template>
                <p x-show="linked.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-6 text-center text-sm text-subtle">{{ __('Add your first profession below.') }}</p>
            </div>
        </x-ui.section>

        <x-ui.section :title="__('Add a profession')">
            <x-ui.card class="flex flex-wrap items-end gap-3">
                <div class="flex-1">
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Profession') }}</label>
                    <select x-model="addId" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                        <option value="">{{ __('Choose…') }}</option>
                        <template x-for="type in available" :key="type.id">
                            <option :value="type.id" x-text="t(type.name)"></option>
                        </template>
                    </select>
                </div>
                <x-ui.button variant="accent" x-on:click="add()" x-bind:disabled="!addId">{{ __('Add profession') }}</x-ui.button>
            </x-ui.card>
        </x-ui.section>
    </div>
</x-talent-layout>
