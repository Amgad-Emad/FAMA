{{-- One block-scope card (Alpine context: `group` from the enclosing x-for).
     Used for the universal/profile-level section and for the currently
     selected skill section in the Profile editor's Blocks area. --}}
<x-ui.card class="space-y-3">
    <div class="flex items-center gap-2">
        <span class="font-display text-lg text-ink" x-text="group.label"></span>
        <span x-show="group.is_primary" x-cloak class="rounded-pill bg-gold-weak px-2 py-0.5 text-[10px] font-medium text-gold">{{ __('Primary') }}</span>
        <span x-show="group.typeId === null" x-cloak class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Above the tabs') }}</span>
    </div>

    {{-- Blocks in this scope --}}
    <div class="space-y-2">
        <template x-for="block in blocksInScope(group.typeId)" :key="block.id">
            <div draggable="true"
                 @dragstart="dragId = block.id"
                 @dragover.prevent
                 @drop.prevent="onDrop(block)"
                 class="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-elevated p-3"
                 :class="block.is_visible ? '' : 'opacity-60'">
                <span class="cursor-grab text-subtle" title="{{ __('Drag') }}">⋮⋮</span>
                <div class="min-w-0 flex-1">
                    <div class="font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="block.block_type.key"></div>
                    <input type="text" :value="t(block.title) || t(block.block_type.name)"
                           @change="block.title = { ...(block.title||{}), en: $event.target.value }; saveTitle(block)"
                           class="w-full border-0 bg-transparent p-0 text-ink focus:ring-0">
                </div>
                <template x-if="block.block_type.content_source === 'table'">
                    <a :href="'/talent/content/' + block.block_type.key" class="rounded-pill border border-line-strong px-3 py-1 text-xs text-muted hover:text-ink">{{ __('Edit content') }}</a>
                </template>
                {{-- Move between scopes (only shows eligible targets) --}}
                <template x-if="moveTargets(block).length">
                    <select @change="moveBlock(block, $event.target.value); $event.target.value=''"
                            class="rounded-md border-line bg-surface py-1 text-xs text-muted focus:border-accent focus:ring-accent">
                        <option value="">{{ __('Move to…') }}</option>
                        <template x-for="g in moveTargets(block)" :key="g.key">
                            <option :value="g.typeId === null ? '' : g.typeId" x-text="g.label"></option>
                        </template>
                    </select>
                </template>
                <button @click="toggleVisible(block)" class="rounded-pill px-3 py-1 text-xs font-medium"
                        :class="block.is_visible ? 'bg-success-weak text-success' : 'bg-surface text-muted border border-line'">
                    <span x-text="block.is_visible ? '{{ __('Visible') }}' : '{{ __('Hidden') }}'"></span>
                </button>
                <button @click="$confirm({ title: '{{ __('Remove this block?') }}', message: '{{ __('The block and its content will be removed from your profile.') }}', confirmLabel: '{{ __('Remove') }}' }).then(ok => ok && removeBlock(block))" class="text-subtle hover:text-danger" title="{{ __('Remove') }}">✕</button>
            </div>
        </template>
        <p x-show="blocksInScope(group.typeId).length === 0" x-cloak class="rounded-lg border border-dashed border-line p-4 text-center text-xs text-subtle">{{ __('No blocks here yet.') }}</p>
    </div>

    {{-- Add a block into this scope --}}
    <div class="flex flex-wrap items-end gap-2 border-t border-line pt-3">
        <select x-model="newBlock[group.key]" class="flex-1 rounded-md border-line bg-elevated text-sm text-ink shadow-sm focus:border-accent focus:ring-accent">
            <option value="">{{ __('Add a block…') }}</option>
            <template x-for="bt in pickerForScope(group)" :key="bt.id">
                <option :value="bt.id" x-text="t(bt.name)"></option>
            </template>
        </select>
        <x-ui.button variant="accent" size="sm" x-on:click="addBlock(group)" x-bind:disabled="!newBlock[group.key]">{{ __('Add') }}</x-ui.button>
    </div>
</x-ui.card>
