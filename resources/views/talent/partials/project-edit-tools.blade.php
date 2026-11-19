{{--
    A block's tools in the editor's margin (fama-ui project-editor.js `tools()`, ADR-AN): what the block is,
    a grip to drag it (or the arrows), and a "More" menu (add below · turn into · duplicate · delete).
    Rendered inside the blocks loop of talent/project-edit — scope: famaProjectEdit, with `b` and `i`.
--}}
<div class="pe-tools" role="toolbar" :aria-label="kindOf(b) + ' ' + @js(__('block'))">
    <span class="pe-kind" x-text="kindOf(b)"></span>
    <button type="button" class="pe-grip" data-grip tabindex="-1" @pointerdown="onGrip($event, i)" aria-label="{{ __('Drag to move (or use the arrows)') }}"><svg class="pe-i" aria-hidden="true"><use href="#e-grip"/></svg></button>
    <button type="button" data-act="up" @click="moveBlock(i, i - 1)" :disabled="i === 0" aria-label="{{ __('Move up') }}"><svg class="pe-i" aria-hidden="true"><use href="#e-up"/></svg></button>
    <button type="button" data-act="down" @click="moveBlock(i, i + 1)" :disabled="i === blocks.length - 1" aria-label="{{ __('Move down') }}"><svg class="pe-i" aria-hidden="true"><use href="#e-down"/></svg></button>
    <button type="button" data-act="more" @click.stop="openMoreMenu($el, i)" aria-label="{{ __('More') }}" aria-haspopup="menu"><svg class="pe-i" aria-hidden="true"><use href="#e-more"/></svg></button>
</div>
