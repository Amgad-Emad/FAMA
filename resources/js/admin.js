/**
 * Admin dashboard Alpine components. Every mutation goes through the shared
 * http.js wrapper (JSON envelope); pages never reload. Authorization is enforced
 * server-side (can: middleware + the Phase 3A services).
 */
import { del, get, patch, post, ApiError } from './http';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Semantic status pill: $pill('active') → token-bound classes. One mapping
    // for every admin list so the same state always reads the same colour.
    const PILL_BASE = 'rounded-pill px-2 py-0.5 text-xs font-medium';
    const PILL_TONES = [
        [['active', 'live', 'approved', 'completed', 'published', 'open', 'verified'], 'bg-success-weak text-success'],
        [['pending', 'draft', 'created', 'awaiting_brand', 'awaiting_talent', 'awaiting_admin', 'in_progress', 'onboarding', 'registered'], 'bg-warn-weak text-warn'],
        [['suspended', 'cancelled', 'rejected', 'declined', 'expired', 'archived', 'deleted', 'unpublished'], 'bg-danger-weak text-danger'],
    ];
    Alpine.magic('pill', () => (status) => {
        const tone = PILL_TONES.find(([states]) => states.includes(status))?.[1] ?? 'bg-elevated text-muted';
        return `${PILL_BASE} ${tone}`;
    });

    // --- Contract-flow list -----------------------------------------------------
    Alpine.data('adminFlows', () => ({
        flows: [],
        meta: null,
        loading: true,
        form: { name: '', description: '', applies_to: '' },
        creating: false,
        errors: {},

        async init() { await this.load(); },
        async load(page = 1) {
            this.loading = true;
            try {
                const { data, meta } = await get(`/admin/flows/data?page=${page}`);
                this.flows = data;
                this.meta = meta;
            } finally { this.loading = false; }
        },
        async create() {
            this.errors = {};
            this.creating = true;
            try {
                const { data } = await post('/admin/flows', this.form);
                window.location.href = data.redirect;
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally { this.creating = false; }
        },
    }));

    // --- Contract-flow workspace ------------------------------------------------
    Alpine.data('adminFlow', (id) => ({
        id,
        flow: null,
        templates: [],
        loading: true,
        acting: false,
        dragId: null,
        newStep: { key: '', name: '', actor: 'brand', step_type: 'form', is_required: true, is_skippable: false },
        errors: {},

        async init() { await this.refresh(); },
        async refresh() {
            this.loading = true;
            try {
                const { data } = await get(`/admin/flows/${this.id}/data`);
                this.flow = data.flow;
                this.templates = data.templates || [];
            } finally { this.loading = false; }
        },
        async addStep() {
            this.errors = {};
            try {
                await post(`/admin/flows/${this.id}/steps`, this.newStep);
                this.newStep = { key: '', name: '', actor: 'brand', step_type: 'form', is_required: true, is_skippable: false };
                await this.refresh();
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            }
        },
        async updateStep(step) {
            await patch(`/admin/flows/${this.id}/steps/${step.id}`, step);
            await this.refresh();
        },
        async removeStep(step) {
            await del(`/admin/flows/${this.id}/steps/${step.id}`);
            await this.refresh();
        },
        async drop(step) {
            if (this.dragId === null || this.dragId === step.id) return;
            const ids = this.flow.steps.map((s) => s.id);
            const from = ids.indexOf(this.dragId);
            const to = ids.indexOf(step.id);
            ids.splice(to, 0, ids.splice(from, 1)[0]);
            this.dragId = null;
            await patch(`/admin/flows/${this.id}/steps/reorder`, { ids });
            await this.refresh();
        },
        async lifecycle(action) {
            this.acting = true;
            try {
                await patch(`/admin/flows/${this.id}/${action}`, {});
                await this.refresh();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            } finally { this.acting = false; }
        },
        // Attach / detach the contract-document template (PATCHes the flow meta).
        async saveTemplate() {
            try {
                await patch(`/admin/flows/${this.id}`, {
                    name: this.flow.name,
                    description: this.flow.description,
                    applies_to: this.flow.applies_to,
                    contract_template_id: this.flow.contract_template_id || null,
                });
                await this.refresh();
            } catch (e) { if (e instanceof ApiError) window.alert(e.message); }
        },
    }));

    // --- Contract-template list -------------------------------------------------
    Alpine.data('adminContractTemplates', () => ({
        templates: [],
        meta: null,
        loading: true,
        form: { name: '', applies_to: '' },
        creating: false,
        errors: {},

        async init() { await this.load(); },
        async load(page = 1) {
            this.loading = true;
            try {
                const { data, meta } = await get(`/admin/contract-templates/data?page=${page}`);
                this.templates = data;
                this.meta = meta;
            } finally { this.loading = false; }
        },
        async create() {
            this.errors = {};
            this.creating = true;
            try {
                const { data } = await post('/admin/contract-templates', this.form);
                window.location.href = data.redirect;
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally { this.creating = false; }
        },
    }));

    // --- Contract-template builder (terms + clauses + LIVE document preview) -----
    Alpine.data('adminContractTemplate', (id) => ({
        id,
        loading: true,
        saving: false,
        saved: false,
        errors: {},
        isDefault: false,
        isActive: true,
        flowsCount: 0,
        dragIndex: null,
        // Authored terms (the saved schema).
        form: {
            name: '', description: '', applies_to: '',
            deposit_pct: 50, deposit_holder: 'Fama', cancellation_forfeit_pct: 50,
            revision_rounds: 1, preview_currency: 'EGP', intro: '', clauses: [],
        },
        // Fictional negotiation inputs that drive the live preview (never saved).
        sample: {
            talent: 'Yasmine K., Photographer', brand: 'Studio Nour',
            scope: '3 lookbook images, delivered edited',
            timeline: 'Shoot Aug 4 · Delivery Aug 11', total: 4500, exception: '',
        },
        previewHtml: '',
        previewing: false,
        _previewTimer: null,

        async init() {
            await this.load();
            this.renderPreview();
        },
        async load() {
            this.loading = true;
            try {
                const { data } = await get(`/admin/contract-templates/${this.id}/data`);
                const t = data.template;
                this.form = {
                    name: t.name, description: t.description || '', applies_to: t.applies_to || '',
                    deposit_pct: t.deposit_pct, deposit_holder: t.deposit_holder,
                    cancellation_forfeit_pct: t.cancellation_forfeit_pct, revision_rounds: t.revision_rounds,
                    preview_currency: t.preview_currency, intro: t.intro || '', clauses: t.clauses || [],
                };
                this.isDefault = t.is_default;
                this.isActive = t.is_active;
                this.flowsCount = t.flows_count ?? 0;
            } finally { this.loading = false; }
        },

        // Clause editing.
        addClause() { this.form.clauses.push({ label: '', body: '' }); this.schedulePreview(); },
        removeClause(i) { this.form.clauses.splice(i, 1); this.schedulePreview(); },
        dropClause(i) {
            if (this.dragIndex === null || this.dragIndex === i) return;
            const moved = this.form.clauses.splice(this.dragIndex, 1)[0];
            this.form.clauses.splice(i, 0, moved);
            this.dragIndex = null;
            this.schedulePreview();
        },

        // Debounced live preview — renders the SAME partial the contract room uses.
        schedulePreview() {
            clearTimeout(this._previewTimer);
            this._previewTimer = setTimeout(() => this.renderPreview(), 250);
        },
        async renderPreview() {
            this.previewing = true;
            try {
                const { data } = await post('/admin/contract-templates/preview', {
                    deposit_pct: this.numeric(this.form.deposit_pct),
                    deposit_holder: this.form.deposit_holder,
                    cancellation_forfeit_pct: this.numeric(this.form.cancellation_forfeit_pct),
                    revision_rounds: this.numeric(this.form.revision_rounds),
                    preview_currency: this.form.preview_currency,
                    intro: this.form.intro,
                    clauses: this.form.clauses,
                    sample: { ...this.sample, currency: this.form.preview_currency, total: this.numeric(this.sample.total) },
                });
                this.previewHtml = data.html;
            } catch (e) { /* preview is best-effort; keep the last good render */ }
            finally { this.previewing = false; }
        },
        numeric(v) { const n = Number(v); return Number.isFinite(n) ? n : 0; },

        async save() {
            this.errors = {};
            this.saving = true;
            this.saved = false;
            try {
                await patch(`/admin/contract-templates/${this.id}`, {
                    ...this.form,
                    applies_to: this.form.applies_to || null,
                });
                this.saved = true;
                setTimeout(() => (this.saved = false), 2000);
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally { this.saving = false; }
        },
        async makeDefault() {
            try { await patch(`/admin/contract-templates/${this.id}/default`, {}); this.isDefault = true; this.isActive = true; }
            catch (e) { if (e instanceof ApiError) window.alert(e.message); }
        },
        async toggleActive() {
            try { await patch(`/admin/contract-templates/${this.id}/toggle`, {}); this.isActive = !this.isActive; }
            catch (e) { if (e instanceof ApiError) window.alert(e.message); }
        },
        async remove() {
            try { await del(`/admin/contract-templates/${this.id}`); window.location.href = window.fama.localizeUrl('/admin/contract-templates'); }
            catch (e) { if (e instanceof ApiError) window.alert(e.message); }
        },
    }));

    // --- Moderation queues --------------------------------------------------
    Alpine.data('adminModeration', (initial = 'talents') => ({
        tab: initial,
        rows: [],
        meta: null,
        loading: false,
        selected: [],
        q: '',
        statusFilter: '',
        _searchTimer: null,

        async init() { await this.openTab(this.tab); },

        async openTab(tab) {
            this.tab = tab;
            this.selected = [];
            // Switching queue clears the query so filters don't leak across tabs.
            this.q = '';
            this.statusFilter = '';
            // Keep ?queue= in sync so a reload (and the sidebar's active state)
            // lands on the same tab. replaceState: tab switches aren't history.
            const url = new URL(window.location);
            url.searchParams.set('queue', tab);
            window.history.replaceState(null, '', url);
            await this.load();
        },
        // Status filter options per queue (empty = no status filter for this queue).
        statusOptions() {
            if (this.tab === 'talents') return ['created', 'draft', 'live', 'unpublished', 'suspended', 'archived'];
            if (this.tab === 'brands') return ['registered', 'onboarding', 'complete', 'published', 'unpublished', 'suspended'];
            if (this.tab === 'projects') return ['draft', 'open', 'in_progress', 'completed', 'cancelled'];
            return [];
        },
        onSearch() {
            clearTimeout(this._searchTimer);
            this._searchTimer = setTimeout(() => this.load(), 350);
        },
        setStatusFilter(status) {
            this.statusFilter = status;
            this.load();
        },
        async load(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({ page });
                if (this.q) params.set('q', this.q);
                if (this.statusFilter) params.set('status', this.statusFilter);
                const { data, meta } = await get(`/admin/moderation/${this.tab}?${params.toString()}`);
                this.rows = data;
                this.meta = meta;
            } finally { this.loading = false; }
        },
        toggle(id) {
            const i = this.selected.indexOf(id);
            i >= 0 ? this.selected.splice(i, 1) : this.selected.push(id);
        },
        // The global queue mixes talent + brand reviews; route each row's
        // action to its kind's endpoint.
        actionQueue(row) {
            if (this.tab !== 'all-reviews') return this.tab;
            return row.kind === 'brand' ? 'brand-reviews' : 'reviews';
        },
        async action(row, action, extra = {}) {
            try {
                await patch(`/admin/moderation/${this.actionQueue(row)}/${row.id}/${action}`, extra);
                this.closeDetail();
                await this.load();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },

        // --- Detail drawer -------------------------------------------------
        // `detail` carries the fetched payload and MOUNTS the overlay;
        // `drawerOpen` drives the CSS enter/leave transitions (flipped a tick
        // after mount so the browser paints the closed state first, and
        // flipped back on close with `detail` cleared only after the leave
        // finishes). `detailKind` picks the drawer template — the global
        // queue resolves per row via actionQueue().
        detail: null,
        detailKind: null,
        detailLoading: false,
        drawerOpen: false,
        drawerTimer: null,
        async openDetail(row) {
            clearTimeout(this.drawerTimer);
            this.detailKind = this.actionQueue(row);
            this.detailLoading = true;
            this.detail = { id: row.id };
            this.$nextTick(() => { this.drawerOpen = true; });
            try {
                const { data } = await get(`/admin/moderation/${this.detailKind}/${row.id}`);
                this.detail = data;
            } catch (e) {
                this.closeDetail();
                if (e instanceof ApiError) window.alert(e.message);
            } finally { this.detailLoading = false; }
        },
        closeDetail() {
            if (!this.detail) return;
            this.drawerOpen = false;
            // Matches the panel's transition duration (300ms) so the slide-out
            // completes before the overlay unmounts.
            this.drawerTimer = setTimeout(() => {
                this.detail = null;
                this.detailKind = null;
            }, 300);
        },
        async batch(action) {
            if (!this.selected.length) return;
            await post('/admin/moderation/reviews/batch', { action, ids: this.selected });
            this.selected = [];
            await this.load();
        },

        // --- Delete-review composer (email notice + reason + live preview) ---
        // Removing a review sends the reviewed talent a notice explaining why.
        // The modal composes that email (recipient, subject, reason) with a live
        // preview that mirrors the emails/review-removed.blade.php template.
        del: { open: false, review: null, email: '', subject: '', reason: '', sending: false, errors: {} },
        openDeleteReview(review) {
            this.del = {
                open: true,
                // Reviews are anonymous, so the notice defaults to the reviewed
                // talent's email (editable).
                review: {
                    id: review.id,
                    reviewer_name: review.reviewer_name,
                    talent: review.talent_name || review.talent || '',
                    rating: review.rating,
                    body: review.body,
                },
                email: review.talent_email || '',
                subject: 'A review was removed from your Fama profile',
                reason: '',
                sending: false,
                errors: {},
            };
        },
        closeDeleteReview() { this.del.open = false; },
        async submitDeleteReview() {
            if (this.del.sending) return;
            this.del.sending = true;
            this.del.errors = {};
            try {
                await post(`/admin/moderation/reviews/${this.del.review.id}/delete`, {
                    email: this.del.email,
                    subject: this.del.subject,
                    reason: this.del.reason,
                });
                this.del.open = false;
                this.closeDetail();
                await this.load();
            } catch (e) {
                if (e instanceof ApiError && e.status === 422) {
                    this.del.errors = e.errors || {};
                } else if (e instanceof ApiError) {
                    window.alert(e.message);
                }
            } finally {
                this.del.sending = false;
            }
        },
    }));

    // --- Skills template manager ----------------------------------------
    // Preselection + order only; eligibility comes from the Block catalog
    // (each type carries its own eligible_blocks / invalid_blocks from data()).
    Alpine.data('adminSkills', () => ({
        types: [],
        loading: true,
        dragKey: null,
        newType: { name: { en: '', ar: '' }, category: 'model', default_blocks: [], icon: '' },
        // New-speciality drafts, keyed by skill id — each skill's row has its own
        // input, so one shared field would leak text between rows.
        newSpeciality: {},
        errors: {},

        async init() { await this.load(); },
        async load() {
            const { data } = await get('/admin/skills/data');
            this.types = data.types;
            this.loading = false;
        },
        blockName(type, key) {
            return type.eligible_blocks.find(b => b.key === key)?.name ?? key;
        },
        addBlock(type, key) {
            if (!type.default_blocks.includes(key)) type.default_blocks.push(key);
        },
        removeBlock(type, key) {
            const i = type.default_blocks.indexOf(key);
            if (i >= 0) type.default_blocks.splice(i, 1);
        },
        dropOn(type, targetKey) {
            if (!this.dragKey || this.dragKey === targetKey) return;
            const from = type.default_blocks.indexOf(this.dragKey);
            const to = type.default_blocks.indexOf(targetKey);
            if (from < 0 || to < 0) return;
            type.default_blocks.splice(from, 1);
            type.default_blocks.splice(to, 0, this.dragKey);
            this.dragKey = null;
        },
        async saveBlocks(type) {
            try {
                await patch(`/admin/skills/${type.id}/blocks`, { default_blocks: type.default_blocks });
                await this.load();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },
        // --- Specialities (the controlled vocabulary, ADR-Y) ------------------

        specialityDraft(typeId) {
            if (!this.newSpeciality[typeId]) this.newSpeciality[typeId] = { name: '', is_regional: false };
            return this.newSpeciality[typeId];
        },

        async addSpeciality(type) {
            const draft = this.specialityDraft(type.id);
            if (!draft.name.trim()) return;
            this.errors = {};
            try {
                await post(`/admin/skills/${type.id}/specialities`, {
                    name: { en: draft.name.trim() },
                    is_regional: draft.is_regional,
                });
                this.newSpeciality[type.id] = { name: '', is_regional: false };
                await this.load();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },

        // Retiring is grandfathering, never a delete: the entry leaves every
        // picker while existing claims keep rendering and keep being found.
        async toggleSpeciality(speciality) {
            try {
                await patch(`/admin/specialities/${speciality.id}/active`, { is_active: !speciality.is_active });
                await this.load();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },

        async addType() {
            this.errors = {};
            try {
                await post('/admin/skills', this.newType);
                this.newType = { name: { en: '', ar: '' }, category: 'model', default_blocks: [], icon: '' };
                await this.load();
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            }
        },
    }));

    // --- Block catalog manager ------------------------------------------------
    // Owns block-type existence + eligibility; the Skills page only preselects.
    const emptyBlockForm = () => ({
        id: null, in_use_count: 0, key: '', name: { en: '', ar: '' },
        availability: 'universal', categories: [], talent_type_ids: [],
        content_source: 'inline', default_layout: '', is_active: true,
        is_repeatable: false, settings_schema: '',
    });

    Alpine.data('adminBlocks', (initial) => ({
        rows: [],
        meta: null,
        loading: true,
        talentTypes: initial.talentTypes,
        locale: initial.locale || 'en',
        t: initial.t || {},
        form: emptyBlockForm(),
        errors: {},

        async init() { await this.load(); },
        async load(page = 1) {
            this.loading = true;
            try {
                const { data, meta } = await get(`/admin/blocks/data?page=${page}`);
                this.rows = data;
                this.meta = meta;
            } finally { this.loading = false; }
        },
        // Localized block name (falls back to English).
        blockName(row) {
            return (row.name && (row.name[this.locale] || row.name.en)) || row.key;
        },
        sourceLabel(source) {
            return this.t[source] || source;
        },
        catLabel(cat) {
            return (this.t.categories && this.t.categories[cat]) || cat;
        },
        availabilityLabel(row) {
            if (row.availability === 'by_category') return `${this.t.byCategory}: ${row.categories.map(c => this.catLabel(c)).join(', ')}`;
            if (row.availability === 'by_type') return this.t.bySkill;
            return this.t.universal;
        },
        toggleIn(list, value) {
            const i = list.indexOf(value);
            i >= 0 ? list.splice(i, 1) : list.push(value);
        },
        edit(row) {
            this.errors = {};
            this.form = {
                id: row.id, in_use_count: row.in_use_count, key: row.key,
                name: { en: row.name.en ?? '', ar: row.name.ar ?? '' },
                availability: row.availability,
                categories: [...row.categories],
                talent_type_ids: [...row.talent_type_ids],
                content_source: row.content_source,
                default_layout: row.default_layout ?? '',
                is_active: row.is_active, is_repeatable: row.is_repeatable,
                settings_schema: row.settings_schema ? JSON.stringify(row.settings_schema) : '',
            };
        },
        reset() { this.form = emptyBlockForm(); this.errors = {}; },
        async save() {
            this.errors = {};
            const payload = { ...this.form, default_layout: this.form.default_layout || null, settings_schema: this.form.settings_schema || null };
            try {
                if (this.form.id) await patch(`/admin/blocks/${this.form.id}`, payload);
                else await post('/admin/blocks', payload);
                this.reset();
                await this.load(this.meta?.pagination?.current_page ?? 1);
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            }
        },
        async toggle(row) {
            try {
                await patch(`/admin/blocks/${row.id}/toggle`);
                await this.load(this.meta?.pagination?.current_page ?? 1);
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },
    }));

    // --- Contract console (list) ------------------------------------------------
    Alpine.data('adminContracts', () => ({
        contracts: [],
        meta: null,
        loading: true,
        status: '',
        step: '',

        async init() { await this.load(); },
        async load(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.status) params.set('status', this.status);
                if (this.step) params.set('step', this.step);
                params.set('page', page);
                const { data, meta } = await get(`/admin/contracts/data?${params.toString()}`);
                this.contracts = data;
                this.meta = meta;
            } finally { this.loading = false; }
        },
        setStatus(s) { this.status = s; this.load(); },
        setStep(s) { this.step = s; this.load(); },
    }));

    // --- Contract console (intervention) ----------------------------------------
    Alpine.data('adminContract', (id) => ({
        id,
        contract: null,
        steps: [],
        messages: [],
        loading: true,
        acting: false,
        note: '',

        async init() { await this.refresh(); },
        async refresh() {
            this.loading = true;
            try {
                const { data } = await get(`/admin/contracts/${this.id}/thread`);
                this.contract = data.contract;
                this.steps = data.steps;
                this.messages = data.messages;
            } finally { this.loading = false; }
        },
        async act(action, extra = {}) {
            this.acting = true;
            try {
                await post(`/admin/contracts/${this.id}/${action}`, extra);
                this.note = '';
                await this.refresh();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            } finally { this.acting = false; }
        },
    }));

    // --- Activity log -------------------------------------------------------
    Alpine.data('adminActivity', () => ({
        rows: [],
        meta: null,
        loading: true,
        q: '',
        log: '',

        async init() { await this.load(); },
        async load(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.q) params.set('q', this.q);
                if (this.log) params.set('log', this.log);
                params.set('page', page);
                const { data, meta } = await get(`/admin/activity/data?${params.toString()}`);
                this.rows = data;
                this.meta = meta;
            } finally { this.loading = false; }
        },
    }));

    // --- Settings -----------------------------------------------------------
    Alpine.data('adminSettings', (initial) => ({
        form: {
            default_currency: initial.settings.default_currency || 'EGP',
            default_contract_flow_id: initial.settings.default_contract_flow_id || null,
            feature_flags: initial.settings.feature_flags || {},
        },
        flows: initial.flows,
        saving: false,
        saved: false,

        async save() {
            this.saving = true;
            this.saved = false;
            try {
                await patch('/admin/settings', this.form);
                this.saved = true;
                setTimeout(() => (this.saved = false), 2000);
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            } finally { this.saving = false; }
        },
    }));

    // --- Admin users --------------------------------------------------------
    Alpine.data('adminUsers', (initial) => ({
        users: [],
        meta: null,
        loading: true,
        roles: initial.roles,
        emptyForm: () => ({ account_type: 'admin', name: '', email: '', password: '', locale: 'en', roles: [] }),
        form: null,
        creating: false,
        flash: '',
        errors: {},

        init() { this.form = this.emptyForm(); return this.load(); },
        async load(page = 1) {
            this.loading = true;
            try {
                const { data, meta } = await get(`/admin/users/data?page=${page}`);
                this.users = data;
                this.meta = meta;
            } finally { this.loading = false; }
        },
        async create() {
            this.errors = {};
            this.flash = '';
            this.creating = true;
            try {
                const { message } = await post('/admin/users', this.form);
                const wasAdmin = this.form.account_type === 'admin';
                this.form = this.emptyForm();
                this.flash = message || '';
                // Only admins appear in this list; refresh just for that case.
                if (wasAdmin) await this.load();
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally { this.creating = false; }
        },
        async syncRoles(user, roles) {
            await patch(`/admin/users/${user.id}/roles`, { roles });
            await this.load();
        },
        async remove(user) {
            try {
                await del(`/admin/users/${user.id}`);
                await this.load();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },
    }));
});
