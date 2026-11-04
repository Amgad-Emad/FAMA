{{--
    A block's tools in the editor's margin (fama-ui project-editor.js `tools()`): what the block is, and a
    "More" menu. A Fama project's blocks sit in the page's fixed order, so there is no grip or up/down —
    More only takes the block off the page (with Undo). Param: $block (gallery|story|results|credits).
    Scope: famaProjectEdit.
--}}
<div class="pe-tools" role="toolbar" :aria-label="strings.kinds.{{ $block }}">
    <span class="pe-kind" x-text="strings.kinds.{{ $block }}"></span>
    <button type="button" data-act="more" @click.stop="openMoreMenu($el, '{{ $block }}')" aria-label="{{ __('More') }}" aria-haspopup="menu"><svg class="pe-i" aria-hidden="true"><use href="#e-more"/></svg></button>
</div>
