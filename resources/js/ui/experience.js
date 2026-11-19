/**
 * Experience roles (ADR-AK) — port of the prototype's profile.js FAMA_EXP for
 * the Profile structure editor: the grouped list with a pencil per role. The
 * public profile draws the same list server-side (App\Support\Experience +
 * talent/blocks/brand_collabs), so the two must keep the same rules:
 * newest first (current roles on top), consecutive roles at one company grouped.
 */

const MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const esc = (x) => String(x == null ? '' : x).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const NOW = (() => { const d = new Date(); return { y: d.getFullYear(), m: d.getMonth() + 1 }; })();

export { MON };

/** Months a role lasted (at least one). */
export function months(r) {
    const ey = r.cur ? NOW.y : (r.ey || r.sy);
    const em = r.cur ? NOW.m : (r.em || (r.ey ? 12 : r.sm || 12));
    const sm = r.sm || 1;
    return Math.max(1, (ey - r.sy) * 12 + (em - sm) + 1);
}
/** "2 yrs 3 mos". */
export function dur(m) {
    const y = Math.floor(m / 12); const mo = m % 12; const out = [];
    if (y) out.push(y + (y === 1 ? ' yr' : ' yrs'));
    if (mo) out.push(mo + (mo === 1 ? ' mo' : ' mos'));
    return out.join(' ');
}
const when = (m, y) => (y ? (m ? MON[m - 1] + ' ' : '') + y : '');
/** "Mar 2024 – Present · 2 yrs 9 mos". */
export function range(r) {
    return when(r.sm, r.sy) + ' – ' + (r.cur ? 'Present' : when(r.em, r.ey) || when(r.sm, r.sy)) + (r.sy ? ' · ' + dur(months(r)) : '');
}
const key = (r) => (r.cur ? 1e6 + r.sy * 12 + (r.sm || 1) : (r.ey || r.sy) * 12 + (r.em || 12));

/** Newest first; roles at the same company in a row grouped under it. */
export function groups(list) {
    const rows = list.map((r, i) => ({ r, i })).filter((x) => x.r.n || x.r.t).sort((a, b) => key(b.r) - key(a.r));
    const out = [];
    rows.forEach((x) => {
        const g = out[out.length - 1];
        if (g && g.n.toLowerCase() === (x.r.n || '').toLowerCase()) g.roles.push(x); else out.push({ n: x.r.n || '', roles: [x] });
    });
    return out;
}

/** The list as HTML. opts.edit adds a pencil per role (data-edit-role="index"); opts.skill maps id → name. */
export function html(list, opts = {}) {
    const pen = (i, t) => (opts.edit ? '<button type="button" class="iconbtn plain xp-ed" data-edit-role="' + i + '" aria-label="Edit ' + esc(t) + '"><svg class="icon" aria-hidden="true"><use href="#i-pen"/></svg></button>' : '');
    const hl = (r) => {
        const l = String(r.hl || '').split('\n').map((x) => x.replace(/^[\s·\-•]+/, '').trim()).filter(Boolean);
        return l.length ? '<ul class="xp-hl">' + l.map((x) => '<li>' + esc(x) + '</li>').join('') + '</ul>' : '';
    };
    const sk = (r) => {
        const n = (r.sk || []).map((id) => (opts.skill ? opts.skill(id) : id)).filter(Boolean);
        return n.length ? '<p class="xp-sk"><b>Skills:</b> ' + esc(n.join(', ')) + '</p>' : '';
    };
    const place = (r) => [r.loc, r.lt].filter(Boolean).join(' · ');
    const logo = '<span class="xp-logo" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/></svg></span>';
    return '<ol class="xp">' + groups(list).map((g) => {
        if (g.roles.length === 1) {
            const x = g.roles[0]; const r = x.r;
            return '<li class="xp-g one">' + logo + '<div class="xp-b"><div class="xp-top"><div><h3 class="xp-t">' + esc(r.t) + '</h3>'
                + '<p class="xp-m">' + esc([g.n, r.et].filter(Boolean).join(' · ')) + '</p><p class="xp-d">' + esc(range(r)) + '</p>'
                + (place(r) ? '<p class="xp-d">' + esc(place(r)) + '</p>' : '') + '</div>' + pen(x.i, r.t) + '</div>' + hl(r) + sk(r) + '</div></li>';
        }
        const first = g.roles[g.roles.length - 1].r; const last = g.roles[0].r;
        const total = Math.max(1, ((last.cur ? NOW.y : (last.ey || last.sy)) - first.sy) * 12 + ((last.cur ? NOW.m : (last.em || 12)) - (first.sm || 1)) + 1);
        const et = g.roles.every((y) => y.r.et === last.et) ? last.et : '';
        return '<li class="xp-g">' + logo + '<div class="xp-b"><h3 class="xp-org">' + esc(g.n) + '</h3><p class="xp-m">' + esc([et, dur(total)].filter(Boolean).join(' · ')) + '</p>'
            + (place(last) ? '<p class="xp-d">' + esc(place(last)) + '</p>' : '')
            + '<ol class="xp-roles">' + g.roles.map((x) => {
                const r = x.r;
                return '<li class="xp-r"><div class="xp-top"><div><h4 class="xp-t">' + esc(r.t) + '</h4><p class="xp-d">' + esc(range(r)) + '</p></div>' + pen(x.i, r.t) + '</div>' + hl(r) + sk(r) + '</li>';
            }).join('') + '</ol></div></li>';
    }).join('') + '</ol>';
}
