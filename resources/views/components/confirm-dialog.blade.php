{{--
    Global confirmation dialog, bound to the `confirm` Alpine store (see
    resources/js/confirm.js). Rendered ONCE per dashboard layout. Teleported to
    <body> so no transformed/overflow ancestor can clip it. Token-only, animated
    (backdrop fade + panel scale), RTL-aware, dark/light. Accessibility:
    role=dialog + aria-modal, ESC/backdrop cancel, focus moves to the (safer)
    Cancel button on open and is restored on close, a minimal Tab loop keeps
    focus inside, and Enter on the confirm button proceeds.

    Copy is passed by each caller via $confirm({...}); the strings below are the
    localized fallbacks when a caller omits them.
--}}
<div x-data="{
        get s() { return $store.confirm },
        restoreFocus: null,
        fallback: {
            title: @js(__('Are you sure?')),
            message: @js(__('This action cannot be undone.')),
            confirm: @js(__('Confirm')),
            cancel: @js(__('Cancel')),
        },
        onKeydown(e) {
            if (!this.s.visible) return;
            if (e.key !== 'Tab') return;
            const focusables = [...$refs.panel.querySelectorAll('button')];
            if (!focusables.length) return;
            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        },
     }"
     x-effect="
        if (s.visible) { restoreFocus = document.activeElement; $nextTick(() => $refs.cancelBtn?.focus()); }
        else if (restoreFocus) { restoreFocus.focus?.(); restoreFocus = null; }
     ">
    <template x-teleport="body">
        <div x-show="s.open" x-cloak
             class="fixed inset-0 z-[60]" :class="s.visible ? '' : 'pointer-events-none'"
             @keydown.escape.window="s.open && s.cancel()"
             @keydown="onKeydown($event)">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px] transition-opacity duration-200 ease-out motion-reduce:transition-none"
                 :class="s.visible ? 'opacity-100' : 'opacity-0'"
                 @click="s.cancel()"></div>

            {{-- Panel --}}
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div x-ref="panel" role="dialog" aria-modal="true"
                     :aria-label="s.title || fallback.title"
                     @click.stop
                     class="w-full max-w-sm transform rounded-2xl border border-line bg-surface p-6 shadow-e2 transition duration-200 ease-out will-change-transform motion-reduce:transition-none"
                     :class="s.visible ? 'opacity-100 scale-100' : 'opacity-0 scale-95'">
                    <div class="flex items-start gap-4">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-pill"
                              :class="s.tone === 'accent' ? 'bg-accent-weak text-accent-ink' : 'bg-danger-weak text-danger'">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                            </svg>
                        </span>
                        <div class="min-w-0 pt-0.5">
                            <h2 class="font-display text-lg text-ink" x-text="s.title || fallback.title"></h2>
                            <p class="mt-1 text-sm text-muted" x-text="s.message || fallback.message"></p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" x-ref="cancelBtn" @click="s.cancel()"
                                class="rounded-pill border border-line-strong px-4 py-2 text-sm font-medium text-ink transition hover:bg-elevated focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                                x-text="s.cancelLabel || fallback.cancel"></button>
                        <button type="button" @click="s.agree()"
                                class="rounded-pill px-4 py-2 text-sm font-medium text-white transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                                :class="s.tone === 'accent' ? 'bg-accent focus-visible:ring-accent' : 'bg-danger focus-visible:ring-danger'"
                                x-text="s.confirmLabel || fallback.confirm"></button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
