/**
 * Admin dashboard Alpine components. Every mutation goes through the shared
 * http.js wrapper (JSON envelope); pages never reload. Authorization is enforced
 * server-side (can: middleware + the Phase 3A services).
 */
import { del, get, patch, post, ApiError } from './http';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // --- Deal-flow list -----------------------------------------------------
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

    // --- Deal-flow workspace ------------------------------------------------
    Alpine.data('adminFlow', (id) => ({
        id,
        flow: null,
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
    }));

    // --- Moderation queues --------------------------------------------------
    Alpine.data('adminModeration', () => ({
        tab: 'talents',
        rows: [],
        meta: null,
        loading: false,
        selected: [],

        async init() { await this.openTab('talents'); },
        async openTab(tab) {
            this.tab = tab;
            this.selected = [];
            await this.load();
        },
        async load(page = 1) {
            this.loading = true;
            try {
                const { data, meta } = await get(`/admin/moderation/${this.tab}?page=${page}`);
                this.rows = data;
                this.meta = meta;
            } finally { this.loading = false; }
        },
        toggle(id) {
            const i = this.selected.indexOf(id);
            i >= 0 ? this.selected.splice(i, 1) : this.selected.push(id);
        },
        async action(row, action, extra = {}) {
            try {
                await patch(`/admin/moderation/${this.tab}/${row.id}/${action}`, extra);
                await this.load();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },
        async batch(action) {
            if (!this.selected.length) return;
            await post('/admin/moderation/reviews/batch', { action, ids: this.selected });
            this.selected = [];
            await this.load();
        },
    }));

    // --- Profession/template manager ----------------------------------------
    Alpine.data('adminProfessions', (initial) => ({
        types: [],
        blockTypes: initial.blockTypes,
        loading: true,
        newType: { name: { en: '', ar: '' }, category: 'model', default_blocks: [], icon: '' },
        errors: {},

        async init() { await this.load(); },
        async load() {
            const { data } = await get('/admin/professions/data');
            this.types = data.types;
            this.loading = false;
        },
        toggleBlock(type, key) {
            const i = type.default_blocks.indexOf(key);
            i >= 0 ? type.default_blocks.splice(i, 1) : type.default_blocks.push(key);
        },
        async saveBlocks(type) {
            await patch(`/admin/professions/${type.id}/blocks`, { default_blocks: type.default_blocks });
        },
        async addType() {
            this.errors = {};
            try {
                await post('/admin/professions', this.newType);
                this.newType = { name: { en: '', ar: '' }, category: 'model', default_blocks: [], icon: '' };
                await this.load();
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            }
        },
    }));

    // --- Deal console (list) ------------------------------------------------
    Alpine.data('adminDeals', () => ({
        deals: [],
        meta: null,
        loading: true,
        status: '',

        async init() { await this.load(); },
        async load(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.status) params.set('status', this.status);
                params.set('page', page);
                const { data, meta } = await get(`/admin/deals/data?${params.toString()}`);
                this.deals = data;
                this.meta = meta;
            } finally { this.loading = false; }
        },
        setStatus(s) { this.status = s; this.load(); },
    }));

    // --- Deal console (intervention) ----------------------------------------
    Alpine.data('adminDeal', (id) => ({
        id,
        deal: null,
        steps: [],
        messages: [],
        loading: true,
        acting: false,
        note: '',

        async init() { await this.refresh(); },
        async refresh() {
            this.loading = true;
            try {
                const { data } = await get(`/admin/deals/${this.id}/thread`);
                this.deal = data.deal;
                this.steps = data.steps;
                this.messages = data.messages;
            } finally { this.loading = false; }
        },
        async act(action, extra = {}) {
            this.acting = true;
            try {
                await post(`/admin/deals/${this.id}/${action}`, extra);
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
            default_deal_flow_id: initial.settings.default_deal_flow_id || null,
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
        form: { name: '', email: '', password: '', locale: 'en', roles: [] },
        creating: false,
        errors: {},

        async init() { await this.load(); },
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
            this.creating = true;
            try {
                await post('/admin/users', this.form);
                this.form = { name: '', email: '', password: '', locale: 'en', roles: [] };
                await this.load();
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
