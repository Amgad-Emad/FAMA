/**
 * Profile structure (GET /talent/profile) — the fama-ui "profile-structure.html"
 * screen, Alpine components registered on alpine:init:
 *
 *   profileStructure — the whole page as ONE DRAFT: identity, availability,
 *                      links, skills (order / add / remove / specialities),
 *                      the Explore / Experience show switches,
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
 * The list editors (links, skills, picker, show switches, explore) are a mixin in
 * ./profile-structure-lists.js.
 *
 * Product rules (2026-10-12): no separately chosen primary skill — the order is
 * the tab order and the first opens first (the server derives is_primary);
 * a Private profile shows its link LOCKED (prototype 9 Oct 2026, ADR-AH).
 */
import { del, get, patch, post, put, upload, ApiError } from '../http';
import { tr, clone, fmt, toast, ask } from './profile-helpers';
import { PLATFORMS, socialIcon, isWeb, urlOf } from './social';
import { listsMixin, merge } from './profile-structure-lists';
import './profile-views';
import { famaGate } from './profile-gate';
import * as FX from './practice-fields';
import * as XP from './experience';

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
        // "About your practice" values, once per profile, keyed by field id (ADR-AG).
        practice: clone((initial.practice || {}).values || {}),
        // Experience roles (ADR-AK), part of the draft like everything else here.
        roles: clone((initial.experience || {}).roles || []),

        // ----- the last saved state (set in init) --------------------------
        base: null,

        // ----- not part of the draft ---------------------------------------
        allSkills: initial.interestSkills || [],
        xpEmployment: (initial.experience || {}).employment || [],
        xpLocationTypes: (initial.experience || {}).locationTypes || [],
        xpMonths: (initial.experience || {}).months || [],
        // The Add/Edit role dialog.
        roleAt: null,
        roleForm: {},
        roleErrors: {},
        hasPricing: !!initial.hasPricing,
        // The profile path from SAVED data: { steps, can_go_live, is_published, listed, profile_done, status, stage }.
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
        newSkillId: null,
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
        // The practice-field catalog (App\\Support\\PracticeFields) — not part of the draft.
        fxDefs: (initial.practice || {}).defs || {},
        fxBySkill: (initial.practice || {}).bySkill || {},
        digitalsUrl: (initial.practice || {}).digitalsUrl || '#',
        picker: { open: false, title: '', help: '', ok: '', exclude: [], current: null, q: '', resolve: null },

        /** Snapshot the saved state, wire Ctrl/Cmd+S, the leave guard and #hash landing. */
        init() {
            this.rootEl = this.$root; // `$root` is undefined from a swapped-out row
            this.rebase();
            this.bindPractice();
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
                    // ADR-AQ: finishing the steps makes the profile Private; the card offers Go Live.
                    stage: () => this.stage,
                    goLive: () => this.setPublished(true),
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
                practice: this.practice, roles: this.roles,
            });
        },
        /** Put a state back into the draft (Discard, Undo). */
        applyState(st) {
            const c = clone(st);
            Object.assign(this, c);
            this.$nextTick(() => this.redrawFields());
        },
        /** The current draft becomes the saved state. */
        rebase() { this.base = this.draftState(); },

        // ================= About your practice (ADR-AG) =====================
        /** Practice values with empties dropped and keys sorted — comparable. */
        practiceSig(v) {
            const out = {};
            Object.keys(v || {}).sort().forEach((k) => {
                if (this.fxDefs[k] && FX.filled(this.fxDefs, k, v[k])) out[k] = v[k];
            });
            return JSON.stringify(out);
        },
        /** The practice draft differs from what is saved. */
        get practiceDirty() { return !!this.base && this.practiceSig(this.practice) !== this.practiceSig(this.base.practice); },
        /** Skill order or specialities differ from what is saved. */
        get skillsChanged() {
            const B = this.base;
            if (!B) return false;
            const j = JSON.stringify;
            return j(this.skills.map((s) => s.id)) !== j(B.skills.map((s) => s.id))
                || this.skills.some((s) => j(this.picked(s.id)) !== j(B.specialities[String(s.id)]?.selected || []));
        },
        /** The fields one skill asks for ([{ id, opt }]). */
        fxFor(s) { return FX.forSkill(this.fxBySkill, s.slug); },
        /** "2 of 5 added" for a skill's practice details. */
        fxCount(s) {
            const l = this.fxFor(s);
            const got = l.filter((f) => FX.filled(this.fxDefs, f.id, this.practice[f.id])).length;
            return fmt(this.S.fxCount || ':n of :m added', { n: got, m: l.length });
        },
        /** The editor HTML for one field of one skill. */
        fxHtml(sk, f) {
            return FX.editor(f.id, this.practice[f.id], f.opt, { defs: this.fxDefs, all: this.practice, digitalsUrl: this.digitalsUrl });
        },
        /** Fill a skill's fields box (x-init on each skill section). */
        mountFields(el, s) {
            el.innerHTML = this.fxFor(s).map((f) => this.fxHtml(s.slug, f)).join('');
        },
        /** Redraw every skill's fields (after Discard / Undo / save). */
        redrawFields() {
            this.rootEl.querySelectorAll('.fx-fields[data-s]').forEach((box) => {
                const s = this.skills.find((x) => x.slug === box.dataset.s);
                if (s) this.mountFields(box, s);
            });
        },
        /**
         * One delegated listener for every field on the page. Typing only
         * refreshes the small read-outs (the caret stays); anything else redraws
         * that field wherever it appears (two skills can share one), and Reach
         * when Platforms change.
         */
        bindPractice() {
            const root = this.rootEl.querySelector('#skillList');
            if (!root) return;
            FX.bind(root, this.fxDefs, () => this.practice, (id, el, typing) => {
                if (typing) {
                    const box = el.closest('.fld'); const v = this.practice[id];
                    const out = box.querySelector('[data-out]');
                    if (out) out.textContent = FX.inchOut(v);
                    const er = box.querySelector('[data-err]');
                    if (er) { const r = v || {}; er.hidden = !(r.min && r.max && r.max - r.min < 5); }
                    return;
                }
                const sk = el && el.closest('.skill')?.dataset.s;
                const focusSel = el ? (el.dataset.opt != null ? `[data-opt="${CSS.escape(el.dataset.opt)}"]${el.dataset.g != null ? `[data-g="${el.dataset.g}"]` : ''}`
                    : el.dataset.k ? `[data-k="${CSS.escape(el.dataset.k)}"]` : el.hasAttribute('data-addpair') ? '[data-addpair]' : null) : null;
                [id].concat(id === 'platforms' ? ['reach'] : []).forEach((fid) => {
                    root.querySelectorAll(`.fld[data-fld="${fid}"]`).forEach((box) => {
                        const s = this.skills.find((x) => x.slug === box.closest('.fx-fields').dataset.s);
                        const f = s && this.fxFor(s).find((x) => x.id === fid);
                        if (f) box.outerHTML = this.fxHtml(s.slug, f);
                    });
                });
                if (focusSel && sk) root.querySelector(`.skill[data-s="${sk}"] .fld[data-fld="${id}"] ${focusSel}`)?.focus({ preventScroll: true });
            }, (id, was, name) => {
                toast(fmt(this.S.fxCleared || ':name cleared', { name }), () => {
                    this.practice[id] = was;
                    this.redrawFields();
                });
            });
        },
        // ================= Experience roles (ADR-AK) ========================
        /** The roles differ from what is saved. */
        get rolesDirty() { return !!this.base && JSON.stringify(this.roles) !== JSON.stringify(this.base.roles || []); },
        /** The grouped list with a pencil per role (prototype FAMA_EXP.html). */
        xpHtml() {
            const name = (id) => this.skills.find((s) => s.id === id)?.name ?? '';
            return XP.html(this.roles, { edit: true, skill: (id) => tr(name(id)) });
        },
        /** A pencil in the list opens that role. */
        onExpClick(e) {
            const b = e.target.closest('[data-edit-role]');
            if (b) this.openRole(+b.dataset.editRole);
        },
        /** Open the Add / Edit role dialog. */
        openRole(i) {
            this.roleAt = i;
            const r = i == null ? { t: '', n: '', et: '', loc: this.core.base_city || '', lt: '', cur: false, sm: 0, sy: 0, em: 0, ey: 0, hl: '', sk: [] } : this.roles[i];
            this.roleForm = clone(r);
            this.roleErrors = {};
            this.$refs.roleDlg.showModal();
            this.$nextTick(() => this.$refs.roleTitle?.focus());
        },
        /** Years for the start/end pickers: this year back 60. */
        get roleYears() { const y = new Date().getFullYear(); return Array.from({ length: 60 }, (_, k) => y - k); },
        /** Toggle a skill this role used. */
        toggleRoleSkill(id) {
            const sk = this.roleForm.sk || [];
            this.roleForm.sk = sk.includes(id) ? sk.filter((x) => x !== id) : sk.concat(id);
        },
        /** Validate (title, company, start year, end after start) and put the role into the draft. */
        saveRole() {
            const r = { ...this.roleForm, t: (this.roleForm.t || '').trim(), n: (this.roleForm.n || '').trim(), loc: (this.roleForm.loc || '').trim(), hl: (this.roleForm.hl || '').trim(),
                sm: +this.roleForm.sm || 0, sy: +this.roleForm.sy || 0, em: +this.roleForm.em || 0, ey: +this.roleForm.ey || 0 };
            if (r.cur) { r.em = 0; r.ey = 0; }
            const order = !r.cur && r.ey && r.sy && (r.ey * 12 + (r.em || 12)) < (r.sy * 12 + (r.sm || 1));
            this.roleErrors = { t: !r.t, n: !r.n, start: !r.sy ? this.S.roleNeedY : (order ? this.S.roleOrder : '') };
            const bad = !r.t ? 'roleTitle' : !r.n ? 'roleOrg' : this.roleErrors.start ? 'roleSy' : null;
            if (bad) { this.$refs[bad]?.focus(); return; }
            const was = this.roleAt;
            if (was == null) this.roles.push(r); else this.roles.splice(was, 1, r);
            this.$refs.roleDlg.close();
            toast(fmt(was == null ? this.S.roleAdded : this.S.roleUpdated, { r: r.t }));
        },
        /** Delete the open role from the draft, with Undo. */
        deleteRole() {
            const i = this.roleAt; const old = this.roles[i];
            this.$refs.roleDlg.close();
            this.roles.splice(i, 1);
            toast(fmt(this.S.roleRemoved, { r: old.t }), () => { this.roles.splice(i, 0, old); });
        },
        /** Save the roles (the server answers with them, ids included). */
        async saveRoles() {
            const { data } = await put('/talent/profile/experience', { roles: this.roles });
            this.roles = clone(data.roles || []);
            this.base.roles = clone(this.roles);
        },

        /** Save the practice values (the server answers with the values it kept). */
        async savePractice() {
            const { data } = await put('/talent/profile/practice', { fields: this.practice });
            this.practice = clone(data.values || {});
            this.base.practice = clone(this.practice);
            this.$nextTick(() => this.redrawFields());
        },

        /** Links as the API stores them (empty handles dropped). */
        cleanLinks(list = this.links) {
            return list.filter((l) => String(l.handle || '').trim())
                .map((l) => ({ platform: l.platform, handle: String(l.handle).trim(), url: urlOf(l.platform, l.handle) }));
        },
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
            const specsDirty = this.skills.some((s) => j(this.picked(s.id)) !== j(B.specialities[String(s.id)]?.selected || []));
            return {
                core: j(this.core) !== j(B.core),
                now: j(this.now) !== j(B.now),
                social: j(this.cleanLinks()) !== j(this.cleanLinks(B.links)),
                skills: j(this.skills.map((s) => s.id)) !== j(B.skills.map((s) => s.id)) || specsDirty || this.practiceDirty,
                explore: j(this.interests) !== j(B.interests) || this.switchDirty('interests'),
                brands: this.switchDirty('brand_collabs') || this.rolesDirty,
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
         * Explore / Experience show switches → skills (remove, add,
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
                if (this.switchDirty('interests') || this.switchDirty('brand_collabs')) await this.saveBlocks();
                if (d.skills && this.skillsChanged) await this.saveSkills();
                if (this.practiceDirty) await this.savePractice();
                if (this.rolesDirty) await this.saveRoles();
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
        /**
         * The Explore / Experience show switches: each changed universal block's
         * visibility, then a re-read of the blocks so the draft rebases.
         */
        async saveBlocks() {
            const was = this.blockMap(this.base.blocks);
            for (const key of ['interests', 'brand_collabs']) {
                const b = this.uniBlock(key);
                if (b && was[b.id] && !!b.is_visible !== !!was[b.id].is_visible) {
                    await patch(`/talent/profile/blocks/${b.id}/visibility`, { is_visible: !!b.is_visible });
                }
            }
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
                const { data } = await window.famaUploading({ kind: 'avatar' },
                    (onProgress) => upload('/talent/profile/avatar', body, { onProgress }));
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
        /**
         * Where the profile is on Draft → Private → Live (ADR-AQ), from SAVED
         * data: 'draft' | 'private' | 'live' | 'suspended' | 'archived'.
         */
        get stage() {
            if (this.isPublished) return 'live';
            const st = this.path.stage;
            return st && st !== 'live' ? st : (this.path.status === 'unpublished' ? 'private' : 'draft');
        },
        /** The state pill's word. */
        get stageLabel() { return this.stage === 'live' ? this.S.stLive : this.stage === 'private' ? this.S.stPrivate : this.stage === 'draft' ? this.S.stDraft : this.S.stHeld; },
        /** Private with every Go Live step saved: the talent can go Live now. */
        get canPublish() { return this.stage === 'private' && !!this.path.can_go_live; },
        /** Taken down by Fama (Suspended). */
        get isSuspended() { return this.path.stage === 'suspended' || this.path.status === 'suspended'; },
        /** Copy the profile link (rendered only while Live). */
        async copyLink() {
            if (!this.isPublished) {
                // Locked (prototype 9 Oct 2026): say why — Private only needs Go Live; a Draft needs the steps.
                if (this.stage === 'private') { toast(this.S.copyOffPrivate); return; }
                toast(this.S.copyOffN);
                const g = document.getElementById('gate');
                if (g && !g.hidden) g.scrollIntoView({ block: 'start', behavior: 'smooth' });
                return;
            }
            try { await navigator.clipboard.writeText(`${window.location.origin}/${this.base.core.slug}`); } catch (e) { /* label still shows it */ }
            this.copied = true;
            this.note = this.S.copiedA;
            setTimeout(() => { this.copied = false; this.note = ''; }, 2000);
        },
        /**
         * Private ⇄ Live — the talent's own call (ADR-AQ: Draft → Private → Live).
         * Saving the Go Live steps only makes a profile Private; this is the one
         * step that makes the link work. Making it Private again offers Undo.
         */
        async setPublished(on) {
            if (this.publishing) return;
            this.publishing = true;
            this.publishError = '';
            try {
                const { data } = await patch('/talent/profile/publish', { publish: on });
                this.isPublished = !!data.is_published;
                this.path = { ...this.path, status: data.status, stage: this.isPublished ? 'live' : 'private', is_published: this.isPublished };
                if (this.isPublished) this.flashLink();
                toast(this.isPublished ? this.S.tLive : this.S.tPrivate, this.isPublished ? null : () => this.setPublished(true));
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
