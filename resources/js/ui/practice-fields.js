/**
 * "About your practice" — the per-skill custom fields on Profile structure
 * (ADR-AG). A port of the prototype's assets/js/fields.js editor + bind, minus
 * its fake uploads. The field catalog is NOT defined here: the page passes the
 * server's App\Support\PracticeFields definitions (`defs`) and skill map
 * (`bySkill`), so PHP stays the one home.
 *
 * Values live once per profile (`vals`, keyed by field id), so a field two
 * skills share — Dialects for Acting and Voice — is edited in one place and
 * redrawn wherever it appears.
 */

const esc = (s) => String(s == null ? '' : s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const arr = (v) => (Array.isArray(v) ? v : []);
const inch = (cm) => { const i = Math.round(cm / 2.54); return Math.floor(i / 12) + '′' + (i % 12) + '″'; };
const age = (d) => (d ? (Date.now() - new Date(d)) / 864e5 : 0);
const date = (d) => new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });

/** Fields one skill asks for: [{ id, opt }]. */
export function forSkill(bySkill, slug) {
    return (bySkill[slug] || []).map((x) => ({ id: x[0], opt: !!x[1] }));
}

/** Whether a value counts as filled in. */
export function filled(defs, id, v) {
    const t = defs[id][1];
    if (v == null || v === '') return false;
    if (t === 'two') return arr(v.a).length + arr(v.b).length > 0;
    if (Array.isArray(v)) return v.length > 0;
    if (typeof v === 'object') return Object.keys(v).some((k) => v[k] !== '' && v[k] != null);
    return true;
}

const chips = (id, list, on, g) => '<div class="fx-chips" role="group" aria-labelledby="fl-' + id + '">' + list.map((o) => '<button type="button" class="chip" data-opt="' + esc(o) + '"' + (g != null ? ' data-g="' + g + '"' : '') + ' aria-pressed="' + (on.indexOf(o) > -1) + '">' + esc(o) + '</button>').join('') + '</div>';
const sel = (k, list, v, blank, label) => '<select class="sel" data-k="' + k + '"' + (label ? ' aria-label="' + esc(label) + '"' : '') + '>' + (blank != null ? '<option value="">' + esc(blank) + '</option>' : '')
    + list.map((o) => '<option' + (o === v ? ' selected' : '') + '>' + esc(o) + '</option>').join('') + '</select>';

/**
 * One field's editor, open (the prototype's Profile structure always shows them
 * open). `ctx` = { defs, all, digitalsUrl }.
 */
export function editor(id, v, opt, ctx) {
    const f = ctx.defs[id]; const type = f[1]; const o = f[2]; const all = ctx.all || {};
    let h = '';
    if (type === 'num') {
        h = '<div class="fx-row"><input class="in fx-n" type="number" inputmode="numeric" data-k="v" min="' + o.min + '" max="' + o.max + '" value="' + esc(v || '') + '" aria-labelledby="fl-' + id + '"><span class="fx-unit">' + o.unit + '</span><span class="fx-out" data-out>' + (v ? inch(v) : '') + '</span></div>';
    } else if (type === 'nums') {
        v = v || {};
        h = '<div class="fx-grid">' + o.map((p) => '<label><span class="fx-sub">' + p[1] + (p[2] ? ' <small>Optional</small>' : '') + '</span><span class="fx-row"><input class="in fx-n" type="number" inputmode="numeric" data-k="' + p[0] + '" value="' + esc(v[p[0]] || '') + '"><span class="fx-unit">cm</span></span></label>').join('') + '</div>';
    } else if (type === 'sizes') {
        v = v || {};
        h = '<div class="fx-grid"><label><span class="fx-sub">Size scale</span>' + sel('r', o, v.r || 'EU') + '</label>'
            + '<label><span class="fx-sub">Clothing size</span><input class="in" data-k="c" value="' + esc(v.c || '') + '" maxlength="8"></label>'
            + '<label><span class="fx-sub">Shoe size</span><input class="in" data-k="s" value="' + esc(v.s || '') + '" maxlength="6" inputmode="decimal"></label></div>';
    } else if (type === 'one' || type === 'many') {
        h = chips(id, o, type === 'one' ? (v ? [v] : []) : arr(v));
    } else if (type === 'two') {
        v = v || {};
        h = o.map((g, i) => '<p class="fx-sub">' + g[0] + '</p>' + chips(id, g[1], arr(v[i ? 'b' : 'a']), i)).join('');
    } else if (type === 'swatch') {
        const on = arr(v);
        h = '<div class="fx-sw" role="group" aria-labelledby="fl-' + id + '">' + o.map((c, i) => '<button type="button" data-opt="' + (i + 1) + '" aria-pressed="' + (on.indexOf(i + 1) > -1) + '" aria-label="Monk ' + (i + 1) + '" title="Monk ' + (i + 1) + '" style="background:' + c + '"></button>').join('')
            + '</div><p class="fx-meta">' + (on.length ? on.length + ' of 10 shades' : 'No shades chosen') + '</p>';
    } else if (type === 'range') {
        v = v || {};
        const bad = v.min && v.max && (v.max - v.min) < o.span;
        h = '<div class="fx-row"><input class="in fx-n" type="number" inputmode="numeric" data-k="min" min="' + o.min + '" max="' + o.max + '" value="' + esc(v.min || '') + '" aria-label="From"><span class="fx-unit">to</span><input class="in fx-n" type="number" inputmode="numeric" data-k="max" min="' + o.min + '" max="' + o.max + '" value="' + esc(v.max || '') + '" aria-label="To"><span class="fx-unit">years</span></div>'
            + '<p class="fx-err" data-err' + (bad ? '' : ' hidden') + '>Make it at least ' + o.span + ' years wide.</p>';
    } else if (type === 'levels') {
        v = v || {};
        h = '<ul class="fx-lv">' + o[0].map((d) => '<li><span>' + esc(d) + '</span>' + sel('lv:' + d, o[1], v[d], 'Not spoken', d) + '</li>').join('') + '</ul>';
    } else if (type === 'reach') {
        const pl = arr(all.platforms); v = v || {};
        h = pl.length ? '<ul class="fx-lv">' + pl.map((p) => '<li><span>' + esc(p) + '</span>' + sel('rc:' + p, o, v[p], 'Choose a range', p) + '</li>').join('') + '</ul>'
            : '<p class="fx-meta">Choose your platforms first, then set the reach for each.</p>';
    } else if (type === 'pairs') {
        const ps = arr(v);
        h = '<ul class="fx-pairs">' + ps.map((p, i) => '<li>' + sel('p0:' + i, o, p[0], null, 'From') + '<span class="fx-unit" aria-hidden="true">→</span>' + sel('p1:' + i, o, p[1], null, 'To')
            + '<button type="button" class="iconbtn" data-rmpair="' + i + '" aria-label="Remove ' + esc(p[0] + ' to ' + p[1]) + '"><svg class="icon"><use href="#i-trash"/></svg></button></li>').join('')
            + '</ul><button type="button" class="btn btn-2 fx-add" data-addpair><svg class="icon"><use href="#i-plus"/></svg>Add a pair</button>';
    } else if (type === 'licence') {
        v = v || {};
        h = '<div class="fx-grid"><label><span class="fx-sub">Certificate number</span><input class="in" data-k="cert" value="' + esc(v.cert || '') + '" dir="ltr"></label>'
            + '<label><span class="fx-sub">Drone registration</span><input class="in" data-k="reg" value="' + esc(v.reg || '') + '" dir="ltr"></label>'
            + '<label><span class="fx-sub">Valid until</span><input class="in" type="date" data-k="exp" value="' + esc(v.exp || '') + '"></label></div>';
    } else if (type === 'digitals') {
        // A view of the Digitals section (dated photos) — uploaded there, never stored twice.
        v = v || {};
        const old = v.d && age(v.d) > 182;
        h = '<div class="fx-digi">' + o.map((n) => '<figure><div class="fx-ph' + (v.d ? ' on' : '') + '"></div><figcaption>' + n + '</figcaption></figure>').join('') + '</div>'
            + (v.d ? '<p class="fx-meta' + (old ? ' fx-old' : '') + '">' + (old ? 'Taken ' + date(v.d) + '. Over six months old, so brands see it flagged. Upload a new set.' : 'Taken ' + date(v.d)) + '</p>' : '')
            + '<a class="btn btn-2 fx-add" href="' + esc(ctx.digitalsUrl || '#') + '"><svg class="icon"><use href="#i-upload"/></svg>' + (v.d ? 'Upload a new set' : 'Upload digitals') + '</a>';
    }
    // A filled field can be emptied in one go; it then drops off the profile (not digitals: delete those photos instead).
    const clr = type !== 'digitals' && filled(ctx.defs, id, v) ? '<button type="button" class="fx-clear" data-clear aria-label="Clear ' + esc(f[0].toLowerCase()) + '">Clear</button>' : '';
    return '<div class="fld fx-opened" data-fld="' + id + '"><div class="fx-l"><p id="fl-' + id + '"><span>' + f[0] + '</span>' + (opt ? ' <small>Optional</small>' : '') + '</p>' + clr + '</div>'
        + (f[3] ? '<p class="fx-help">' + esc(f[3]) + '</p>' : '') + h + '</div>';
}

/**
 * One listener set for every field under `root`. `get()` returns the live
 * values object (re-read each event, so a Discard that swaps the object is
 * honoured); `done(id, el, typing)` redraws; `cleared(id, was, name)` offers Undo.
 */
export function bind(root, defs, get, done, cleared) {
    const key = (el) => el.closest('[data-fld]').dataset.fld;
    root.addEventListener('click', (e) => {
        const vals = get();
        const b = e.target.closest('[data-fld] [data-opt]');
        if (b) {
            const id = key(b); const t = defs[id][1]; const o = t === 'swatch' ? +b.dataset.opt : b.dataset.opt;
            if (t === 'one') vals[id] = vals[id] === o ? '' : o;
            else if (t === 'two') {
                const v = vals[id] = { ...(vals[id] || {}) }; const g = b.dataset.g === '1' ? 'b' : 'a'; const l = arr(v[g]);
                v[g] = l.indexOf(o) > -1 ? l.filter((x) => x !== o) : l.concat(o);
            } else { const l2 = arr(vals[id]); vals[id] = l2.indexOf(o) > -1 ? l2.filter((x) => x !== o) : l2.concat(o); }
            done(id, b); return;
        }
        const c = e.target.closest('[data-fld] [data-clear]');
        if (c) { const idc = key(c); const was = vals[idc]; delete vals[idc]; done(idc, null); if (cleared) cleared(idc, was, defs[idc][0]); return; }
        const a = e.target.closest('[data-fld] [data-addpair]');
        if (a) { const id2 = key(a); vals[id2] = arr(vals[id2]).concat([['English', 'Arabic']]); done(id2, a); return; }
        const r = e.target.closest('[data-fld] [data-rmpair]');
        if (r) { const id3 = key(r); vals[id3] = arr(vals[id3]).filter((_, i) => i !== +r.dataset.rmpair); done(id3, null); }
    });
    const put = (e, redraw) => {
        const el = e.target; const k = el.dataset && el.dataset.k;
        if (!k || !el.closest('[data-fld]')) return;
        const vals = get();
        const id = key(el); const t = defs[id][1]; const val = el.value.trim();
        if (t === 'num') vals[id] = val ? +val : '';
        else if (t === 'levels' || t === 'reach') {
            const m = { ...(vals[id] || {}) }; const n = k.slice(3);
            if (val) m[n] = val; else delete m[n];
            vals[id] = m;
        } else if (t === 'pairs') {
            const ix = +k.slice(3); const list = arr(vals[id]).map((p) => p.slice());
            list[ix][k[1] === '0' ? 0 : 1] = val; vals[id] = list;
        } else { vals[id] = { ...(vals[id] || {}), [k]: (t === 'range' || t === 'nums') && val ? +val : val }; }
        done(id, el, !redraw);
    };
    root.addEventListener('input', (e) => { if (e.target.tagName === 'INPUT' && e.target.closest('[data-fld]')) put(e, false); });
    root.addEventListener('change', (e) => { if (e.target.closest('[data-fld]')) put(e, true); });
}

/** The live inch read-out next to Height while typing. */
export function inchOut(v) { return v ? inch(v) : ''; }
