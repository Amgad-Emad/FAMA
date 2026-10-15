/** Small shared helpers for the fama-ui profile components. */

/** Resolve a translation map ({en, ar}) to the current locale. */
export const tr = (map) => {
    if (!map || typeof map !== 'object') return map ?? '';
    const locale = (document.documentElement.lang || 'en').slice(0, 2);
    return map[locale] || map.en || Object.values(map)[0] || '';
};

/** Deep copy of plain data (the save bar's snapshots). */
export const clone = (o) => JSON.parse(JSON.stringify(o));

/** Fill `:name` placeholders in a Blade-provided string. */
export const fmt = (s, params = {}) => String(s ?? '').replace(/:(\w+)/g, (m, k) => (k in params ? params[k] : m));

/**
 * Show the shared fama-ui toast (resources/views/components/fama/toast). The
 * toast's `x-show="$store.famaToast.undo"` makes Alpine CALL a function value
 * while evaluating it (with the component scope as `this`), which would run the
 * Undo the moment the toast appears. The guard only lets a direct call — the
 * Undo button's runUndo(), where `this` is undefined — through.
 */
export const toast = (msg, undo = null) => {
    if (!window.famaToast) return;
    const guarded = undo ? function guardedUndo(...args) { return this === undefined ? undo(...args) : true; } : null;
    window.famaToast(msg, guarded);
};

/** Ask the shared confirm dialog (resources/js/confirm.js store). */
export const ask = (opts) => window.Alpine.store('confirm').ask(opts);

