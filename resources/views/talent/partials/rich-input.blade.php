{{-- A lightweight WYSIWYG bound to one Alpine model path ($model, e.g.
     'form.summary.en'), included with ['model' => …, 'minHeight' => …]. Toolbar keeps focus (@mousedown.prevent); the editor
     two-way binds — input pushes to the model, x-effect restores it when the
     model changes from elsewhere (opening a different project, resetting the
     form). HTML is sanitized server-side with the shared allowlist. --}}
<div x-data="richField()" class="overflow-hidden rounded-md border border-line bg-elevated focus-within:border-accent focus-within:ring-1 focus-within:ring-accent">
    <div class="flex items-center gap-0.5 border-b border-line px-1.5 py-1">
        <button type="button" @mousedown.prevent="exec('bold')" title="{{ __('Bold') }}" aria-label="{{ __('Bold') }}"
                class="grid h-7 w-7 place-items-center rounded font-bold text-muted transition hover:bg-surface hover:text-ink">B</button>
        <button type="button" @mousedown.prevent="exec('italic')" title="{{ __('Italic') }}" aria-label="{{ __('Italic') }}"
                class="grid h-7 w-7 place-items-center rounded italic text-muted transition hover:bg-surface hover:text-ink">I</button>
        <button type="button" @mousedown.prevent="exec('insertUnorderedList')" title="{{ __('Bullet list') }}" aria-label="{{ __('Bullet list') }}"
                class="grid h-7 w-7 place-items-center rounded text-muted transition hover:bg-surface hover:text-ink">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
        </button>
        <button type="button" @mousedown.prevent="addLink()" title="{{ __('Link') }}" aria-label="{{ __('Link') }}"
                class="grid h-7 w-7 place-items-center rounded text-muted transition hover:bg-surface hover:text-ink">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
        </button>
    </div>
    <div x-ref="ed" contenteditable="true" role="textbox" aria-multiline="true"
         style="min-height: {{ $minHeight ?? '92px' }};"
         x-init="$refs.ed.innerHTML = {{ $model }} || ''"
         @input="{{ $model }} = $refs.ed.innerHTML; touch()"
         x-effect="(function (v) { if (document.activeElement !== $refs.ed && v !== $refs.ed.innerHTML) $refs.ed.innerHTML = v; })({{ $model }} || '')"
         class="w-full px-3 py-2 text-sm leading-relaxed text-ink focus:outline-none [&_a]:text-accent [&_a]:underline [&_ul]:list-disc [&_ul]:ps-5"></div>
</div>
