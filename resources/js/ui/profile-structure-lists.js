/**
 * The list editors of Profile structure (a mixin merged into profileStructure):
 * social links (max 9), skills (order = tab order, the first opens first),
 * the shared skill picker, the Explore / Experience show switches and
 * willing to explore (max 6).
 * Every change here edits the draft; the save bar commits it.
 */
import { tr, clone, fmt, toast, ask } from './profile-helpers';
import { SOCIAL, detectPlatform, isWeb, handleOf, urlOf } from './social';

/** Merge objects keeping getters as getters (a spread would evaluate them). */
export const merge = (...objs) => objs.reduce((t, o) => Object.defineProperties(t, Object.getOwnPropertyDescriptors(o)), {});

/** The list-editing methods and getters of profileStructure. */
export const listsMixin = () => ({
    // ================= social links (max 9) ============================
    /** The fixed address before the username. */
    prefix(id) { return SOCIAL[id]?.prefix || ''; },
    /** Display name of a platform. */
    platName(id) { return SOCIAL[id]?.name || (id === 'website' ? this.S.website : this.S.other); },
    /** Platforms already used by another row. */
    usedBy(i) { return this.links.filter((l, j) => j !== i).map((l) => l.platform); },
    /** Nine is the cap (talent_social_handles; the server checks it too). */
    get linksFull() { return this.links.length >= 9; },
    /** The dashed empty row: when there are no links, or after "Add another link". */
    get showNewRow() { return !this.linksFull && (this.showNew || this.links.length === 0); },
    /** Open / close a row's platform menu (i = row index or 'new'). */
    togglePlat(i) {
        this.platOpen = this.platOpen === i ? null : i;
        if (this.platOpen !== null) this.$nextTick(() => this.rootEl.querySelector('.platmenu:not([style*="none"]) [aria-selected="true"], .platmenu:not([style*="none"]) .pm-o')?.focus({ preventScroll: true }));
    },
    /** Keyboard inside the platform menu: arrows move, Escape closes. */
    platKey(e) {
        const os = [...e.currentTarget.querySelectorAll('.pm-o:not([aria-disabled=true])')];
        const i = os.indexOf(document.activeElement);
        if (e.key === 'Escape') { e.preventDefault(); this.platOpen = null; }
        else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            os[(i + (e.key === 'ArrowDown' ? 1 : -1) + os.length) % os.length]?.focus();
        } else if (e.key === 'Tab') this.platOpen = null;
    },
    /** Pick a platform for a row; the username carries over. */
    setPlat(i, id) {
        this.platOpen = null;
        if (i === 'new') { this.newRow.platform = id; this.focusLink('new'); return; }
        if (this.usedBy(i).includes(id) && !isWeb(id)) return;
        const l = this.links[i];
        const h = handleOf(urlOf(l.platform, l.handle), l.platform);
        l.platform = id;
        l.handle = isWeb(id) ? (h ? urlOf(id, h).replace(/^https?:\/\//, '') : '') : h;
        this.focusLink(i);
    },
    /** Typing in a row: a pasted link switches the row to its platform and keeps the username. */
    onLinkInput(i, e) {
        let v = e.target.value;
        if (i === 'new') {
            if (!v.trim() || this.linksFull) return;
            const d = /[./]/.test(v) ? detectPlatform(v) : null;
            const id = d && !isWeb(d) && !this.links.some((x) => x.platform === d) ? d : this.newRow.platform;
            this.links.push({ platform: id, handle: isWeb(id) ? v : handleOf(v, id), _k: 'l' + (this._k++) });
            this.newRow = { platform: 'website' };
            this.showNew = false;
            this.focusLink(this.links.length - 1, true);
            return;
        }
        const l = this.links[i];
        const d = /[./]/.test(v) ? detectPlatform(v) : null;
        if (d && !isWeb(d) && d !== l.platform && !this.usedBy(i).includes(d)) {
            l.platform = d;
            l.handle = handleOf(v, d);
            this.focusLink(i, true);
            return;
        }
        if (!isWeb(l.platform) && /[/@]/.test(v)) {
            const h = handleOf(v, l.platform);
            if (h !== v) { e.target.value = h; v = h; }
        }
        l.handle = v;
    },
    /** Focus a link row's input (optionally with the caret at the end). */
    focusLink(i, end = false) {
        this.$nextTick(() => {
            const f = this.rootEl.querySelector(`[data-link="${i}"]`);
            if (!f) return;
            f.focus();
            if (end) f.setSelectionRange(f.value.length, f.value.length);
        });
    },
    /** Remove a row (Undo puts it back). */
    removeLink(i) {
        const [old] = this.links.splice(i, 1);
        toast(fmt(this.S.tRemoved, { name: this.platName(old.platform) }), () => this.links.splice(i, 0, old));
    },

    // ================= skills (order = tab order) ======================
    /** The skill's display name. */
    skName(s) { return tr(s.name); },
    /** Speciality options for one skill (draft-added skills read the full catalog). */
    specialityOptions(typeId) { return this.specialities[String(typeId)]?.options || []; },
    /** The picked speciality ids of one skill. */
    picked(typeId) { return this.specialities[String(typeId)]?.selected || []; },
    /** Whether a speciality is picked. */
    specialityPicked(typeId, id) { return this.picked(typeId).includes(id); },
    /** Toggle a speciality (draft). */
    toggleSpeciality(typeId, id) {
        const bucket = this.specialities[String(typeId)];
        if (!bucket) return;
        bucket.selected = bucket.selected.includes(id) ? bucket.selected.filter((x) => x !== id) : [...bucket.selected, id];
    },
    /** Skills that can still be added. */
    get canAddSkill() { return this.allSkills.some((s) => !this.skills.some((x) => x.id === s.id)); },
    /**
     * Make a skill the primary one (draft): it moves to the front, so it is the
     * first tab and opens first. No other reordering (13 Nov 2026 review).
     */
    makePrimary(s) {
        const from = this.skills.findIndex((x) => x.id === s.id);
        if (from <= 0) return;
        const [m] = this.skills.splice(from, 1);
        this.skills.unshift(m);
        this.note = fmt(this.S.skPrimary, { name: this.skName(m) });
        this.$nextTick(() => this.rootEl.querySelector(`.sk[data-s="${s.id}"] .skrm`)?.focus({ preventScroll: true }));
    },
    /** Add a skill through the picker (draft; its sections are seeded on save). */
    async addSkill() {
        const id = await this.openPicker({ exclude: this.skills.map((s) => s.id), title: this.S.addTitle, help: this.S.addHelp, ok: this.S.addOk });
        if (!id) return;
        const was = (this.base.skills || []).find((s) => s.id === id);
        const cat = this.allSkills.find((s) => s.id === id);
        this.skills.push(was ? clone(was) : { id, slug: cat.slug, name: cat.name, category: cat.category ?? null, _new: true });
        if (!this.specialities[String(id)]) {
            const prev = this.base.specialities[String(id)];
            this.specialities[String(id)] = prev ? clone(prev) : { options: cat.specialities.map((x) => ({ id: x.id, name: x.name, slug: '' })), selected: [] };
        }
        this.newSkillId = id;
        toast(fmt(this.S.tAdded, { name: cat.name }));
        this.$nextTick(() => {
            const r = this.rootEl.querySelector(`.sk[data-s="${id}"]`);
            r?.scrollIntoView({ block: 'center', behavior: 'smooth' });
            (r?.querySelector('.chip') || r)?.focus?.({ preventScroll: true });
        });
    },
    /** Remove a skill (draft, confirmed; Undo puts it back where it was). */
    async removeSkill(s) {
        const name = this.skName(s);
        const ok = await ask({ title: fmt(this.S.rmTitle, { name }), message: this.S.rmHelp, confirmLabel: this.S.rmOk, tone: 'danger' });
        if (!ok) return;
        const i = this.skills.findIndex((x) => x.id === s.id);
        const [old] = this.skills.splice(i, 1);
        toast(fmt(this.S.tRemoved, { name }), () => this.skills.splice(i, 0, old));
    },

    // ================= the shared skill picker =========================
    /** Open the searchable skill dialog; resolves with a skill id or null. */
    openPicker(o) {
        return new Promise((resolve) => {
            this.picker = { open: true, title: o.title, help: o.help, ok: o.ok, exclude: o.exclude || [], current: o.current || null, q: '', resolve };
            this.$nextTick(() => {
                const d = this.$refs.dlgAdd;
                if (!d.open) d.showModal();
                this.$refs.pickSearch?.focus();
            });
        });
    },
    /** The skills left to pick, matching the search by name or by speciality. */
    get pickerList() {
        const q = this.picker.q.trim().toLowerCase();
        return this.allSkills.filter((s) => !this.picker.exclude.includes(s.id)).map((s) => {
            const name = String(s.name || '');
            // A speciality match still finds the skill; the list shows the skill name only
            // (the matched speciality under it was removed 2026-11-23).
            const via = !q || name.toLowerCase().includes(q) ? false : s.specialities.some((x) => String(x.name).toLowerCase().includes(q));
            return { id: s.id, name, hit: !q || name.toLowerCase().includes(q) || via };
        }).filter((s) => s.hit);
    },
    /** The checked option (or the first match). */
    get currentPick() {
        const list = this.pickerList;
        return list.some((s) => s.id === this.picker.current) ? this.picker.current : (list[0]?.id ?? null);
    },
    /** Close the picker with a result. */
    closePicker(value) {
        const r = this.picker.resolve;
        this.picker.resolve = null;
        this.picker.open = false;
        if (this.$refs.dlgAdd.open) this.$refs.dlgAdd.close();
        if (r) r(value);
    },

    // ================= section switches (Explore / Experience) =========
    // The Sections panel (per-tab show / hide / reorder / move) was removed
    // on 2026-11-23: a profile shows its seeded sections in their seeded
    // order. Only the two profile-wide switches on Willing to explore and
    // Experience remain, and they still save through the visibility endpoint.
    /** A block's display name (admin-governed; read-only). */
    blockName(b) { return tr(b.title) || tr(b.block_type.name); },
    /** The universal block of a key (the switches on Explore / Experience). */
    uniBlock(key) { return this.blocks.find((b) => (b.talent_type_id ?? null) === null && b.block_type.key === key) || null; },
    /** Show / hide a profile-wide block (draft; content is kept). */
    toggleVisible(b) {
        b.is_visible = !b.is_visible;
        if (!b.is_visible) this.note = fmt(this.S.offA, { name: this.blockName(b) });
    },

    // ================= willing to explore (max 6) ======================
    /** The specialities of a skill (from the full catalog). */
    skillSpecs(typeId) { return this.allSkills.find((s) => s.id === Number(typeId))?.specialities || []; },
    /** A skill's name from the full catalog. */
    allSkillName(typeId) { return this.allSkills.find((s) => s.id === Number(typeId))?.name || ''; },
    /** Change a row's skill through the picker (its speciality resets). */
    async pickInterestSkill(w) {
        const id = await this.openPicker({
            exclude: this.interests.filter((x) => x !== w).map((x) => x.talent_type_id),
            current: w.talent_type_id, title: this.S.wtePickT, help: this.S.wtePickH, ok: this.S.wtePickOk,
        });
        if (!id || id === w.talent_type_id) return;
        w.talent_type_id = id;
        w.speciality_id = null;
    },
    /** Add a direction: it starts with the skill, so no row ever lacks one. */
    async addInterest() {
        if (this.interests.length >= 6) return;
        const id = await this.openPicker({ exclude: this.interests.map((x) => x.talent_type_id), title: this.S.wtePickT, help: this.S.wtePickH, ok: this.S.wtePickOk });
        if (!id) return;
        this.interests.push({ id: 'new' + (++this._tmp), talent_type_id: id, speciality_id: null, note: '' });
    },
    /** Remove a direction (Undo puts it back). */
    removeInterest(w) {
        const i = this.interests.indexOf(w);
        this.interests.splice(i, 1);
        toast(fmt(this.S.tRemoved, { name: this.allSkillName(w.talent_type_id) }), () => this.interests.splice(i, 0, w));
    },
    /** Company initials for the experience logo fallback. */
});
