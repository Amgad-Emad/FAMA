/**
 * fama-ui projects area (Alpine), registered on alpine:init:
 *
 *   famaProjects          — /talent/projects: skill-filtered grid, drag reorder,
 *                            delete, plus confirmed credits with Leave.
 *   (the project editor lives in project-editor.js — famaProjectEdit)
 *   famaCredits           — /talent/credits: confirm / decline / leave.
 *   famaRecognitionsSwitch— /talent/credits: show/hide the Recognitions section.
 *   famaRecognise         — /{slug}/recognise: write a recognition.
 *   famaProjectLike       — the public project page's ♡ Like buttons (ADR-AJ).
 *
 * Ported from the prototype's inline scripts onto the real JSON endpoints via
 * window.fama (http.js). Logic for projects/credits is copied from
 * dashboard.js (talentProjects / talentCredits), which this area does not own.
 * Runtime copy always comes from Blade (`strings`), never hardcoded here.
 */

import { fmt, initials, toast, firstError, ask } from './project-util';

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

        /**
         * Hide a confirmed credit from your profile, or show it again (ADR-AI).
         * Optimistic; the toast offers Undo. It stays on the project page.
         */
        async toggleHidden(c, undoing = false) {
            const next = !c.is_hidden;
            c.is_hidden = next;
            try {
                await patch(`/talent/credits/${c.id}/hidden`, { hidden: next });
                if (!undoing) toast(fmt(next ? this.strings.tHidden : this.strings.tShown, { p: c.project?.title || '' }), () => this.toggleHidden(c, true));
            } catch (e) {
                c.is_hidden = !next;
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

    // One recognition: hide it from your profile or show it again (ADR-AI), with Undo.
    Alpine.data('famaRecognitionHide', ({ url, hidden = false, who = '', strings = {} } = {}) => ({
        url,
        hidden,
        who,
        strings,
        busy: false,

        /** Flip; the toast's Undo flips it back. */
        async toggle(undoing = false) {
            if (this.busy) return;
            this.busy = true;
            const next = !this.hidden;
            try {
                await patch(this.url, { hidden: next });
                this.hidden = next;
                if (!undoing) toast(fmt(next ? this.strings.hidden : this.strings.shown, { w: this.who }), () => this.toggle(true));
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
    // Like a project (public project page, ADR-AJ — prototype "♡ Like")
    // ---------------------------------------------------------------------
    // A visitor's POST answers 401 with the intent captured server-side, so the
    // like replays after they sign in.
    Alpine.data('famaProjectLike', ({ id, liked = false } = {}) => ({
        liked,
        busy: false,

        /**
         * Optimistic like toggle (both Like buttons share this state). A visitor
         * is checked FIRST: the button never flips — the POST records the
         * intent (401), then they go to the talent login and the like replays.
         */
        async toggle() {
            if (this.busy) return;
            if (window.famaStaff) { window.famaToast?.(window.famaStaffNote); return; }
            this.busy = true;
            if (window.famaSignedIn === false) {
                try { await post(`/projects/${id}/like`); } catch (e) { /* 401 expected — intent captured */ }
                window.location.href = window.fama.localizeUrl('/login?role=talent');
                return;
            }
            const was = this.liked;
            this.liked = !was;
            try {
                if (was) await del(`/projects/${id}/like`);
                else await post(`/projects/${id}/like`);
            } catch (e) {
                this.liked = was;
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
    // The public project page (project.html): Share and the full-screen
    // pictures. Ported from the prototype's inline script.
    // ---------------------------------------------------------------------
    Alpine.data('famaProjectPage', ({ copied = '' } = {}) => ({
        list: [],          // [{ bg, cap }] — every picture of the story, in page order
        index: 0,
        x0: null,

        /**
         * Wire the page: picture clicks (the full-screen viewer), the video
         * posters (the player loads only when asked), the before/after sliders
         * and the look tags (ADR-AN). The phone's follow bar is gone (13 Nov 2026).
         */
        init() {
            this.$el.addEventListener('click', (e) => {
                const poster = e.target.closest('.vposter');
                if (poster) {
                    this.play(poster);
                    return;
                }
                const fig = e.target.closest('.story figure:not([data-nolb])');
                if (!fig || e.target.closest('figcaption')) return;
                const figs = [...this.$el.querySelectorAll('.story figure:not([data-nolb])')];
                this.list = figs.map((f) => {
                    const img = f.querySelector('.ph-img');
                    const cap = f.querySelector('figcaption')?.textContent.trim();
                    return { bg: img?.style.backgroundImage || '', cap: cap || img?.getAttribute('aria-label') || '' };
                });
                const k = figs.indexOf(fig);
                if (k < 0) return;
                this.show(k);
                this.$refs.lb.showModal();
            });
            this.sliders();
        },

        get current() {
            return this.list[this.index] || null;
        },

        /** Before / after: drag, click, or the arrow keys move the line. */
        sliders() {
            this.$el.querySelectorAll('.story .ba-box').forEach((box) => {
                const line = box.querySelector('.ba-line');
                const set = (v) => {
                    const p = Math.max(0, Math.min(100, v));
                    box.style.setProperty('--p', p + '%');
                    line?.setAttribute('aria-valuenow', Math.round(p));
                };
                const at = (e) => { const r = box.getBoundingClientRect(); set((e.clientX - r.left) / r.width * 100); };
                let drag = false;
                box.addEventListener('pointerdown', (e) => { drag = true; box.setPointerCapture?.(e.pointerId); at(e); });
                box.addEventListener('pointermove', (e) => { if (drag) at(e); });
                box.addEventListener('pointerup', () => { drag = false; });
                line?.addEventListener('keydown', (e) => {
                    const v = Number(line.getAttribute('aria-valuenow')) || 50;
                    if (e.key === 'ArrowLeft') { set(v - 5); e.preventDefault(); }
                    if (e.key === 'ArrowRight') { set(v + 5); e.preventDefault(); }
                });
            });
            // A look with tags: the dot and its line light up together.
            this.$el.querySelectorAll('.story .look').forEach((look) => {
                const on = (k) => look.querySelectorAll('[data-tag]').forEach((x) => x.classList.toggle('on', x.dataset.tag === k));
                look.addEventListener('pointerover', (e) => { const x = e.target.closest('[data-tag]'); if (x) on(x.dataset.tag); });
                look.addEventListener('focusin', (e) => { const x = e.target.closest('[data-tag]'); if (x) on(x.dataset.tag); });
                look.addEventListener('pointerleave', () => on(''));
            });
        },

        /** A video poster becomes the YouTube / Vimeo player (loaded only now — no tracking before a click). */
        play(poster) {
            const src = poster.dataset.player;
            if (!src) {
                window.open(poster.dataset.href, '_blank', 'noopener');
                return;
            }
            const frame = document.createElement('iframe');
            frame.src = src;
            frame.className = 'ph-img vframe';
            frame.allow = 'autoplay; fullscreen; picture-in-picture';
            frame.allowFullscreen = true;
            frame.title = poster.getAttribute('aria-label') || '';
            poster.replaceWith(frame);
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
