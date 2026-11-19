/**
 * Small helpers shared by the projects area (projects.js) and the project
 * editor (project-editor.js): placeholder strings, initials, the fama-ui toast,
 * the first validation message, and the shared confirm dialog.
 */

/** Replace :name placeholders in a translated string. */
export function fmt(str, params = {}) {
    return Object.entries(params).reduce(
        (out, [key, value]) => out.replaceAll(`:${key}`, value ?? ''),
        String(str ?? ''),
    );
}

/** Up to two initials for an avatar fallback. */
export function initials(name) {
    return String(name || '')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0].toUpperCase())
        .join('');
}

/** Show the fama-ui toast (optional Undo callback). */
export function toast(message, undo = null) {
    if (message && window.famaToast) window.famaToast(message, undo);
}

/** The first validation message of an ApiError (or its message). */
export function firstError(e, fallback = '') {
    if (e?.errors) {
        const first = Object.values(e.errors)[0];
        if (Array.isArray(first) && first.length) return first[0];
    }
    return e?.message || fallback;
}

/** Ask the shared confirm dialog; resolves true/false. */
export function ask(opts) {
    return window.Alpine.store('confirm').ask(opts);
}
