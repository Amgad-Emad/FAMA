/**
 * The guided onboarding (GET /talent/welcome/{step?}, ADR-AR) — `famaOnboarding`.
 *
 * Five screens (you · craft · work · story · live) over the endpoints Profile
 * already uses; this file owns no rules of its own:
 *   - the step map + resume screen come from the server
 *     (ProfileCompletionService::ONBOARDING / state().onboarding_step);
 *   - ticks follow SAVED data only — every save ends with GET /talent/profile/state;
 *   - Continue saves the screen (when it changed) and moves on; the photo, the
 *     skills and the specialities save the moment they change (like Profile).
 * Screens switch without a reload (pushState; back/forward re-open the screen).
 */
import { del, get, patch, post, put, upload, ApiError } from '../http';
import { tr, fmt, toast, ask } from './profile-helpers';
import { handleOf, urlOf, SOCIAL, PLATFORMS, socialIcon } from './social';
import skillIcons from '../../data/skill-icons.json';

/** The steps a talent can't leave onboarding without (mirrors ONBOARDING_ESSENTIAL). */
const ESSENTIAL = ['name', 'place', 'skill', 'show'];

/**
 * The link platforms "Your story" offers — every platform the app stores except the free-form
 * 'other' (social.js PLATFORMS is THE list, shared with Profile). An 'other' link a talent added on
 * Profile is kept as-is.
 */
const LINK_PLATFORMS = PLATFORMS.filter((p) => p !== 'other');
/** An empty draft: one field per platform. */
const noLinks = () => Object.fromEntries(LINK_PLATFORMS.map((p) => [p, '']));

/** The identity fields the first screen ("You") owns and saves. */
const YOU_KEYS = ['display_name', 'headline', 'base_city', 'base_country'];

/** The text fields "Your story" owns (bio is a step; point of view is optional). */
const STORY_KEYS = ['bio', 'worldview'];

/** Identity fields stored as translations (sent as { en }). */
const TRANSLATED = ['headline', 'bio', 'worldview'];

/** The width the preview renders the public profile at (a desktop page), before scaling. */
const PREVIEW_WIDTH = 1280;

/** First error message of an ApiError field, or the error's own message. */
const firstError = (e, key) => (e instanceof ApiError && e.errors && e.errors[key] ? e.errors[key][0] : null);

document.addEventListener('alpine:init', () => {
    window.Alpine.data('famaOnboarding', (init) => ({
        S: init.strings || {},
        order: init.order,
        screens: init.screens,
        step: init.step,
        state: init.state,
        core: { ...init.core },
        saved: { ...init.core },
        avatarUrl: init.avatarUrl,
        skills: init.skills,
        projects: init.projects || [],
        projectCount: init.projectCount || 0,
        social: init.social || [],
        links: noLinks(),
        savedLinks: noLinks(),
        countries: init.countries || [],
        cityCache: {},
        slug: init.slug,
        host: init.host,
        baseUrl: init.baseUrl,
        essential: ESSENTIAL,
        errors: {},
        linkError: null,
        linkErrorText: '',
        avatarError: '',
        publishError: '',
        uploading: false,
        busy: false,
        copied: false,
        hint: '',
        skillQuery: '',
        pvOpen: false,
        pvLoading: true,
        // Availability (saves on flip) — mirrored into the preview's chips + green dot.
        now: { is_available: !!(init.preview || {}).is_available, will_travel: !!(init.preview || {}).will_travel },
        nowBusy: false,

        /** Seed the link fields from the saved handles, load the city list, wire history. */
        init() {
            this.links = this.linksFrom(this.social);
            this.savedLinks = { ...this.links };
            if (this.countryCode) this.loadCities(this.countryCode);
            window.history.replaceState({ step: this.step }, '', this.urlFor(this.step));
            // The preview frame + its box, captured ONCE here: methods called from inside an x-for
            // row (the skill ✕, a speciality chip) see that row's $refs, which has no frame — the
            // reload silently did nothing (2026-11-25). Always use these, never $refs.pv.
            this._frame = this.$refs.pv;
            this._pvBox = this.$refs.pvBox;
            // The preview: fit it now and on resize, and follow the draft as it's typed.
            this.$nextTick(() => this.fitPreview());
            if (window.ResizeObserver && this._pvBox) new ResizeObserver(() => this.fitPreview()).observe(this._pvBox);
            this.$watch('core', () => this.postDraft(), { deep: true });
            this.$watch('links', () => this.postDraft(), { deep: true });
            // The framed page asks for a reload when it can't apply a change in place.
            window.addEventListener('message', (e) => {
                if (e.origin === window.location.origin && e.data && e.data.type === 'fama-preview-reload') this.reloadPreview();
            });
            // Away for a while (another tab) → pick up what changed there.
            let hiddenAt = 0;
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) { hiddenAt = Date.now(); return; }
                if (hiddenAt && Date.now() - hiddenAt > 1500) this.resync();
            });
            this.$watch('pvOpen', (v) => { if (v) this.$nextTick(() => this.fitPreview()); });
            window.addEventListener('popstate', (e) => {
                if (e.state && e.state.step) this.show(e.state.step, false);
            });
            window.addEventListener('beforeunload', (e) => {
                if (this.dirty) { e.preventDefault(); e.returnValue = ''; }
            });
            // Coming back from the project editor: the project list may have changed.
            window.addEventListener('pageshow', (e) => { if (e.persisted) window.location.reload(); });
        },

        // ================= helpers =========================================
        tr,
        fmt,
        /** Skill glyph markup (one map with Profile: resources/data/skill-icons.json). */
        iconFor(icon) {
            const inner = skillIcons[icon] || skillIcons._fallback || '';
            return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${inner}</svg>`;
        },
        /** "12 / 255" for a counted field. */
        count(v, max) { return `${String(v || '').length.toLocaleString('en')} / ${max.toLocaleString('en')}`; },
        urlFor(step) { return `${this.baseUrl}/${step}`; },
        /** A step is done in SAVED data. */
        done(key) { return !!(this.state.steps || {})[key]; },
        /** Every step of a screen is done. */
        screenDone(screen) { return (this.screens[screen] || []).every((k) => this.done(k)); },
        get stage() { return this.state.stage || 'draft'; },
        get doneCount() { return Object.values(this.state.steps || {}).filter(Boolean).length; },
        get totalCount() { return Object.keys(this.state.steps || {}).length || 10; },
        get initials() {
            const n = String(this.core.display_name || this.slug || '?').trim();
            return n.split(/\s+/).filter(Boolean).map((w) => w[0]).join('').slice(0, 2).toUpperCase();
        },
        get prevStep() { return this.order[this.order.indexOf(this.step) - 1] || null; },
        get nextStep() { return this.order[this.order.indexOf(this.step) + 1] || null; },

        // ================= navigation ======================================
        /** Open a screen (no save — the rail and the checklist jump freely). */
        goTo(step) { if (step !== this.step) this.show(step, true); },
        /** Switch screens, update the URL, and move focus to the new heading. */
        show(step, push) {
            if (!this.screens[step]) return;
            this.step = step;
            this.hint = '';
            if (push) window.history.pushState({ step }, '', this.urlFor(step));
            this.previewFocus();
            this.$nextTick(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                const h = document.getElementById(`obH-${step}`);
                if (h) { h.setAttribute('tabindex', '-1'); h.focus({ preventScroll: true }); }
            });
        },
        back() { if (this.prevStep) this.show(this.prevStep, true); },
        /** Continue: check the screen's essentials, save it if it changed, then move on. */
        async next() {
            const why = this.blocker();
            if (why) { this.hint = why; return; }
            this.busy = true;
            try {
                const ok = await this.saveStep();
                if (ok && this.nextStep) this.show(this.nextStep, true);
            } finally {
                this.busy = false;
            }
        },
        /** Why Continue can't move on yet ('' when it can). */
        blocker() {
            if (this.step === 'you') {
                if (!String(this.core.display_name || '').trim()) { this.errors.display_name = this.S.needName; return this.S.needName; }
                if (!String(this.core.base_city || '').trim()) { this.errors.base_city = this.S.needCity; return this.S.needCity; }
            }
            if (this.step === 'craft' && !this.done('skill')) return this.S.needSkill;
            return '';
        },
        get nextLabel() {
            if (this.step === 'work' && !this.projectCount) return this.S.skip;
            // "Skip" only while the screen is untouched — once anything on it is saved, it's Continue.
            if (this.step === 'story' && !this.storyDirty && !(this.screens.story || []).some((k) => this.done(k))) return this.S.skip;
            return this.S.continue;
        },

        // ================= saving ==========================================
        get youDirty() { return YOU_KEYS.some((k) => (this.core[k] || '') !== (this.saved[k] || '')); },
        get storyDirty() {
            return STORY_KEYS.some((k) => (this.core[k] || '') !== (this.saved[k] || ''))
                || this.linksDirty;
        },
        get dirty() { return this.youDirty || this.storyDirty; },
        /** Save the current screen (only what it owns); false when the server refused. */
        async saveStep() {
            this.errors = {};
            try {
                if (this.step === 'you' && this.youDirty) {
                    await this.saveCore(YOU_KEYS);
                } else if (this.step === 'story' && this.storyDirty) {
                    const text = STORY_KEYS.filter((k) => (this.core[k] || '') !== (this.saved[k] || ''));
                    if (text.length) await this.saveCore(text);
                    await this.saveLinks();
                } else {
                    return true;
                }
                await this.refreshState();
                this.reloadPreview();
                return true;
            } catch (e) {
                if (e instanceof ApiError && e.errors) {
                    Object.keys(e.errors).forEach((k) => { this.errors[k] = e.errors[k][0]; });
                }
                this.hint = (e instanceof ApiError && e.message) || this.S.saveFailed;
                return false;
            }
        },
        /** PATCH the identity keys given (headline/bio go as English translations). */
        async saveCore(keys) {
            const body = {};
            keys.forEach((k) => {
                body[k] = TRANSLATED.includes(k) ? { en: this.core[k] || '' } : (this.core[k] || '');
            });
            const { data } = await patch('/talent/profile', body);
            ['display_name', 'base_city', 'base_country'].forEach((k) => { if (k in data) this.core[k] = this.saved[k] = data[k] || ''; });
            TRANSLATED.forEach((k) => { if (k in body && data[k]) this.core[k] = this.saved[k] = (data[k].en || ''); });
        },
        // ================= links (Your story) ==========================
        socialIcon,
        /** One field per platform: { id, name, prefix } — the fixed address before the username. */
        get linkFields() {
            return LINK_PLATFORMS.map((id) => ({ id, name: SOCIAL[id] ? SOCIAL[id].name : this.S.website, prefix: SOCIAL[id] ? SOCIAL[id].prefix : '' }));
        },
        /** Saved handles → the draft's fields. */
        linksFrom(handles) {
            const out = noLinks();
            (handles || []).forEach((h) => { if (h.platform in out) out[h.platform] = h.handle || ''; });
            return out;
        },
        get linksDirty() { return LINK_PLATFORMS.some((p) => (this.links[p] || '') !== (this.savedLinks[p] || '')); },
        /**
         * The full handle list the profile will have: saved links keep their order (a field
         * cleared drops its link; an 'other' link rides along), new platforms are appended in
         * platform order. Returns [{ platform, handle, url }].
         */
        linkList() {
            const seen = new Set();
            const out = [];
            const draft = (platform) => {
                const handle = handleOf(this.links[platform], platform);
                return handle ? { platform, handle, url: urlOf(platform, handle) } : null;
            };
            this.social.forEach((h) => {
                seen.add(h.platform);
                if (!LINK_PLATFORMS.includes(h.platform)) { out.push({ platform: h.platform, handle: h.handle, url: h.url }); return; }
                const d = draft(h.platform); if (d) out.push(d);
            });
            LINK_PLATFORMS.forEach((p) => { if (!seen.has(p)) { const d = draft(p); if (d) out.push(d); } });
            return out;
        },
        /** Save the links (whole-list replace — the endpoint's contract). */
        async saveLinks() {
            if (!this.linksDirty) return;
            this.linkError = null; this.linkErrorText = '';
            const list = this.linkList();
            try {
                const { data } = await put('/talent/profile/social', { handles: list });
                this.social = data.handles || [];
                this.links = this.linksFrom(this.social);
                this.savedLinks = { ...this.links };
            } catch (e) {
                const bad = e instanceof ApiError && e.errors ? Object.keys(e.errors).find((k) => k.startsWith('handles.')) : null;
                if (bad) {
                    this.linkError = (list[Number(bad.split('.')[1])] || {}).platform || null;
                    this.linkErrorText = e.errors[bad][0];
                }
                throw e;
            }
        },
        /** Re-read the path from SAVED data (ticks, stage, the meter). */
        async refreshState() {
            try {
                const { data } = await get('/talent/profile/state');
                this.state = data;
            } catch (e) { /* keep the last known state; the next save retries */ }
        },

        // ================= the live preview =============================
        // The real profile page (?embed=1) framed at DESKTOP width and scaled to fit; the draft is
        // posted into it, and it reloads after a save. See public/fama-ui/js/profile-embed.js.
        get stageLabel() { return { draft: this.S.stDraft, private: this.S.stPrivate, live: this.S.stLive }[this.stage] || this.S.stDraft; },
        /** The public header falls back to the first skill when there is no headline. */
        get firstSkillName() { return this.linked.length ? tr(this.linked[0].name) : ''; },
        /** Fit the 1280px-wide page into the frame (re-run on resize). */
        fitPreview() {
            const box = this._pvBox; const frame = this._frame;
            if (!box || !frame || !box.clientWidth) return;
            const scale = box.clientWidth / PREVIEW_WIDTH;
            frame.style.width = `${PREVIEW_WIDTH}px`;
            frame.style.height = `${box.clientHeight / scale}px`;
            frame.style.transform = `scale(${scale})`;
        },
        /** Post a message to the framed page (same origin only). */
        toPreview(msg) {
            const w = this._frame && this._frame.contentWindow;
            if (w) w.postMessage(msg, window.location.origin);
        },
        /** The draft text the header shows. */
        postDraft() {
            this.toPreview({ type: 'fama-preview', draft: {
                name: this.core.display_name || this.slug,
                headline: this.core.headline || this.firstSkillName,
                place: [this.core.base_city, this.core.base_country].filter(Boolean).join(', '),
                bio: this.core.bio,
                pov: this.core.worldview,
                available: this.now.is_available,
                travel: this.now.will_travel,
                links: this.draftLinks(),
            } });
        },
        /**
         * The links row as the public profile will show it (the same list Continue saves).
         */
        draftLinks() {
            return this.linkList().map((l) => ({
                platform: l.platform,
                url: l.url,
                label: SOCIAL[l.platform] ? SOCIAL[l.platform].name : l.handle,
            }));
        },
        get previewBase() { return `${window.location.origin}/${this.slug}?embed=1`; },
        /**
         * Back to this tab after being away (an edit in another tab — Profile, a project):
         * re-read the skills + the saved path and reload the preview, unless a draft is open here.
         */
        async resync() {
            if (this.dirty) return;
            try { const { data } = await get('/talent/profile/skills'); if (data) this.skills = data; } catch (e) { /* keep */ }
            await this.refreshState();
            this.reloadPreview();
        },
        /** Flip Open for work / Will travel: optimistic, saved at once, shown in the preview. */
        async toggleNow(key) {
            const prev = this.now[key];
            this.now[key] = !prev;
            this.postDraft();
            this.nowBusy = true;
            try {
                await patch('/talent/profile/now', { [key]: this.now[key] });
            } catch (e) {
                this.now[key] = prev;
                this.postDraft();
                this.hint = (e instanceof ApiError && e.message) || this.S.saveFailed;
            } finally {
                this.nowBusy = false;
            }
        },
        /** Light + scroll the part of the page the current screen edits. */
        previewFocus() { this.toPreview({ type: 'fama-preview-focus', sec: this.step }); },
        onPreviewLoad() {
            this.pvLoading = false;
            this.fitPreview();
            this.postDraft();
            this.previewFocus();
        },
        /** Reload the framed page after a save (debounced — several writes in a row reload once). */
        reloadPreview() {
            clearTimeout(this._pvT);
            this._pvT = setTimeout(() => {
                const f = this._frame; if (!f) return;
                this.pvLoading = true;
                // A fresh URL every time (no cache), replaced in the frame's own history.
                const url = `${this.previewBase}&v=${Date.now()}`;
                try { f.contentWindow.location.replace(url); } catch (e) { f.src = url; }
            }, 250);
        },

        // ================= 1 · You =========================================
        get countryOptions() { return this.countries.map((c) => ({ value: c.name, label: c.name, code: c.code })); },
        get countryCode() {
            const v = String(this.core.base_country || '').toLowerCase();
            return (this.countries.find((c) => c.name.toLowerCase() === v || c.code.toLowerCase() === v) || {}).code || '';
        },
        get cityOptions() {
            const list = (this.cityCache[this.countryCode] || []).map((n) => ({ value: n, label: n }));
            const cur = this.core.base_city;
            return cur && !list.some((o) => o.value === cur) ? [{ value: cur, label: cur }, ...list] : list;
        },
        loadCities(code) {
            if (!code || this.cityCache[code]) return Promise.resolve();
            return get(`/locations/${code}/cities`)
                .then(({ data }) => { this.cityCache = { ...this.cityCache, [code]: data || [] }; })
                .catch(() => {});
        },
        /** A country picked: keep the city only if it belongs to that country. */
        async onCountryPicked(item) {
            this.errors.base_country = null;
            const city = this.core.base_city;
            await this.loadCities(item.code);
            if (city && this.countryCode === item.code && !(this.cityCache[item.code] || []).includes(city)) this.core.base_city = '';
        },
        /**
         * Upload the photo now (it is a file, not a draft — same as Profile). The picked
         * image shows AT ONCE (a local preview) while it uploads, so the screen never
         * looks unsaved. If the request fails, the saved path decides: the server may
         * have stored the photo before the response broke (a slow conversion, a dropped
         * connection) — then the preview stays; otherwise it reverts with a message.
         * Never silent: an error with no message falls back to a generic one.
         */
        async uploadAvatar(files) {
            const file = files && files[0];
            if (this.$refs.avatarInput) this.$refs.avatarInput.value = '';
            if (!file) return;
            if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { this.avatarError = this.S.photoType; return; }
            if (file.size > 5 * 1024 * 1024) { this.avatarError = this.S.photoBig; return; }
            const previous = this.avatarUrl;
            const preview = URL.createObjectURL(file);
            this.avatarError = '';
            this.avatarUrl = preview;
            this.uploading = true;
            try {
                const body = new FormData();
                body.append('avatar', file);
                const { data } = await window.famaUploading({ kind: 'avatar' },
                    (onProgress) => upload('/talent/profile/avatar', body, { onProgress }));
                if (data && data.avatar_url) this.avatarUrl = data.avatar_url;
                toast(this.S.photoAdded);
                await this.refreshState();
                this.reloadPreview();
            } catch (e) {
                await this.refreshState();
                if (this.done('photo')) {
                    toast(this.S.photoAdded); // stored before the response broke — keep the preview
                } else {
                    this.avatarUrl = previous;
                    this.avatarError = firstError(e, 'avatar') || (e instanceof ApiError && e.message) || this.S.photoFailed;
                }
            } finally {
                this.uploading = false;
                if (this.avatarUrl !== preview) URL.revokeObjectURL(preview);
            }
        },
        /** Remove the photo (confirmed — it deletes the file). */
        async removeAvatar() {
            const ok = await ask({ title: this.S.photoRmTitle, message: this.S.photoRmHelp, confirmLabel: this.S.remove, tone: 'danger' });
            if (!ok) return;
            this.uploading = true;
            try {
                await del('/talent/profile/avatar');
                this.avatarUrl = null;
                await this.refreshState();
                this.reloadPreview();
            } catch (e) {
                this.avatarError = e instanceof ApiError ? e.message : this.S.saveFailed;
            } finally {
                this.uploading = false;
            }
        },

        // ================= 2 · What you do =================================
        get linked() { return this.skills.linked || []; },
        get availableFiltered() {
            const q = this.skillQuery.trim().toLowerCase();
            const all = this.skills.available || [];
            return q ? all.filter((s) => tr(s.name).toLowerCase().includes(q)) : all;
        },
        options(id) { return ((this.skills.specialities || {})[id] || {}).options || []; },
        picks(id) { return ((this.skills.specialities || {})[id] || {}).selected || []; },
        /** Run a skills write, take the fresh editor payload, re-read the path. */
        async skillWrite(fn, reload = true) {
            this.busy = true; this.hint = '';
            try {
                const { data } = await fn();
                if (data) this.skills = data;
                await this.refreshState();
                if (reload) this.reloadPreview();
                return true;
            } catch (e) {
                this.hint = (e instanceof ApiError && e.message) || this.S.saveFailed;
                return false;
            } finally {
                this.busy = false;
            }
        },
        /**
         * Add a skill. The preview shows its tag AT ONCE (before the server answers); once the
         * server has seeded it, its tab joins the bar and opens. No frame reload.
         */
        async addSkill(sk) {
            const before = this.linked.slice();
            this.postSkills([...before, sk]);
            this.skillQuery = '';
            if (await this.skillWrite(() => post('/talent/profile/skills', { talent_type_id: sk.id }), false)) {
                toast(fmt(this.S.skillAdded, { name: tr(sk.name) }));
                this.postSkills(this.linked, sk.slug);
            } else {
                this.postSkills(before);
            }
        },
        /** Remove a skill (confirmed): gone from the preview at once; restored if the server refuses. */
        async removeSkill(sk) {
            const ok = await ask({ title: fmt(this.S.skillRmTitle, { name: tr(sk.name) }), message: this.S.skillRmHelp, confirmLabel: this.S.remove, tone: 'danger' });
            if (!ok) return;
            const before = this.linked.slice();
            this.postSkills(before.filter((s) => s.id !== sk.id));
            if (!(await this.skillWrite(() => del(`/talent/profile/skills/${sk.id}`), false))) this.postSkills(before);
        },
        /** Send the skills (in order) to the preview; `open` = the tab to show once it exists. */
        postSkills(list, open = null) {
            this.toPreview({ type: 'fama-preview-skills', list: list.map((s) => ({ slug: s.slug, name: tr(s.name) })), open });
        },
        /**
         * Toggle one speciality: optimistic, then the per-skill replace endpoint. NO preview
         * reload — the change is written straight into the framed page (its tab opens, the
         * chips update, the new one pops), so nothing flashes.
         */
        async toggleSpeciality(skillId, id) {
            const cur = this.picks(skillId);
            const adding = !cur.includes(id);
            const nextPicks = adding ? [...cur, id] : cur.filter((x) => x !== id);
            this.skills.specialities[skillId].selected = nextPicks;
            this.postSpecs(skillId, adding ? id : null);
            try {
                const { data } = await put(`/talent/profile/skills/${skillId}/specialities`, { speciality_ids: nextPicks });
                if (data) this.skills = data;
                await this.refreshState();
            } catch (e) {
                this.skills.specialities[skillId].selected = cur;
                this.postSpecs(skillId, null);
                this.hint = (e instanceof ApiError && e.message) || this.S.saveFailed;
            }
        },
        /** Send a skill's picked speciality names to the preview. */
        postSpecs(skillId, addedId) {
            const sk = this.linked.find((s) => s.id === skillId);
            if (!sk) return;
            const picks = this.picks(skillId);
            const opts = this.options(skillId);
            this.toPreview({
                type: 'fama-preview-specs',
                skill: sk.slug,
                // in PICK order — the order the profile lists them after a reload
                names: picks.map((pid) => opts.find((o) => o.id === pid)).filter(Boolean).map((o) => tr(o.name)),
                added: addedId ? tr((opts.find((o) => o.id === addedId) || {}).name) : null,
            });
        },

        // ================= 3 · Your work ===================================
        /** Empty project slots up to the goal of three (none past it). */
        get emptySlots() { return Math.max(0, 3 - this.projects.length); },

        // ================= 5 · Go Live =====================================
        get liveTitle() {
            if (this.stage === 'live') return this.done('show') ? this.S.tLiveListed : this.S.tLive;
            return this.stage === 'private' ? this.S.tPrivate : this.S.tDraft;
        },
        get liveLead() {
            const missing = ESSENTIAL.filter((k) => k !== 'show' && !this.done(k)).length;
            if (this.stage === 'live') return this.S.lLive;
            if (this.stage === 'private') return this.S.lPrivate;
            if (this.stage === 'draft' && missing) return fmt(missing === 1 ? this.S.lDraft1 : this.S.lDraftN, { n: missing });
            return '';
        },
        get meterNote() {
            if (!this.done('show')) return this.S.mShow;
            if (!this.done('projects3')) return this.S.mThree;
            return this.doneCount === this.totalCount ? this.S.mDone : this.S.mMore;
        },
        async goLive() {
            this.busy = true; this.publishError = '';
            try {
                await patch('/talent/profile/publish', { publish: true });
                await this.refreshState();
                this.reloadPreview();
                toast(this.S.live);
            } catch (e) {
                this.publishError = (e instanceof ApiError && e.message) || this.S.saveFailed;
            } finally {
                this.busy = false;
            }
        },
        async copyLink() {
            try { await navigator.clipboard.writeText(`${window.location.origin}/${this.slug}`); } catch (e) { /* the link still shows */ }
            this.copied = true;
            toast(this.S.copied);
            setTimeout(() => { this.copied = false; }, 2000);
        },
    }));
});
