/**
 * Profile structure (GET /talent/profile) — the fama-ui "profile-structure.html"
 * screen, Alpine components registered on alpine:init:
 *
 *   profileStructure — the whole page as ONE DRAFT: identity, availability,
 *                      links, skills (order / add / remove / specialities),
 *                      sections (visibility / order / scope / placement),
 *                      willing to explore and pricing all change locally; the
 *                      bottom bar saves them through the existing endpoints or
 *                      discards them (toast with Undo). Leaving with unsaved
 *                      changes warns. Only the photo uploads immediately (a file
 *                      is not a draft — the prototype treats it the same way).
 *   profileViews     — the views card (./profile-views.js).
 * The profile path (Go Live → Stand out, ADR-AC) is ./profile-gate.js: it
 * follows what is SAVED (GET /talent/profile/state after every save), never
 * the draft — a half-typed edit must not tell anyone they are no longer Live.
 * If saving the draft WOULD take the profile out of Live, the bar says so first.
 * The list editors (links, skills, picker, sections, explore) are a mixin in
 * ./profile-structure-lists.js.
 *
 * Product rules (2026-10-12): no separately chosen primary skill — the order is
 * the tab order and the first opens first (the server derives is_primary);
 * a Private profile shows no link at all.
 */
import { del, get, patch, post, put, ApiError } from '../http';
import { tr, clone, fmt, toast, ask } from './profile-helpers';
import { PLATFORMS, socialIcon, isWeb, urlOf } from './social';
import { listsMixin, merge } from './profile-structure-lists';
import './profile-views';
import { famaGate } from './profile-gate';

/** First validation message of an ApiError (or its message). */
const firstError = (e) => Object.values(e?.errors || {})[0]?.[0] || e?.message || '';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.data('profileStructure', (initial) => merge({
        S: initial.strings || {},
        host: initial.host || '',
        tr,
        socialIcon,
        isWeb,
        platforms: PLATFORMS,
        reviewsOn: !!initial.reviewsOn,

        // ----- location drop lists -----------------------------------------
        countries: initial.countries || [],
        cityCache: {},
        cityLoads: {},

        // ----- the draft ----------------------------------------------------
        core: clone(initial.core),
        links: initial.social.map((l, i) => ({ platform: l.platform, handle: isWeb(l.platform) ? l.handle : String(l.handle || '').replace(/^@/, ''), _k: 'l' + i })),
        rate: clone(initial.rate),
        now: { is_available: !!initial.now.is_available, will_travel: !!initial.now.will_travel },
        skills: initial.skills,
        specialities: initial.specialities || {},
        blocks: initial.blocks,
        interests: (initial.interests || []).map((w) => ({ id: w.id, talent_type_id: w.talent_type_id, speciality_id: w.speciality_id, note: w.note || '' })),

        // ----- the last saved state (set in init) --------------------------
        base: null,

        // ----- not part of the draft ---------------------------------------
        allSkills: initial.interestSkills || [],
        catalog: initial.catalog,
        contentKeys: initial.contentKeys || [],
        collabs: initial.collabs || [],
        hasPricing: !!initial.hasPricing,
        // The profile path from SAVED data: { steps, can_go_live, is_published, listed, profile_done, status }.
        path: initial.completion || { steps: {} },
        avatarUrl: initial.avatarUrl || null,
        uploadingAvatar: false,
        avatarError: '',
        isPublished: !!initial.publish.is_published,
        publishing: false,
        publishError: '',
        copied: false,
        note: '',

        // ----- UI state -----------------------------------------------------
        errors: {},
        rateErrors: {},
        socialError: '',
        newRow: { platform: 'website' },
        showNew: false,
        platOpen: null,
        openGroups: {},
        newSkillId: null,
        skDrag: null,
        skDropOn: null,
        skDropAfter: false,
        dragBlock: null,
        dropOn: null,
        dropDeny: null,
        saving: false,
        savedAt: null,
        // The bar stays up for a moment after a save ("Saved just now"), then slides away.
        savedFlash: false,
        flashTimer: null,
        badField: null,
        badNote: '',
        // Phones keep the bar to one row (a short title and a short button).
        narrow: window.matchMedia('(max-width:760px)').matches,
        _k: 1000,
        _tmp: 0,
        picker: { open: false, title: '', help: '', ok: '', exclude: [], current: null, q: '', resolve: null },

        /** Snapshot the saved state, wire Ctrl/Cmd+S, the leave guard and #hash landing. */
        init() {
            this.rootEl = this.$root; // `$root` is undefined from a swapped-out row
            this.rebase();
            const first = this.skills[0];
            this.openGroups = { universal: true, ...(first ? { [String(first.id)]: true } : {}) };
            window.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's') {
                    e.preventDefault();
                    if (this.dirtyCount) this.saveAll();
                }
            });
            window.addEventListener('beforeunload', (e) => {
                if (this.dirtyCount) { e.preventDefault(); e.returnValue = ''; }
            });
            document.addEventListener('click', (e) => {
                if (this.platOpen !== null && !e.target.closest('.platmenu,.platbtn')) this.platOpen = null;
            });
            if (window.location.hash === '#display') this.focusField('fDisplay');
            window.matchMedia('(max-width:760px)').addEventListener('change', (e) => { this.narrow = e.matches; });
            // The path card + the tracker under the nav (follows SAVED data only).
            const gateEl = document.getElementById('gate');
            if (gateEl) {
                const gate = famaGate(gateEl, {
                    req: this.S.goLive || [],
                    rec: this.S.standOut || [],
                    indexKey: 'show',
                    S: this.S.gate || {},
                    go: { name: '#fDisplay', place: '#fCity', skill: '#skills', photo: '#fPhoto', headline: '#fHeadline', bio: '#fBio', links: '#social .pin-card', brands: '#addBrand' },
                    done: (k) => !!(this.path.steps || {})[k],
                    // Going Live hands over the link: the copy button lights up in the hero.
                    onState: (st) => { if (st.wentLive) this.flashLink(); },
                });
                this.gateRefresh = () => gate.refresh();
            }
        },

        // ================= draft bookkeeping ================================
        /** Everything the save bar tracks, as plain data. */
        draftState() {
            return clone({
                core: this.core, links: this.links, rate: this.rate, now: this.now, skills: this.skills,
                specialities: this.specialities, blocks: this.blocks, interests: this.interests,
            });
        },
        /** Put a state back into the draft (Discard, Undo). */
        applyState(st) {
            const c = clone(st);
            Object.assign(this, c);
        },
        /** The current draft becomes the saved state. */
        rebase() { this.base = this.draftState(); },

        /** Links as the API stores them (empty handles dropped). */
        cleanLinks(list = this.links) {
            return list.filter((l) => String(l.handle || '').trim())
                .map((l) => ({ platform: l.platform, handle: String(l.handle).trim(), url: urlOf(l.platform, l.handle) }));
        },
        /** A block's saved-or-draft row as comparable data. */
        blockSig(b) { return { t: b.talent_type_id ?? null, p: b.position, v: !!b.is_visible }; },
        /** The draft block keyed by id, from a state. */
        blockMap(list) { return Object.fromEntries((list || []).map((b) => [b.id, b])); },
        /** Whether one universal switch (interests / brand_collabs) differs from saved. */
        switchDirty(key) {
            const b = this.uniBlock(key);
            if (!b) return false;
            const was = this.blockMap(this.base?.blocks)[b.id];
            return !!was && !!was.is_visible !== !!b.is_visible;
        },
        /** Which parts differ from the saved state. */
        get dirty() {
            const B = this.base;
            if (!B) return {};
            const j = JSON.stringify;
            const baseBlocks = this.blockMap(B.blocks);
            const switchKeys = ['interests', 'brand_collabs'];
            const sectionsDirty = this.blocks.some((b) => {
                const was = baseBlocks[b.id];
                if (!was) return false;
                const a = this.blockSig(b); const o = this.blockSig(was);
                const isSwitch = (b.talent_type_id ?? null) === null && switchKeys.includes(b.block_type.key);
                return a.t !== o.t || a.p !== o.p || (!isSwitch && a.v !== o.v);
            });
            const specsDirty = this.skills.some((s) => j(this.picked(s.id)) !== j(B.specialities[String(s.id)]?.selected || []));
            return {
                core: j(this.core) !== j(B.core),
                now: j(this.now) !== j(B.now),
                social: j(this.cleanLinks()) !== j(this.cleanLinks(B.links)),
                skills: j(this.skills.map((s) => s.id)) !== j(B.skills.map((s) => s.id)) || specsDirty,
                sections: sectionsDirty,
                explore: j(this.interests) !== j(B.interests) || this.switchDirty('interests'),
                brands: this.switchDirty('brand_collabs'),
                pricing: this.hasPricing && j(this.rate) !== j(B.rate),
            };
        },
        /** Keep the clean "Saved just now" bar up for 2.5s, then let it slide away. */
        flashSaved() {
            this.savedFlash = true;
            clearTimeout(this.flashTimer);
            this.flashTimer = setTimeout(() => { this.savedFlash = false; }, 2500);
        },
        /** The save bar shows only with unsaved changes, or briefly after a save. */
        get barShown() { return this.dirtyCount > 0 || this.savedFlash; },
        /** How many parts have unsaved changes. */
        get dirtyCount() { return Object.values(this.dirty).filter(Boolean).length; },
        /**
         * Required fields the draft has emptied while the SAVED profile has them
         * (24 Oct 2026): an emptied display name can't be saved; an emptied city
         * would take a Live profile out of Live.
         */
        get liveRisk() {
            const B = this.base;
            if (!B) return [];
            const had = (v) => String(v || '').trim() !== '';
            const out = [];
            if (had(B.core.display_name) && !had(this.core.display_name)) out.push('display');
            if (this.isPublished && had(B.core.base_city) && !had(this.core.base_city)) out.push('city');
            return out;
        },
        /** The draft would take the profile out of Live (the bar gets a yellow ring). */
        get atRisk() { return this.dirtyCount > 0 && !this.badField && this.liveRisk.length > 0; },
        /** Names of the parts with unsaved changes (their panel headings). */
        get dirtyNames() { return Object.keys(this.dirty).filter((k) => this.dirty[k]).map((k) => (this.S.partNames || {})[k] || k); },
        /** The save bar's first line. */
        get sbTitle() {
            if (this.dirtyCount && this.badField) {
                return this.badField === 'fDisplay' ? this.S.sbBadName : this.badField === 'fUser' ? this.S.sbBadUser : this.S.sbBad;
            }
            if (this.dirtyCount) {
                if (this.liveRisk.includes('display')) return this.S.sbBadName;
                if (this.liveRisk.length) return this.S.sbRisk;
                if (this.narrow) return this.S.sbShort;
                const n = this.dirtyNames;
                return n.length === 1 ? fmt(this.S.sbDirtyOneIn, { name: n[0] }) : fmt(this.S.sbDirtyManyIn, { n: n.length });
            }
            return this.S.sbClean;
        },
        /** The save bar's second line. */
        get sbNote() {
            if (this.dirtyCount && this.badField) {
                return this.badNote || (this.badField === 'fDisplay' ? this.S.sbBadNameN : this.badField === 'fUser' ? this.S.sbBadUserN : this.S.sbBadN);
            }
            if (this.dirtyCount) {
                if (this.liveRisk.includes('display')) return this.S.sbNoNameN;
                if (this.liveRisk.length) return this.S.sbRiskN;
                return this.S.sbDirtyN;
            }
            return this.savedAt ? this.S.sbSavedNow : this.S.sbCleanN;
        },

        /** Client-side check before saving: the two identity fields the prototype requires. */
        invalid() {
            if (!String(this.core.display_name || '').trim()) return 'fDisplay';
            if (!String(this.core.slug || '').trim() || !/^[a-z0-9-]+$/i.test(this.core.slug)) return 'fUser';
            return null;
        },
        /** Stop the save on an error: mark the bar bad and say why. */
        fail(field, e) {
            this.badField = field;
            this.badNote = firstError(e);
        },

        /**
         * Save every dirty part through the existing endpoints, in an order that
         * keeps each step valid: identity → links → pricing → availability →
         * sections (visibility, moves, order) → skills (remove, add,
         * specialities, order — the server derives the first-skill flag) →
         * profile-wide placement → willing to explore. Each part rebases as it
         * lands, so a failure part-way keeps only what is still unsaved dirty.
         */
        async saveAll() {
            if (this.saving || !this.dirtyCount) return;
            const bad = this.dirty.core ? this.invalid() : null;
            if (bad) { this.badField = bad; this.badNote = ''; this.focusField(bad); return; }
            this.badField = null;
            this.badNote = '';
            this.saving = true;
            const d = this.dirty;
            try {
                if (d.core) {
                    this.errors = {};
                    try {
                        const { data } = await patch('/talent/profile', this.core);
                        this.core = { ...this.core, ...this.pickCore(data) };
                        this.base.core = clone(this.core);
                    } catch (e) {
                        if (!(e instanceof ApiError)) throw e;
                        this.errors = e.errors || {};
                        this.fail(this.errors.slug ? 'fUser' : 'fDisplay', e);
                        this.focusField(this.badField);
                        return;
                    }
                }
                if (d.social) {
                    this.socialError = '';
                    try {
                        const { data } = await put('/talent/profile/social', { handles: this.cleanLinks() });
                        this.links = data.handles.map((l) => ({ platform: l.platform, handle: isWeb(l.platform) ? l.handle : String(l.handle).replace(/^@/, ''), _k: 'l' + (this._k++) }));
                        this.base.links = clone(this.links);
                    } catch (e) {
                        if (!(e instanceof ApiError)) throw e;
                        this.socialError = firstError(e);
                        this.fail('links', e);
                        return;
                    }
                }
                if (d.pricing) {
                    this.rateErrors = {};
                    try {
                        const { data } = await patch('/talent/profile/pricing', this.rate);
                        this.rate = { rate_unit: data.rate_unit || '', rate_amount: data.rate_amount ?? '', rate_currency: data.rate_currency || '' };
                        this.base.rate = clone(this.rate);
                    } catch (e) {
                        if (!(e instanceof ApiError)) throw e;
                        this.rateErrors = e.errors || {};
                        this.fail('pricing', e);
                        return;
                    }
                }
                if (d.now) {
                    const { data } = await patch('/talent/profile/now', this.now);
                    this.now = { is_available: !!data.is_available, will_travel: !!data.will_travel };
                    this.base.now = clone(this.now);
                }
                if (d.sections || d.explore || d.brands) await this.saveBlocks();
                if (d.skills) await this.saveSkills();
                if (d.explore) await this.saveInterests();
                this.savedAt = Date.now();
                this.flashSaved();
                toast(this.S.tSaved);
            } catch (e) {
                this.fail('server', e instanceof ApiError ? e : { message: this.S.saveFailed });
            } finally {
                this.saving = false;
                // The steps (and Live itself) follow what is now SAVED.
                this.refreshState();
            }
        },
        /**
         * Re-read the profile path from the server (GET /talent/profile/state):
         * the steps, Live and the Creatives Index follow SAVED data only. Redraws
         * the card + tracker and tells the nav (`fama-gate`).
         */
        async refreshState() {
            try {
                const { data } = await get('/talent/profile/state');
                this.path = data;
                this.isPublished = !!data.is_published;
            } catch (e) {
                return; // keep the last known path; the next save retries
            }
            if (this.gateRefresh) this.gateRefresh();
            window.dispatchEvent(new CustomEvent('fama-gate', {
                detail: { complete: !!this.path.can_go_live, verified: true, live: this.isPublished, listed: !!this.path.listed, done: !!this.path.profile_done },
            }));
        },
        /** Light up the copy-link button once (the profile just went Live). */
        flashLink() {
            this.$nextTick(() => {
                const lk = document.getElementById('copyLink');
                if (!lk) return;
                lk.classList.remove('gt-flash'); void lk.offsetWidth; lk.classList.add('gt-flash');
                setTimeout(() => lk.classList.remove('gt-flash'), 1800);
            });
        },
        /** Sections: visibility, scope moves, then each scope's order. */
        async saveBlocks() {
            const removed = this.removedSkillIds();
            const was = this.blockMap(this.base.blocks);
            const live = this.blocks.filter((b) => was[b.id] && !removed.includes(b.talent_type_id));
            for (const b of live) {
                if (!!b.is_visible !== !!was[b.id].is_visible) {
                    await patch(`/talent/profile/blocks/${b.id}/visibility`, { is_visible: !!b.is_visible });
                }
            }
            for (const b of live) {
                if ((b.talent_type_id ?? null) !== (was[b.id].talent_type_id ?? null)) {
                    await patch(`/talent/profile/blocks/${b.id}/move`, { talent_type_id: b.talent_type_id ?? null });
                }
            }
            const scopes = [null, ...this.skills.filter((s) => !s._new).map((s) => s.id)];
            for (const scope of scopes) {
                const order = this.blocksInScope(scope, true).map((b) => b.id);
                const before = (this.base.blocks || []).filter((b) => (b.talent_type_id ?? null) === scope).sort((a, b) => a.position - b.position).map((b) => b.id);
                if (order.length && JSON.stringify(order) !== JSON.stringify(before)) {
                    await patch('/talent/profile/blocks/reorder', { talent_type_id: scope, order });
                }
            }
            // Re-read: the server re-numbers positions on moves.
            const { data } = await get('/talent/profile/blocks');
            this.blocks = data;
            this.base.blocks = clone(this.blocks);
        },
        /** Skill ids that are saved but removed in the draft. */
        removedSkillIds() {
            const now = this.skills.map((s) => s.id);
            return (this.base?.skills || []).map((s) => s.id).filter((id) => !now.includes(id));
        },
        /** Skills: removals, additions, specialities, then the order. */
        async saveSkills() {
            let payload = null;
            for (const id of this.removedSkillIds()) {
                ({ data: payload } = await del(`/talent/profile/skills/${id}`));
                this.base.skills = this.base.skills.filter((s) => s.id !== id);
            }
            for (const s of this.skills.filter((x) => x._new)) {
                ({ data: payload } = await post('/talent/profile/skills', { talent_type_id: s.id }));
                s._new = false;
            }
            for (const s of this.skills) {
                const sel = this.picked(s.id);
                const was = this.base.specialities[String(s.id)]?.selected || [];
                if (JSON.stringify(sel) !== JSON.stringify(was)) {
                    ({ data: payload } = await put(`/talent/profile/skills/${s.id}/specialities`, { speciality_ids: sel }));
                }
            }
            const order = this.skills.map((s) => s.id);
            ({ data: payload } = await patch('/talent/profile/skills/reorder', { order }).then(async () => get('/talent/profile/skills')));
            this.skills = payload.linked;
            this.specialities = payload.specialities;
            this.base.skills = clone(this.skills);
            this.base.specialities = clone(this.specialities);
            // Added skills seeded their sections; removed ones took theirs away.
            const { data } = await get('/talent/profile/blocks');
            this.blocks = data;
            this.base.blocks = clone(this.blocks);
            this.newSkillId = null;
        },
        /** Willing to explore: removals, edits, then new entries. */
        async saveInterests() {
            const was = Object.fromEntries(this.base.interests.map((w) => [w.id, w]));
            const ids = this.interests.map((w) => w.id);
            let list = null;
            for (const w of this.base.interests.filter((x) => !ids.includes(x.id))) {
                ({ data: { interests: list } } = await del(`/talent/profile/interests/${w.id}`));
            }
            for (const w of this.interests) {
                const body = { talent_type_id: Number(w.talent_type_id), speciality_id: w.speciality_id ? Number(w.speciality_id) : null, note: w.note || null };
                if (typeof w.id === 'string') ({ data: { interests: list } } = await post('/talent/profile/interests', body));
                else if (JSON.stringify(w) !== JSON.stringify(was[w.id])) ({ data: { interests: list } } = await patch(`/talent/profile/interests/${w.id}`, body));
            }
            if (list) this.interests = list.map((w) => ({ id: w.id, talent_type_id: w.talent_type_id, speciality_id: w.speciality_id, note: w.note || '' }));
            this.base.interests = clone(this.interests);
            this.base.blocks = clone(this.blocks);
        },
        /** Throw the draft away; Undo puts it back. */
        discardAll() {
            const draft = this.draftState();
            this.applyState(this.base);
            this.errors = {};
            this.socialError = '';
            this.rateErrors = {};
            this.badField = null;
            this.newSkillId = null;
            toast(this.S.tDiscarded, () => this.applyState(draft));
        },
        // ================= location ==========================================
        /** Country items for the drop list (value = the stored English name). */
        get countryOptions() { return this.countries.map((c) => ({ value: c.name, label: c.name, code: c.code })); },
        /** ISO code of the drafted country ('' when none / unknown). */
        get countryCode() {
            const v = String(this.core.base_country || '').toLowerCase();
            return (this.countries.find((c) => c.name.toLowerCase() === v || c.code.toLowerCase() === v) || {}).code || '';
        },
        /** City items for the drafted country; a stored city not in the list still shows. */
        get cityOptions() {
            const list = (this.cityCache[this.countryCode] || []).map((n) => ({ value: n, label: n }));
            const cur = this.core.base_city;
            return cur && !list.some((o) => o.value === cur) ? [{ value: cur, label: cur }, ...list] : list;
        },
        /** Fetch a country's cities once (cached for the page; concurrent calls share one request). */
        loadCities(code) {
            if (!code) return Promise.resolve();
            if (this.cityCache[code]) return Promise.resolve();
            this.cityLoads ??= {};
            this.cityLoads[code] ??= get(`/locations/${code}/cities`)
                .then(({ data }) => { this.cityCache = { ...this.cityCache, [code]: data || [] }; })
                .catch(() => {})
                .finally(() => { delete this.cityLoads[code]; });
            return this.cityLoads[code];
        },
        /** A country chosen by the talent: keep the city only if it is in that country. */
        async onCountryPicked(item) {
            if (this.errors) this.errors.base_country = null;
            const city = this.core.base_city;
            if (!city) return;
            await this.loadCities(item.code);
            if (this.countryCode === item.code && !(this.cityCache[item.code] || []).includes(city)) this.core.base_city = '';
        },
        /** Scroll to and focus a field by id. */
        focusField(id) {
            this.$nextTick(() => {
                const f = document.getElementById(id);
                if (!f) return;
                f.scrollIntoView({ block: 'center', behavior: 'smooth' });
                f.focus({ preventScroll: true });
            });
        },
        /** The identity keys the PATCH echoes back. */
        pickCore(d) {
            const out = {};
            ['display_name', 'slug', 'base_city', 'base_country'].forEach((k) => { if (k in d) out[k] = d[k] ?? ''; });
            ['headline', 'bio', 'worldview'].forEach((k) => { if (d[k]) out[k] = { en: '', ar: '', ...d[k] }; });
            return out;
        },

        // ================= identity ========================================
        /** Initials for the avatar fallback (from the saved name). */
        get initials() {
            const n = (this.base?.core.display_name || this.core.display_name || this.core.slug || '?').trim();
            return n.split(/\s+/).filter(Boolean).map((w) => w[0]).join('').slice(0, 2).toUpperCase();
        },
        /** The saved display name (the hero shows what is saved). */
        get savedName() { return this.base?.core.display_name || ''; },
        /** True when the display name field should show its error. */
        get nameBad() { return !String(this.core.display_name || '').trim() || !!this.errors.display_name; },
        /** True when the username field should show its error. */
        get userBad() { return !/^[a-z0-9-]+$/i.test(this.core.slug || '') || !!this.errors.slug; },
        /** "1,024 / 5,000" for a counted textarea. */
        count(v, max) { return `${String(v || '').length.toLocaleString('en')} / ${max.toLocaleString('en')}`; },
        /** Append a point-of-view prompt to the textarea. */
        addPrompt(p) {
            const v = this.core.worldview.en || '';
            this.core.worldview.en = (v ? v.trimEnd() + '\n\n' : '') + p + '\n';
            this.$nextTick(() => document.getElementById('fPov')?.focus());
        },
        /** Upload / replace the profile photo now (checked here, then by the server). */
        async uploadAvatar(fileList) {
            const file = fileList && fileList[0];
            if (this.$refs.avatarInput) this.$refs.avatarInput.value = '';
            if (!file) return;
            if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { this.avatarError = this.S.photoType; return; }
            if (file.size > 5 * 1024 * 1024) { this.avatarError = this.S.photoBig; return; }
            this.avatarError = '';
            this.uploadingAvatar = true;
            try {
                const body = new FormData();
                body.append('avatar', file);
                const { data } = await post('/talent/profile/avatar', body);
                this.avatarUrl = data.avatar_url;
                toast(this.S.tPhoto);
                this.refreshState();
            } catch (e) {
                this.avatarError = e instanceof ApiError ? (e.errors?.avatar?.[0] || e.message) : this.S.uploadFailed;
            } finally {
                this.uploadingAvatar = false;
            }
        },
        /** Remove the profile photo now (confirmed — it deletes the file). */
        async removeAvatar() {
            const ok = await ask({ title: this.S.photoRmTitle, message: this.S.photoRmHelp, confirmLabel: this.S.remove, tone: 'danger' });
            if (!ok) return;
            this.uploadingAvatar = true;
            try {
                const { data } = await del('/talent/profile/avatar');
                this.avatarUrl = data.avatar_url;
                toast(fmt(this.S.tRemoved, { name: this.S.photo }));
                this.refreshState();
            } catch (e) {
                this.avatarError = e instanceof ApiError ? e.message : this.S.uploadFailed;
            } finally {
                this.uploadingAvatar = false;
            }
        },

        // ================= Live / Private ==================================
        /** "fama.test/username" — the SAVED address (only ever shown while Live). */
        get linkLabel() { return `${this.host}/${this.base?.core.slug || ''}`; },
        /** Listed in the Creatives Index (Live + something to show). */
        get isListed() { return !!this.path.listed; },
        /** Held private on purpose (Unpublished): the Go Live steps alone don't bring it back. */
        get isHeld() { return !this.isPublished && this.path.status === 'unpublished'; },
        /** Taken down by Fama (Suspended). */
        get isSuspended() { return this.path.status === 'suspended'; },
        /** Copy the profile link (rendered only while Live). */
        async copyLink() {
            if (!this.isPublished) return;
            try { await navigator.clipboard.writeText(`${window.location.origin}/${this.base.core.slug}`); } catch (e) { /* label still shows it */ }
            this.copied = true;
            this.note = this.S.copiedA;
            setTimeout(() => { this.copied = false; this.note = ''; }, 2000);
        },
        /**
         * Go Live by hand — only for a profile HELD private (Unpublished). Every
         * other profile goes Live by itself once its Go Live steps are saved.
         */
        async setPublished(on) {
            if (this.publishing) return;
            this.publishing = true;
            this.publishError = '';
            try {
                const { data } = await patch('/talent/profile/publish', { publish: on });
                this.isPublished = !!data.is_published;
                toast(this.isPublished ? this.S.tLive : this.S.tPrivate);
                this.refreshState();
            } catch (e) {
                if (e instanceof ApiError) this.publishError = e.message;
            } finally {
                this.publishing = false;
            }
        },

        // ================= availability ====================================
        /** Flip one availability switch (draft). */
        flipNow(key) { this.now[key] = !this.now[key]; },
        /** "Brands see you'll take jobs outside Cairo." */
        travelText(on) { return fmt(on ? this.S.travelOn : this.S.travelOff, { city: this.core.base_city || this.S.yourCity }); },
    }, listsMixin()));
});
