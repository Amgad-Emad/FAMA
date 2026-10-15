/**
 * fama-ui projects area (Alpine), registered on alpine:init:
 *
 *   famaProjects          — /talent/projects: skill-filtered grid, drag reorder,
 *                            delete, plus confirmed credits with Leave.
 *   famaProjectEdit       — /talent/projects/new | /{id}/edit: the in-place
 *                            editor (publish, then autosave).
 *   famaCredits           — /talent/credits: confirm / decline / leave.
 *   famaRecognitionsSwitch— /talent/credits: show/hide the Recognitions section.
 *   famaRecognise         — /{slug}/recognise: write a recognition.
 *   famaProjectFollow     — the public project page's Follow button.
 *
 * Ported from the prototype's inline scripts onto the real JSON endpoints via
 * window.fama (http.js). Logic for projects/credits is copied from
 * dashboard.js (talentProjects / talentCredits), which this area does not own.
 * Runtime copy always comes from Blade (`strings`), never hardcoded here.
 */

/** Replace :name placeholders in a translated string. */
function fmt(str, params = {}) {
    return Object.entries(params).reduce(
        (out, [key, value]) => out.replaceAll(`:${key}`, value ?? ''),
        String(str ?? ''),
    );
}

/** Up to two initials for an avatar fallback. */
function initials(name) {
    return String(name || '')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0].toUpperCase())
        .join('');
}

/** Show the fama-ui toast (optional Undo callback). */
function toast(message, undo = null) {
    if (message && window.famaToast) window.famaToast(message, undo);
}

/** The first validation message of an ApiError (or its message). */
function firstError(e, fallback = '') {
    if (e?.errors) {
        const first = Object.values(e.errors)[0];
        if (Array.isArray(first) && first.length) return first[0];
    }
    return e?.message || fallback;
}

/** Ask the shared confirm dialog; resolves true/false. */
function ask(opts) {
    return window.Alpine.store('confirm').ask(opts);
}

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;
    const { get, post, patch, del, ApiError } = window.fama;

    // ---------------------------------------------------------------------
    // Projects list
    // ---------------------------------------------------------------------
    Alpine.data('famaProjects', ({ skills = [], total = 0, slug = '', live = false, me = '', strings = {} } = {}) => ({
        skills,
        slug,
        live,
        me,
        strings,
        fmt,
        initials,
        items: [],
        credited: [],          // accepted credits on other people's projects
        meta: null,
        counts: { all: total, none: 0, by_skill: {} },
        filter: '',            // '' = all, 'none' = untagged, else a skill id (string)
        loading: true,
        page: 1,
        dragId: null,

        /** Load the first page and the confirmed credits in parallel. */
        async init() {
            await Promise.all([this.load(1), this.loadCredited()]);
        },

        /** Fetch one page of the talent's own projects for the active filter. */
        async load(page = 1) {
            this.loading = true;
            try {
                const query = this.filter === '' ? '' : `&skill=${encodeURIComponent(this.filter)}`;
                const { data, meta } = await get(`/talent/projects/data?page=${page}${query}`);
                this.items = data || [];
                this.meta = meta;
                this.page = page;
                if (meta?.counts) this.counts = meta.counts;
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            } finally {
                this.loading = false;
            }
        },

        /** Confirmed credits (ADR-AA) show after the talent's own work. */
        async loadCredited() {
            try {
                const { data } = await get('/talent/credits/data');
                this.credited = (data?.credits || []).filter((c) => c.status === 'accepted');
            } catch (e) {
                this.credited = [];
            }
        },

        /** Switch the skill filter and reload from page 1. */
        async setFilter(value) {
            if (this.filter === value) return;
            this.filter = value;
            await this.load(1);
        },

        /** Chip count: own projects (server counts) + confirmed credits in that skill. */
        countFor(value) {
            if (value === 'none') return this.counts.none ?? 0;
            const extra = this.credited.filter((c) => value === '' || String(c.skill?.id) === String(value)).length;
            if (value === '') return (this.counts.all ?? 0) + extra;
            return (this.counts.by_skill?.[value] ?? 0) + extra;
        },

        /** Credited cards for the active filter, only on the last page. */
        get creditedShown() {
            if (this.filter === 'none' || this.page < this.pages) return [];
            return this.credited.filter((c) => this.filter === '' || String(c.skill?.id) === String(this.filter));
        },

        get pages() {
            return this.meta?.pagination?.last_page ?? 1;
        },

        /** Ordering is only expressible on the whole, unfiltered list. */
        get canReorder() {
            return this.filter === '' && this.pages === 1 && this.items.length > 1;
        },

        titleOf(p) {
            return p.label || p.title?.en || this.strings.untitled;
        },

        /** Public page when the profile is live, else the editor. */
        viewUrl(p) {
            return this.live && this.slug ? `/${this.slug}/work/${p.id}` : this.editUrl(p);
        },

        editUrl(p) {
            return window.fama.localizeUrl(`/talent/projects/${p.id}/edit`);
        },

        creditUrl(c) {
            const owner = c.project?.owner?.slug;
            return owner ? `/${owner}/work/${c.project.id}` : '#';
        },

        /** People on Fama who confirmed their credit on this project. */
        others(p) {
            return (p.credits || [])
                .filter((c) => c.status === 'accepted' && c.is_on_platform)
                .map((c) => c.name);
        },

        waitingCount(p) {
            return (p.credits || []).filter((c) => c.status === 'pending').length;
        },

        /** Up to three faces: you, then two others. */
        faces(others) {
            return [this.me, ...others.slice(0, 2).map(initials)];
        },

        /** "A and B", "A, B and C". */
        names(list) {
            if (list.length < 3) return list.join(` ${this.strings.and} `);
            return `${list.slice(0, -1).join(', ')} ${this.strings.and} ${list[list.length - 1]}`;
        },

        /** "You and Omar" while there are two others at most, then "You and N others". */
        ownersLine(others) {
            return others.length <= 2
                ? this.names([this.strings.you, ...others])
                : fmt(this.strings.ownMany, { n: others.length });
        },

        /** Delete one of the talent's own projects (confirmed). */
        async remove(p) {
            const ok = await ask({
                title: fmt(this.strings.delTitle, { p: this.titleOf(p) }),
                message: this.strings.delBody,
                confirmLabel: this.strings.delOk,
                tone: 'danger',
            });
            if (!ok) return;

            try {
                const { meta } = await del(`/talent/projects/${p.id}`);
                if (meta?.counts) this.counts = meta.counts;
                toast(fmt(this.strings.deleted, { p: this.titleOf(p) }));
                await this.load(this.items.length === 1 && this.page > 1 ? this.page - 1 : this.page);
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            }
        },

        /**
         * Leave a project you own (co-ownership). The server decides how: a
         * co-owner's credit is withdrawn; the creator hands the project to the
         * longest-standing co-owner. `others` names the owners who stay.
         */
        async leave(projectId, title, others) {
            const ok = await ask({
                title: fmt(this.strings.leaveTitle, { p: title }),
                message: fmt(this.strings.leaveBody, { n: this.names(others) }),
                confirmLabel: this.strings.leaveOk,
                tone: 'danger',
            });
            if (!ok) return;

            try {
                const { meta } = await post(`/talent/projects/${projectId}/leave`);
                if (meta?.counts) this.counts = meta.counts;
                toast(fmt(this.strings.left, { p: title }));
                await Promise.all([this.load(this.page), this.loadCredited()]);
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            }
        },

        /** Leave one of your own projects that has co-owners. */
        leaveOwn(p) {
            return this.leave(p.id, this.titleOf(p), this.others(p));
        },

        /** Leave a project you co-own through a confirmed credit. */
        leaveCredited(c) {
            return this.leave(c.project?.id, c.project?.title || '', [c.project?.owner?.display_name || '']);
        },

        /** Start dragging a card (Firefox needs a payload to drag). */
        onDragStart(event, p) {
            if (!this.canReorder) {
                event.preventDefault();
                return;
            }
            this.dragId = p.id;
            event.dataTransfer?.setData('text/plain', String(p.id));
            if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move';
        },

        /** Drop onto a card: move optimistically, then persist the order. */
        async onDrop(target) {
            if (!this.canReorder || this.dragId === null || this.dragId === target.id) return;

            const previous = [...this.items];
            const from = this.items.findIndex((x) => x.id === this.dragId);
            const to = this.items.findIndex((x) => x.id === target.id);
            const [moved] = this.items.splice(from, 1);
            this.items.splice(to, 0, moved);
            this.dragId = null;

            try {
                await patch('/talent/projects/reorder', { order: this.items.map((x) => x.id) });
            } catch (e) {
                this.items = previous;
                toast(firstError(e, this.strings.failed));
            }
        },
    }));

    // ---------------------------------------------------------------------
    // Project editor (new + edit)
    // ---------------------------------------------------------------------

    /** The empty form (English-only in V1; `.ar` keys ride along). */
    const blankForm = () => ({
        title: { en: '', ar: '' },
        role: { en: '', ar: '' },
        summary: { en: '', ar: '' },
        body: { en: '', ar: '' },
        client_name: '',
        url: '',
        occurred_on: '',
        skill_ids: [],
        speciality_ids: [],
        results: [],
    });

    /** Strip an empty contenteditable down to '' so the placeholder shows. */
    const cleanHtml = (el) => {
        const empty = el.textContent.trim() === '' && !el.querySelector('img,video,iframe');
        if (empty && el.innerHTML !== '') el.innerHTML = '';
        return empty ? '' : el.innerHTML;
    };

    let draftKey = 0;

    Alpine.data('famaProjectEdit', ({
        project = null, skills = [], specialities = {}, allSkills = [], slug = '', live = false, editUrl = '', strings = {},
    } = {}) => ({
        skills,
        specialities,
        allSkills,
        slug,
        live,
        editUrlTemplate: editUrl,
        strings,
        initials,

        id: project?.id ?? null,
        form: project
            ? {
                title: { en: '', ar: '', ...(project.title || {}) },
                role: { en: '', ar: '', ...(project.role || {}) },
                summary: { en: '', ar: '', ...(project.summary || {}) },
                body: { en: '', ar: '', ...(project.body || {}) },
                client_name: project.client_name || '',
                url: project.url || '',
                occurred_on: project.occurred_on || '',
                skill_ids: [...(project.skill_ids || [])],
                speciality_ids: [...(project.speciality_ids || [])],
                results: (project.results || []).slice(0, 4).map((r) => ({ label: r.label || '', value: r.value || '', compare: r.compare || '' })),
            }
            : blankForm(),
        originalDate: project?.occurred_on || '',

        // The work + credits: written straight through once the project exists;
        // staged in the page (and sent right after Publish) before that.
        media: project?.media || [],
        staged: [],            // [{ key, url, is_video, file }]
        mediaDrag: null,
        uploading: false,
        credits: project?.credits || [],
        drafts: [],            // [{ key, name, talent, talent_type_id, role, results, busy }]
        coverUrl: project?.cover_image_url || null,
        coverFile: null,
        coverBusy: false,

        // Which blocks are on the page (the summary always is).
        shown: {
            story: !!(project?.body?.en),
            gallery: !!(project?.media?.length),
            results: !!(project?.results?.length),
            credits: !!(project?.credits?.length),
        },

        dirty: false,
        saving: false,
        resave: false,
        publishing: false,
        saveTimer: null,
        savedText: project ? strings.saved : strings.notSaved,
        menu: { open: false, items: [], top: 0, left: 0, firstIndex: 0, onPick: null, anchor: null },

        /** Single-skill talents never have to think about the skill tag. */
        init() {
            if (!this.id && this.skills.length) {
                const primary = this.skills.find((s) => s.is_primary) || this.skills[0];
                this.form.skill_ids = [primary.id];
            }
        },

        // --- Derived ------------------------------------------------------

        get homeSkillId() {
            return this.form.skill_ids[0] ?? null;
        },

        get monthValue() {
            return (this.form.occurred_on || '').slice(0, 7);
        },

        get previewUrl() {
            return this.id && this.live && this.slug ? `/${this.slug}/work/${this.id}?preview=1` : '';
        },

        /** Gallery tiles: server media once created, staged files before. */
        get gallery() {
            return this.id
                ? this.media.map((m) => ({ ...m, key: `m${m.id}` }))
                : this.staged;
        },

        get selectedSpecialities() {
            const all = Object.values(this.specialities).flat();
            return this.form.speciality_ids.map((id) => all.find((s) => s.id === id)).filter(Boolean);
        },

        /** The block types not on the page yet, for the + / "/" menu. */
        get addable() {
            const types = [
                { key: 'story', label: 'Text', sub: 'The full story: the brief, what you did', icon: 'e-text' },
                { key: 'gallery', label: 'Gallery', sub: 'A grid of photos and video', icon: 'e-images' },
                { key: 'results', label: 'Results', sub: 'Numbers the work moved', icon: 'e-chart' },
                { key: 'credits', label: 'Credits', sub: 'The people you made it with', icon: 'e-users' },
            ];
            const labels = this.strings.blocks || {};
            return types
                .filter((t) => !this.shown[t.key])
                .map((t) => ({ ...t, label: labels[t.key]?.[0] || t.label, sub: labels[t.key]?.[1] || t.sub }));
        },

        // --- Edits --------------------------------------------------------

        /** Title is a contenteditable h1; keep the model + placeholder in step. */
        onTitle(el) {
            if (el.textContent === '' && el.innerHTML !== '') el.innerHTML = '';
            this.form.title.en = el.textContent.replace(/\s+/g, ' ').trim() === '' ? '' : el.textContent;
            this.changed();
        },

        /** A rich text block; "/" in an empty summary opens the block menu. */
        onRich(el, field) {
            if (el.textContent === '/') {
                el.innerHTML = '';
                this.form[field].en = '';
                this.openAddMenu(el);
                return;
            }
            this.form[field].en = cleanHtml(el);
            this.changed();
        },

        /** Month picker → occurred_on; an unchanged month keeps the stored day. */
        onMonth(value) {
            if (!value) this.form.occurred_on = '';
            else if (this.originalDate.slice(0, 7) === value) this.form.occurred_on = this.originalDate;
            else this.form.occurred_on = `${value}-01`;
            this.changed();
        },

        focusFirstBlock() {
            this.$root.querySelector('#blocks [contenteditable]')?.focus();
        },

        hasSkill(id) {
            return this.form.skill_ids.includes(id);
        },

        /** Tag/untag a skill; untagging takes its specialities with it. */
        toggleSkill(id) {
            const at = this.form.skill_ids.indexOf(id);
            if (at === -1) this.form.skill_ids.push(id);
            else this.form.skill_ids.splice(at, 1);
            const allowed = new Set(this.form.skill_ids.flatMap((s) => (this.specialities[String(s)] || []).map((x) => x.id)));
            this.form.speciality_ids = this.form.speciality_ids.filter((x) => allowed.has(x));
            this.changed();
        },

        toggleSpeciality(id) {
            const at = this.form.speciality_ids.indexOf(id);
            if (at === -1) this.form.speciality_ids.push(id);
            else this.form.speciality_ids.splice(at, 1);
            this.changed();
        },

        /** Result tile class by what was typed (number / percent / change). */
        metricClass(value) {
            const v = String(value || '');
            const type = /^\s*[+\-−]/.test(v) ? 'change' : (/%\s*$/.test(v) ? 'percent' : 'number');
            return `t-${type}${/^\s*[\-−]/.test(v) ? ' down' : ''}`;
        },

        pctOf(value) {
            return Math.max(0, Math.min(100, parseFloat(value) || 0));
        },

        addResult() {
            if (this.form.results.length >= 4) return;
            this.form.results.push({ label: '', value: '', compare: '' });
            this.$nextTick(() => {
                const tiles = this.$root.querySelectorAll('[data-b="results"] .metric .n');
                tiles[tiles.length - 1]?.focus();
            });
        },

        // --- Saving (publish, then autosave) ------------------------------

        /** Any edit: mark dirty, and autosave once the project exists. */
        changed() {
            this.dirty = true;
            if (!this.id) return;
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => this.save(), 700);
        },

        /** Why the project cannot be saved yet ('' when it can). */
        invalidReason() {
            if ((this.form.title.en || '').trim() === '') return this.strings.needTitle;
            if (!this.form.skill_ids.length) return this.strings.needSkill;
            return '';
        },

        /** The PATCH/POST body — the whole project (it is edited whole). */
        payload() {
            const f = this.form;
            return {
                title: f.title,
                role: f.role,
                summary: f.summary,
                body: f.body,
                client_name: f.client_name || null,
                url: f.url || null,
                occurred_on: f.occurred_on || null,
                skill_ids: f.skill_ids,
                speciality_ids: f.speciality_ids,
                results: f.results
                    .map((r) => ({ label: (r.label || '').trim(), value: (r.value || '').trim(), compare: (r.compare || '').trim() }))
                    .filter((r) => r.label && r.value)
                    .slice(0, 4),
            };
        },

        /** Autosave the existing project (queued while a save is in flight). */
        async save() {
            if (!this.id) return false;
            const reason = this.invalidReason();
            if (reason) {
                this.savedText = reason;
                return false;
            }
            if (this.saving) {
                this.resave = true;
                return false;
            }

            this.saving = true;
            this.savedText = this.strings.saving;
            try {
                const { data } = await patch(`/talent/projects/${this.id}`, this.payload());
                this.originalDate = data?.occurred_on || '';
                this.dirty = false;
                this.savedText = this.strings.saved;
                return true;
            } catch (e) {
                this.savedText = fmt(this.strings.saveFailed, { m: firstError(e, this.strings.failed) });
                return false;
            } finally {
                this.saving = false;
                if (this.resave) {
                    this.resave = false;
                    this.save();
                }
            }
        },

        /** Publish a new project (create + send staged work), or save now. */
        async publish() {
            const reason = this.invalidReason();
            if (reason) {
                toast(reason);
                return;
            }

            if (this.id) {
                clearTimeout(this.saveTimer);
                if (await this.save()) toast(this.strings.live);
                return;
            }

            this.publishing = true;
            this.savedText = this.strings.saving;
            try {
                const { data } = await post('/talent/projects', this.payload());
                this.id = data.id;
                this.originalDate = data.occurred_on || '';
                this.media = data.media || [];
                this.dirty = false;
                window.history.replaceState(null, '', this.editUrlTemplate.replace('__ID__', data.id));

                if (this.coverFile) await this.sendCover(this.coverFile);
                if (this.staged.length) await this.uploadFiles(this.staged.map((s) => s.file));
                this.staged = [];

                const asked = [];
                for (const d of this.drafts.filter((x) => this.draftComplete(x))) {
                    if (await this.commitDraft(d, true) && d.talent) asked.push(d.talent.display_name);
                }

                this.savedText = this.strings.saved;
                if (asked.length === 1) toast(fmt(this.strings.publishedAsked1, { n: asked[0] }));
                else if (asked.length > 1) toast(fmt(this.strings.publishedAskedN, { n: asked.length }));
                else toast(this.strings.published1);
            } catch (e) {
                this.savedText = this.id ? this.strings.saved : this.strings.notSaved;
                toast(firstError(e, this.strings.failed));
            } finally {
                this.publishing = false;
            }
        },

        /** Warn before leaving an unpublished draft or an unsaved edit. */
        onBeforeUnload(event) {
            if (this.dirty || this.saving) {
                event.preventDefault();
                event.returnValue = this.strings.leave;
            }
        },

        // --- Cover --------------------------------------------------------

        /** Upload straight away on a project, or stage it on a draft. */
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

        /** Remove the cover (the lead photo then stands in on cards). */
        async removeCover() {
            if (!this.id) {
                this.coverFile = null;
                this.coverUrl = null;
                return;
            }
            try {
                const { data } = await del(`/talent/projects/${this.id}/cover`);
                // Without a cover the resource falls back to the lead frame;
                // the banner shows only a real cover.
                this.coverUrl = null;
                if (data) toast(this.strings.coverRemoved);
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            }
        },

        // --- Gallery ------------------------------------------------------

        /** Add files: upload on a project, stage on a draft. */
        async addMedia(fileList) {
            const files = Array.from(fileList || []);
            if (!files.length) return;
            if (!this.id) {
                files.forEach((file) => this.staged.push({
                    key: `s${++draftKey}`,
                    url: URL.createObjectURL(file),
                    is_video: file.type.startsWith('video/'),
                    file,
                }));
                this.dirty = true;
                return;
            }
            await this.uploadFiles(files);
        },

        /** POST files in batches of 12 (the request's cap); uploads append. */
        async uploadFiles(files) {
            this.uploading = true;
            try {
                for (let i = 0; i < files.length; i += 12) {
                    const body = new FormData();
                    files.slice(i, i + 12).forEach((file) => body.append('files[]', file));
                    const { data } = await post(`/talent/projects/${this.id}/media`, body);
                    this.media = data.media || [];
                }
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            } finally {
                this.uploading = false;
            }
        },

        /** Remove a tile (server files are confirmed — they are gone for good). */
        async removeMedia(tile) {
            if (!this.id) {
                this.staged = this.staged.filter((s) => s.key !== tile.key);
                return;
            }
            const ok = await ask({
                title: this.strings.removeMediaTitle,
                message: this.strings.removeMediaBody,
                confirmLabel: this.strings.removeOk,
                tone: 'danger',
            });
            if (!ok) return;
            try {
                const { data } = await del(`/talent/projects/${this.id}/media/${tile.id}`);
                this.media = data.media || [];
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            }
        },

        onMediaDragStart(event, tile) {
            this.mediaDrag = tile.key;
            event.dataTransfer?.setData('text/plain', tile.key);
            if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move';
        },

        /** Reorder optimistically, then persist — the ORDER is the edit. */
        async onMediaDrop(target) {
            const key = this.mediaDrag;
            this.mediaDrag = null;
            if (!key || key === target.key) return;

            const list = this.id ? this.media : this.staged;
            const keyOf = (x) => (this.id ? `m${x.id}` : x.key);
            const previous = [...list];
            const from = list.findIndex((x) => keyOf(x) === key);
            const to = list.findIndex((x) => keyOf(x) === target.key);
            if (from < 0 || to < 0) return;
            const [moved] = list.splice(from, 1);
            list.splice(to, 0, moved);

            if (!this.id) return;
            try {
                await patch(`/talent/projects/${this.id}/media/reorder`, { order: this.media.map((m) => m.id) });
            } catch (e) {
                this.media = previous;
                toast(firstError(e, this.strings.failed));
            }
        },

        // --- Credits ------------------------------------------------------

        /** A credit's confirmation state, said plainly. */
        creditState(c) {
            const s = this.strings.creditStates || {};
            if (c.status === 'accepted') return s.accepted || 'Confirmed';
            if (c.status === 'pending') return s.pending || 'Waiting for them to confirm';
            if (c.status === 'declined') return s.declined || 'Declined';
            return s.listed || 'Not on Fama — listed on the project';
        },

        draftState(d) {
            const s = this.strings.creditStates || {};
            if (!d.talent) return s.listedDraft || 'Not on Fama — listed once saved';
            return this.id ? (s.sending || 'Asked to confirm once saved') : (s.onPublish || 'Asked to confirm when you publish');
        },

        addDraft() {
            const d = { key: `d${++draftKey}`, name: '', talent: null, talent_type_id: '', role: '', results: [], timer: null };
            this.drafts.push(d);
            this.$nextTick(() => this.$root.querySelector(`[data-draft="${d.key}"]`)?.focus());
        },

        /** Typing a name makes it off-platform until a Fama person is picked. */
        onDraftName(d) {
            d.talent = null;
            clearTimeout(d.timer);
            const q = d.name.trim();
            if (!q) {
                d.results = [];
                return;
            }
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

        draftComplete(d) {
            return (d.talent || d.name.trim()) && d.talent_type_id && d.role.trim();
        },

        /** Leaving a complete row saves it (on a project that exists). */
        onDraftBlur(event, d) {
            if (event.currentTarget.contains(event.relatedTarget)) return;
            d.results = [];
            this.maybeCommit(d);
        },

        maybeCommit(d) {
            if (this.id && this.draftComplete(d)) this.commitDraft(d);
        },

        /** POST one credit; on success the server list replaces the draft. */
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

        /** Remove a saved credit (confirmed — it may be on their profile). */
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

        // --- Menus (+ / "/" block menu, specialities picker) ---------------

        /** Open a menu under an anchor element (viewport coordinates). */
        openMenu(anchor, items, onPick) {
            const r = anchor.getBoundingClientRect();
            const firstIndex = items.findIndex((it) => !it.header);
            this.menu = {
                open: true,
                items,
                onPick,
                anchor,
                firstIndex,
                top: r.bottom + 6,
                left: Math.max(8, Math.min(r.left, window.innerWidth - 296)),
            };
            this.$nextTick(() => this.$refs.menu?.querySelector('button')?.focus());
        },

        closeMenu() {
            if (!this.menu.open) return;
            this.menu.open = false;
        },

        pickMenu(item) {
            const fn = this.menu.onPick;
            this.closeMenu();
            if (fn) fn(item);
        },

        /** Arrow keys move between items; Escape returns focus to the anchor. */
        onMenuKey(event) {
            const buttons = [...this.$refs.menu.querySelectorAll('button')];
            const at = buttons.indexOf(document.activeElement);
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                const next = buttons[(at + (event.key === 'ArrowDown' ? 1 : -1) + buttons.length) % buttons.length];
                buttons.forEach((b) => b.classList.remove('on'));
                next?.classList.add('on');
                next?.focus();
            }
            if (event.key === 'Escape') {
                const anchor = this.menu.anchor;
                this.closeMenu();
                anchor?.focus();
            }
        },

        /** The + menu: reveal a block that is not on the page yet. */
        openAddMenu(anchor) {
            if (!this.addable.length) return;
            this.openMenu(anchor, this.addable, (item) => {
                this.shown[item.key] = true;
                this.$nextTick(() => {
                    const blk = this.$root.querySelector(`[data-b="${item.key}"]`);
                    blk?.querySelector('[contenteditable], input, button:not(.gbtn)')?.focus();
                    if (item.key === 'credits' && !this.credits.length && !this.drafts.length) this.addDraft();
                    if (item.key === 'results' && !this.form.results.length) this.addResult();
                });
            });
        },

        /** The specialities picker: what is left under the tagged skills. */
        openSpecMenu(anchor) {
            const items = [];
            this.form.skill_ids.forEach((skillId) => {
                const skill = this.skills.find((s) => s.id === skillId);
                const left = (this.specialities[String(skillId)] || []).filter((s) => !this.form.speciality_ids.includes(s.id));
                if (!skill || !left.length) return;
                items.push({ key: `h${skillId}`, header: true, label: skill.name });
                left.forEach((s) => items.push({ key: `s${s.id}`, id: s.id, label: s.name }));
            });
            if (!items.length) {
                toast(this.strings.allSpecs);
                return;
            }
            this.openMenu(anchor, items, (item) => this.toggleSpeciality(item.id));
        },
    }));

    // ---------------------------------------------------------------------
    // Credits (the credited person's side)
    // ---------------------------------------------------------------------
    Alpine.data('famaCredits', ({ held = [], slug = '', skillsUrl = '', strings = {} } = {}) => ({
        held,
        slug,
        skillsUrl,
        strings,
        fmt,
        credits: [],
        loaded: false,

        init() {
            this.load();
        },

        /** The credits naming this talent: pending, then accepted. */
        async load() {
            try {
                const { data } = await get('/talent/credits/data');
                this.credits = data?.credits || [];
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            } finally {
                this.loaded = true;
            }
        },

        get pending() {
            return this.credits.filter((c) => c.status === 'pending');
        },

        get accepted() {
            return this.credits.filter((c) => c.status === 'accepted');
        },

        /** Does the talent hold the credited skill (so it gets its own tab)? */
        holds(c) {
            return this.held.includes(c.skill?.id);
        },

        noteText(c) {
            return this.holds(c)
                ? fmt(this.strings.whereTab, { s: c.skill?.name || '' })
                : fmt(this.strings.noskill, { s: c.skill?.name || '' });
        },

        projectUrl(c) {
            const owner = c.project?.owner?.slug;
            return owner ? `/${owner}/work/${c.project.id}` : '#';
        },

        profileUrl(c) {
            return `/${this.slug}?skill=${encodeURIComponent(c.skill?.slug || '')}`;
        },

        /** "3 Oct 2026". */
        date(iso) {
            return new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        async respond(c, action) {
            await patch(`/talent/credits/${c.id}/${action}`);
            await this.load();
        },

        async accept(c) {
            try {
                await this.respond(c, 'accept');
                toast(fmt(this.strings.tConfirmed, { p: c.project?.title || '' }));
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            }
        },

        async decline(c) {
            const ok = await ask({
                title: this.strings.declineTitle,
                message: this.strings.declineBody,
                confirmLabel: this.strings.declineOk,
                tone: 'danger',
            });
            if (!ok) return;
            try {
                await this.respond(c, 'decline');
                toast(fmt(this.strings.tDeclined, { p: c.project?.title || '' }));
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            }
        },

        /** Leaving = the same decline transition, asked differently. */
        async withdraw(c) {
            const ok = await ask({
                title: fmt(this.strings.wTitle, { p: c.project?.title || '' }),
                message: this.strings.wBody,
                confirmLabel: this.strings.wOk,
                tone: 'danger',
            });
            if (!ok) return;
            try {
                // Leaving goes through the ONE leave path (co-ownership rules).
                await post(`/talent/projects/${c.project.id}/leave`);
                await this.load();
                toast(fmt(this.strings.tWithdrawn, { p: c.project?.title || '' }));
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            }
        },
    }));

    // The whole Recognitions section on/off — the profile-level `recognitions`
    // block's visibility (existing endpoint), with Undo.
    Alpine.data('famaRecognitionsSwitch', ({ url = null, on = true, strings = {} } = {}) => ({
        url,
        on,
        busy: false,
        strings,

        /** Flip visibility; the toast's Undo flips it back. */
        async toggle(undoing = false) {
            if (!this.url || this.busy) return;
            this.busy = true;
            const next = !this.on;
            try {
                await patch(this.url, { is_visible: next });
                this.on = next;
                if (!undoing) toast(next ? this.strings.on : this.strings.off, () => this.toggle(true));
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            } finally {
                this.busy = false;
            }
        },
    }));

    // ---------------------------------------------------------------------
    // Write a recognition
    // ---------------------------------------------------------------------
    Alpine.data('famaRecognise', ({ url = '', relationship = '', body = '', author = '', strings = {} } = {}) => ({
        url,
        author,
        strings,
        fmt,
        form: { relationship, body },
        errors: {},
        saving: false,
        done: false,

        /** Check the prototype's lengths client-side, then post. */
        async submit() {
            this.errors = {};
            const how = this.form.relationship.trim();
            const say = this.form.body.trim();
            if (how.length < 8) {
                this.errors = { relationship: this.strings.eHow };
                this.$refs.how.focus();
                return;
            }
            if (say.length < 40) {
                this.errors = { body: this.strings.eSay };
                this.$refs.say.focus();
                return;
            }

            this.saving = true;
            try {
                await post(this.url, { relationship: how, body: say });
                this.done = true;
            } catch (e) {
                if (e instanceof ApiError && e.errors) {
                    this.errors = Object.fromEntries(Object.entries(e.errors).map(([k, v]) => [k, v[0]]));
                } else {
                    this.errors = { _: firstError(e) };
                }
            } finally {
                this.saving = false;
            }
        },
    }));

    // ---------------------------------------------------------------------
    // Follow the project's owner (public project page)
    // ---------------------------------------------------------------------
    // A guest's POST answers 401 with the intent captured server-side, so the
    // follow replays after they sign in.
    Alpine.data('famaProjectFollow', ({ id, following = false } = {}) => ({
        following,
        busy: false,

        /**
         * Optimistic follow toggle for a signed-in viewer. A visitor is checked
         * FIRST: the button never flips — the POST records the intent (401), then
         * they go to the talent login and the follow replays after it.
         */
        async toggle() {
            if (this.busy) return;
            this.busy = true;
            if (window.famaSignedIn === false) {
                try { await post(`/follow/talent/${id}`); } catch (e) { /* 401 expected — intent captured */ }
                window.location.href = window.fama.localizeUrl('/login?role=talent');
                return;
            }
            const was = this.following;
            this.following = !was;
            try {
                if (was) await del(`/follow/talent/${id}`);
                else await post(`/follow/talent/${id}`);
            } catch (e) {
                this.following = was;
                if (e instanceof ApiError && e.status === 401) {
                    window.location.href = window.fama.localizeUrl('/login?role=talent');
                } else {
                    toast(firstError(e));
                }
            } finally {
                this.busy = false;
            }
        },
    }));
});
