/**
 * Global confirmation dialog — a promise-based `$confirm` for every destructive
 * action (delete / remove / cancel). A single Alpine store drives one dialog
 * (rendered once per dashboard layout via <x-confirm-dialog>); `$confirm(opts)`
 * opens it and resolves true (confirmed) or false (dismissed).
 *
 *   @click="$confirm({ title, message, confirmLabel, tone })
 *            .then(ok => ok && remove(item))"
 *
 * Copy stays in Blade (so __() localizes it); this layer is behaviour only.
 */
document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.store('confirm', {
        open: false,      // mounts the overlay
        visible: false,   // drives the enter/leave transition
        title: '',
        message: '',
        confirmLabel: '',
        cancelLabel: '',
        tone: 'danger',   // 'danger' | 'accent'
        _resolve: null,
        _timer: null,

        /**
         * Open the dialog and return a promise resolving to the user's choice.
         */
        ask(opts = {}) {
            clearTimeout(this._timer);
            // A previous still-open request resolves false (superseded).
            if (this._resolve) { this._resolve(false); this._resolve = null; }

            this.title = opts.title || '';
            this.message = opts.message || '';
            this.confirmLabel = opts.confirmLabel || '';
            this.cancelLabel = opts.cancelLabel || '';
            this.tone = opts.tone || 'danger';
            this.open = true;
            // Paint the closed state, then flip to trigger the CSS transition.
            requestAnimationFrame(() => requestAnimationFrame(() => { this.visible = true; }));

            return new Promise((resolve) => { this._resolve = resolve; });
        },

        _settle(result) {
            this.visible = false;
            const resolve = this._resolve;
            this._resolve = null;
            // Unmount only after the leave transition (matches duration-200).
            this._timer = setTimeout(() => { this.open = false; }, 200);
            if (resolve) resolve(result);
        },

        agree() { this._settle(true); },
        cancel() { this._settle(false); },
    });

    // Sugar so markup reads `$confirm({...}).then(...)`.
    Alpine.magic('confirm', () => (opts) => Alpine.store('confirm').ask(opts));
});
