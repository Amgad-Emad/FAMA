/**
 * fama-ui profile page components (Alpine), registered on alpine:init.
 *
 *   (profileStructure + profileViews live in ./profile-structure.js.)
 *   publicProfile    — GET /{slug}: share, follow/recognise sign-in dialog,
 *                      lightbox, "show all recognitions".
 *   contentBlock     — one block's item editor on GET /talent/content/{section}.
 *
 * Port of the prototype's inline scripts (profile-structure.html, preview.html,
 * profile-content.html, views.js) onto the real endpoints via http.js. Every
 * runtime string arrives from Blade (`strings`), so the copy stays in __().
 */
import { del, get, patch, post, put, upload as uploadFile, ApiError } from '../http';
import { tr, clone, fmt, toast, ask } from './profile-helpers';
import './profile-structure';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // =====================================================================
    // Public profile (GET /{slug}) — share, sign-in prompt, lightbox, more.
    // =====================================================================
    Alpine.data('publicProfile', ({ strings = {}, url = '', name = '', own = false, guest = false, live = true } = {}) => ({
        S: strings,
        url,
        name,
        own,
        guest,
        live,
        sign: { open: false, act: 'follow' },
        lb: { items: [], i: 0, from: null },
        recOpen: false,

        /** Share: the phone's share sheet when there is one, otherwise copy the link. */
        async share() {
            if (!this.live) { toast(this.S.shareOff); return; }
            if (navigator.share) {
                try { await navigator.share({ title: fmt(this.S.shareTitle, { name: this.name }), url: this.url }); } catch (e) { /* cancelled */ }
                return;
            }
            let ok = true;
            try { await navigator.clipboard.writeText(this.url); } catch (e) { ok = false; }
            const bare = this.url.replace(/^https?:\/\//, '');
            toast(ok ? `${this.S.copied}: ${bare}` : `${this.S.yourLink}: ${bare}`);
        },
        /** Your own profile: the visitor buttons explain themselves instead. */
        ownNote() { toast(this.S.ownNote); },
        /**
         * Guests: follow / recognise open the sign-in prompt. Fama staff are
         * signed in but are not talent — they get the read-only note instead.
         */
        signIn(act) {
            if (window.famaStaff) { toast(window.famaStaffNote); return; }
            this.sign = { open: true, act };
            this.$nextTick(() => {
                const d = this.$refs.signDlg;
                if (d && !d.open) d.showModal();
                d?.querySelector('.acts a')?.focus();
            });
        },
        /** Close the sign-in prompt. */
        closeSign() { this.sign.open = false; if (this.$refs.signDlg?.open) this.$refs.signDlg.close(); },
        /**
         * "Log in" from the follow prompt: record the follow first (the 401
         * captures the intent server-side, replayed once after login), then go.
         */
        async loginFor(e, href) {
            if (this.sign.act !== 'follow' || !this.$refs.signDlg?.dataset.followId) return;
            e.preventDefault();
            try { await post(`/follow/talent/${this.$refs.signDlg.dataset.followId}`); } catch (err) { /* 401 expected */ }
            window.location.href = href;
        },
        /** Open the lightbox on a set of [src, caption] pairs. */
        openLb(items, i, from) {
            if (!items || !items.length) return;
            this.lb = { items, i: i || 0, from };
            this.$nextTick(() => {
                const d = this.$refs.lb;
                if (d && !d.open) d.showModal();
                this.$refs.lbNext?.focus();
            });
        },
        /** Delegated click: any [data-lb] (works inside lazily loaded tabs too). */
        onClick(e) {
            const g = e.target.closest('[data-lb]');
            if (!g) return;
            const set = [...this.$root.querySelectorAll(`[data-lb="${g.dataset.lb}"]`)];
            this.openLb(set.map((x) => [x.dataset.src || '', x.dataset.cap || '']), set.indexOf(g), g);
        },
        /** Step the lightbox. */
        step(d) { const n = this.lb.items.length; this.lb.i = (this.lb.i + d + n) % n; },
        /** The current lightbox item. */
        get lbItem() { return this.lb.items[this.lb.i] || ['', '']; },
        /** Lightbox closed: focus goes back where it came from. */
        lbClosed() { this.lb.from?.focus?.(); },
    }));

    // =====================================================================
    // Profile content — the section row. Switching section fetches that
    // section's page and swaps #blocks in place (Alpine boots the new blocks),
    // so the page never reloads; the URL follows with replaceState.
    // =====================================================================
    Alpine.data('contentTabs', () => ({
        loading: false,

        /** Open a section from its tab button. */
        async open(btn) {
            if (this.loading || btn.getAttribute('aria-selected') === 'true') return;
            this.loading = true;
            try {
                const res = await fetch(btn.dataset.url, { headers: { Accept: 'text/html' }, credentials: 'same-origin' });
                if (!res.ok) throw new Error(String(res.status));
                const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                const next = doc.getElementById('blocks');
                if (!next) throw new Error('no blocks');
                this.$refs.blocks.innerHTML = next.innerHTML;
                this.$root.querySelectorAll('#tabs [role=tab]').forEach((b) => b.setAttribute('aria-selected', String(b === btn)));
                window.history.replaceState(null, '', btn.dataset.url);
                btn.focus();
            } catch (e) {
                window.location.href = btn.dataset.url; // fall back to a normal visit
            } finally {
                this.loading = false;
            }
        },
        /** Arrow keys move between sections. */
        step(d) {
            const tabs = [...this.$root.querySelectorAll('#tabs [role=tab]')];
            const i = tabs.findIndex((b) => b.getAttribute('aria-selected') === 'true');
            const rtl = document.documentElement.dir === 'rtl';
            const n = tabs[(i + (rtl ? -d : d) + tabs.length) % tabs.length];
            if (n) this.open(n);
        },
    }));

    // =====================================================================
    // Profile content — one block's items (GET /talent/content/{section}).
    // Port of profile-content.html's list/gallery blocks on the existing CRUD
    // endpoints: add, edit in place, drag to reorder, delete with confirm + Undo.
    // =====================================================================
    Alpine.data('contentBlock', ({ endpoint, blank = {}, fields = [], media = null, label = '', strings = {} } = {}) => ({
        S: strings,
        endpoint,
        blank,
        fields,
        media,
        label,
        items: [],
        loading: true,
        saving: false,
        editing: null, // null | 'new' | item id
        form: clone(blank),
        errors: {},
        errorText: '',
        dragId: null,
        selTile: null,
        tr,

        /** Load the block's items (and keep the root: `$root` is undefined
         *  from an element an x-if has just swapped out). */
        async init() { this.rootEl = this.$root; await this.load(); },
        /** Fetch the first page (24 — the CRUD endpoint's page size). */
        async load() {
            this.loading = true;
            try {
                const { data } = await get(`${this.endpoint}/data`);
                this.items = Array.isArray(data) ? data : data.items || [];
            } catch (e) {
                /* keep the current list; the next action retries */
            } finally {
                this.loading = false;
            }
        },
        /** Open the add form. */
        startAdd(preset = {}) {
            this.editing = 'new';
            const f = clone(this.blank);
            // Selects start on their first option (as in the prototype) — some
            // columns behind them are NOT NULL (equipment.category).
            this.fields.forEach((fd) => { if (fd.kind === 'select' && !f[fd.name] && fd.options?.length) f[fd.name] = fd.options[0]; });
            this.form = { ...f, ...preset };
            this.errors = {};
            this.errorText = '';
            this.focusForm();
        },
        /** Open the edit form for an item (prefilled from its stored values). */
        startEdit(item) {
            this.editing = item.id;
            const f = clone(this.blank);
            Object.keys(f).forEach((k) => {
                if (item.values && k in item.values && item.values[k] !== null) {
                    f[k] = typeof f[k] === 'object' && f[k] !== null ? { ...f[k], ...item.values[k] } : item.values[k];
                }
            });
            this.form = f;
            this.errors = {};
            this.errorText = '';
            this.focusForm();
        },
        /** The item being edited (null when adding). */
        get current() { return this.items.find((x) => x.id === this.editing) || null; },
        /** Whether a field spans both columns of the form. */
        isFull(f) { return f.kind === 'url' || f.name === 'notes' || f.name === 'caption' || this.fields.length === 1; },
        /** The grey line under an item: its other values (options as labels). */
        subOf(item) {
            const skip = ['url', 'embed_url', 'video_url', 'notes'];
            return Object.entries(item.values || {})
                .filter(([k, v]) => !skip.includes(k) && v !== null && v !== '' && (typeof v !== 'object' || tr(v)))
                .map(([, v]) => (typeof v === 'object' ? tr(v) : this.optLabel(v)))
                .filter((v) => v && v !== item.label)
                .join(' · ');
        },
        /** Close the form. */
        cancel() { this.editing = null; this.selTile = null; this.errors = {}; this.errorText = ''; },
        /** Focus the first input of the open form. */
        focusForm() { this.$nextTick(() => this.rootEl.querySelector('form.form input, form.form select')?.focus({ preventScroll: true })); },
        /** The error for a field (plain or translatable). */
        err(name) { return (this.errors[name] || this.errors[name + '.en'] || [])[0] || ''; },
        /** Human label of a field. */
        fieldLabel(f) { return this.S.labels?.[f.name] || f.name.replaceAll('_', ' ').replace(/^./, (c) => c.toUpperCase()); },
        /** Human label of a select option. */
        optLabel(o) { return this.S.options?.[o] || String(o).replaceAll('_', ' ').replace(/^./, (c) => c.toUpperCase()); },
        /** Whether a field is required. */
        required(f) { return (f.options || []).includes('required') && f.kind !== 'select'; },
        /** Save the add / edit form. */
        async save() {
            this.errors = {};
            this.errorText = '';
            this.saving = true;
            try {
                const isNew = this.editing === 'new';
                const { data } = isNew ? await post(this.endpoint, this.form) : await patch(`${this.endpoint}/${this.editing}`, this.form);
                await this.load();
                this.editing = null;
                this.selTile = null;
                toast(fmt(this.S.savedT, { name: data?.label || this.label }));
            } catch (e) {
                if (e instanceof ApiError) {
                    this.errors = e.errors || {};
                    this.errorText = Object.values(this.errors)[0]?.[0] || e.message;
                }
            } finally {
                this.saving = false;
            }
        },
        /** Delete an item (confirmed). */
        async remove(item) {
            const name = item.label || this.label;
            const ok = await ask({ title: fmt(this.S.delTitle, { name }), message: this.S.delHelp, confirmLabel: this.S.delOk, tone: 'danger' });
            if (!ok) return;
            try {
                await del(`${this.endpoint}/${item.id}`);
                this.editing = null;
                this.selTile = null;
                await this.load();
                toast(fmt(this.S.deleted, { name }));
            } catch (e) {
                if (e instanceof ApiError) toast(e.message);
            }
        },
        /** Upload files as new items (gallery-style blocks). */
        async upload(fileList) {
            const files = Array.from(fileList || []);
            if (!files.length) return;
            this.saving = true;
            try {
                // One upload modal for the batch; each file is its share of the bar.
                await window.famaUploading({ count: files.length, kind: 'media' }, async (onProgress) => {
                    for (const [i, file] of files.entries()) {
                        const payload = clone(this.blank);
                        if ('media_type' in payload) payload.media_type = file.type.startsWith('video') ? 'video' : 'image';
                        const { data } = await post(this.endpoint, payload);
                        const body = new FormData();
                        body.append('file', file);
                        await uploadFile(`${this.endpoint}/${data.id}/media`, body, {
                            onProgress: (f) => onProgress((i + f) / files.length),
                        });
                    }
                });
                await this.load();
            } catch (e) {
                if (e instanceof ApiError) toast(Object.values(e.errors || {})[0]?.[0] || e.message);
            } finally {
                this.saving = false;
            }
        },
        /** Replace one item's media. */
        async replaceMedia(item, file) {
            if (!file) return;
            const body = new FormData();
            body.append('file', file);
            try {
                await window.famaUploading({ kind: 'media' },
                    (onProgress) => uploadFile(`${this.endpoint}/${item.id}/media`, body, { onProgress }));
                await this.load();
            } catch (e) {
                if (e instanceof ApiError) toast(Object.values(e.errors || {})[0]?.[0] || e.message);
            }
        },
        /** Select a gallery tile (opens its caption form). */
        pickTile(item) {
            if (this.selTile === item.id) { this.cancel(); return; }
            this.selTile = item.id;
            this.startEdit(item);
        },
        /** Drag-to-reorder drop. */
        onDrop(item) {
            if (this.dragId === null || this.dragId === item.id) return;
            const from = this.items.findIndex((x) => x.id === this.dragId);
            const to = this.items.findIndex((x) => x.id === item.id);
            const [m] = this.items.splice(from, 1);
            this.items.splice(to, 0, m);
            this.dragId = null;
            patch(`${this.endpoint}/reorder`, { order: this.items.map((x) => x.id) }).catch(() => {});
        },
    }));
});
