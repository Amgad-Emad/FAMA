/**
 * The project editor (new + edit) — port of fama-ui project-editor.js (24 Oct –
 * 13 Nov 2026, ADR-AN). The project is edited on a page that looks like the
 * published one; the story is FREE-FORM BLOCKS (text · heading · quote · image ·
 * photos · before/after · look with tags · video · social post · results ·
 * credits), added anywhere, moved by grip or arrows, each with a More menu.
 *
 * Data: `blocks` mirror `projects.blocks` (App\Support\ProjectBlocks) plus a
 * client-only `key` per block/item. A picture slot holds a saved media id (`m`,
 * `mb`, `ma`) and/or a STAGED file key (`s`, `sb`, `sa`) while it waits to
 * upload — before Publish, or for the second it takes. Results rows stay in
 * `form.results` and credits in `credits` (their blocks only mark WHERE).
 *
 * Saving: a new project is created by Publish (POST), its staged cover /
 * pictures / credits sent right after, then the blocks saved again with the
 * pictures' ids. After that every change autosaves (PATCH, 700ms) and a picked
 * file uploads at once with `place=0` (the editor places its own pictures).
 * Runtime copy comes from Blade (`strings`).
 */
import { fmt, initials, toast, firstError, ask } from './project-util';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;
    const { get, post, patch, del, ApiError } = window.fama;

    let seq = 0;
    const nextKey = (p = 'k') => `${p}${++seq}`;
    const two = (n) => String(n).padStart(2, '0');
    const WORDS = ['text', 'heading', 'quote'];
    const SLOT = { m: 's', mb: 'sb', ma: 'sa' };
    const VIDEO_RE = /^https?:\/\/([\w-]+\.)*(youtube\.com|youtu\.be|vimeo\.com)\//i;

    /** A new block of type t, as the prototype's `fresh()` makes it. */
    const fresh = (t) => ({
        text: { t, v: '' },
        heading: { t, v: '' },
        quote: { t, v: '', by: '' },
        image: { t, m: null, s: null, cap: '' },
        gallery: { t, items: [] },
        beforeafter: { t, mb: null, sb: null, ma: null, sa: null, lb: 'Before', la: 'After', cap: '' },
        look: { t, m: null, s: null, title: '', items: [] },
        video: { t, url: '', cap: '' },
        embed: { t, url: '' },
        results: { t },
        credits: { t },
    }[t] || { t: 'text', v: '' });

    /** A stored block, with the client-only keys x-for needs. */
    const withKeys = (b) => {
        const out = { ...fresh(b.t), ...JSON.parse(JSON.stringify(b)), key: nextKey('b') };
        if (out.items && b.t === 'gallery') out.items = out.items.map((it) => ({ m: it.m ?? null, s: null, cap: it.cap || '', key: nextKey('g') }));
        if (out.items && b.t === 'look') out.items = out.items.map((x) => ({ n: x.n || '', d: x.d || '', x: x.x ?? 50, y: x.y ?? 50, key: nextKey('p') }));

        return out;
    };

    /** A result row as the editor holds it (a stable key for x-for). */
    const resultRow = (r = {}) => ({ key: nextKey('r'), label: r.label || '', value: r.value || '', compare: r.compare || '' });

    Alpine.data('famaProjectEdit', ({
        project = null, skills = [], specialities = {}, allSkills = [], slug = '', live = false, editUrl = '', projectsUrl = '',
        strings = {}, projectCount = 0, me = {},
    } = {}) => ({
        docked: document.documentElement.classList.contains('fx-has-side') && window.matchMedia('(min-width:1101px)').matches,
        touch: window.matchMedia('(hover:none)').matches,
        featured: project ? !!project.is_featured : projectCount === 0,
        featuredLocked: project ? !!project.featured_locked : projectCount === 0,
        canFeature: project ? !!project.can_feature : true,
        hasCoOwners: !!project?.has_co_owners,
        featuring: false,
        skills,
        specialities,
        allSkills,
        slug,
        live,
        me,
        editUrlTemplate: editUrl,
        projectsUrl,
        strings,
        initials,
        fmt,

        id: project?.id ?? null,
        form: {
            title: { en: '', ar: '', ...(project?.title || {}) },
            role: { en: '', ar: '', ...(project?.role || {}) },
            client_name: project?.client_name || '',
            client_confidential: !!project?.client_confidential,
            url: project?.url || '',
            occurred_on: project?.occurred_on || '',
            skill_ids: [...(project?.skill_ids || [])],
            speciality_ids: [...(project?.speciality_ids || [])],
            results: (project?.results || []).slice(0, 4).map(resultRow),
        },
        originalDate: project?.occurred_on || '',

        // The story. 13 Nov 2026 review: a NEW project opens with Add photos ready.
        blocks: (project
            ? (project.blocks?.length ? project.blocks : [{ t: 'text', v: '' }])
            : [{ t: 'gallery', items: [] }, { t: 'text', v: '' }]).map(withKeys),
        media: project?.media || [],     // [{ id, url, is_video }] — everything the blocks can point at
        staged: {},                      // key → { file, url, video, busy }
        dragFrom: null,
        credits: project?.credits || [],
        drafts: [],
        coverUrl: project?.cover_image_url || null,
        coverFile: null,
        coverBusy: false,
        active: null,

        dirty: false,
        saving: false,
        resave: false,
        publishing: false,
        saveState: project ? 'ok' : 'none',
        errors: {},
        saveTimer: null,
        savedText: project ? strings.saved : strings.notSaved,
        menu: { open: false, items: [], top: 0, left: 0, hl: 0, onPick: null, anchor: null },
        picker: { title: '', many: false, video: false, chosen: [], onDone: null },
        panelMounted: false,
        panelOpen: false,
        panelReturn: null,

        /** Single-skill talents never think about the skill tag; narrow screens hand the header to the bar. */
        init() {
            if (!this.id && this.skills.length) {
                const primary = this.skills.find((s) => s.is_primary) || this.skills[0];
                this.form.skill_ids = [primary.id];
            }
            if (!this.docked) document.querySelector('.fx-nav')?.classList.add('fx-editor');
        },

        // --- Derived ------------------------------------------------------

        get homeSkillId() { return this.form.skill_ids[0] ?? null; },

        get displayName() { return (this.form.title.en || '').trim() || this.strings.untitled; },

        get savedDot() {
            return { busy: this.saveState === 'busy' || this.saveState === 'none', bad: this.saveState === 'bad' };
        },

        get year() { return /^\d{4}/.test(this.form.occurred_on || '') ? this.form.occurred_on.slice(0, 4) : ''; },

        /** The cover's kicker: client (or "Confidential client") · first skill. */
        get kicker() {
            const first = this.skills.find((s) => s.id === this.homeSkillId);
            const client = this.form.client_confidential ? this.strings.confidential : (this.form.client_name || '').trim();
            return [client, first?.name].filter(Boolean).join(' · ');
        },

        get monthValue() { return (this.form.occurred_on || '').slice(0, 7); },

        get previewUrl() { return this.id && this.live && this.slug ? `/${this.slug}/work/${this.id}?preview=1` : ''; },

        get selectedSkills() {
            return this.form.skill_ids.map((id) => this.skills.find((s) => s.id === id)).filter(Boolean);
        },

        get selectedSpecialities() {
            const all = Object.values(this.specialities).flat();
            return this.form.speciality_ids.map((id) => all.find((s) => s.id === id)).filter(Boolean);
        },

        /** The opening line: the first text block before any heading. */
        get leadKey() {
            for (const b of this.blocks) {
                if (b.t === 'heading') return null;
                if (b.t === 'text') return b.key;
            }
            return null;
        },

        /** This project's own pictures, for the picker's "pick one of yours". */
        get pool() {
            return this.media.filter((m) => !m.is_video || this.picker.video);
        },

        /** How many blocks still name a confidential client. */
        get ndaHits() {
            const name = (this.form.client_name || '').trim().toLowerCase();
            if (!this.form.client_confidential || !name) return 0;
            return this.blocks.filter((b) => JSON.stringify(this.toServer(b)).toLowerCase().includes(name)).length;
        },

        kindOf(b) { return this.strings.kinds?.[b.t] || b.t; },

        chapterOf(i) {
            const n = this.blocks.slice(0, i + 1).filter((b) => b.t === 'heading').length;
            return `${this.strings.chapter} ${two(n)}`;
        },

        galleryNote(n) {
            const g = this.strings.gallery || {};
            return n === 0 ? g.none : n === 1 ? g.one : n === 2 ? g.two : n === 3 ? g.three : g.many;
        },

        /** Posts next to each other show side by side: the first of a run says so. */
        firstEmbed(i) { return this.blocks[i]?.t === 'embed' && this.blocks[i - 1]?.t !== 'embed'; },

        platformOf(u) {
            u = String(u || '');
            return /instagram/.test(u) ? 'Instagram' : /tiktok/.test(u) ? 'TikTok' : /behance/.test(u) ? 'Behance' : /youtu/.test(u) ? 'YouTube' : '';
        },

        handleOf(u) {
            u = String(u || '');
            const m = u.match(/@([\w.-]+)/) || u.match(/instagram\.com\/(?:reel\/)?([\w.-]+?)(?:-|\/|$)/);
            return (m && m[1]) || (this.platformOf(u) || 'post').toLowerCase();
        },

        videoHost(u) {
            u = String(u || '');
            return /vimeo/.test(u) ? 'Vimeo' : /youtu/.test(u) ? 'YouTube' : '';
        },

        // --- Pictures -------------------------------------------------------

        mediaById(id) { return id ? this.media.find((m) => m.id === id) : null; },

        /** A slot's picture: the staged file while it waits, else the saved one. */
        picUrl(m, s) {
            if (s && this.staged[s]) return this.staged[s].url;
            return this.mediaById(m)?.url || '';
        },

        bg(m, s) {
            const u = this.picUrl(m, s);
            return u ? `background-image:url('${u}')` : '';
        },

        isVideo(m, s) { return s && this.staged[s] ? this.staged[s].video : !!this.mediaById(m)?.is_video; },

        busy(s) { return !!(s && this.staged[s]?.busy); },

        /** Open the picker; `done` receives [{ m }|{ s }] (one, or several when many). */
        openPicker(title, done, { many = false, video = false } = {}) {
            this.closeMenu();
            this.picker = { title, many, video, chosen: [], onDone: done };
            this.$refs.picker.returnValue = '';
            this.$refs.picker.showModal();
        },

        onPoolPick(m) {
            if (!this.picker.many) {
                this.$refs.picker.close('cancel');
                this.picker.onDone?.([{ m: m.id }]);
                return;
            }
            const at = this.picker.chosen.indexOf(m.id);
            if (at === -1) this.picker.chosen.push(m.id);
            else this.picker.chosen.splice(at, 1);
        },

        onPickerFiles(files) {
            const list = Array.from(files || []).slice(0, this.picker.many ? 24 : 1);
            const done = this.picker.onDone;
            this.picker.chosen = [];
            this.$refs.picker.close('cancel');
            if (!list.length) return;
            const keys = list.map((file) => this.stage(file));
            done?.(keys.map((s) => ({ s })));
            if (this.id) this.uploadStaged(keys);
        },

        onPickerClose() {
            if (this.picker.many && this.$refs.picker.returnValue === 'ok' && this.picker.chosen.length) {
                this.picker.onDone?.(this.picker.chosen.map((m) => ({ m })));
            }
            this.picker.chosen = [];
        },

        /** Hold a picked file in the page until it is uploaded. */
        stage(file) {
            const key = nextKey('s');
            this.staged[key] = { file, url: URL.createObjectURL(file), video: file.type.startsWith('video/'), busy: false };
            this.dirty = true;
            return key;
        },

        /** Put a picked picture into a slot (field m / mb / ma). */
        place(target, field, pick) {
            if (pick.m) {
                target[field] = pick.m;
                target[SLOT[field]] = null;
            } else {
                target[SLOT[field]] = pick.s;
            }
            this.changed('blocks');
        },

        /** Image, look, a before or after, or one gallery picture: one picture. */
        pick(target, field, opts = {}) {
            const b = target;
            const has = !!this.picUrl(b[field], b[SLOT[field]]);
            const s = this.strings;
            const title = opts.gallery ? s.replaceImage
                : b.t === 'look' ? (has ? s.replaceLook : s.pickLook)
                    : field === 'mb' ? s.pickBefore : field === 'ma' ? s.pickAfter
                        : (has ? s.replaceImage : s.pickImage);
            this.openPicker(title, ([p]) => p && this.place(target, field, p), { video: !!opts.gallery });
        },

        /** Before / After from scratch: the before, then the after. */
        pickBeforeAfter(b) {
            this.openPicker(this.strings.pickBefore, ([p1]) => {
                if (!p1) return;
                this.place(b, 'mb', p1);
                this.$nextTick(() => this.openPicker(this.strings.pickAfter, ([p2]) => p2 && this.place(b, 'ma', p2)));
            });
        },

        /** Photos: add several at once. */
        pickMany(b) {
            this.openPicker(this.strings.pickMany, (picks) => {
                picks.slice(0, 24 - b.items.length).forEach((p) => b.items.push({ m: p.m || null, s: p.s || null, cap: '', key: nextKey('g') }));
                this.changed('blocks');
            }, { many: true, video: true });
        },

        /**
         * Upload staged files (batches of 12, `place=0`) and swap each slot's
         * staged key for the saved media id, in order.
         */
        async uploadStaged(keys) {
            keys = keys.filter((k) => this.staged[k] && !this.staged[k].busy);
            if (!this.id || !keys.length) return;
            keys.forEach((k) => { this.staged[k].busy = true; });
            for (let i = 0; i < keys.length; i += 12) {
                const batch = keys.slice(i, i + 12);
                const body = new FormData();
                batch.forEach((k) => body.append('files[]', this.staged[k].file));
                body.append('place', '0');
                try {
                    const { data, meta } = await post(`/talent/projects/${this.id}/media`, body);
                    this.media = data?.media || this.media;
                    (meta?.added || []).forEach((id, n) => this.settle(batch[n], id));
                } catch (e) {
                    batch.forEach((k) => this.settle(k, null));
                    toast(firstError(e, this.strings.failed));
                }
            }
            this.changed('blocks');
        },

        /** A staged file finished (id) or failed (null): every slot holding it follows. */
        settle(key, id) {
            if (!key) return;
            const swap = (o) => {
                Object.entries(SLOT).forEach(([field, sField]) => {
                    if (o[sField] === key) {
                        if (id) o[field] = id;
                        o[sField] = null;
                    }
                });
            };
            this.blocks.forEach((b) => {
                swap(b);
                if (b.t === 'gallery') {
                    b.items.forEach(swap);
                    if (!id) b.items = b.items.filter((it) => it.m || it.s);
                }
            });
            if (this.staged[key]) {
                URL.revokeObjectURL(this.staged[key].url);
                delete this.staged[key];
            }
        },

        moveItem(b, k, dir) {
            const j = k + dir;
            if (j < 0 || j >= b.items.length) return;
            const [it] = b.items.splice(k, 1);
            b.items.splice(j, 0, it);
            this.changed('blocks');
        },

        /** Take a picture out of Photos, with Undo (the file is kept a while — ADR-AN). */
        removeItem(b, k) {
            const [gone] = b.items.splice(k, 1);
            this.changed('blocks');
            toast(this.strings.picRemoved, () => { b.items.splice(k, 0, gone); this.changed('blocks'); });
        },

        // --- Look with tags ----------------------------------------------------

        addPiece(b, x = 50, y = 50) {
            if (b.items.length >= 8) { toast(this.strings.lookFull); return; }
            const piece = { n: '', d: '', x, y, key: nextKey('p') };
            b.items.push(piece);
            this.changed('blocks');
            this.$nextTick(() => this.$root.querySelector(`[data-piece="${piece.key}"]`)?.focus());
        },

        removePiece(b, k) {
            const [gone] = b.items.splice(k, 1);
            this.changed('blocks');
            toast(this.strings.pieceRemoved, () => { b.items.splice(k, 0, gone); this.changed('blocks'); });
        },

        movePiece(b, k) {
            const [x] = b.items.splice(k, 1);
            b.items.splice(k - 1, 0, x);
            this.changed('blocks');
        },

        /** Click (tap) the photo to add a piece there; drag a number onto its piece. */
        onLookDown(e, b) {
            if (e.target.closest('.pe-pic')) return;
            const fig = e.currentTarget;
            const pos = (ev) => {
                const r = fig.getBoundingClientRect();
                return [Math.round(Math.max(3, Math.min(97, (ev.clientX - r.left) / r.width * 100))), Math.round(Math.max(3, Math.min(97, (ev.clientY - r.top) / r.height * 100)))];
            };
            const dot = e.target.closest('.dot');
            if (!dot) {
                const sx = e.clientX; const sy = e.clientY; const t0 = Date.now();
                const up = (ev) => {
                    fig.removeEventListener('pointerup', up);
                    if (Math.abs(ev.clientX - sx) > 6 || Math.abs(ev.clientY - sy) > 6 || Date.now() - t0 > 600) return;
                    const [x, y] = pos(ev);
                    this.addPiece(b, x, y);
                };
                fig.addEventListener('pointerup', up);
                return;
            }
            e.preventDefault();
            const piece = b.items[Number(dot.dataset.dot)];
            try { dot.setPointerCapture(e.pointerId); } catch (er) { /* older browsers */ }
            dot.classList.add('pe-drag');
            const move = (ev) => { [piece.x, piece.y] = pos(ev); };
            const up = () => {
                dot.classList.remove('pe-drag');
                dot.removeEventListener('pointermove', move);
                dot.removeEventListener('pointerup', up);
                this.changed('blocks');
            };
            dot.addEventListener('pointermove', move);
            dot.addEventListener('pointerup', up);
        },

        /** A look's pin moves with the arrow keys (Shift = 5 steps). */
        onDotKey(e, x) {
            if (!/^Arrow/.test(e.key)) return;
            e.preventDefault();
            const st = e.shiftKey ? 5 : 1;
            x.x = Math.max(3, Math.min(97, x.x + (e.key === 'ArrowLeft' ? -st : e.key === 'ArrowRight' ? st : 0)));
            x.y = Math.max(3, Math.min(97, x.y + (e.key === 'ArrowUp' ? -st : e.key === 'ArrowDown' ? st : 0)));
            this.changed('blocks');
        },

        /** Hovering a piece lights its pin, and the other way round. */
        lightPiece(e) {
            const t = e.target.closest('[data-tag], .dot');
            if (!t) return;
            const look = e.currentTarget;
            const k = t.dataset.tag ? Number(t.dataset.tag) - 1 : Number(t.dataset.dot);
            look.querySelectorAll('.dot').forEach((d) => d.classList.toggle('on', Number(d.dataset.dot) === k));
            look.querySelectorAll('li[data-tag]').forEach((li) => li.classList.toggle('on', Number(li.dataset.tag) - 1 === k));
        },

        /** The before/after line drags in the editor too. */
        onBaDown(e) {
            if (e.target.closest('[contenteditable], .pe-pic')) return;
            const box = e.currentTarget;
            const set = (ev) => { const r = box.getBoundingClientRect(); box.style.setProperty('--p', `${Math.max(0, Math.min(100, (ev.clientX - r.left) / r.width * 100))}%`); };
            set(e);
            try { box.setPointerCapture(e.pointerId); } catch (er) { /* older browsers */ }
            const up = () => { box.removeEventListener('pointermove', set); box.removeEventListener('pointerup', up); };
            box.addEventListener('pointermove', set);
            box.addEventListener('pointerup', up);
        },

        // --- Typing in place -------------------------------------------------

        onFocusIn(event) {
            this.active = event.target.closest('.pe-b')?.dataset.key || null;
        },

        /** The visible text of a contenteditable (and '' so the placeholder shows). */
        plainOf(el) {
            if (el.textContent === '' && el.innerHTML !== '') el.innerHTML = '';
            return el.textContent.replace(/[^\S\n]+/g, ' ');
        },

        onTitle(el) {
            const v = this.plainOf(el);
            this.form.title.en = v.trim() === '' ? '' : v;
            this.changed('title');
        },

        onPlain(el, field) {
            const value = this.plainOf(el);
            if (field === 'client') this.form.client_name = value.trim() === '' ? '' : value;
            else this.form.role.en = value.trim() === '' ? '' : value;
            this.changed(field);
        },

        /** Text: "/" alone opens the block menu (and the block becomes what is picked). */
        onText(el, b, i) {
            if (el.textContent === '/') {
                el.textContent = '';
                b.v = '';
                this.openMenu(el, this.blockMenu(), (item) => {
                    if (item.key === 'text') { el.focus(); return; }
                    this.replaceBlock(i, item.key);
                });
                return;
            }
            b.v = this.plainOf(el);
            this.changed('blocks');
        },

        /** Enter: a new text block below. Backspace in an empty one: remove it (Undo). */
        onTextKey(e, b, i) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (b.t === 'text' || b.t === 'heading') this.insertAt(i + 1, 'text');
                return;
            }
            if (e.key === 'Backspace' && !e.target.textContent && (b.t === 'text' || b.t === 'heading') && this.blocks.length > 1 && e.target.dataset.f === 'v') {
                e.preventDefault();
                this.removeBlock(i, { focusPrev: true });
            }
        },

        /**
         * Paste as plain text, so nothing but words gets in. Several paragraphs
         * pasted into a text block: the first goes here, each other one becomes
         * its own text block.
         */
        onPaste(event, b = null, i = -1) {
            event.preventDefault();
            const txt = (event.clipboardData || window.clipboardData).getData('text/plain').replace(/\r/g, '');
            const paras = txt.split(/\n\s*\n+/).map((x) => x.replace(/\s*\n\s*/g, ' ').trim()).filter(Boolean);
            if (paras.length > 1 && b?.t === 'text') {
                document.execCommand('insertText', false, paras[0]);
                b.v = event.target.textContent;
                paras.slice(1).forEach((v, k) => this.blocks.splice(i + 1 + k, 0, withKeys({ t: 'text', v })));
                this.changed('blocks');
                toast(fmt(this.strings.paras, { n: paras.length }));
                return;
            }
            document.execCommand('insertText', false, paras.join(' '));
        },

        /** Enter on the title: on to the story's first field. */
        focusStory() {
            this.$root.querySelector('#story [contenteditable], #story input, #story .pe-drop')?.focus();
        },

        /** Focus the first field of a block (by key), or a selector inside it. */
        focusBlock(key, sel = '[contenteditable], input, button.pe-drop, .pe-ph.add button, .pe-add-li button') {
            this.$nextTick(() => this.$root.querySelector(`.pe-b[data-key="${key}"]`)?.querySelector(sel)?.focus());
        },

        // --- Blocks ------------------------------------------------------------

        has(t) { return this.blocks.some((b) => b.t === t); },

        /** The block menu (prototype MENU): Write · Show · Prove. Results / Credits once each. */
        blockMenu() {
            const labels = this.strings.blocks || {};
            const groups = { write: WORDS, show: ['image', 'gallery', 'beforeafter', 'look', 'video', 'embed'], prove: ['results', 'credits'] };
            const items = [];
            Object.entries(groups).forEach(([g, types]) => {
                const shown = types.filter((t) => !((t === 'results' || t === 'credits') && this.has(t)));
                if (!shown.length) return;
                items.push({ key: `g-${g}`, header: true, label: this.strings.groups?.[g] || g });
                shown.forEach((t) => items.push({ key: t, icon: labels[t]?.[2], label: labels[t]?.[0] || t, sub: labels[t]?.[1] || '' }));
            });
            return items;
        },

        openAddMenu(anchor, at) {
            this.openMenu(anchor, this.blockMenu(), (item) => this.insertAt(at, item.key));
        },

        /** Put a new block of type t at position `at`. */
        insertAt(at, t) {
            if ((t === 'results' || t === 'credits') && this.has(t)) {
                toast(fmt(this.strings.oneOnly, { b: this.kindOf({ t }) }));
                return;
            }
            const b = withKeys(fresh(t));
            this.blocks.splice(Math.max(0, Math.min(at, this.blocks.length)), 0, b);
            if (t === 'results' && !this.form.results.length) this.form.results.push(resultRow());
            if (t === 'credits' && !this.credits.length && !this.drafts.length) this.addDraft();
            this.changed('blocks');
            this.focusBlock(b.key);
        },

        /** "/" in an empty text: the block turns into what was picked. */
        replaceBlock(i, t) {
            if ((t === 'results' || t === 'credits') && this.has(t)) {
                toast(fmt(this.strings.oneOnly, { b: this.kindOf({ t }) }));
                return;
            }
            const b = withKeys(fresh(t));
            this.blocks.splice(i, 1, b);
            if (t === 'results' && !this.form.results.length) this.form.results.push(resultRow());
            if (t === 'credits' && !this.credits.length && !this.drafts.length) this.addDraft();
            this.changed('blocks');
            this.focusBlock(b.key);
        },

        moveBlock(i, j) {
            if (j < 0 || j >= this.blocks.length || i === j) return;
            const [b] = this.blocks.splice(i, 1);
            this.blocks.splice(j, 0, b);
            this.changed('blocks');
            this.focusBlock(b.key, `[data-act="${j < i ? 'up' : 'down'}"]:not([disabled]), [data-act="more"]`);
        },

        /**
         * Delete a block, with Undo. Results take their numbers with them (and
         * bring them back on Undo); Credits only go once nobody is on them.
         */
        removeBlock(i, { focusPrev = false } = {}) {
            const b = this.blocks[i];
            if (b.t === 'credits' && (this.credits.length || this.drafts.some((d) => d.name.trim()))) {
                toast(this.strings.blockNotEmpty);
                return;
            }
            const rows = b.t === 'results' ? this.form.results : null;
            if (rows) this.form.results = [];
            if (b.t === 'credits') this.drafts = [];
            this.blocks.splice(i, 1);
            if (!this.blocks.length) this.blocks.push(withKeys(fresh('text')));
            this.changed(rows ? 'results' : 'blocks');
            const next = this.blocks[focusPrev ? Math.max(0, i - 1) : Math.min(i, this.blocks.length - 1)];
            this.focusBlock(next.key, focusPrev ? '[contenteditable]' : '[data-act="more"]');
            toast(fmt(this.strings.deleted, { b: this.kindOf(b) }), () => {
                this.blocks.splice(i, 0, b);
                if (rows) this.form.results = rows;
                this.changed('results');
            });
        },

        /** A block's More menu: add below · turn into · duplicate · delete. */
        openMoreMenu(anchor, i) {
            const b = this.blocks[i];
            const items = [{ key: 'below', label: this.strings.addBelow }];
            if (WORDS.includes(b.t)) {
                WORDS.filter((t) => t !== b.t).forEach((t) => items.push({ key: `to-${t}`, label: fmt(this.strings.turnInto, { k: this.kindOf({ t }).toLowerCase() }) }));
            }
            if (b.t !== 'results' && b.t !== 'credits') items.push({ key: 'dup', label: this.strings.duplicate });
            items.push({ key: 'del', cls: 'del', label: this.strings.deleteBlock });
            this.openMenu(anchor, items, (item) => {
                if (item.key === 'below') {
                    this.$nextTick(() => this.openMenu(anchor, this.blockMenu(), (pick) => this.insertAt(i + 1, pick.key)));
                    return;
                }
                if (item.key.startsWith('to-')) {
                    const t = item.key.slice(3);
                    const nb = withKeys({ ...fresh(t), v: b.v || '' });
                    this.blocks.splice(i, 1, nb);
                    this.changed('blocks');
                    this.focusBlock(nb.key);
                    return;
                }
                if (item.key === 'dup') {
                    const copy = withKeys(this.toServer(b));
                    this.blocks.splice(i + 1, 0, copy);
                    this.changed('blocks');
                    this.focusBlock(copy.key, '[data-act="more"]');
                    return;
                }
                this.removeBlock(i);
            });
        },

        /** Drag a block by its grip: a purple line shows where it will land. */
        onGrip(e, from) {
            const grip = e.currentTarget;
            const story = this.$root.querySelector('#story');
            const els = [...story.querySelectorAll(':scope > .pe-b')];
            const line = document.createElement('div');
            line.className = 'pe-drop-line';
            story.appendChild(line);
            e.preventDefault();
            try { grip.setPointerCapture(e.pointerId); } catch (er) { /* older browsers */ }
            this.dragFrom = from;
            let to = from;
            const move = (ev) => {
                to = els.length;
                for (let k = 0; k < els.length; k++) {
                    const r = els[k].getBoundingClientRect();
                    if (ev.clientY < r.top + r.height / 2) { to = k; break; }
                }
                const sr = story.getBoundingClientRect();
                const ref = els[Math.min(to, els.length - 1)].getBoundingClientRect();
                line.style.top = `${(to < els.length ? ref.top : ref.bottom) - sr.top - 2}px`;
                if (ev.clientY < 90) window.scrollBy(0, -12);
                else if (ev.clientY > window.innerHeight - 60) window.scrollBy(0, 12);
            };
            const up = () => {
                grip.removeEventListener('pointermove', move);
                grip.removeEventListener('pointerup', up);
                line.remove();
                this.dragFrom = null;
                const j = to > from ? to - 1 : to;
                if (j !== from && j >= 0) this.moveBlock(from, j);
            };
            grip.addEventListener('pointermove', move);
            grip.addEventListener('pointerup', up);
        },

        /** A block as the server stores it (client keys and staged files left out). */
        toServer(b) {
            const ok = (u) => (/^https?:\/\/\S+\.\S+/i.test(String(u || '').trim()) ? String(u).trim() : '');
            switch (b.t) {
            case 'text': case 'heading': return { t: b.t, v: b.v || '' };
            case 'quote': return { t: b.t, v: b.v || '', by: b.by || '' };
            case 'image': return { t: b.t, m: b.m || null, cap: b.cap || '' };
            case 'gallery': return { t: b.t, items: b.items.filter((it) => it.m).map((it) => ({ m: it.m, cap: it.cap || '' })) };
            case 'beforeafter': return { t: b.t, mb: b.mb || null, ma: b.ma || null, lb: b.lb || '', la: b.la || '', cap: b.cap || '' };
            case 'look': return { t: b.t, m: b.m || null, title: b.title || '', items: b.items.map((x) => ({ n: x.n || '', d: x.d || '', x: x.x, y: x.y })) };
            case 'video': return { t: b.t, url: VIDEO_RE.test(String(b.url || '').trim()) ? String(b.url).trim() : '', cap: b.cap || '' };
            case 'embed': return { t: b.t, url: ok(b.url) };
            default: return { t: b.t };
            }
        },

        /** Something to look at besides the cover (the prototype's Publish check). */
        hasVisual() {
            return this.blocks.some((b) => (b.t === 'image' || b.t === 'look') ? !!this.picUrl(b.m, b.s)
                : b.t === 'beforeafter' ? !!(this.picUrl(b.ma, b.sa) || this.picUrl(b.mb, b.sb))
                    : b.t === 'gallery' ? b.items.length > 0
                        : b.t === 'video' ? VIDEO_RE.test(String(b.url || ''))
                            : b.t === 'embed' ? !!String(b.url || '').trim() : false);
        },

        // --- Skills & specialities ------------------------------------------

        hasSkill(id) { return this.form.skill_ids.includes(id); },

        toggleSkill(id) {
            const at = this.form.skill_ids.indexOf(id);
            if (at === -1) this.form.skill_ids.push(id);
            else this.form.skill_ids.splice(at, 1);
            const allowed = new Set(this.form.skill_ids.flatMap((s) => (this.specialities[String(s)] || []).map((x) => x.id)));
            this.form.speciality_ids = this.form.speciality_ids.filter((x) => allowed.has(x));
            this.changed('skills');
        },

        toggleSpeciality(id) {
            const at = this.form.speciality_ids.indexOf(id);
            if (at === -1) this.form.speciality_ids.push(id);
            else this.form.speciality_ids.splice(at, 1);
            this.changed('specialities');
        },

        openSkillMenu(anchor) {
            const left = this.skills.filter((s) => !this.hasSkill(s.id));
            if (!left.length) { toast(this.strings.allSkills); return; }
            this.openMenu(anchor, left.map((s) => ({ key: `s${s.id}`, id: s.id, label: s.name })), (item) => this.toggleSkill(item.id));
        },

        openSpecMenu(anchor) {
            const items = [];
            this.form.skill_ids.forEach((skillId) => {
                const skill = this.skills.find((s) => s.id === skillId);
                const left = (this.specialities[String(skillId)] || []).filter((s) => !this.form.speciality_ids.includes(s.id));
                if (!skill || !left.length) return;
                items.push({ key: `h${skillId}`, header: true, label: skill.name });
                left.forEach((s) => items.push({ key: `p${s.id}`, id: s.id, label: s.name }));
            });
            if (!items.length) { toast(this.strings.allSpecs); return; }
            this.openMenu(anchor, items, (item) => this.toggleSpeciality(item.id));
        },

        // --- Results ----------------------------------------------------------

        resultKind(value) {
            const v = String(value || '');
            return /^\s*[+\-−]/.test(v) ? 'change' : (/%\s*$/.test(v) ? 'percent' : 'number');
        },

        resultClass(value) {
            if (this.resultKind(value) !== 'change') return '';
            return /^\s*[\-−]/.test(String(value || '')) ? 'up down' : 'up';
        },

        pctOf(value) { return Math.max(0, Math.min(100, parseFloat(value) || 0)); },

        addResult() {
            if (this.form.results.length >= 4) return;
            this.form.results.push(resultRow());
            this.$nextTick(() => {
                const tiles = this.$root.querySelectorAll('.pe-results .pe-item > b');
                tiles[tiles.length - 1]?.focus();
            });
        },

        removeResult(k) {
            const [gone] = this.form.results.splice(k, 1);
            this.changed('results');
            toast(this.strings.numberRemoved, () => { this.form.results.splice(k, 0, gone); this.changed('results'); });
        },

        // --- Saving (publish, then autosave) ------------------------------

        changed(field = null) {
            if (field && this.errors[field]) delete this.errors[field];
            this.dirty = true;
            if (!this.id) return;
            this.saveState = 'busy';
            this.savedText = this.strings.saving;
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => this.save(), 700);
        },

        validate() {
            const errors = {};
            if ((this.form.title.en || '').trim() === '') errors.title = this.strings.needTitle;
            if (!this.form.skill_ids.length) errors.skills = this.strings.needSkill;
            const url = (this.form.url || '').trim();
            if (url && !/^https?:\/\/[^\s.]+\.[^\s]+$/i.test(url)) errors.url = this.strings.badUrl;
            return errors;
        },

        fieldOf(key) {
            const map = [
                [/^title/, 'title'], [/^skill_ids/, 'skills'], [/^speciality_ids/, 'specialities'],
                [/^client_name/, 'client'], [/^role/, 'role'], [/^occurred_on/, 'date'], [/^url/, 'url'],
                [/^results/, 'results'],
            ];
            return (map.find(([re]) => re.test(key)) || [null, ''])[1];
        },

        fieldTargets() {
            return {
                title: '#peTitle', client: '[data-f="client"] dd', role: '[data-f="role"] dd',
                skills: '[data-act="skadd"]', specialities: '[data-act="specadd"]', url: '[data-f="url"] input',
                results: '.pe-results', date: null,
            };
        },

        showErrors(errors) {
            const rest = [];
            const mapped = {};
            for (const [key, msg] of Object.entries(errors)) {
                const text = Array.isArray(msg) ? msg[0] : msg;
                const field = this.fieldOf(key) || (key in this.fieldTargets() ? key : '');
                if (field) mapped[field] ??= text;
                else rest.push(text);
            }
            this.errors = mapped;
            this.$nextTick(() => {
                const targets = this.fieldTargets();
                const first = Object.keys(targets).find((f) => mapped[f] && targets[f]);
                this.reveal(first && targets[first]);
            });
            return rest;
        },

        reveal(selector) {
            const el = selector && this.$root.querySelector(selector);
            if (!el) return;
            el.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
            el.focus({ preventScroll: true });
        },

        showFailure(e) {
            const rest = e instanceof ApiError && e.errors ? this.showErrors(e.errors) : [firstError(e, this.strings.failed)];
            if (rest.length) toast(rest[0]);
            else if (Object.keys(this.errors).length) toast(this.strings.fixFields);
        },

        /** The PATCH/POST body — the whole project (it is edited whole). */
        payload() {
            const f = this.form;
            return {
                title: f.title,
                role: f.role,
                client_name: (f.client_name || '').trim() || null,
                client_confidential: !!f.client_confidential,
                url: (f.url || '').trim() || null,
                occurred_on: f.occurred_on || null,
                skill_ids: f.skill_ids,
                speciality_ids: f.speciality_ids,
                ...(this.id ? {} : { featured: this.featured }),
                results: f.results
                    .map((r) => ({ label: (r.label || '').trim(), value: (r.value || '').trim(), compare: (r.compare || '').trim() }))
                    .filter((r) => r.label && r.value)
                    .slice(0, 4),
                blocks: this.blocks.map((b) => this.toServer(b)),
            };
        },

        get featuredNote() {
            if (this.featuredLocked) return this.strings.featOnly;
            if (this.featured) return this.id ? this.strings.featOnSaved : this.strings.featOnNew;
            return this.strings.featOff;
        },

        async toggleFeatured() {
            if (this.featuredLocked || !this.canFeature || this.featuring) return;
            if (!this.id) { this.featured = !this.featured; return; }
            if (this.featured) return;
            this.featuring = true;
            this.featured = true;
            try {
                await post(`/talent/projects/${this.id}/feature`);
                toast(this.strings.featDone);
            } catch (e) {
                this.featured = false;
                toast(firstError(e, this.strings.failed));
            } finally {
                this.featuring = false;
            }
        },

        /** Autosave the existing project (queued while a save is in flight). */
        async save() {
            if (!this.id) return false;
            const invalid = this.validate();
            if (Object.keys(invalid).length) {
                this.errors = invalid;
                this.saveState = 'bad';
                this.savedText = this.strings.notSavedFix;
                return false;
            }
            if (this.saving) { this.resave = true; return false; }

            this.saving = true;
            this.saveState = 'busy';
            this.savedText = this.strings.saving;
            try {
                const { data } = await patch(`/talent/projects/${this.id}`, this.payload());
                this.originalDate = data?.occurred_on || '';
                if (data?.media) this.media = data.media;
                this.dirty = Object.keys(this.staged).length > 0;
                this.errors = {};
                this.saveState = 'ok';
                this.savedText = this.strings.saved;
                return true;
            } catch (e) {
                this.saveState = 'bad';
                if (e instanceof ApiError && e.errors) {
                    this.showErrors(e.errors);
                    this.savedText = this.strings.notSavedFix;
                } else {
                    this.savedText = fmt(this.strings.saveFailed, { m: firstError(e, this.strings.failed) });
                }
                return false;
            } finally {
                this.saving = false;
                if (this.resave) { this.resave = false; this.save(); }
            }
        },

        /**
         * The main button. Everything missing is listed at once (each line
         * takes you to its field); otherwise a new project is created (+ its
         * staged work sent, then the blocks saved with the pictures' ids) or an
         * existing one saved now.
         */
        async publish(anchor) {
            const invalid = this.validate();
            const visual = !!this.coverUrl || this.hasVisual();
            if (invalid.title || invalid.skills || !visual) {
                const items = [{ key: 'need-h', header: true, label: this.strings.needHead }];
                if (invalid.title) items.push({ key: 'need-title', need: true, label: this.strings.needTitle, target: '#peTitle' });
                if (invalid.skills) items.push({ key: 'need-skill', need: true, label: this.strings.needSkill, target: '[data-act="skadd"]' });
                if (!visual) items.push({ key: 'need-visual', need: true, label: this.strings.needVisual, target: '.pe-cover-acts .btn' });
                this.openMenu(anchor, items, (item) => this.reveal(item.target));
                return;
            }
            if (Object.keys(invalid).length) {
                this.showErrors(invalid);
                toast(this.strings.fixFields);
                return;
            }

            if (this.id) {
                clearTimeout(this.saveTimer);
                if (await this.save()) toast(this.strings.live);
                else if (Object.keys(this.errors).length) toast(this.strings.fixFields);
                return;
            }

            this.publishing = true;
            this.saveState = 'busy';
            this.savedText = this.strings.saving;
            try {
                const { data } = await post('/talent/projects', this.payload());
                this.id = data.id;
                this.originalDate = data.occurred_on || '';
                this.media = data.media || [];
                this.errors = {};
                this.featured = !!data.is_featured;
                this.featuredLocked = !!data.featured_locked;
                window.history.replaceState(null, '', this.editUrlTemplate.replace('__ID__', data.id));

                if (this.coverFile) await this.sendCover(this.coverFile);
                await this.uploadStaged(Object.keys(this.staged));
                clearTimeout(this.saveTimer);
                await this.save();

                const asked = [];
                for (const d of this.drafts.filter((x) => this.draftComplete(x))) {
                    if (await this.commitDraft(d, true) && d.talent) asked.push(d.talent.display_name);
                }

                this.dirty = false;
                this.saveState = 'ok';
                this.savedText = this.strings.saved;
                if (asked.length === 1) toast(fmt(this.strings.publishedAsked1, { n: asked[0] }));
                else if (asked.length > 1) toast(fmt(this.strings.publishedAskedN, { n: asked.length }));
                else toast(this.strings.published1);
            } catch (e) {
                this.saveState = this.id ? 'ok' : 'none';
                this.savedText = this.id ? this.strings.saved : this.strings.notSaved;
                this.showFailure(e);
            } finally {
                this.publishing = false;
            }
        },

        onBeforeUnload(event) {
            if (this.dirty || this.saving) {
                event.preventDefault();
                event.returnValue = this.strings.leave;
            }
        },

        onEscape() {
            if (this.menu.open) {
                const anchor = this.menu.anchor;
                this.closeMenu();
                anchor?.focus();
            } else if (this.panelMounted) {
                this.closeSettings();
            }
        },

        /** Cmd/Ctrl+S saves now. */
        onKey(event) {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 's') {
                event.preventDefault();
                if (this.id) { clearTimeout(this.saveTimer); this.save(); }
            }
        },

        // --- Settings (what visitors never see) -----------------------------

        openSettings(anchor) {
            this.closeMenu();
            this.panelReturn = anchor || document.activeElement;
            this.panelMounted = true;
            this.$nextTick(() => setTimeout(() => {
                this.panelOpen = true;
                this.$refs.panel?.querySelector('#peDate')?.focus();
            }, 20));
        },

        closeSettings() {
            this.panelOpen = false;
            setTimeout(() => { this.panelMounted = false; }, 200);
            this.panelReturn?.focus?.();
        },

        trapPanel(event) {
            const items = [...this.$refs.panel.querySelectorAll('button, input, [href]')].filter((x) => !x.disabled && x.offsetParent !== null);
            if (!items.length) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        },

        onMonth(value) {
            if (!value) this.form.occurred_on = '';
            else if (this.originalDate.slice(0, 7) === value) this.form.occurred_on = this.originalDate;
            else this.form.occurred_on = `${value}-01`;
            this.changed('date');
        },

        async leaveProject() {
            const ok = await ask({ title: fmt(this.strings.leaveTitle, { p: this.displayName }), message: this.strings.leaveBody, confirmLabel: this.strings.leaveOk, tone: 'danger' });
            if (!ok) return;
            try {
                await post(`/talent/projects/${this.id}/leave`);
                this.dirty = false;
                window.location.href = this.projectsUrl;
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            }
        },

        async deleteProject() {
            const ok = await ask({ title: fmt(this.strings.delTitle, { p: this.displayName }), message: this.strings.delBody, confirmLabel: this.strings.delOk, tone: 'danger' });
            if (!ok) return;
            try {
                await del(`/talent/projects/${this.id}`);
                this.dirty = false;
                window.location.href = this.projectsUrl;
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            }
        },

        // --- Cover --------------------------------------------------------

        async pickCover(file) {
            if (!file) return;
            if (!this.id) {
                this.coverFile = file;
                this.coverUrl = URL.createObjectURL(file);
                this.dirty = true;
                return;
            }
            if (await this.sendCover(file)) toast(this.strings.coverAdded);
        },

        async sendCover(file) {
            this.coverBusy = true;
            try {
                const body = new FormData();
                body.append('cover', file);
                const { data } = await post(`/talent/projects/${this.id}/cover`, body);
                this.coverUrl = data.cover_image_url;
                this.coverFile = null;
                return true;
            } catch (e) {
                toast(firstError(e, this.strings.failed));
                return false;
            } finally {
                this.coverBusy = false;
            }
        },

        async removeCover() {
            if (!this.id) { this.coverFile = null; this.coverUrl = null; return; }
            try {
                const { data } = await del(`/talent/projects/${this.id}/cover`);
                this.coverUrl = null;
                if (data) toast(this.strings.coverRemoved);
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            }
        },

        // --- Credits ------------------------------------------------------

        creditState(c) {
            const s = this.strings.creditStates || {};
            if (c.status === 'accepted') return s.accepted;
            if (c.status === 'pending') return s.pending;
            if (c.status === 'declined') return s.declined;
            return s.listed;
        },

        creditClass(status) {
            if (status === 'pending') return 'pe-st-awaiting';
            if (status === 'accepted') return '';
            return 'pe-st-listed';
        },

        draftState(d) {
            const s = this.strings.creditStates || {};
            if (!d.talent) return s.listedDraft;
            return this.id ? s.sending : s.onPublish;
        },

        addDraft() {
            const d = { key: nextKey('d'), name: '', talent: null, talent_type_id: '', role: '', results: [], timer: null };
            this.drafts.push(d);
            this.$nextTick(() => this.$root.querySelector(`[data-draft="${d.key}"]`)?.focus());
        },

        onDraftName(d) {
            d.talent = null;
            clearTimeout(d.timer);
            const q = d.name.trim();
            if (!q) { d.results = []; return; }
            d.timer = setTimeout(async () => {
                try {
                    const { data } = await get(`/mentions/search?q=${encodeURIComponent(q)}`);
                    if (d.name.trim() === q) d.results = (data?.talents || []).slice(0, 5);
                } catch (e) {
                    d.results = [];
                }
            }, 200);
        },

        pickPerson(d, p) {
            d.talent = p;
            d.name = p.display_name;
            d.results = [];
            this.maybeCommit(d);
        },

        draftComplete(d) { return (d.talent || d.name.trim()) && d.talent_type_id && d.role.trim(); },

        onDraftBlur(event, d) {
            if (event.currentTarget.contains(event.relatedTarget)) return;
            d.results = [];
            this.maybeCommit(d);
        },

        maybeCommit(d) {
            if (this.id && this.draftComplete(d)) this.commitDraft(d);
        },

        async commitDraft(d, quiet = false) {
            if (d.busy) return false;
            d.busy = true;
            try {
                const body = d.talent
                    ? { talent_id: d.talent.id, talent_type_id: Number(d.talent_type_id), role: d.role.trim() }
                    : { name: d.name.trim(), talent_type_id: Number(d.talent_type_id), role: d.role.trim() };
                const { data, message } = await post(`/talent/projects/${this.id}/credits`, body);
                this.credits = data?.credits || [];
                this.drafts = this.drafts.filter((x) => x.key !== d.key);
                if (!quiet) toast(message);
                return true;
            } catch (e) {
                toast(firstError(e, this.strings.failed));
                return false;
            } finally {
                d.busy = false;
            }
        },

        async removeCredit(c) {
            const ok = await ask({
                title: fmt(this.strings.removeCreditTitle, { n: c.name }),
                message: this.strings.removeCreditBody,
                confirmLabel: this.strings.removeOk,
                tone: 'danger',
            });
            if (!ok) return;
            try {
                const { data } = await del(`/talent/projects/${this.id}/credits/${c.id}`);
                this.credits = data?.credits || [];
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            }
        },

        // --- Menus ------------------------------------------------------------

        openMenu(anchor, items, onPick) {
            const r = anchor.getBoundingClientRect();
            const hl = items.findIndex((it) => !it.header);
            this.menu = {
                open: true, items, onPick, anchor, hl,
                top: r.bottom + 6,
                left: Math.max(8, Math.min(r.left + r.width / 2 - 170, window.innerWidth - 348)),
            };
            this.$nextTick(() => {
                const el = this.$refs.menu;
                if (!el) return;
                const h = el.offsetHeight;
                if (r.bottom + 6 + h > window.innerHeight && r.top - h - 6 > 0) this.menu.top = r.top - h - 6;
                el.querySelector('button.is-hl, button')?.focus();
            });
        },

        closeMenu() {
            if (this.menu.open) this.menu.open = false;
        },

        pickMenu(item) {
            const fn = this.menu.onPick;
            this.closeMenu();
            if (fn) fn(item);
        },

        onMenuKey(event) {
            if (event.key === 'Tab') { this.closeMenu(); return; }
            if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;
            event.preventDefault();
            const picks = this.menu.items.map((it, i) => (it.header ? -1 : i)).filter((i) => i >= 0);
            const at = picks.indexOf(this.menu.hl);
            this.menu.hl = picks[(at + (event.key === 'ArrowDown' ? 1 : -1) + picks.length) % picks.length];
            this.$nextTick(() => this.$refs.menu?.querySelector('button.is-hl')?.focus());
        },
    }));
});
