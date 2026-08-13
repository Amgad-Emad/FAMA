{{-- One block row (Alpine context: `block` from the enclosing x-for). Used by
     the unified Blocks card for universal rows and for the rows inside an
     expanded skill section. --}}
<div draggable="true"
     @dragstart="onRowDragStart($event, 'block', 'b' + block.id)"
     @dragover.prevent="onRowDragOver($event, 'b' + block.id)"
     @drop.prevent="onRowDrop('block', block)"
     @dragend="onRowDragEnd()"
     class="flex select-none flex-wrap items-center gap-3 rounded-lg border border-line bg-elevated p-3 transition-all duration-150"
     :class="[
         block.is_visible ? '' : 'opacity-60',
         dragging?.key === 'b' + block.id ? 'scale-[.99] opacity-40' : '',
         dragOverKey === 'b' + block.id ? 'border-transparent ring-2 ring-accent' : '',
         denyKey === 'b' + block.id ? 'border-transparent ring-2 ring-danger' : '',
     ]">
    <span class="cursor-grab text-subtle" title="{{ __('Drag') }}">⋮⋮</span>
    <div class="min-w-0 flex-1">
        <div class="font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="block.block_type.key"></div>
        {{-- Block names are admin-governed (block catalog) — read-only here. --}}
        <div class="truncate text-ink" x-text="t(block.title) || t(block.block_type.name)"></div>
    </div>
    {{-- Only blocks with an editor on the profile-content page link there
         (comp_card is table-backed but has no editor). --}}
    <template x-if="hasContentEditor(block)">
        <a :href="contentUrl(block)" class="rounded-pill border border-line-strong px-3 py-1 text-xs text-muted hover:text-ink">{{ __('Edit content') }}</a>
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
    {{-- V1: blocks are the seeded set — no add/remove; hide instead. --}}
    <button @click="toggleVisible(block)" class="rounded-pill px-3 py-1 text-xs font-medium"
            :class="block.is_visible ? 'bg-success-weak text-success' : 'bg-surface text-muted border border-line'">
        <span x-text="block.is_visible ? '{{ __('Visible') }}' : '{{ __('Hidden') }}'"></span>
    </button>
</div>
