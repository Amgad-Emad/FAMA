/**
 * Talent dashboard Alpine components. Every mutation goes through the shared
 * http.js wrapper (JSON envelope), so pages never reload; validation errors are
 * surfaced inline from `envelope.errors`, and list edits are optimistic.
 */
import { del, get, patch, post, ApiError } from './http';

/** Resolve a translation map ({en, ar}) to the current locale. */
function t(map) {
    if (!map || typeof map !== 'object') return map ?? '';
    const locale = (document.documentElement.lang || 'en').slice(0, 2);
    return map[locale] || map.en || Object.values(map)[0] || '';
}

/**
 * Shared advanced-filters modal machinery (teleported, scroll-locked, focus-trapped) —
 * mirrors talentSearch's modal exactly. METHODS ONLY (a getter would be evaluated at
 * spread time, not per-access). The host object must provide snapshotDraft() (seed the
 * draft on open) and applyFilters() (commit the draft). Do NOT use Alpine x-transition
 * on the teleported node — its leave never completes; animate via :class + CSS instead.
 */
function filterModal() {
    return {
        modalOpen: false,   // mounted (x-show) — reliable display toggle
        modalActive: false, // animation state (opacity/transform via :class)
        triggerEl: null,
        _scrollY: 0,

        openFilters() {
            if (this.modalOpen) return;
            this.triggerEl = document.activeElement;
            this.snapshotDraft();
            this.modalOpen = true;
            this.lockScroll();
            this.$nextTick(() => { this.modalActive = true; this.$refs.dialog?.focus(); });
        },
        closeFilters() {
            if (!this.modalOpen) return;
            this.modalActive = false;
            this.unlockScroll();
            const ms = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 200;
            const done = () => { this.modalOpen = false; this.$nextTick(() => this.triggerEl?.focus?.()); };
            if (ms === 0) done();
            else setTimeout(done, ms + 20);
        },
        lockScroll() {
            this._scrollY = window.scrollY;
            const body = document.body;
            body.style.position = 'fixed';
            body.style.top = `-${this._scrollY}px`;
            body.style.insetInlineStart = '0';
            body.style.width = '100%';
        },
        unlockScroll() {
            const body = document.body;
            body.style.position = '';
            body.style.top = '';
            body.style.insetInlineStart = '';
            body.style.width = '';
            window.scrollTo(0, this._scrollY || 0);
        },
        trapFocus(e) {
            if (e.key !== 'Tab' || !this.modalOpen) return;
            const dialog = this.$refs.dialog;
            if (!dialog) return;
            const focusables = Array.from(dialog.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
            )).filter((el) => el.offsetParent !== null);
            if (!focusables.length) return;
            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            const active = document.activeElement;
            if (e.shiftKey && (active === first || active === dialog)) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && active === last) { e.preventDefault(); first.focus(); }
        },
    };
}

/** Discipline (talent_type) chip glyphs, keyed by `talent_types.icon` (lucide-<slug>). */
const SKILL_ICONS = {
    'lucide-modeling': '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'lucide-photography': '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>',
    'lucide-cinematography': '<path d="m22 8-6 4 6 4V8Z"/><rect width="14" height="12" x="2" y="6" rx="2"/>',
    'lucide-creative-direction': '<path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/>',
    'lucide-styling': '<path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/>',
    'lucide-graphic-design': '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
};
const SKILL_ICON_FALLBACK = '<path d="m12 3 1.9 5.8a2 2 0 0 0 1.3 1.3L21 12l-5.8 1.9a2 2 0 0 0-1.3 1.3L12 21l-1.9-5.8a2 2 0 0 0-1.3-1.3L3 12l5.8-1.9a2 2 0 0 0 1.3-1.3z"/>';

function disciplineIcon(type) {
    const inner = SKILL_ICONS[type?.icon] || SKILL_ICON_FALLBACK;
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-full w-full" aria-hidden="true">${inner}</svg>`;
}

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // --- Public profile skill tabs (talent-spec, ADR-R) ---------------------
    // The skill tab bar is the profile's primary navigation (role="tablist"). The
    // active tab is server-rendered; other tabs are fetched lazily on first click
    // (envelope, no reload) and cached so re-clicking is instant. The active tab is
    // mirrored in the URL (`?skill=`) so it's shareable + the back button works.
    // Keyboard: arrow/Home/End move between tabs (activation follows focus).
    Alpine.data('profileTabs', (initial) => ({
        active: initial.active,
        tabs: initial.tabs || [],
        labels: initial.labels || {},
        urls: initial.urls || {},
        cache: {},
        loading: false,

        init() {
            // Cache the server-rendered active panel so switching back is instant.
            if (this.active && this.$refs.panel) {
                this.cache[this.active] = this.$refs.panel.innerHTML;
            }
            // Back/forward buttons re-open the tab named in the URL.
            window.addEventListener('popstate', () => {
                const slug = new URLSearchParams(window.location.search).get('skill');
                const target = slug && this.tabs.includes(slug) ? slug : this.tabs[0];
                if (target) this.show(target, { push: false });
            });
        },

        // Roving-tabindex keyboard nav across the tablist (RTL-aware). Activation
        // follows focus, matching the automatic-activation tab pattern.
        onTabKey(e, index) {
            const keys = { ArrowRight: 1, ArrowLeft: -1, Home: 'first', End: 'last' };
            if (!(e.key in keys)) return;
            e.preventDefault();
            const rtl = document.documentElement.dir === 'rtl';
            const n = this.tabs.length;
            let target;
            if (keys[e.key] === 'first') target = 0;
            else if (keys[e.key] === 'last') target = n - 1;
            else {
                const step = rtl ? -keys[e.key] : keys[e.key]; // mirror arrows in RTL
                target = (index + step + n) % n;
            }
            const btn = this.$refs.tablist?.querySelectorAll('[role="tab"]')[target];
            btn?.focus();
            this.show(this.tabs[target]);
        },

        async show(slug, { push = true } = {}) {
            if (!this.tabs.includes(slug) || !this.$refs.panel) return;
            if (slug === this.active && this.cache[slug] !== undefined) return;

            // Preserve the current panel before switching away.
            if (this.active) this.cache[this.active] = this.$refs.panel.innerHTML;
            this.active = slug;

            if (push) {
                const url = new URL(window.location);
                url.searchParams.set('skill', slug);
                window.history.pushState({ skill: slug }, '', url);
            }

            if (this.cache[slug] !== undefined) {
                this.swapPanel(this.cache[slug]);
                return;
            }

            this.loading = true;
            try {
                const { data } = await get(this.urls[slug]);
                this.cache[slug] = data.html;
                this.swapPanel(data.html);
            } catch (e) {
                /* keep the current panel on failure */
            } finally {
                this.loading = false;
            }
        },

        // Swap the panel content with a brief fade. A forced reflow between opacity
        // 0 and 1 makes the transition play without requestAnimationFrame (which is
        // paused in background tabs); reduced-motion zeroes the duration via CSS, so
        // it always ends fully visible.
        swapPanel(html) {
            const p = this.$refs.panel;
            if (!p) return;
            p.style.opacity = '0';
            p.innerHTML = html;
            void p.offsetHeight; // reflow
            p.style.opacity = '';
        },
    }));

    // --- Profile editor (the single profile surface) ------------------------
    // Holds identity + username, Skills, the Pricing rate, the publish toggle,
    // and the reorderable blocks — the old Professions + Account tabs folded in.
    Alpine.data('profileEditor', (initial) => ({
        core: initial.core,
        blocks: initial.blocks,
        catalog: initial.catalog,
        universalLabel: initial.universalLabel || 'Universal',
        newBlock: {},
        errors: {},
        savingCore: false,
        coreSaved: false,
        dragId: null,

        // Profile image (avatar).
        avatarUrl: initial.avatarUrl || null,
        displayName: initial.displayName || '',
        uploadingAvatar: false,
        avatarError: '',

        // Publish toggle (moved from Account).
        isPublished: initial.publish.is_published,
        status: initial.publish.status,
        publishing: false,
        publishError: '',

        // Pricing rate (all-or-nothing).
        rate: { rate_unit: initial.rate.rate_unit || '', rate_amount: initial.rate.rate_amount || '', rate_currency: initial.rate.rate_currency || '' },
        savingRate: false,
        rateSaved: false,
        rateErrors: {},

        // Skills — each is a tab; blocks are scoped per skill (ADR-Q).
        skills: initial.skills,
        availableSkills: initial.availableSkills,
        addSkillId: '',
        skillDragId: null,
        confirmRemoveSkillId: null,

        t,

        // ----- Scopes: universal (profile-level) + one tab per skill --------
        get scopeGroups() {
            const sorted = this.skills.slice().sort((a, b) => (b.is_primary ? 1 : 0) - (a.is_primary ? 1 : 0) || (a.position - b.position));
            return [{ key: 'universal', typeId: null, label: this.universalLabel, category: null }].concat(
                sorted.map((s) => ({ key: String(s.id), typeId: s.id, label: this.t(s.name), category: s.category, is_primary: s.is_primary })),
            );
        },

        blocksInScope(typeId) {
            return this.blocks.filter((b) => (b.talent_type_id ?? null) === typeId).sort((a, b) => a.position - b.position);
        },

        catalogFor(block) {
            return this.catalog.find((c) => c.id === block.block_type.id) || null;
        },

        eligibleInScope(bt, group) {
            if (group.typeId === null) return bt.availability === 'universal';
            if (bt.availability === 'universal') return true;
            if (bt.availability === 'by_category') return (bt.category_gates || []).includes(group.category);
            if (bt.availability === 'by_type') return (bt.type_gates || []).includes(group.typeId);
            return false;
        },

        // The add-picker for a scope: eligible, minus non-repeatables already there.
        pickerForScope(group) {
            const present = this.blocksInScope(group.typeId).map((b) => b.block_type.id);
            return this.catalog.filter((bt) => this.eligibleInScope(bt, group) && !(bt.is_repeatable === false && present.includes(bt.id)));
        },

        // Scopes a block may move to (eligible + no non-repeatable clash).
        moveTargets(block) {
            const bt = this.catalogFor(block);
            if (!bt) return [];
            return this.scopeGroups.filter((g) => {
                if ((block.talent_type_id ?? null) === g.typeId) return false;
                if (!this.eligibleInScope(bt, g)) return false;
                if (bt.is_repeatable) return true;
                return !this.blocksInScope(g.typeId).some((b) => b.block_type.id === bt.id && b.id !== block.id);
            });
        },

        async saveCore() {
            this.errors = {};
            this.savingCore = true;
            this.coreSaved = false;
            try {
                const { data } = await patch('/talent/profile', this.core);
                this.core = { ...this.core, ...data };
                this.coreSaved = true;
                setTimeout(() => (this.coreSaved = false), 2000);
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally {
                this.savingCore = false;
            }
        },

        // Initials fallback shown when there's no uploaded avatar.
        get avatarInitials() {
            const parts = (this.displayName || '').trim().split(/\s+/).filter(Boolean).slice(0, 2);
            return parts.map((w) => w[0].toUpperCase()).join('') || '—';
        },

        // Upload / replace the profile image (multipart, no reload).
        async uploadAvatar(fileList) {
            const file = fileList && fileList[0];
            if (!file) return;
            this.avatarError = '';
            this.uploadingAvatar = true;
            try {
                const body = new FormData();
                body.append('avatar', file);
                const { data } = await post('/talent/profile/avatar', body);
                this.avatarUrl = data.avatar_url;
            } catch (e) {
                this.avatarError = e instanceof ApiError ? (e.errors?.avatar?.[0] || e.message) : 'Upload failed';
            } finally {
                this.uploadingAvatar = false;
                if (this.$refs.avatarInput) this.$refs.avatarInput.value = ''; // allow re-picking the same file
            }
        },

        // Remove the profile image (falls back to initials).
        async removeAvatar() {
            this.avatarError = '';
            this.uploadingAvatar = true;
            try {
                const { data } = await del('/talent/profile/avatar');
                this.avatarUrl = data.avatar_url;
            } catch (e) {
                this.avatarError = e instanceof ApiError ? e.message : 'Remove failed';
            } finally {
                this.uploadingAvatar = false;
            }
        },

        // Add a block into a specific scope (a tab, or the universal section).
        async addBlock(group) {
            const id = this.newBlock[group.key];
            if (!id) return;
            try {
                const { data } = await post('/talent/profile/blocks', { block_type_id: id, talent_type_id: group.typeId });
                this.blocks.push(data);
                this.newBlock[group.key] = '';
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },

        async removeBlock(block) {
            const prev = [...this.blocks];
            this.blocks = this.blocks.filter((b) => b.id !== block.id);
            try {
                await del(`/talent/profile/blocks/${block.id}`);
            } catch (e) {
                this.blocks = prev;
            }
        },

        async toggleVisible(block) {
            block.is_visible = !block.is_visible;
            try {
                await patch(`/talent/profile/blocks/${block.id}/visibility`, { is_visible: block.is_visible });
            } catch (e) {
                block.is_visible = !block.is_visible;
            }
        },

        async saveTitle(block) {
            try {
                await patch(`/talent/profile/blocks/${block.id}`, { title: block.title });
                block._saved = true;
                setTimeout(() => (block._saved = false), 1500);
            } catch (e) {
                /* validation ignored for the inline title */
            }
        },

        // Move a block to another scope (re-stamp talent_type_id + reposition).
        async moveBlock(block, targetTypeId) {
            const value = targetTypeId === '' ? null : Number(targetTypeId);
            try {
                const { data } = await patch(`/talent/profile/blocks/${block.id}/move`, { talent_type_id: value });
                Object.assign(block, data);
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },

        // Reorder only within the dragged block's own scope.
        onDrop(target) {
            if (this.dragId === null || this.dragId === target.id) return;
            const dragged = this.blocks.find((b) => b.id === this.dragId);
            const scopeId = target.talent_type_id ?? null;
            this.dragId = null;
            if (!dragged || (dragged.talent_type_id ?? null) !== scopeId) return; // same scope only

            const scoped = this.blocksInScope(scopeId);
            const from = scoped.findIndex((b) => b.id === dragged.id);
            const to = scoped.findIndex((b) => b.id === target.id);
            const [moved] = scoped.splice(from, 1);
            scoped.splice(to, 0, moved);
            scoped.forEach((b, i) => { b.position = i; });
            this.persistOrder(scopeId, scoped.map((b) => b.id));
        },

        async persistOrder(scopeTypeId, order) {
            try {
                await patch('/talent/profile/blocks/reorder', { talent_type_id: scopeTypeId, order });
            } catch (e) {
                /* keep optimistic order; a reload would resync */
            }
        },

        async refreshBlocks() {
            try {
                const { data } = await get('/talent/profile/blocks');
                this.blocks = data;
            } catch (e) {
                /* ignore */
            }
        },

        // ----- Publish (moved from Account) ---------------------------------
        async togglePublish() {
            this.publishing = true;
            this.publishError = '';
            try {
                const { data } = await patch('/talent/profile/publish', { publish: !this.isPublished });
                this.isPublished = data.is_published;
                this.status = data.status;
            } catch (e) {
                if (e instanceof ApiError) this.publishError = e.message;
            } finally {
                this.publishing = false;
            }
        },

        // ----- Pricing rate (all-or-nothing) --------------------------------
        async saveRate() {
            this.rateErrors = {};
            this.savingRate = true;
            this.rateSaved = false;
            try {
                const { data } = await patch('/talent/profile/pricing', this.rate);
                this.rate = { rate_unit: data.rate_unit || '', rate_amount: data.rate_amount || '', rate_currency: data.rate_currency || '' };
                this.rateSaved = true;
                setTimeout(() => (this.rateSaved = false), 2000);
            } catch (e) {
                if (e instanceof ApiError) this.rateErrors = e.errors || { _: [e.message] };
            } finally {
                this.savingRate = false;
            }
        },

        clearRate() {
            this.rate = { rate_unit: '', rate_amount: '', rate_currency: '' };
            this.saveRate();
        },

        // ----- Skills (the old Professions tab) -----------------------------
        applySkills(payload) {
            this.skills = payload.linked;
            this.availableSkills = payload.available;
        },

        async addSkill() {
            if (!this.addSkillId) return;
            try {
                const { data } = await post('/talent/profile/skills', { talent_type_id: this.addSkillId });
                this.applySkills(data);
                this.addSkillId = '';
                await this.refreshBlocks(); // its tab was seeded with blocks
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },

        // Removing a skill deletes its tab's blocks — require an explicit confirm.
        requestRemoveSkill(type) { this.confirmRemoveSkillId = type.id; },
        cancelRemoveSkill() { this.confirmRemoveSkillId = null; },

        async removeSkill(type) {
            this.confirmRemoveSkillId = null;
            try {
                const { data } = await del(`/talent/profile/skills/${type.id}`);
                this.applySkills(data);
                await this.refreshBlocks(); // that tab's blocks are gone (content preserved)
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },

        async makePrimarySkill(type) {
            const { data } = await patch(`/talent/profile/skills/${type.id}/primary`);
            this.applySkills(data);
        },

        onSkillDrop(target) {
            if (this.skillDragId === null || this.skillDragId === target.id) return;
            const from = this.skills.findIndex((x) => x.id === this.skillDragId);
            const to = this.skills.findIndex((x) => x.id === target.id);
            const [moved] = this.skills.splice(from, 1);
            this.skills.splice(to, 0, moved);
            this.skillDragId = null;
            patch('/talent/profile/skills/reorder', { order: this.skills.map((x) => x.id) }).catch(() => {});
        },
    }));

    // --- Public discovery / talent search -----------------------------------
    // Skills-first: skills (talent_types) are THE primary filter, grouped by scope
    // (model/crew/creative) and rendered as multi-select chips with real states.
    // The full filter set lives in a teleported, focus-trapped "Advanced filters"
    // modal whose groups are revealed by the selected skills' categories; the
    // free-text search is a demoted secondary control. Active filters sync to the
    // URL (shareable + back-button), results paginate, everything is Ajax.
    Alpine.data('talentSearch', (initial) => ({
        types: initial.types || [],
        equipmentCategories: initial.equipmentCategories || [],
        softwareOptions: initial.softwareOptions || [],
        lookOptions: initial.lookOptions || [],
        scopeLabels: initial.scopeLabels || { model: 'Modeling', crew: 'Crew', creative: 'Creative' },
        filters: { type: [], city: '', country: '', equipment: '', software: '', looks: '', q: '' },
        // Advanced-filters modal edits a STAGED copy; nothing commits to `filters`
        // (or the results) until "Apply filters". Snapshotted from `filters` on open.
        draft: { type: [], city: '', country: '', equipment: '', software: '', looks: '' },
        results: [],
        meta: null,
        page: 1,
        loading: true,
        modalOpen: false,   // mounted (x-show) — reliable display toggle
        modalActive: false, // animation state (opacity/transform via :class)
        skeletons: [0, 1, 2, 3, 4, 5],
        triggerEl: null,
        t,

        // Inline SVG glyphs keyed by `talent_types.icon` (lucide-<slug>); a sparkle
        // fallback keeps unknown icons from rendering blank.
        skillIcons: {
            'lucide-modeling': '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'lucide-photography': '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>',
            'lucide-cinematography': '<path d="m22 8-6 4 6 4V8Z"/><rect width="14" height="12" x="2" y="6" rx="2"/>',
            'lucide-creative-direction': '<path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/>',
            'lucide-styling': '<path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/>',
            'lucide-graphic-design': '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        },
        _fallbackIcon: '<path d="m12 3 1.9 5.8a2 2 0 0 0 1.3 1.3L21 12l-5.8 1.9a2 2 0 0 0-1.3 1.3L12 21l-1.9-5.8a2 2 0 0 0-1.3-1.3L3 12l5.8-1.9a2 2 0 0 0 1.3-1.3z"/>',

        async init() {
            this.hydrateFromUrl();
            // Back/forward buttons restore the filtered view without re-pushing.
            window.addEventListener('popstate', () => {
                this.hydrateFromUrl();
                this.runSearch(this.page, 'none');
            });
            // Normalise the URL (replace, not push) on first paint.
            await this.runSearch(this.page, 'replace');
        },

        // Skills grouped by scope (model → crew → creative), empty groups dropped.
        get skillGroups() {
            return ['model', 'crew', 'creative']
                .map((category) => ({
                    category,
                    label: this.scopeLabels[category] || category,
                    types: this.types.filter((type) => type.category === category),
                }))
                .filter((group) => group.types.length > 0);
        },

        // The set of scopes (categories) covered by a list of skill slugs.
        scopesOf(slugs) {
            const byslug = {};
            this.types.forEach((type) => { byslug[type.slug] = type.category; });
            return new Set((slugs || []).map((slug) => byslug[slug]).filter(Boolean));
        },

        get selectedScopes() { return this.scopesOf(this.filters.type); },

        // Scoped filters in the modal are shown ONLY for the DRAFT skills' categories:
        // a scoped filter appears only once its related skill is selected (no skill →
        // no scoped filters), so picking a skill reveals the filters that narrow it.
        get draftScopes() { return this.scopesOf(this.draft.type); },
        get showEquipment() { return this.draftScopes.has('crew'); },
        get showSoftware() { return this.draftScopes.has('creative'); },
        get showLooks() { return this.draftScopes.has('model'); },
        get hasScopedFilters() { return this.showEquipment || this.showSoftware || this.showLooks; },

        // Advanced (scoped/location) filter count — badge on the modal trigger,
        // reflecting the APPLIED filters (not the unsaved draft).
        get activeFilterCount() {
            return ['city', 'country', 'equipment', 'software', 'looks']
                .filter((key) => this.filters[key]).length;
        },

        get selectedSkillCount() { return this.filters.type.length; },
        get draftSelectedCount() { return this.draft.type.length; },
        get resultTotal() { return this.meta?.pagination?.total ?? 0; },

        // Removable chips for the active-filter summary row (skills first, then
        // location/scoped, then the free-text query).
        get activeSummary() {
            const out = [];
            this.filters.type.forEach((slug) => out.push({ kind: 'type', value: slug, label: this.typeName(slug) }));
            [['city', this.filters.city], ['country', this.filters.country], ['equipment', this.filters.equipment],
                ['software', this.filters.software], ['looks', this.filters.looks]]
                .forEach(([key, val]) => { if (val) out.push({ kind: key, value: val, label: val }); });
            if (this.filters.q) out.push({ kind: 'q', value: this.filters.q, label: `“${this.filters.q}”` });
            return out;
        },

        typeName(slug) {
            const type = this.types.find((x) => x.slug === slug);
            return type ? this.t(type.name) : slug;
        },

        iconFor(type) {
            const inner = this.skillIcons[type?.icon] || this._fallbackIcon;
            return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-full w-full" aria-hidden="true">${inner}</svg>`;
        },

        // --- Query <-> URL --------------------------------------------------
        buildQuery(page, forUrl = false) {
            const p = new URLSearchParams();
            if (this.filters.type.length) p.set('filter[type]', this.filters.type.join(','));
            if (this.filters.city) p.set('filter[city]', this.filters.city);
            if (this.filters.country) p.set('filter[country]', this.filters.country);
            if (this.filters.equipment) p.set('filter[equipment]', this.filters.equipment);
            if (this.filters.software) p.set('filter[software]', this.filters.software);
            if (this.filters.looks) p.set('filter[looks]', this.filters.looks);
            if (this.filters.q) p.set('filter[q]', this.filters.q);
            if (forUrl) { if (page > 1) p.set('page', page); } else { p.set('page', page); }
            return p.toString();
        },

        hydrateFromUrl() {
            const p = new URLSearchParams(window.location.search);
            const type = p.get('filter[type]');
            this.filters.type = type ? type.split(',').filter(Boolean) : [];
            this.filters.city = p.get('filter[city]') || '';
            this.filters.country = p.get('filter[country]') || '';
            this.filters.equipment = p.get('filter[equipment]') || '';
            this.filters.software = p.get('filter[software]') || '';
            this.filters.looks = p.get('filter[looks]') || '';
            this.filters.q = p.get('filter[q]') || '';
            this.pruneScopedFilters();
            this.page = parseInt(p.get('page') || '1', 10) || 1;
        },

        // mode: 'push' (discrete change) | 'replace' (typing/initial) | 'none' (popstate).
        syncUrl(mode) {
            if (mode === 'none') return;
            const qs = this.buildQuery(this.page, true);
            const url = qs ? `${window.location.pathname}?${qs}` : window.location.pathname;
            if (mode === 'replace') window.history.replaceState({ discover: true }, '', url);
            else window.history.pushState({ discover: true }, '', url);
        },

        async runSearch(page = 1, mode = 'push') {
            this.page = page;
            this.loading = true;
            try {
                const { data, meta } = await get(`/discover/search?${this.buildQuery(page)}`);
                this.results = data;
                this.meta = meta;
                this.syncUrl(mode);
            } finally {
                this.loading = false;
            }
        },

        // Public entry point: discrete changes push history; typing replaces it.
        search(page = 1, opts = {}) {
            return this.runSearch(page, opts.replace ? 'replace' : 'push');
        },

        // --- Skill chips (LIVE — the sticky bar; applies immediately) -------
        toggleType(slug) {
            const i = this.filters.type.indexOf(slug);
            if (i >= 0) this.filters.type.splice(i, 1);
            else this.filters.type.push(slug);
            this.pruneScopedFilters();
            this.search();
        },

        clearSkills() {
            if (!this.filters.type.length) return;
            this.filters.type = [];
            this.pruneScopedFilters();
            this.search();
        },

        // --- Skill chips (STAGED — inside the modal; no search until Apply) -
        toggleDraftType(slug) {
            const i = this.draft.type.indexOf(slug);
            if (i >= 0) this.draft.type.splice(i, 1);
            else this.draft.type.push(slug);
            this.pruneDraftScoped();
        },

        clearDraftSkills() {
            if (!this.draft.type.length) return;
            this.draft.type = [];
            this.pruneDraftScoped();
        },

        // Drop any scoped filter whose scope is no longer covered (self-contained
        // so it doesn't depend on the draft-based show getters).
        pruneScopedFilters() {
            const scopes = this.selectedScopes;
            const none = this.filters.type.length === 0;
            if (!(none || scopes.has('crew'))) this.filters.equipment = '';
            if (!(none || scopes.has('creative'))) this.filters.software = '';
            if (!(none || scopes.has('model'))) this.filters.looks = '';
        },

        pruneDraftScoped() {
            if (!this.showEquipment) this.draft.equipment = '';
            if (!this.showSoftware) this.draft.software = '';
            if (!this.showLooks) this.draft.looks = '';
        },

        // Remove one chip from the active-filter summary row.
        removeFilter(item) {
            if (item.kind === 'type') { this.toggleType(item.value); return; }
            this.filters[item.kind] = '';
            this.pruneScopedFilters();
            this.search();
        },

        clearAll() {
            this.filters = { type: [], city: '', country: '', equipment: '', software: '', looks: '', q: '' };
            this.search();
        },

        // --- Advanced-filters modal (teleported, scroll-locked, focus-trapped) --
        // We DON'T use Alpine x-transition here: its leave transition doesn't complete
        // for x-teleport'd nodes (the overlay stays display:flex and traps clicks). So
        // x-show plainly toggles display, and enter/leave animate via :class + CSS. A
        // mount→activate→(leave)→unmount cycle gives a real leave transition + reliable hide.
        get modalMotionMs() {
            return window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 200;
        },

        openFilters() {
            if (this.modalOpen) return;
            this.triggerEl = document.activeElement;
            // Snapshot the applied filters — the modal edits this draft only.
            this.draft = {
                type: [...this.filters.type],
                city: this.filters.city,
                country: this.filters.country,
                equipment: this.filters.equipment,
                software: this.filters.software,
                looks: this.filters.looks,
            };
            this.modalOpen = true;   // mount (display)
            this.lockScroll();
            // $nextTick (not rAF — rAF is throttled/paused in background tabs) flips the
            // animation state after the mount paints so CSS transitions it in.
            this.$nextTick(() => {
                this.modalActive = true;
                this.$refs.dialog?.focus();
            });
        },

        closeFilters() {
            if (!this.modalOpen) return;
            this.modalActive = false;   // animate out
            this.unlockScroll();
            const done = () => { this.modalOpen = false; this.$nextTick(() => this.triggerEl?.focus?.()); };
            if (this.modalMotionMs === 0) done();
            else setTimeout(done, this.modalMotionMs + 20);
        },

        // Commit the staged draft → applied filters, then search + close. (The only
        // path that applies the modal's changes; the free-text `q` is untouched.)
        applyFilters() {
            this.filters.type = [...this.draft.type];
            this.filters.city = this.draft.city;
            this.filters.country = this.draft.country;
            this.filters.equipment = this.draft.equipment;
            this.filters.software = this.draft.software;
            this.filters.looks = this.draft.looks;
            this.pruneScopedFilters();
            this.closeFilters();
            this.search();
        },

        // Reset the draft in place (skills + location + scoped groups). Does NOT
        // apply — the visitor still presses "Apply filters" to commit.
        clearModalFilters() {
            this.draft = { type: [], city: '', country: '', equipment: '', software: '', looks: '' };
        },

        // Freeze the page behind the dialog, preserving scroll position.
        lockScroll() {
            this._scrollY = window.scrollY;
            const body = document.body;
            body.style.position = 'fixed';
            body.style.top = `-${this._scrollY}px`;
            body.style.insetInlineStart = '0';
            body.style.width = '100%';
        },

        unlockScroll() {
            const body = document.body;
            body.style.position = '';
            body.style.top = '';
            body.style.insetInlineStart = '';
            body.style.width = '';
            window.scrollTo(0, this._scrollY || 0);
        },

        // Keep Tab focus inside the dialog; wrap at both ends.
        trapFocus(e) {
            if (e.key !== 'Tab' || !this.modalOpen) return;
            const dialog = this.$refs.dialog;
            if (!dialog) return;
            const focusables = Array.from(dialog.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
            )).filter((el) => el.offsetParent !== null);
            if (!focusables.length) return;
            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            const active = document.activeElement;
            if (e.shiftKey && (active === first || active === dialog)) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && active === last) { e.preventDefault(); first.focus(); }
        },
    }));

    // --- Public brand discovery (talent-facing) -----------------------------
    // Mirrors talentSearch: a primary Industry chip bar (live) + an advanced-filters
    // modal (Stage / Reach / Verified, staged until Apply), active-filter summary,
    // live count, and skeleton loaders.
    Alpine.data('brandsDiscover', (initial = {}) => ({
        ...filterModal(),
        brands: [],
        meta: null,
        loading: true,
        skeletons: [0, 1, 2, 3, 4, 5],
        industries: initial.industries || [],
        stages: initial.stages || [],
        reaches: initial.reaches || [],
        labels: initial.labels || {},
        // Applied filters (industry is the live primary facet; the rest are staged).
        filters: { industry: '', brand_stage: '', geographic_reach: '', verified: false, q: '' },
        draft: { brand_stage: '', geographic_reach: '', verified: false },

        async init() { await this.load(1); },

        label(key) { return this.labels[key] || key; },

        get resultTotal() { return this.meta?.pagination?.total ?? 0; },
        get activeFilterCount() {
            return (this.filters.brand_stage ? 1 : 0) + (this.filters.geographic_reach ? 1 : 0) + (this.filters.verified ? 1 : 0);
        },
        get selectedIndustryCount() { return this.filters.industry ? 1 : 0; },

        get activeSummary() {
            const out = [];
            if (this.filters.industry) out.push({ kind: 'industry', value: this.filters.industry, label: this.label(this.filters.industry) });
            if (this.filters.brand_stage) out.push({ kind: 'brand_stage', value: this.filters.brand_stage, label: this.label(this.filters.brand_stage) });
            if (this.filters.geographic_reach) out.push({ kind: 'geographic_reach', value: this.filters.geographic_reach, label: this.label(this.filters.geographic_reach) });
            if (this.filters.verified) out.push({ kind: 'verified', value: '1', label: this.labels.verified || 'Verified' });
            if (this.filters.q) out.push({ kind: 'q', value: this.filters.q, label: `“${this.filters.q}”` });
            return out;
        },

        // Primary Industry chips (live — apply immediately).
        toggleIndustry(v) { this.filters.industry = this.filters.industry === v ? '' : v; this.load(1); },
        clearIndustry() { if (this.filters.industry) { this.filters.industry = ''; this.load(1); } },

        removeFilter(item) {
            if (item.kind === 'verified') this.filters.verified = false;
            else this.filters[item.kind] = '';
            this.load(1);
        },
        clearAll() {
            this.filters = { industry: '', brand_stage: '', geographic_reach: '', verified: false, q: '' };
            this.load(1);
        },

        // Modal staging (Stage / Reach / Verified).
        snapshotDraft() {
            this.draft = {
                brand_stage: this.filters.brand_stage,
                geographic_reach: this.filters.geographic_reach,
                verified: this.filters.verified,
            };
        },
        toggleDraft(key, value) { this.draft[key] = this.draft[key] === value ? '' : value; },
        clearModalFilters() { this.draft = { brand_stage: '', geographic_reach: '', verified: false }; },
        applyFilters() {
            this.filters.brand_stage = this.draft.brand_stage;
            this.filters.geographic_reach = this.draft.geographic_reach;
            this.filters.verified = this.draft.verified;
            this.closeFilters();
            this.load(1);
        },

        async load(page = 1) {
            this.loading = true;
            try {
                const p = new URLSearchParams();
                if (this.filters.q) p.set('q', this.filters.q);
                if (this.filters.industry) p.set('industry', this.filters.industry);
                if (this.filters.brand_stage) p.set('brand_stage', this.filters.brand_stage);
                if (this.filters.geographic_reach) p.set('geographic_reach', this.filters.geographic_reach);
                if (this.filters.verified) p.set('verified', '1');
                p.set('page', page);
                const { data, meta } = await get(`/brands/feed?${p.toString()}`);
                this.brands = data;
                this.meta = meta;
            } finally {
                this.loading = false;
            }
        },
    }));

    // --- Public project browsing (talent-facing opportunities) -------------
    // Mirrors talentSearch: a primary Discipline chip bar (live, grouped by scope via
    // the shared skill-filter-chips partial) + an advanced-filters modal (Type / Budget
    // / Location, staged), active-filter summary, live count, skeleton loaders.
    Alpine.data('projectBrowse', (initial = {}) => ({
        ...filterModal(),
        types: initial.types || [],
        scopeLabels: initial.scopeLabels || { model: 'Modeling', crew: 'Crew', creative: 'Creative' },
        typeLabels: initial.typeLabels || { campaign: 'Campaign', shoot: 'Shoot' },
        campaigns: [],
        meta: null,
        loading: true,
        skeletons: [0, 1, 2, 3, 4, 5],
        // Applied filters — discipline `type` is the live primary facet (array of slugs).
        filters: { type: [], campaign_type: '', budget_min: '', budget_max: '', city: '', q: '' },
        // Staged draft for the modal (discipline chips + type/budget/city).
        draft: { type: [], campaign_type: '', budget_min: '', budget_max: '', city: '' },
        t,

        async init() { await this.load(1); },

        // --- Discipline chips (shared partial API) --------------------------
        get skillGroups() {
            return ['model', 'crew', 'creative']
                .map((category) => ({
                    category,
                    label: this.scopeLabels[category] || category,
                    types: this.types.filter((type) => type.category === category),
                }))
                .filter((group) => group.types.length > 0);
        },
        iconFor(type) { return disciplineIcon(type); },
        typeName(slug) {
            const type = this.types.find((x) => x.slug === slug);
            return type ? this.t(type.name) : slug;
        },

        get selectedSkillCount() { return this.filters.type.length; },
        get draftSelectedCount() { return this.draft.type.length; },
        get resultTotal() { return this.meta?.pagination?.total ?? 0; },
        get activeFilterCount() {
            return (this.filters.campaign_type ? 1 : 0) + (this.filters.city ? 1 : 0)
                + (this.filters.budget_min ? 1 : 0) + (this.filters.budget_max ? 1 : 0);
        },

        get activeSummary() {
            const out = [];
            this.filters.type.forEach((slug) => out.push({ kind: 'type', value: slug, label: this.typeName(slug) }));
            if (this.filters.campaign_type) out.push({ kind: 'campaign_type', value: this.filters.campaign_type, label: this.typeLabels[this.filters.campaign_type] || this.filters.campaign_type });
            if (this.filters.city) out.push({ kind: 'city', value: this.filters.city, label: this.filters.city });
            if (this.filters.budget_min) out.push({ kind: 'budget_min', value: this.filters.budget_min, label: `≥ ${Number(this.filters.budget_min).toLocaleString()}` });
            if (this.filters.budget_max) out.push({ kind: 'budget_max', value: this.filters.budget_max, label: `≤ ${Number(this.filters.budget_max).toLocaleString()}` });
            if (this.filters.q) out.push({ kind: 'q', value: this.filters.q, label: `“${this.filters.q}”` });
            return out;
        },

        // LIVE discipline chips (sticky bar).
        toggleType(slug) {
            const i = this.filters.type.indexOf(slug);
            if (i >= 0) this.filters.type.splice(i, 1); else this.filters.type.push(slug);
            this.load(1);
        },
        clearSkills() { if (this.filters.type.length) { this.filters.type = []; this.load(1); } },

        // STAGED discipline chips (modal).
        toggleDraftType(slug) {
            const i = this.draft.type.indexOf(slug);
            if (i >= 0) this.draft.type.splice(i, 1); else this.draft.type.push(slug);
        },
        clearDraftSkills() { this.draft.type = []; },

        removeFilter(item) {
            if (item.kind === 'type') { this.toggleType(item.value); return; }
            this.filters[item.kind] = '';
            this.load(1);
        },
        clearAll() {
            this.filters = { type: [], campaign_type: '', budget_min: '', budget_max: '', city: '', q: '' };
            this.load(1);
        },

        // Modal staging.
        snapshotDraft() {
            this.draft = {
                type: [...this.filters.type],
                campaign_type: this.filters.campaign_type,
                budget_min: this.filters.budget_min,
                budget_max: this.filters.budget_max,
                city: this.filters.city,
            };
        },
        clearModalFilters() { this.draft = { type: [], campaign_type: '', budget_min: '', budget_max: '', city: '' }; },
        applyFilters() {
            this.filters.type = [...this.draft.type];
            this.filters.campaign_type = this.draft.campaign_type;
            this.filters.budget_min = this.draft.budget_min;
            this.filters.budget_max = this.draft.budget_max;
            this.filters.city = this.draft.city;
            this.closeFilters();
            this.load(1);
        },

        async load(page = 1) {
            this.loading = true;
            try {
                const p = new URLSearchParams();
                if (this.filters.q) p.set('q', this.filters.q);
                if (this.filters.type.length) p.set('type', this.filters.type.join(','));
                if (this.filters.campaign_type) p.set('campaign_type', this.filters.campaign_type);
                if (this.filters.city) p.set('city', this.filters.city);
                if (this.filters.budget_min) p.set('budget_min', this.filters.budget_min);
                if (this.filters.budget_max) p.set('budget_max', this.filters.budget_max);
                p.set('page', page);
                const { data, meta } = await get(`/projects/feed?${p.toString()}`);
                this.campaigns = data;
                this.meta = meta;
            } finally {
                this.loading = false;
            }
        },
    }));

    // --- Generic list CRUD (reviews, content) -------------------------------
    Alpine.data('crudList', (config) => ({
        items: [],
        meta: null,
        page: 1,
        loading: true,
        saving: false,
        errors: {},
        form: JSON.parse(JSON.stringify(config.blank || {})),
        fields: config.fields || [],
        media: config.media || null,
        endpoint: config.endpoint,
        dataUrl: config.dataUrl || `${config.endpoint}/data`,
        dragId: null,
        t,

        async init() {
            await this.load();
        },

        async load(page = 1) {
            this.loading = true;
            try {
                const sep = this.dataUrl.includes('?') ? '&' : '?';
                const { data, meta } = await get(`${this.dataUrl}${sep}page=${page}`);
                this.items = Array.isArray(data) ? data : data.items || [];
                this.meta = meta;
                this.page = page;
            } finally {
                this.loading = false;
            }
        },

        async create() {
            this.errors = {};
            this.saving = true;
            try {
                await post(this.endpoint, this.form);
                await this.load(this.page);
                this.form = JSON.parse(JSON.stringify(config.blank || {}));
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally {
                this.saving = false;
            }
        },

        async remove(id) {
            await del(`${this.endpoint}/${id}`);
            await this.load(this.page);
        },

        async act(id, path, body = {}) {
            await patch(`${this.endpoint}/${id}/${path}`, body);
            await this.load(this.page);
        },

        /** Upload a media file to an existing item. */
        async uploadMedia(id, file) {
            if (!file) return;
            const body = new FormData();
            body.append('file', file);
            await post(`${this.endpoint}/${id}/media`, body);
            await this.load(this.page);
        },

        /** Quick-add media items: create a blank row per file, then upload it. */
        async createAndUpload(fileList) {
            this.saving = true;
            try {
                for (const file of Array.from(fileList)) {
                    const payload = JSON.parse(JSON.stringify(config.blank || {}));
                    // Infer the media type from the dropped file (gallery items).
                    if ('media_type' in payload) {
                        payload.media_type = file.type.startsWith('video') ? 'video' : 'image';
                    }
                    const { data } = await post(this.endpoint, payload);
                    const body = new FormData();
                    body.append('file', file);
                    await post(`${this.endpoint}/${data.id}/media`, body);
                }
                await this.load(this.page);
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally {
                this.saving = false;
            }
        },

        onDrop(item) {
            if (this.dragId === null || this.dragId === item.id) return;
            const from = this.items.findIndex((x) => x.id === this.dragId);
            const to = this.items.findIndex((x) => x.id === item.id);
            const [moved] = this.items.splice(from, 1);
            this.items.splice(to, 0, moved);
            this.dragId = null;
            patch(`${this.endpoint}/reorder`, { order: this.items.map((x) => x.id) }).catch(() => {});
        },
    }));
});
