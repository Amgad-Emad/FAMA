/**
 * The profile path on Profile — a SLIM BAR since ADR-AR (2026-11-25). The big
 * two-part Go Live / Stand out card was replaced by the guided onboarding
 * (/talent/welcome); this bar is the way back into it: where the profile
 * stands, a portfolio meter (n of 10), and ONE next action that opens the
 * onboarding on the screen holding that step. While Private with Go Live done
 * (ADR-AQ) it leads with the Go Live button. Hidden once every step is done.
 *
 * opts.done(key) answers from SAVED data — never the draft. All copy comes from
 * Blade (opts.S) so it is translatable; labels are escaped here.
 *
 * @param {HTMLElement} el the #gate section
 * @param {object} opts { req, rec, indexKey, S, done, stage, goLive, url, screenOf }
 *   req/rec  — [{key,label}] Go Live / Stand out steps, in order
 *   url      — the onboarding base URL (a screen key is appended)
 *   screenOf — step key → onboarding screen (ProfileCompletionService::ONBOARDING)
 * @returns {{ refresh: Function }}
 */
const esc = (x) => String(x == null ? '' : x).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const ic = (id) => `<svg class="icon sm" aria-hidden="true"><use href="#${id}"/></svg>`;
/** Fill `:name` placeholders. */
const f = (s, p = {}) => String(s ?? '').replace(/:(\w+)/g, (m, k) => (k in p ? p[k] : m));

/** Draw the bar; returns { refresh } to redraw after a save. */
export function famaGate(el, opts = {}) {
    const S = opts.S || {};
    const REQ = opts.req || [];
    const REC = opts.rec || [];
    const ALL = REQ.concat(REC);
    const screenOf = opts.screenOf || {};
    let lastSig = null;

    // The nav's Private notice is dropped on this page (the bar says the same thing).
    document.querySelectorAll('.fx-notice:not(.gt)').forEach((n) => n.remove());

    function draw() {
        const done = (s) => !!(opts.done && opts.done(s.key));
        const pub = opts.stage ? opts.stage() : 'live';
        const reqLeft = REQ.filter((s) => !done(s));
        const recLeft = REC.filter((s) => !done(s));
        const got = ALL.length - reqLeft.length - recLeft.length;
        const priv = !reqLeft.length && pub === 'private';
        const sig = ALL.map((s) => (done(s) ? 1 : 0)).join('') + pub;
        if (sig === lastSig) return;
        lastSig = sig;

        el.hidden = !reqLeft.length && !recLeft.length && !priv;
        if (el.hidden) { el.innerHTML = ''; return; }

        const listed = REC.some((s) => s.key === opts.indexKey && done(s));
        const head = reqLeft.length
            ? f(reqLeft.length === 1 ? S.moreOne : S.moreMany, { n: reqLeft.length })
            : priv ? S.privH : !listed ? S.liveNext : f(S.standN, { n: recLeft.length });
        const next = reqLeft[0] || recLeft[0] || null;
        const screen = next ? (screenOf[next.key] || 'you') : 'live';
        const pct = Math.round((got / ALL.length) * 100);
        const goBtn = priv ? `<button type="button" class="btn" data-golive>${esc(S.goLive)}${ic('i-arrow')}</button>` : '';
        const cta = `<a class="btn${priv ? ' btn-2' : ''}" href="${esc(opts.url)}/${esc(screen)}">${esc(got === 0 ? S.start : S.cont)}${ic('i-arrow')}</a>`;

        el.innerHTML = '<div class="obb">'
            + `<div class="obb-t"><span class="vw-label">${esc(S.yourPortfolio)}</span><h2 class="obb-h">${esc(head)}</h2>`
            + (next ? `<p class="obb-n">${esc(S.nextL)}<b>${esc(next.label)}</b></p>` : '') + '</div>'
            + `<div class="obb-m"><span class="obb-bar" role="progressbar" aria-valuemin="0" aria-valuemax="${ALL.length}" aria-valuenow="${got}" aria-label="${esc(S.meterA)}"><i style="width:${pct}%"></i></span>`
            + `<b>${esc(f(S.ofN, { n: got, m: ALL.length }))}</b></div>`
            + `<div class="obb-a">${goBtn}${cta}</div>`
            + '</div>';
    }

    el.addEventListener('click', (e) => {
        if (e.target.closest('[data-golive]') && opts.goLive) opts.goLive();
    });
    draw();
    return {
        /** Redraw from opts.done (after a save). */
        refresh() { draw(); },
    };
}
