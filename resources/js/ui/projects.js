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

        /** "Desert Light — 2026" → { name: "Desert Light", year: "2026" } (the year shows as a tag on the picture). */
        splitTitle(title) {
            const parts = String(title || '').split(/\s+[—–-]\s+/);
            const last = parts.length > 1 ? parts[parts.length - 1] : '';
            const year = /^(19|20)\d\d$/.test(last) ? last : '';
            return { name: year ? parts.slice(0, -1).join(' — ') : String(title || ''), year };
        },

        /** The card's name: the title without a trailing year. */
        nameOf(p) {
            return this.splitTitle(this.titleOf(p)).name;
        },

        /** The card's year tag: from the title when it has one, otherwise from the project's date. */
        yearOf(p) {
            return this.splitTitle(this.titleOf(p)).year || (p.year ? String(p.year) : '');
        },

        /** The card's picture: the cover, else the first photo of the work. */
        coverOf(p) {
            return p.cover_image_url || (p.media || []).find((m) => !m.is_video)?.url || '';
        },

        /** The star's tooltip: why it can't be switched off, or what it does. */
        featureTitle(p) {
            if (p.featured_locked) return this.strings.featuredOnly;
            if (p.is_featured) return this.strings.featuredA;
            return this.strings.featureT;
        },

        /** The star's accessible name (names the project). */
        featureLabel(p) {
            return p.is_featured
                ? fmt(this.strings.featuredL, { p: this.titleOf(p) })
                : fmt(this.strings.featureA, { p: this.titleOf(p) });
        },

        /** Put a project on the profile cover (optimistic; the old pick steps down). */
        async feature(p) {
            if (p.is_featured) return;
            const before = this.items.map((x) => [x.id, x.is_featured, x.featured_locked]);
            this.items.forEach((x) => { x.is_featured = x.id === p.id; x.featured_locked = false; });
            try {
                await post(`/talent/projects/${p.id}/feature`);
                toast(fmt(this.strings.featuredT, { p: this.titleOf(p) }));
            } catch (e) {
                before.forEach(([id, f, l]) => { const x = this.items.find((i) => i.id === id); if (x) { x.is_featured = f; x.featured_locked = l; } });
                toast(firstError(e, this.strings.failed));
            }
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
    // Project editor (new + edit) — fama-ui project-editor.js (24 Oct–1 Nov
    // 2026): the project is edited on a page that looks like the published
    // one. A Fama project is a fixed set of fields, so the blocks are fixed
    // (opening line, Photos, Text, Results, Credits) and sit in page order.
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

    /** Escape text for HTML (the opening line is stored as one safe paragraph). */
    const escHtml = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    /** A result row as the editor holds it (a stable key for x-for). */
    const resultRow = (r = {}) => ({ key: `r${++draftKey}`, label: r.label || '', value: r.value || '', compare: r.compare || '' });

    let draftKey = 0;

    Alpine.data('famaProjectEdit', ({
        project = null, skills = [], specialities = {}, allSkills = [], slug = '', live = false, editUrl = '', projectsUrl = '',
        strings = {}, projectCount = 0, me = {},
    } = {}) => ({
        // Wide screens keep the site's side bar and dock the bar at the top of
        // the page panel; narrow ones hand the site header to the bar. Read once
        // here (the layout sets html.fx-has-side before paint), like the prototype.
        docked: document.documentElement.classList.contains('fx-has-side') && window.matchMedia('(min-width:1101px)').matches,
        // Featured = the profile cover. A talent's only project is featured and
        // stays featured; otherwise it changes by featuring another project.
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
                results: (project.results || []).slice(0, 4).map(resultRow),
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

        // Which optional blocks are on the page (the opening line always is).
        shown: {
            gallery: !!(project?.media?.length),
            story: !!(project?.body?.en),
            results: !!(project?.results?.length),
            credits: !!(project?.credits?.length),
        },
        active: null,          // the block being worked on (its ring)

        dirty: false,
        saving: false,
        resave: false,
        publishing: false,
        saveState: project ? 'ok' : 'none',   // ok | busy | bad | none (drives the dot)
        // Inline field errors, keyed by field: title, skills, specialities,
        // client, role, date, url, summary, story, results.
        errors: {},
        saveTimer: null,
        savedText: project ? strings.saved : strings.notSaved,
        menu: { open: false, items: [], top: 0, left: 0, hl: 0, onPick: null, anchor: null },
        panelMounted: false,
        panelOpen: false,
        panelReturn: null,

        /**
         * Single-skill talents never have to think about the skill tag; on a
         * narrow screen the site header steps aside for the editor's bar.
         */
        init() {
            if (!this.id && this.skills.length) {
                const primary = this.skills.find((s) => s.is_primary) || this.skills[0];
                this.form.skill_ids = [primary.id];
            }
            if (!this.docked) document.querySelector('.fx-nav')?.classList.add('fx-editor');
        },

        // --- Derived ------------------------------------------------------

        get homeSkillId() {
            return this.form.skill_ids[0] ?? null;
        },

        /** The name in the bar. */
        get displayName() {
            return (this.form.title.en || '').trim() || this.strings.untitled;
        },

        get savedDot() {
            return { busy: this.saveState === 'busy' || this.saveState === 'none', bad: this.saveState === 'bad' };
        },

        /** The cover's year tag: the project's date (Settings). */
        get year() {
            return /^\d{4}/.test(this.form.occurred_on || '') ? this.form.occurred_on.slice(0, 4) : '';
        },

        /** The cover's kicker: client · first skill — written for you from the colophon. */
        get kicker() {
            const first = this.skills.find((s) => s.id === this.homeSkillId);
            return [(this.form.client_name || '').trim(), first?.name].filter(Boolean).join(' · ');
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

        /** How the pictures lay out on the page, said in the block's note. */
        get galleryNote() {
            const n = this.gallery.length;
            const g = this.strings.gallery || {};
            return n === 0 ? g.none : (n === 1 ? g.one : g.many);
        },

        get selectedSkills() {
            return this.form.skill_ids.map((id) => this.skills.find((s) => s.id === id)).filter(Boolean);
        },

        get selectedSpecialities() {
            const all = Object.values(this.specialities).flat();
            return this.form.speciality_ids.map((id) => all.find((s) => s.id === id)).filter(Boolean);
        },

        /** The blocks not on the page yet, grouped as the prototype's block menu (Write · Show · Prove). */
        get addable() {
            const labels = this.strings.blocks || {};
            const groups = this.strings.groups || {};
            const types = [
                ['story', 'write', 'e-text'], ['gallery', 'show', 'e-images'], ['results', 'prove', 'e-chart'], ['credits', 'prove', 'e-users'],
            ].filter(([key]) => !this.shown[key]);
            const items = [];
            ['write', 'show', 'prove'].forEach((group) => {
                const inGroup = types.filter((t) => t[1] === group);
                if (!inGroup.length) return;
                items.push({ key: `g-${group}`, header: true, label: groups[group] || group });
                inGroup.forEach(([key, , icon]) => items.push({ key, icon, label: labels[key]?.[0] || key, sub: labels[key]?.[1] || '' }));
            });
            return items;
        },

        // --- Typing in place -------------------------------------------------

        /** The ring follows the block being worked on. */
        onFocusIn(event) {
            this.active = event.target.closest('[data-b]')?.dataset.b || null;
        },

        /** Title is a contenteditable h1; keep the model + placeholder in step. */
        onTitle(el) {
            if (el.textContent === '' && el.innerHTML !== '') el.innerHTML = '';
            this.form.title.en = el.textContent.replace(/\s+/g, ' ').trim() === '' ? '' : el.textContent.replace(/\s+/g, ' ');
            this.changed('title');
        },

        /** One-line fields typed where they show (client, role). */
        onPlain(el, field) {
            if (el.textContent === '' && el.innerHTML !== '') el.innerHTML = '';
            const value = el.textContent.replace(/\s+/g, ' ');
            if (field === 'client') this.form.client_name = value.trim() === '' ? '' : value;
            else this.form.role.en = value.trim() === '' ? '' : value;
            this.changed(field);
        },

        /** The plain text inside stored HTML (DOMParser never runs scripts). */
        textOf(html) {
            if (!html) return '';
            return (new DOMParser().parseFromString(String(html), 'text/html').body.textContent || '').replace(/\s+/g, ' ').trim();
        },

        /** The opening line: one line of text, stored as one safe paragraph. "/" opens the block menu. */
        onLead(el) {
            if (el.textContent === '/') {
                el.textContent = '';
                this.openAddMenu(el);
                return;
            }
            if (el.textContent === '' && el.innerHTML !== '') el.innerHTML = '';
            const text = el.textContent.replace(/\s+/g, ' ').trim();
            this.form.summary.en = text ? `<p>${escHtml(text)}</p>` : '';
            this.changed('summary');
        },

        /** The full story (rich text); "/" in an empty story opens the block menu. */
        onBody(el) {
            if (el.textContent === '/') {
                el.innerHTML = '';
                this.form.body.en = '';
                this.openAddMenu(el);
                return;
            }
            this.form.body.en = cleanHtml(el);
            this.changed('story');
        },

        /**
         * Paste as plain text, so nothing but words gets in. In the story,
         * several paragraphs stay paragraphs; anywhere else they join into one line.
         */
        onPaste(event, multi = false) {
            event.preventDefault();
            const txt = (event.clipboardData || window.clipboardData).getData('text/plain').replace(/\r/g, '');
            const paras = txt.split(/\n\s*\n+/).map((x) => x.replace(/\s*\n\s*/g, ' ').trim()).filter(Boolean);
            if (multi && paras.length > 1) document.execCommand('insertHTML', false, paras.map((p) => `<p>${escHtml(p)}</p>`).join(''));
            else document.execCommand('insertText', false, paras.join(' '));
        },

        focusLead() {
            this.$root.querySelector('[data-b="lead"] [contenteditable]')?.focus();
        },

        /** Enter on the opening line: on to the story when it is on the page. */
        focusBlock(key) {
            this.$root.querySelector(`[data-b="${key}"] [contenteditable]`)?.focus();
        },

        // --- Skills & specialities ------------------------------------------

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
            this.changed('skills');
        },

        toggleSpeciality(id) {
            const at = this.form.speciality_ids.indexOf(id);
            if (at === -1) this.form.speciality_ids.push(id);
            else this.form.speciality_ids.splice(at, 1);
            this.changed('specialities');
        },

        /** "+ Skill": the (home owner's) skills not on it yet. */
        openSkillMenu(anchor) {
            const left = this.skills.filter((s) => !this.hasSkill(s.id));
            if (!left.length) {
                toast(this.strings.allSkills);
                return;
            }
            this.openMenu(anchor, left.map((s) => ({ key: `s${s.id}`, id: s.id, label: s.name })), (item) => this.toggleSkill(item.id));
        },

        /** "+ Speciality": what is left under the tagged skills, grouped by skill. */
        openSpecMenu(anchor) {
            const items = [];
            this.form.skill_ids.forEach((skillId) => {
                const skill = this.skills.find((s) => s.id === skillId);
                const left = (this.specialities[String(skillId)] || []).filter((s) => !this.form.speciality_ids.includes(s.id));
                if (!skill || !left.length) return;
                items.push({ key: `h${skillId}`, header: true, label: skill.name });
                left.forEach((s) => items.push({ key: `p${s.id}`, id: s.id, label: s.name }));
            });
            if (!items.length) {
                toast(this.strings.allSpecs);
                return;
            }
            this.openMenu(anchor, items, (item) => this.toggleSpeciality(item.id));
        },

        // --- Results ----------------------------------------------------------

        /** A result shows by what was typed: + or − is a change, a trailing % a percent. */
        resultKind(value) {
            const v = String(value || '');
            return /^\s*[+\-−]/.test(v) ? 'change' : (/%\s*$/.test(v) ? 'percent' : 'number');
        },

        /** The prototype's tile classes: a rise is `up`, a fall `up down`. */
        resultClass(value) {
            if (this.resultKind(value) !== 'change') return '';
            return /^\s*[\-−]/.test(String(value || '')) ? 'up down' : 'up';
        },

        pctOf(value) {
            return Math.max(0, Math.min(100, parseFloat(value) || 0));
        },

        addResult() {
            if (this.form.results.length >= 4) return;
            this.form.results.push(resultRow());
            this.$nextTick(() => {
                const tiles = this.$root.querySelectorAll('[data-b="results"] .pe-item > b');
                tiles[tiles.length - 1]?.focus();
            });
        },

        /** Remove a number, with Undo. */
        removeResult(k) {
            const [gone] = this.form.results.splice(k, 1);
            this.changed('results');
            toast(fmt(this.strings.blockRemoved, { b: gone.value || this.strings.kinds?.results || '' }), () => {
                this.form.results.splice(k, 0, gone);
                this.changed('results');
            });
        },

        // --- Saving (publish, then autosave) ------------------------------

        /** Any edit: clear that field's error, mark dirty, autosave once the project exists. */
        changed(field = null) {
            if (field && this.errors[field]) delete this.errors[field];
            this.dirty = true;
            if (!this.id) return;
            this.saveState = 'busy';
            this.savedText = this.strings.saving;
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => this.save(), 700);
        },

        /** Client-side checks, as field → message ({} when it can be saved). */
        validate() {
            const errors = {};
            if ((this.form.title.en || '').trim() === '') errors.title = this.strings.needTitle;
            if (!this.form.skill_ids.length) errors.skills = this.strings.needSkill;
            const url = (this.form.url || '').trim();
            if (url && !/^https?:\/\/[^\s.]+\.[^\s]+$/i.test(url)) errors.url = this.strings.badUrl;
            return errors;
        },

        /** Which editor field a server error key belongs to ('' when none). */
        fieldOf(key) {
            const map = [
                [/^title/, 'title'], [/^skill_ids/, 'skills'], [/^speciality_ids/, 'specialities'],
                [/^client_name/, 'client'], [/^role/, 'role'], [/^occurred_on/, 'date'], [/^url/, 'url'],
                [/^summary/, 'summary'], [/^body/, 'story'], [/^results/, 'results'],
            ];
            return (map.find(([re]) => re.test(key)) || [null, ''])[1];
        },

        /** Field → the element to bring into view, in page order. */
        fieldTargets() {
            return {
                title: '#peTitle', client: '[data-f="client"] dd', role: '[data-f="role"] dd',
                skills: '[data-act="skadd"]', specialities: '[data-act="specadd"]', url: '[data-f="url"] input',
                summary: '[data-b="lead"] [contenteditable]', story: '[data-b="story"] [contenteditable]',
                results: '[data-b="results"]', date: null,
            };
        },

        /**
         * Put errors on their fields and bring the first into view. Server
         * messages with no field on this page come back for a toast.
         */
        showErrors(errors) {
            const rest = [];
            const mapped = {};
            for (const [key, msg] of Object.entries(errors)) {
                const text = Array.isArray(msg) ? msg[0] : msg;
                const field = this.fieldOf(key) || (key in this.fieldTargets() ? key : '');
                if (field) mapped[field] ??= text;
                else rest.push(text);
            }
            if (mapped.story) this.shown.story = true;
            if (mapped.results) this.shown.results = true;
            this.errors = mapped;
            this.$nextTick(() => {
                const targets = this.fieldTargets();
                const first = Object.keys(targets).find((f) => mapped[f] && targets[f]);
                this.reveal(first && targets[first]);
            });
            return rest;
        },

        /** Scroll a field into view and focus it. */
        reveal(selector) {
            const el = selector && this.$root.querySelector(selector);
            if (!el) return;
            el.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
            el.focus({ preventScroll: true });
        },

        /** Show a failed request: field errors inline, anything else as a toast. */
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
                summary: f.summary,
                body: f.body,
                client_name: (f.client_name || '').trim() || null,
                url: (f.url || '').trim() || null,
                occurred_on: f.occurred_on || null,
                skill_ids: f.skill_ids,
                speciality_ids: f.speciality_ids,
                ...(this.id ? {} : { featured: this.featured }),
                results: f.results
                    .map((r) => ({ label: (r.label || '').trim(), value: (r.value || '').trim(), compare: (r.compare || '').trim() }))
                    .filter((r) => r.label && r.value)
                    .slice(0, 4),
            };
        },

        /** The Featured switch's note: why it is locked, or what it does. */
        get featuredNote() {
            if (this.featuredLocked) return this.strings.featOnly;
            if (this.featured) return this.id ? this.strings.featOnSaved : this.strings.featOnNew;
            return this.strings.featOff;
        },

        /**
         * Flip the Featured switch. A new project carries the choice to Publish;
         * an existing one is featured right away. Switching OFF is not a thing —
         * featuring another project replaces this one (FeaturedProjectService).
         */
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
            if (this.saving) {
                this.resave = true;
                return false;
            }

            this.saving = true;
            this.saveState = 'busy';
            this.savedText = this.strings.saving;
            try {
                const { data } = await patch(`/talent/projects/${this.id}`, this.payload());
                this.originalDate = data?.occurred_on || '';
                this.dirty = false;
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
                if (this.resave) {
                    this.resave = false;
                    this.save();
                }
            }
        },

        /**
         * The main button. Everything missing is listed at once in a menu under
         * it (each line takes you to its field); otherwise a new project is
         * created (+ its staged work sent) or an existing one saved now.
         */
        async publish(anchor) {
            const invalid = this.validate();
            if (invalid.title || invalid.skills) {
                const items = [{ key: 'need-h', header: true, label: this.strings.needHead }];
                if (invalid.title) items.push({ key: 'need-title', need: true, label: this.strings.needTitle, target: '#peTitle' });
                if (invalid.skills) items.push({ key: 'need-skill', need: true, label: this.strings.needSkill, target: '[data-act="skadd"]' });
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
                this.dirty = false;
                this.errors = {};
                this.featured = !!data.is_featured;
                this.featuredLocked = !!data.featured_locked;
                window.history.replaceState(null, '', this.editUrlTemplate.replace('__ID__', data.id));

                if (this.coverFile) await this.sendCover(this.coverFile);
                if (this.staged.length) await this.uploadFiles(this.staged.map((s) => s.file));
                this.staged = [];

                const asked = [];
                for (const d of this.drafts.filter((x) => this.draftComplete(x))) {
                    if (await this.commitDraft(d, true) && d.talent) asked.push(d.talent.display_name);
                }

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

        /** Warn before leaving an unpublished draft or an unsaved edit. */
        onBeforeUnload(event) {
            if (this.dirty || this.saving) {
                event.preventDefault();
                event.returnValue = this.strings.leave;
            }
        },

        /** Escape closes the menu first, then Settings. */
        onEscape() {
            if (this.menu.open) {
                const anchor = this.menu.anchor;
                this.closeMenu();
                anchor?.focus();
            } else if (this.panelMounted) {
                this.closeSettings();
            }
        },

        /** Cmd/Ctrl+S saves now (the browser's own Save page is never what you meant here). */
        onKey(event) {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 's') {
                event.preventDefault();
                if (this.id) {
                    clearTimeout(this.saveTimer);
                    this.save();
                }
            }
        },

        // --- Settings (what visitors never see) -----------------------------

        /** Slide the panel in and put focus on its first field. */
        openSettings(anchor) {
            this.closeMenu();
            this.panelReturn = anchor || document.activeElement;
            this.panelMounted = true;
            this.$nextTick(() => setTimeout(() => {
                this.panelOpen = true;
                this.$refs.panel?.querySelector('#peDate')?.focus();
            }, 20));
        },

        /** Slide it out, then give focus back to what opened it. */
        closeSettings() {
            this.panelOpen = false;
            setTimeout(() => { this.panelMounted = false; }, 200);
            this.panelReturn?.focus?.();
        },

        /** Keep Tab inside the open panel. */
        trapPanel(event) {
            const items = [...this.$refs.panel.querySelectorAll('button, input, [href]')].filter((x) => !x.disabled && x.offsetParent !== null);
            if (!items.length) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        },

        /** Month picker → occurred_on; an unchanged month keeps the stored day. */
        onMonth(value) {
            if (!value) this.form.occurred_on = '';
            else if (this.originalDate.slice(0, 7) === value) this.form.occurred_on = this.originalDate;
            else this.form.occurred_on = `${value}-01`;
            this.changed('date');
        },

        /** Leave a project with other owners (the ONE leave path decides how). */
        async leaveProject() {
            const name = this.displayName;
            const ok = await ask({ title: fmt(this.strings.leaveTitle, { p: name }), message: this.strings.leaveBody, confirmLabel: this.strings.leaveOk, tone: 'danger' });
            if (!ok) return;
            try {
                await post(`/talent/projects/${this.id}/leave`);
                this.dirty = false;
                window.location.href = this.projectsUrl;
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            }
        },

        /** Delete a project you own alone (the server refuses while others own it). */
        async deleteProject() {
            const name = this.displayName;
            const ok = await ask({ title: fmt(this.strings.delTitle, { p: name }), message: this.strings.delBody, confirmLabel: this.strings.delOk, tone: 'danger' });
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

        /** Remove the cover (the first picture then stands in on the page and cards). */
        async removeCover() {
            if (!this.id) {
                this.coverFile = null;
                this.coverUrl = null;
                return;
            }
            try {
                const { data } = await del(`/talent/projects/${this.id}/cover`);
                this.coverUrl = null;
                if (data) toast(this.strings.coverRemoved);
            } catch (e) {
                toast(firstError(e, this.strings.failed));
            }
        },

        // --- Photos -------------------------------------------------------

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

        /** Remove a picture (server files are confirmed — they are gone for good). */
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

        /** Drop a picture on another: it takes that place. */
        onMediaDrop(target) {
            const key = this.mediaDrag;
            this.mediaDrag = null;
            if (!key || key === target.key) return;
            const keys = this.gallery.map((m) => m.key);
            this.reorderMedia(keys.indexOf(key), keys.indexOf(target.key));
        },

        /** The arrows on a picture: one place earlier (-1) or later (+1). */
        moveMedia(k, dir) {
            this.reorderMedia(k, k + dir);
            this.$nextTick(() => {
                const tiles = this.$root.querySelectorAll('.pe-strip .pe-ph:not(.add)');
                tiles[k + dir]?.querySelector('.pe-ph-acts button')?.focus();
            });
        },

        /** Move from → to optimistically, then persist — the ORDER is the edit. */
        async reorderMedia(from, to) {
            const list = this.id ? this.media : this.staged;
            if (from < 0 || to < 0 || from >= list.length || to >= list.length || from === to) return;
            const previous = [...list];
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
            if (c.status === 'accepted') return s.accepted;
            if (c.status === 'pending') return s.pending;
            if (c.status === 'declined') return s.declined;
            return s.listed;
        },

        /** The prototype's state pill colours: confirmed = mint, waiting = yellow, listed = grey. */
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

        // --- Menus (block menu, More, skill/speciality pickers, publish checklist) ---

        /** Open a menu under an anchor (above it when it doesn't fit). */
        openMenu(anchor, items, onPick) {
            const r = anchor.getBoundingClientRect();
            const hl = items.findIndex((it) => !it.header);
            this.menu = {
                open: true,
                items,
                onPick,
                anchor,
                hl,
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
            if (!this.menu.open) return;
            this.menu.open = false;
        },

        pickMenu(item) {
            const fn = this.menu.onPick;
            this.closeMenu();
            if (fn) fn(item);
        },

        /** Arrow keys move between items; Tab leaves the menu. */
        onMenuKey(event) {
            if (event.key === 'Tab') {
                this.closeMenu();
                return;
            }
            if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;
            event.preventDefault();
            const picks = this.menu.items.map((it, i) => (it.header ? -1 : i)).filter((i) => i >= 0);
            const at = picks.indexOf(this.menu.hl);
            this.menu.hl = picks[(at + (event.key === 'ArrowDown' ? 1 : -1) + picks.length) % picks.length];
            this.$nextTick(() => this.$refs.menu?.querySelector('button.is-hl')?.focus());
        },

        /** The block menu: put a block that is not on the page yet on it. */
        openAddMenu(anchor) {
            if (!this.addable.length) return;
            this.openMenu(anchor, this.addable, (item) => {
                this.shown[item.key] = true;
                this.$nextTick(() => {
                    const blk = this.$root.querySelector(`[data-b="${item.key}"]`);
                    blk?.querySelector('[contenteditable], input, .pe-ph.add button')?.focus();
                    if (item.key === 'credits' && !this.credits.length && !this.drafts.length) this.addDraft();
                    if (item.key === 'results' && !this.form.results.length) this.addResult();
                });
            });
        },

        /**
         * A block's More menu: take it off the page. Text and Results go with
         * Undo; Photos and Credits only once they are empty (their content lives
         * on the server and is removed piece by piece, with confirmation).
         */
        openMoreMenu(anchor, block) {
            const kind = this.strings.kinds?.[block] || block;
            this.openMenu(anchor, [{ key: 'del', cls: 'del', label: this.strings.deleteBlock }], () => {
                if (block === 'gallery' && this.gallery.length) return toast(this.strings.blockNotEmpty);
                if (block === 'credits' && (this.credits.length || this.drafts.some((d) => d.name.trim()))) return toast(this.strings.blockNotEmpty);
                if (block === 'story') {
                    const was = this.form.body.en;
                    this.form.body.en = '';
                    this.shown.story = false;
                    this.changed('story');
                    toast(fmt(this.strings.blockRemoved, { b: kind }), () => { this.form.body.en = was; this.shown.story = true; this.changed('story'); });
                    return;
                }
                if (block === 'results') {
                    const was = this.form.results;
                    this.form.results = [];
                    this.shown.results = false;
                    this.changed('results');
                    toast(fmt(this.strings.blockRemoved, { b: kind }), () => { this.form.results = was; this.shown.results = true; this.changed('results'); });
                    return;
                }
                this.drafts = [];
                this.shown[block] = false;
            });
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
            if (window.famaStaff) { window.famaToast?.(window.famaStaffNote); return; }
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

    // ---------------------------------------------------------------------
    // The public project page (project.html): Share, the full-screen pictures
    // and the phone's follow bar. Ported from the prototype's inline script.
    // ---------------------------------------------------------------------
    Alpine.data('famaProjectPage', ({ copied = '' } = {}) => ({
        list: [],          // [{ bg, cap }] — every picture of the story, in page order
        index: 0,
        x0: null,
        barShown: false,
        pastCover: false,
        atHire: false,

        /** Wire the picture clicks and the follow bar's two watchers (cover gone, "Made by" in view). */
        init() {
            this.$el.addEventListener('click', (e) => {
                const fig = e.target.closest('.story figure:not([data-nolb])');
                if (!fig || e.target.closest('figcaption')) return;
                const figs = [...this.$el.querySelectorAll('.story figure:not([data-nolb])')];
                this.list = figs.map((f) => {
                    const img = f.querySelector('.ph-img');
                    return { bg: img?.style.backgroundImage || '', cap: img?.getAttribute('aria-label') || '' };
                });
                const k = figs.indexOf(fig);
                if (k < 0) return;
                this.show(k);
                this.$refs.lb.showModal();
            });

            const cover = this.$el.querySelector('.cover');
            if (!this.$el.querySelector('.hirebar') || !cover || !this.$refs.hire || !('IntersectionObserver' in window)) return;
            new IntersectionObserver(([entry]) => { this.pastCover = !entry.isIntersecting; this.syncBar(); }).observe(cover);
            new IntersectionObserver(([entry]) => { this.atHire = entry.isIntersecting; this.syncBar(); }).observe(this.$refs.hire);
        },

        /** The bar shows once the cover is gone, until the "Made by" band is on screen. */
        syncBar() {
            this.barShown = this.pastCover && !this.atHire;
        },

        get current() {
            return this.list[this.index] || null;
        },

        /** "03 / 09" over the full-screen picture. */
        get counter() {
            const two = (n) => String(n).padStart(2, '0');
            return this.list.length ? `${two(this.index + 1)} / ${two(this.list.length)}` : '';
        },

        /** Show picture k (wrapping both ways). */
        show(k) {
            if (!this.list.length) return;
            this.index = (k + this.list.length) % this.list.length;
        },

        /** A swipe of more than 40px moves one picture. */
        swipe(event) {
            if (this.x0 === null) return;
            const dx = event.clientX - this.x0;
            this.x0 = null;
            if (Math.abs(dx) > 40) this.show(this.index + (dx < 0 ? 1 : -1));
        },

        /** Copy the page's address (never the editor's preview flag). */
        async share() {
            try {
                await navigator.clipboard.writeText(window.location.href.replace(/[?&]preview=1/, ''));
            } catch (e) { /* clipboard blocked — the toast still confirms the intent */ }
            toast(copied);
        },
    }));
});
