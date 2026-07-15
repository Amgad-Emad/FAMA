{{--
    Apply-to-project modal — bound to the applyModal() Alpine scope on the wrapping
    element. Teleported to <body>; enter/leave animate via :class + CSS (not
    x-transition, which never completes on a teleported node).
--}}
<template x-teleport="body">
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-[70] flex items-end justify-center transition-opacity duration-200 ease-out motion-reduce:transition-none sm:items-center sm:p-6"
         :class="modalActive ? 'opacity-100' : 'opacity-0 pointer-events-none'"
         role="dialog" aria-modal="true" aria-labelledby="apply-title" @keydown.escape.window="close()">
        <div @click="close()" class="absolute inset-0" style="background: var(--scrim);"></div>

        <div x-ref="dialog" tabindex="-1" @keydown="trapFocus($event)"
             class="relative flex max-h-[92vh] w-full flex-col rounded-t-2xl border border-line bg-surface shadow-e4 outline-none transition duration-200 ease-out motion-reduce:transition-none sm:max-h-[88vh] sm:max-w-2xl sm:rounded-2xl"
             :class="modalActive ? 'translate-y-0 opacity-100 sm:scale-100' : 'translate-y-6 opacity-0 sm:translate-y-2 sm:scale-95'">
            {{-- Header --}}
            <div class="flex items-start justify-between gap-4 border-b border-line px-6 py-5">
                <div class="min-w-0">
                    <h2 id="apply-title" class="font-display text-xl leading-tight text-ink">{{ __('Apply to :title', ['title' => $project->title]) }}</h2>
                    <p class="mt-0.5 text-sm text-muted">{{ __('Make your case to :brand.', ['brand' => $brand->name]) }}</p>
                </div>
                <button type="button" @click="close()" aria-label="{{ __('Close') }}" class="grid h-9 w-9 shrink-0 place-items-center rounded-pill text-lg text-subtle transition hover:bg-elevated hover:text-ink">✕</button>
            </div>

            {{-- Body --}}
            <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                <p x-show="error" x-cloak class="rounded-md border border-danger/30 bg-danger-weak px-3 py-2 text-xs text-danger" x-text="error"></p>

                {{-- Rich-text brief --}}
                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <span class="text-xs font-medium text-muted">{{ __('Your brief') }}</span>
                        <span class="text-[11px] text-subtle">{{ __('Type @ to mention your work') }}</span>
                    </div>
                    <div class="flex items-center gap-1 rounded-t-md border border-b-0 border-line bg-elevated px-2 py-1.5">
                        <button type="button" @click="format('bold')" class="grid h-7 w-7 place-items-center rounded text-sm font-bold text-muted transition hover:bg-surface hover:text-ink" aria-label="{{ __('Bold') }}">B</button>
                        <button type="button" @click="format('italic')" class="grid h-7 w-7 place-items-center rounded font-serif text-sm italic text-muted transition hover:bg-surface hover:text-ink" aria-label="{{ __('Italic') }}">I</button>
                        <button type="button" @click="format('insertUnorderedList')" class="grid h-7 w-7 place-items-center rounded text-muted transition hover:bg-surface hover:text-ink" aria-label="{{ __('Bullet list') }}">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                        </button>
                    </div>
                    <div x-ref="editorWrap" class="relative">
                        <div x-ref="editor" contenteditable="true" role="textbox" aria-multiline="true"
                             @input="onInput()" @keydown="onKeydown($event)" @click="closeMentions()"
                             class="brief min-h-[9rem] max-h-[16rem] overflow-y-auto rounded-b-md border border-line bg-bg px-3 py-2.5 text-sm text-ink focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"></div>
                        <div x-show="isEmpty" x-cloak class="pointer-events-none absolute inset-x-3 top-2.5 text-sm text-subtle">{{ __('Why are you the right fit for this project?') }}</div>

                        {{-- @-mention dropdown (caret-anchored) --}}
                        <div x-show="mentionOpen" x-cloak
                             class="absolute z-10 max-h-48 w-64 overflow-y-auto rounded-lg border border-line bg-surface py-1 shadow-e3"
                             :style="`top:${mentionTop}px; inset-inline-start:${mentionLeft}px`">
                            <template x-for="(item, i) in mentionItems" :key="item.id">
                                <button type="button" @mousedown.prevent="pickMention(item)"
                                        class="flex w-full items-center gap-2 px-3 py-1.5 text-start text-sm"
                                        :class="i === mentionIndex ? 'bg-accent-weak text-accent-ink' : 'text-ink hover:bg-elevated'">
                                    <span class="font-medium text-accent-ink">@</span><span class="truncate" x-text="item.title"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Attachments --}}
                <div>
                    <div class="mb-1.5 text-xs font-medium text-muted">{{ __('Attachments') }} <span class="text-subtle">{{ __('(optional)') }}</span></div>
                    <div class="flex flex-col gap-2">
                        <template x-for="(file, i) in files" :key="i">
                            <div class="flex items-center justify-between gap-2 rounded-md border border-line bg-elevated px-3 py-2 text-xs">
                                <span class="min-w-0 truncate text-ink" x-text="file.name"></span>
                                <span class="flex shrink-0 items-center gap-2">
                                    <span class="text-subtle" x-text="fileSize(file)"></span>
                                    <button type="button" @click="removeFile(i)" class="text-danger" aria-label="{{ __('Remove') }}">✕</button>
                                </span>
                            </div>
                        </template>
                        <label x-show="files.length < 5" class="inline-flex w-fit cursor-pointer items-center gap-1.5 rounded-pill border border-dashed border-line-strong px-3 py-1.5 text-xs font-medium text-muted transition hover:border-accent hover:text-ink">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                            {{ __('Add files') }}
                            <input type="file" multiple class="hidden" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.zip" @change="onFiles($event)">
                        </label>
                        <p class="text-[11px] text-subtle">{{ __('Up to 5 files, 10MB each.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-2 border-t border-line px-6 py-4">
                <button type="button" @click="close()" class="rounded-pill border border-line px-4 py-2 text-sm text-muted transition hover:text-ink">{{ __('Cancel') }}</button>
                <button type="button" @click="submit()" :disabled="submitting" class="rounded-pill bg-accent px-5 py-2 text-sm font-semibold text-on-accent transition hover:opacity-90 disabled:opacity-50">
                    <span x-show="!submitting">{{ __('Send application') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('Sending…') }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
