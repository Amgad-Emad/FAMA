/**
 * The profile path on Profile structure — port of the prototype's views.js
 * famaGate (24 Oct 2026) on real data (ADR-AC). Two parts:
 *   Go Live   (opts.req) — the link works and can be shared.
 *   Stand out (opts.rec) — not needed to go Live. Its first step (opts.indexKey,
 *             something to show) lists the profile in the Creatives Index; the
 *             rest is what gets a profile picked.
 * The card (#gate) lists both parts; once Live it folds to one line. The tracker
 * floats under the nav (.fx-notice.gt-float) while the card is scrolled away: a
 * ring for the current part, one segment per step with milestones, and the next
 * step. Every step lands on its own field (centred, focused, flashed).
 *
 * opts.done(key) must answer from SAVED data — never the draft. All copy comes
 * from Blade (opts.S) so it is translatable; labels are escaped here.
 *
 * @param {HTMLElement} el the #gate card
 * @param {object} opts { req, rec, indexKey, S, go, done, onState }
 * @returns {{ refresh: Function }}
 */
const esc = (x) => String(x == null ? '' : x).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const ic = (id) => `<svg class="icon" aria-hidden="true"><use href="#${id}"/></svg>`;
const STAR = '<svg class="gt-star" viewBox="10 80 760 690"><polygon points="314.63 635.59 16.74 747.68 217.29 533.15 86.97 370.29 403.26 356.02 701.63 87.21 636.51 355.76 760 356.91 574.68 534.04 422.33 756.79 314.63 635.59"/></svg>';

/** Fill `:name` placeholders. */
const f = (s, p = {}) => String(s ?? '').replace(/:(\w+)/g, (m, k) => (k in p ? p[k] : m));

/** Draw the card + tracker; returns { refresh } to redraw after a save. */
export function famaGate(el, opts = {}) {
    const S = opts.S || {};
    // Steps as the prototype's tuples: [key, label, help, href] (label/help escaped).
    const tuple = (s) => [s.key, esc(s.label), esc(s.help), s.href];
    const REQ = (opts.req || []).map(tuple);
    const REC = (opts.rec || []).map(tuple);
    const ALL = REQ.concat(REC);
    const GO = opts.go || {};
    const KEY = opts.indexKey;
    const done = {};
    let open = false; let expanded = false; let lastSig = null; let lastStage = null; let was = null;

    // The nav's own status notice gives way to the tracker on this page.
    const nav = document.querySelector('.fx-nav');
    document.querySelectorAll('.fx-notice:not(.gt)').forEach((n) => n.remove());
    let bar = null;
    if (nav) {
        bar = document.createElement('div');
        bar.className = 'fx-notice gt';
        bar.setAttribute('role', 'region');
        bar.setAttribute('aria-label', S.barLabel || 'Profile steps');
        bar.hidden = true;
        nav.appendChild(bar);
    }

    const link = (r, cls, inner) => (GO[r[0]]
        ? `<button type="button" class="${cls}" data-go="${r[0]}">${inner}</button>`
        : `<a class="${cls}" href="${esc(r[3])}">${inner}</a>`);
    const tick = (ok) => `<span class="tick">${ok ? ic('i-check') : ''}</span>`;
    const left = (L) => L.filter((r) => !done[r[0]]);

    /* the track (13 Nov 2026 review: "not clear how to read it"): two labelled groups, each with its name
       and count, one segment per step (each jumps to its step). A group turns solid once done; no Index
       milestone (12 Nov 2026 review). */
    function track(all, next, fresh = []) {
        const isLive = all || !left(REQ).length; const ready = all || (!left(REQ).length && !left(REC).length);
        const seg = (r) => {
            const ok = all || done[r[0]];
            const cls = 'gt-s' + (ok ? ' ok' : '') + (next && r === next ? ' cur' : '') + (fresh.indexOf(r[0]) >= 0 ? ' new' : '');
            return link(r, cls, `<span class="vh">${r[1]}${ok ? esc(S.doneSuffix) : ''}</span>`).replace(/^<(button|a) /, `<$1 title="${r[1]}" `);
        };
        const got = (L) => L.filter((r) => all || done[r[0]]).length;
        const cnt = (L) => f(S.ofN, { n: got(L), m: L.length });
        const grp = (L, label, on) => `<div class="gt-g${on ? ' on' : ''}" style="--n:${L.length}"><span class="gt-gl">${on ? ic('i-check') : ''}${esc(label)}`
            + ` <span class="gt-gc">${got(L)}/${L.length}</span></span><span class="gt-gs">${L.map(seg).join('')}</span></div>`;
        return `<div class="gt-track" role="group" aria-label="${esc(f(S.trackA, { a: cnt(REQ), b: cnt(REC) }))}">`
            + grp(REQ, S.grpLive, isLive) + grp(REC, S.grpStand, ready) + '</div>';
    }

    /* one part of the card: its number, title, count and steps (a single line once done) */
    function part(L, n, title, sub, state) {
        const got = L.length - left(L).length;
        return `<section class="jr-part${state ? ' ' + state : ''}">`
            + `<div class="jr-ph"><span class="jr-num">${state === 'is-done' ? ic('i-check') : n}</span><div><h3>${esc(title)}</h3><p>${esc(sub)}</p></div><span class="jr-c">${got}/${L.length}</span></div>`
            + (state === 'is-done'
                ? `<p class="jr-got">${L.map((r) => r[1]).join(' · ')}</p>`
                : `<ol class="vw-todo jr-steps" style="--cols:${Math.min(L.length, 4)}">` + L.map((r, i) => {
                    const ok = done[r[0]]; const nx = state === 'is-now' && r === left(L)[0];
                    return `<li class="${ok ? 'ok' : ''}${nx ? ' jr-next' : ''}${r[0] === KEY ? ' jr-key' : ''}"><span class="jr-n" aria-hidden="true">${ok ? ic('i-check') : (i + 1 < 10 ? '0' : '') + (i + 1)}</span>`
                        + `<span class="txt"><b>${r[1]}${r[0] === KEY ? `<span class="jr-tag">${esc(ok ? S.inIndex : S.getsIndex)}</span>` : ''}</b>${ok ? '' : `<p>${r[2]}</p>`}</span>`
                        + link(r, 'vw-link', `${esc(ok ? S.edit : S.add)}<span class="vh"> ${r[1]}</span>${ic('i-arrow')}`) + '</li>';
                }).join('') + '</ol>')
            + '</section>';
    }

    function draw() {
        if (opts.done) ALL.forEach((r) => { const v = opts.done(r[0]); if (v != null) done[r[0]] = v; });
        const reqLeft = left(REQ); const recLeft = left(REC);
        const stage = reqLeft.length ? 'live' : recLeft.length ? 'stand' : 'done';
        const sig = ALL.map((r) => (done[r[0]] ? 1 : 0)).join('') + open + expanded;
        if (sig === lastSig) return;
        const fresh = was ? ALL.filter((r) => done[r[0]] && !was[r[0]]).map((r) => r[0]) : [];
        const listed = !!done[KEY]; const gotListed = was && !was[KEY] && listed && stage !== 'live';
        const wentLive = lastStage === 'live' && stage !== 'live'; const finished = !!lastStage && lastStage !== 'done' && stage === 'done';
        if (opts.onState) opts.onState({ live: stage !== 'live', listed: listed && stage !== 'live', wentLive });
        lastSig = sig; lastStage = stage; was = {}; ALL.forEach((r) => { was[r[0]] = done[r[0]]; });

        el.hidden = stage === 'done';
        el.classList.toggle('jr-compact', stage !== 'live' && !expanded);
        /* once Live the card folds to one line: where they stand and the next step. "All steps" opens the rest */
        if (!el.hidden && stage !== 'live' && !expanded) {
            const nx = left(REC)[0]; const got = REC.length - left(REC).length;
            el.innerHTML = '<div class="jr-row">'
                + `<h2 class="jr-rt">${listed ? `${esc(S.standOut)} <span class="jr-c">${got}/${REC.length}</span>` : esc(S.oneToIndex)}</h2>`
                + (nx ? link(nx, 'btn btn-2 jr-go', `<span class="jr-gl">${esc(S.nextColon)}</span> ${nx[1]}${ic('i-arrow')}`) : '')
                + `<button type="button" class="vw-link jr-more" data-jr="open" aria-expanded="false">${esc(S.allSteps)}${ic('i-down')}</button></div>`;
        } else if (!el.hidden) {
            const h = stage === 'live' ? f(reqLeft.length === 1 ? S.moreOne : S.moreMany, { n: reqLeft.length }) : listed ? S.liveStand : S.liveNext;
            const lead = stage === 'live' ? S.leadLive : listed ? S.leadListed : S.leadNotListed;
            el.innerHTML = `<div class="vw-head"><div><span class="vw-label">${esc(S.yourProfile)}</span><h2 class="vw-h">${esc(h)}</h2>`
                + `<p class="vw-lead">${esc(lead)}</p></div>`
                + `<span class="vw-only">${ic('i-lock')}${esc(S.onlyYou)}</span></div>`
                + part(REQ, 1, S.p1Title, S.p1Sub, stage === 'live' ? 'is-now' : 'is-done')
                + part(REC, 2, S.p2Title, S.p2Sub, stage === 'live' ? 'is-later' : 'is-now')
                + (stage !== 'live' ? `<p class="vw-note"><button type="button" class="vw-link jr-more" data-jr="close" aria-expanded="true">${esc(S.fold)}${ic('i-down')}</button></p>` : '');
        }

        if (!bar) return;
        /* the last Stand out step: one moment of credit, then the tracker goes */
        if (finished) {
            bar.classList.add('gt', 'gt-soft', 'gt-live'); bar.hidden = false;
            bar.innerHTML = `<div class="fx-wrap gt-in gt-win"><span class="gt-sum"><span class="gt-ring" aria-hidden="true"><svg viewBox="0 0 36 36"><circle cx="18" cy="18" r="15"/><circle class="fg" cx="18" cy="18" r="15"/></svg>${ic('i-check')}${STAR}</span>`
                + `<span class="gt-t"><b class="gt-h"><span>${esc(S.readyH)}</span></b><span class="gt-sub">${esc(S.readySub)}</span></span></span>`
                + track(true) + '</div>'
                + `<p class="vh" role="status">${esc(S.readySr)}</p>`;
            setTimeout(() => { if (lastStage === 'done') bar.hidden = true; }, 3200);
            return;
        }
        if (stage === 'done') { bar.hidden = true; return; }
        bar.hidden = false;
        const L = stage === 'live' ? REQ : REC; const todo = left(L); const n = L.length - todo.length; const next = todo[0];
        const won = fresh.length > 0; const C = 2 * Math.PI * 15;
        const head = wentLive ? S.headWent : gotListed ? S.headListed : won ? f(S.headNice, { n: todo.length })
            : stage === 'live' ? (todo.length === 1 ? S.headLast : f(S.headMore, { n: todo.length })) : !listed ? S.headGetListed : f(S.headStand, { n: todo.length });
        const short = wentLive ? S.shortWent : gotListed ? S.shortListed : won ? S.shortNice
            : stage === 'live' ? f(S.shortLive, { n: todo.length }) : !listed ? S.shortGet : S.shortStand;
        bar.classList.add('gt', 'gt-soft'); /* one colour in every stage; the win moments are motion and yellow accents */
        bar.classList.toggle('gt-live', !!(wentLive || gotListed));
        bar.innerHTML = `<div class="fx-wrap gt-in${won || wentLive ? ' gt-win' : ''}">`
            + `<button type="button" class="gt-sum" aria-expanded="${open}" aria-controls="gtList">`
            + `<span class="gt-ring" aria-hidden="true"><svg viewBox="0 0 36 36"><circle cx="18" cy="18" r="15"/><circle class="fg" cx="18" cy="18" r="15" style="stroke-dasharray:${C.toFixed(1)};stroke-dashoffset:${(C * (1 - n / L.length)).toFixed(1)}"/></svg>`
            + `<b>${n}/${L.length}</b>${STAR}</span>`
            + `<span class="gt-t"><b class="gt-h"><span class="gt-hl">${esc(head)}</span><span class="gt-hs">${esc(short)}</span></b><span class="gt-sub">${esc(stage === 'live' ? S.sub1 : listed ? S.sub2Listed : S.sub2Not)}</span></span>${ic('i-down')}</button>`
            + track(false, next, fresh)
            + (next ? link(next, 'gt-next', `<span class="gt-nl">${esc(S.nextL)}</span><span class="gt-nn">${next[1]}</span>${ic('i-arrow')}`) : '')
            + '</div>'
            + `<div class="gt-pop" id="gtList"${open ? '' : ' hidden'}><div class="fx-wrap">`
            + [[REQ, S.pop1], [REC, S.pop2]].map((g) => `<p class="gt-ph">${esc(g[1])}<span>${g[0].length - left(g[0]).length}/${g[0].length}</span></p><ul>`
                + g[0].map((r) => { const ok = done[r[0]]; return `<li class="${ok ? 'ok' : ''}${next && r === next ? ' cur' : ''}">${link(r, 'gt-step', `${tick(ok)}<span>${r[1]}</span>${ic('i-arrow')}`)}</li>`; }).join('') + '</ul>').join('')
            + '</div></div>'
            + `<p class="vh" role="status">${esc(wentLive ? S.srWent : gotListed ? S.srListed : won ? f(S.srN, { n, m: L.length }) : '')}</p>`;
        if (won || wentLive) setTimeout(() => { if (lastSig === sig) { lastSig = null; was = null; draw(); } }, wentLive || gotListed ? 3200 : 2400);
    }

    /* a step lands on its own field: centred, focused, flashed */
    function go(k) {
        const t = document.querySelector(GO[k]); if (!t) return;
        setOpen(false);
        t.scrollIntoView({ block: 'center', behavior: 'smooth' });
        const fld = t.matches('input,textarea,button') ? t : t.querySelector('input,textarea,button');
        if (fld) fld.focus({ preventScroll: true });
        const w = t.closest('.f') || t; w.classList.remove('gt-flash'); void w.offsetWidth; w.classList.add('gt-flash');
        setTimeout(() => w.classList.remove('gt-flash'), 1800);
    }
    function setOpen(v) {
        open = v; lastSig = null; if (!bar) return;
        const p = bar.querySelector('.gt-pop'); const b = bar.querySelector('.gt-sum');
        if (p) p.hidden = !v; if (b) b.setAttribute('aria-expanded', v);
    }
    function onClick(e) {
        const j = e.target.closest('[data-jr]');
        if (j) { expanded = j.dataset.jr === 'open'; lastSig = null; draw(); const fb = el.querySelector('[data-jr]'); if (fb) fb.focus(); return; }
        const g = e.target.closest('[data-go]'); if (g) { go(g.dataset.go); return; }
        if (e.target.closest('.gt-sum')) setOpen(!open);
    }
    el.addEventListener('click', onClick);
    if (bar) {
        bar.addEventListener('click', onClick);
        document.addEventListener('click', (e) => { if (open && !bar.contains(e.target)) setOpen(false); });
        document.addEventListener('keydown', (e) => { if (open && e.key === 'Escape') { setOpen(false); bar.querySelector('.gt-sum')?.focus(); } });
    }
    draw();
    /* phones: the tracker steps aside while a field has focus, so the keyboard and the Save bar leave room to type */
    if (bar) {
        const typing = (e) => { const t = e.target; return t && t.matches && t.matches('input:not([type=checkbox]):not([type=radio]),textarea,select,[contenteditable]') && !bar.contains(t); };
        document.addEventListener('focusin', (e) => { if (typing(e)) document.documentElement.classList.add('fx-typing'); });
        document.addEventListener('focusout', (e) => { if (typing(e)) document.documentElement.classList.remove('fx-typing'); });
    }
    /* the tracker floats under the nav, and steps away while the card itself is on screen */
    if (bar && window.IntersectionObserver) {
        const navH = () => { const n = document.querySelector('.fx-nav .fx-in'); return n ? n.offsetHeight : 64; };
        bar.classList.add('gt-float', 'gt-away');
        new IntersectionObserver((es) => {
            const seen = es[0].isIntersecting && !el.hidden;
            bar.classList.toggle('gt-away', seen);
            if (seen && open) setOpen(false);
        }, { rootMargin: '-' + navH() + 'px 0px 0px 0px' }).observe(el);
    }
    return {
        /** Redraw from opts.done (after a save); keeps focus on a step button that was focused. */
        refresh() {
            const a = document.activeElement; const id = a && a.dataset && a.dataset.go;
            draw();
            if (id) { const b = bar && bar.querySelector(`[data-go="${id}"]`); if (b) b.focus(); }
        },
    };
}
