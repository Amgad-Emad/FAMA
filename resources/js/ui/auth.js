/**
 * fama-ui auth page components (Alpine) — the live port of the prototype's
 * assets/js/auth.js (showcase is server-rendered Blade; this file carries the
 * behaviour). Registered on alpine:init.
 *
 *   famaAuthForm — inline field errors: seeded from the server's validation
 *                  errors, re-checked on submit with the prototype's copy so an
 *                  obviously empty/invalid field never makes a round trip. The
 *                  server stays the authority (it re-validates everything).
 *   famaPw       — one password field: show/hide toggle + the 0–4 strength meter.
 */

/**
 * Score a password 0–4 exactly like the prototype: length ≥ 8, mixed case, a
 * number, a symbol; any non-empty value scores at least 1.
 */
export function passwordStrength(v) {
    let s = 0;
    if (v.length >= 8) s++;
    if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
    if (/\d/.test(v)) s++;
    if (/[^\w\s]/.test(v)) s++;
    return v ? Math.max(s, 1) : 0;
}

document.addEventListener('alpine:init', () => {
    /**
     * Form-level error state.
     *
     * opts.errors — {field: message} from the server (first message per field).
     * opts.checks — ordered [{field, rule, message, other?}] where rule is
     *               required | email | min8 | match (match compares to `other`).
     * opts.type   — register's picked account type (drives the name label).
     */
    window.Alpine.data('famaAuthForm', (opts = {}) => ({
        errors: { ...(opts.errors || {}) },
        checks: opts.checks || [],
        type: opts.type || 'talent',

        /**
         * Validate the configured checks against the live form values; on the
         * first failure focus that field and cancel the submit. Otherwise the
         * native POST proceeds (no Ajax — Breeze's redirects/flash drive state).
         */
        submit(event) {
            const form = event.target;
            const value = (name) => (form.elements[name] ? String(form.elements[name].value) : '');
            const errors = {};
            this.checks.forEach((c) => {
                if (errors[c.field]) return;
                const v = value(c.field);
                let bad = false;
                if (c.rule === 'required') bad = v.trim() === '';
                if (c.rule === 'email') bad = !/^[^@\s]+@[^@\s]+$/.test(v.trim());
                if (c.rule === 'min8') bad = v.length < 8;
                if (c.rule === 'match') bad = v === '' || v !== value(c.other);
                if (bad) errors[c.field] = c.message;
            });
            this.errors = errors;
            const first = this.checks.find((c) => errors[c.field]);
            if (first) {
                event.preventDefault();
                form.elements[first.field]?.focus();
            }
        },
    }));

    /**
     * One password input: `show` flips the input type (the static type="password"
     * stays the no-JS default) and `strength` feeds the .meter's data-s.
     */
    window.Alpine.data('famaPw', () => ({
        show: false,
        strength: 0,

        /** Toggle between the masked and visible password. */
        toggle() {
            this.show = !this.show;
        },

        /** Recompute the strength meter from the field's current value. */
        measure(event) {
            this.strength = passwordStrength(event.target.value);
        },
    }));
});
