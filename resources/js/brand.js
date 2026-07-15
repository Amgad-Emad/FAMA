/**
 * Brand dashboard Alpine components. Every mutation goes through the shared
 * http.js wrapper (JSON envelope) so pages never reload; validation errors are
 * surfaced inline from `envelope.errors`. The deal room mirrors the talent side
 * but acts as the `brand` role (awaiting_brand highlighted).
 */
import { del, get, patch, post, ApiError } from './http';

/** Resolve a translation map ({en, ar}) to the current locale. */
function t(map) {
    if (!map || typeof map !== 'object') return map ?? '';
    const locale = (document.documentElement.lang || 'en').slice(0, 2);
    return map[locale] || map.en || Object.values(map)[0] || '';
}

/** Toggle a value in an array in place. */
function toggleIn(list, value) {
    const i = list.indexOf(value);
    if (i >= 0) list.splice(i, 1);
    else list.push(value);
}

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // --- Onboarding wizard (6 steps) ----------------------------------------
    Alpine.data('brandOnboarding', (initial) => ({
        step: 1,
        saving: false,
        errors: {},
        data: initial.data,

        toggle(list, value) { toggleIn(this.data[list], value); },

        async save(endpoint, payload, nextStep) {
            this.saving = true;
            this.errors = {};
            try {
                await post(`/brand/onboarding/${endpoint}`, payload);
                if (nextStep) this.step = nextStep;
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally {
                this.saving = false;
            }
        },

        identity() {
            this.save('identity', {
                name: this.data.name,
                description: this.data.description,
                industry: this.data.industry,
                brand_stage: this.data.brand_stage,
            }, 2);
        },
        location() {
            this.save('location', {
                base_city: this.data.base_city,
                base_country: this.data.base_country,
                geographic_reach: this.data.geographic_reach,
            }, 3);
        },
        needs() {
            this.save('creative-needs', {
                talent_type_ids: this.data.talent_type_ids,
                project_types: this.data.project_types,
                project_frequency: this.data.project_frequency,
            }, 4);
        },
        aesthetic() {
            this.save('aesthetic', {
                mood_tags: this.data.mood_tags,
                brand_references: this.data.brand_references,
            }, 5);
        },
        budget() {
            this.save('budget', { budget_tier: this.data.budget_tier }, 6);
        },
        async complete() {
            this.saving = true;
            try {
                const { data } = await post('/brand/onboarding/complete', {});
                window.location.href = data.redirect;
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            } finally {
                this.saving = false;
            }
        },
    }));

    // --- Profile editor -----------------------------------------------------
    Alpine.data('brandProfile', (initial) => ({
        core: initial.core,
        aesthetic: initial.aesthetic,
        moods: initial.moods,
        platforms: initial.platforms,
        logoUrl: initial.core.logo_url,
        coverUrl: initial.core.cover_image_url,
        images: [],
        handles: [],
        newHandle: { platform: 'instagram', handle: '', url: '' },
        errors: {},
        savingCore: false,
        coreSaved: false,
        savingAesthetic: false,

        // Account settings + publish (folded in from the old Account tab).
        account: initial.account,
        accountErrors: {},
        savingAccount: false,
        accountSaved: false,
        published: initial.published,
        publishing: false,

        // Creative needs (folded in from the old Creative needs tab).
        needs: initial.needs,
        savingNeeds: false,
        needsSaved: false,

        async init() {
            await this.loadImages();
            await this.loadHandles();
        },

        async saveCore() {
            this.errors = {};
            this.savingCore = true;
            this.coreSaved = false;
            try {
                await patch('/brand/profile', this.core);
                this.coreSaved = true;
                setTimeout(() => (this.coreSaved = false), 2000);
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally {
                this.savingCore = false;
            }
        },

        // Account settings (slug, founded year, company size, phone).
        async saveAccount() {
            this.accountErrors = {};
            this.savingAccount = true;
            this.accountSaved = false;
            try {
                const { data } = await patch('/brand/account', this.account);
                if (data.slug) this.account.slug = data.slug;
                this.accountSaved = true;
                setTimeout(() => (this.accountSaved = false), 2000);
            } catch (e) {
                if (e instanceof ApiError) this.accountErrors = e.errors || { _: [e.message] };
            } finally {
                this.savingAccount = false;
            }
        },

        // Publish / unpublish the brand profile.
        async togglePublish() {
            this.publishing = true;
            try {
                const { data } = await patch('/brand/account/publish', { publish: !this.published });
                this.published = data.is_published;
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            } finally {
                this.publishing = false;
            }
        },

        async upload(event, endpoint, target) {
            const file = event.target.files[0];
            if (!file) return;
            const body = new FormData();
            body.append('file', file);
            try {
                const { data } = await post(`/brand/profile/${endpoint}`, body);
                this[target] = data.logo_url || data.cover_image_url;
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },

        async saveAesthetic() {
            this.savingAesthetic = true;
            try {
                await patch('/brand/profile/aesthetic', {
                    brand_references: this.aesthetic.brand_references,
                    mood_tags: this.aesthetic.mood_tags,
                });
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            } finally {
                this.savingAesthetic = false;
            }
        },
        toggleMood(mood) { toggleIn(this.aesthetic.mood_tags, mood); },

        // Creative-needs preferences (folded in from the old Creative needs tab).
        toggleNeed(list, value) { toggleIn(this.needs[list], value); },
        async saveNeeds() {
            this.savingNeeds = true;
            this.needsSaved = false;
            try {
                await patch('/brand/creative-needs', this.needs);
                this.needsSaved = true;
                setTimeout(() => (this.needsSaved = false), 2000);
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            } finally {
                this.savingNeeds = false;
            }
        },

        async loadImages() {
            const { data } = await get('/brand/profile/images');
            this.images = data.images;
        },
        async addImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            const body = new FormData();
            body.append('file', file);
            try {
                await post('/brand/profile/images', body);
                await this.loadImages();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },
        async removeImage(id) {
            await del(`/brand/profile/images/${id}`);
            this.images = this.images.filter((i) => i.id !== id);
        },

        async loadHandles() {
            const { data } = await get('/brand/social/data');
            this.handles = data.handles;
        },
        async addHandle() {
            try {
                await post('/brand/social', this.newHandle);
                this.newHandle = { platform: 'instagram', handle: '', url: '' };
                await this.loadHandles();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },
        async removeHandle(id) {
            await del(`/brand/social/${id}`);
            this.handles = this.handles.filter((h) => h.id !== id);
        },
    }));

    // (The standalone Creative-needs editor was folded into brandProfile.)

    // --- Campaigns manager --------------------------------------------------
    Alpine.data('brandCampaigns', (initial) => ({
        campaigns: [],
        meta: null,
        loading: true,
        showForm: false,
        creating: false,
        errors: {},
        types: initial.types,
        form: { title: '', type: 'campaign', budget_min: null, budget_max: null, is_public: false, roles: [] },
        t,

        async init() { await this.load(); },

        async load(page = 1) {
            this.loading = true;
            try {
                const { data, meta } = await get(`/brand/campaigns/data?page=${page}`);
                this.campaigns = data;
                this.meta = meta;
            } finally {
                this.loading = false;
            }
        },

        addRole() { this.form.roles.push({ talent_type_id: this.types[0]?.id, quantity: 1 }); },
        removeRole(i) { this.form.roles.splice(i, 1); },

        async create() {
            this.errors = {};
            this.creating = true;
            try {
                const { data } = await post('/brand/campaigns', this.form);
                window.location.href = `/brand/campaigns/${data.id}`;
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally {
                this.creating = false;
            }
        },
    }));

    // --- Single campaign workspace ------------------------------------------
    Alpine.data('brandCampaign', (id, initial = {}) => ({
        id,
        campaign: null,
        deals: [],
        loading: true,
        acting: false,
        types: initial.types || [],
        editing: false,
        saving: false,
        saved: false,
        confirmingDelete: false,
        deleting: false,
        errors: {},
        form: {},
        t,

        async init() { await this.refresh(); },

        // Editing is only offered while the campaign is still mutable.
        get editable() { return this.campaign && !['completed', 'cancelled'].includes(this.campaign.status); },

        // Ordered lifecycle stage index (−1 for cancelled) — drives the stepper.
        get statusIndex() { return ['draft', 'open', 'in_progress', 'completed'].indexOf(this.campaign?.status); },

        // Sum of positions across all roles (falls back to role count).
        get totalPositions() {
            const roles = this.campaign?.roles || [];
            return roles.reduce((sum, r) => sum + (Number(r.quantity) || 0), 0);
        },

        async refresh() {
            this.loading = true;
            try {
                const { data } = await get(`/brand/campaigns/${this.id}/data`);
                this.campaign = data.campaign;
                this.deals = data.deals;
            } finally {
                this.loading = false;
            }
        },

        // Seed the edit form from the loaded campaign and reveal it.
        startEdit() {
            const c = this.campaign;
            this.form = {
                title: c.title || '',
                type: c.type || 'campaign',
                budget_min: c.budget_min,
                budget_max: c.budget_max,
                currency: c.currency || 'EGP',
                location_city: c.location_city || '',
                location_country: c.location_country || '',
                start_date: c.start_date || '',
                end_date: c.end_date || '',
                is_public: !!c.is_public,
                roles: (c.roles || []).map((r) => ({ talent_type_id: r.talent_type_id, quantity: r.quantity })),
            };
            this.errors = {};
            this.editing = true;
        },

        cancelEdit() { this.editing = false; this.errors = {}; },

        addRole() { this.form.roles.push({ talent_type_id: this.types[0]?.id, quantity: 1 }); },
        removeRole(i) { this.form.roles.splice(i, 1); },

        async save() {
            this.saving = true;
            this.errors = {};
            try {
                await patch(`/brand/campaigns/${this.id}`, this.form);
                this.editing = false;
                this.saved = true;
                setTimeout(() => (this.saved = false), 2000);
                await this.refresh();
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally {
                this.saving = false;
            }
        },

        async transition(action) {
            this.acting = true;
            try {
                await patch(`/brand/campaigns/${this.id}/status`, { action });
                await this.refresh();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            } finally {
                this.acting = false;
            }
        },

        async togglePublic() {
            await patch(`/brand/campaigns/${this.id}/public`, { public: !this.campaign.is_public });
            await this.refresh();
        },

        async uploadMedia(event) {
            const file = event.target.files[0];
            if (!file) return;
            const body = new FormData();
            body.append('file', file);
            try {
                await post(`/brand/campaigns/${this.id}/media`, body);
                await this.refresh();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            } finally {
                event.target.value = '';
            }
        },

        async removeMedia(mediaId) {
            try {
                await del(`/brand/campaigns/${this.id}/media/${mediaId}`);
                await this.refresh();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
        },

        // Soft-delete the campaign, then return to the list.
        async destroy() {
            this.deleting = true;
            try {
                await del(`/brand/campaigns/${this.id}`);
                window.location.href = '/brand/campaigns';
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
                this.deleting = false;
            }
        },
    }));

    // --- Discovery feed -----------------------------------------------------
    Alpine.data('brandDiscover', () => ({
        talents: [],
        meta: null,
        loading: true,
        page: 1,
        saved: {},
        t,

        async init() { await this.load(); },

        async load(page = 1) {
            this.loading = true;
            try {
                const { data, meta } = await get(`/brand/discover/feed?page=${page}`);
                this.talents = page === 1 ? data : [...this.talents, ...data];
                this.meta = meta;
                this.page = page;
            } finally {
                this.loading = false;
            }
        },

        get hasMore() { return this.meta && this.page < this.meta.last_page; },
        more() { if (this.hasMore) this.load(this.page + 1); },

        async save(id) {
            await post('/brand/discover/save', { talent_id: id });
            this.saved = { ...this.saved, [id]: true };
        },
        async brief(id) {
            await post('/brand/discover/brief', { talent_id: id });
            window.alert('Brief signal recorded.');
        },
    }));

    // --- Reviews received ---------------------------------------------------
    Alpine.data('brandReviews', () => ({
        reviews: [],
        meta: null,
        loading: true,

        async init() {
            try {
                const { data, meta } = await get('/brand/reviews/data');
                this.reviews = data;
                this.meta = meta;
            } finally {
                this.loading = false;
            }
        },
    }));

    // --- Account / settings -------------------------------------------------
    Alpine.data('brandAccount', (initial) => ({
        form: initial.form,
        published: initial.published,
        saving: false,
        saved: false,
        publishing: false,
        errors: {},

        async save() {
            this.errors = {};
            this.saving = true;
            this.saved = false;
            try {
                const { data } = await patch('/brand/account', this.form);
                if (data.slug) this.form.slug = data.slug;
                this.saved = true;
                setTimeout(() => (this.saved = false), 2000);
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally {
                this.saving = false;
            }
        },

        async togglePublish() {
            this.publishing = true;
            try {
                const { data } = await patch('/brand/account/publish', { publish: !this.published });
                this.published = data.is_published;
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            } finally {
                this.publishing = false;
            }
        },
    }));

    // --- Deals inbox (brand) ------------------------------------------------
    Alpine.data('brandDealsInbox', () => ({
        deals: [],
        meta: null,
        loading: true,
        status: '',
        page: 1,

        async init() {
            await this.load();
            // Live inbox: refresh unread badges + statuses every 20s (skip when hidden).
            this._pollTimer = setInterval(() => this.poll(), 20000);
        },

        destroy() { if (this._pollTimer) clearInterval(this._pollTimer); },

        async load(page = 1) {
            this.loading = true;
            try {
                this.deals = await this.fetch(page);
            } finally {
                this.loading = false;
            }
        },

        // Quiet background refresh — keeps the current filter/page, no loading flag.
        async poll() {
            if (document.hidden) return;
            try {
                this.deals = await this.fetch(this.page);
            } catch (e) { /* ignore transient poll errors */ }
        },

        async fetch(page) {
            const params = new URLSearchParams();
            if (this.status) params.set('status', this.status);
            params.set('page', page);
            const { data, meta } = await get(`/brand/deals/data?${params.toString()}`);
            this.meta = meta;
            this.page = page;
            return data;
        },

        setStatus(status) { this.status = status; this.load(); },
    }));

    // --- Deal room (brand actor) --------------------------------------------
    Alpine.data('brandDealRoom', (dealId, labels = {}) => ({
        dealId,
        labels,
        deal: null,
        steps: [],
        messages: [],
        canAct: false,
        loading: true,
        acting: false,
        sending: false,
        messageBody: '',
        form: {},
        errors: {},

        async init() {
            await this.refresh();
            // Live thread: poll for new messages / step changes every 20s (skip when
            // the tab is hidden). Cleared on teardown.
            this._pollTimer = setInterval(() => this.poll(), 20000);
        },

        destroy() {
            if (this._pollTimer) clearInterval(this._pollTimer);
        },

        // Quiet background refresh — no loading flag; only resets the action form when
        // the active step actually changed (so it never wipes what the user is typing).
        async poll() {
            if (document.hidden) return;
            try {
                const { data } = await get(`/brand/deals/${this.dealId}/thread`);
                const prevStepId = this.currentStep?.id ?? null;
                this.deal = data.deal;
                this.steps = data.steps;
                this.messages = data.messages;
                this.canAct = data.can_act;
                if ((this.currentStep?.id ?? null) !== prevStepId) this.resetForm();
            } catch (e) {
                /* ignore transient poll errors */
            }
        },

        // Key/value detail rows for the "Deal details" card: the brief fields the
        // brand submitted, plus the agreed dates + who initiated it.
        get detailRows() {
            const rows = [];
            const brief = this.deal?.brief;
            if (typeof brief === 'string' && brief.trim()) {
                rows.push({ label: this.labels.brief || 'Brief', value: brief, wide: true });
            } else if (brief && typeof brief === 'object') {
                const fields = brief.fields ?? brief;
                Object.entries(fields).forEach(([k, v]) => {
                    if (v !== null && v !== '' && typeof v !== 'object') {
                        rows.push({ label: this.humanize(k), value: String(v) });
                    }
                });
            }
            if (this.deal?.start_date) rows.push({ label: this.labels.startDate || 'Start date', value: this.deal.start_date });
            if (this.deal?.end_date) rows.push({ label: this.labels.endDate || 'End date', value: this.deal.end_date });
            if (this.deal?.initiated_by) rows.push({ label: this.labels.initiatedBy || 'Initiated by', value: this.deal.initiated_by });
            return rows;
        },

        humanize(key) {
            return String(key).replaceAll('_', ' ').replace(/^\w/, (c) => c.toUpperCase());
        },

        async refresh() {
            this.loading = true;
            try {
                const { data } = await get(`/brand/deals/${this.dealId}/thread`);
                this.deal = data.deal;
                this.steps = data.steps;
                this.messages = data.messages;
                this.canAct = data.can_act;
                this.resetForm();
            } finally {
                this.loading = false;
            }
        },

        get currentStep() { return this.steps.find((s) => s.is_current) || null; },

        resetForm() {
            const step = this.currentStep;
            this.errors = {};
            this.form = {};
            if (!step) return;
            if (step.step_type === 'form') {
                this.form.fields = {};
                (step.fields || []).forEach((f) => { this.form.fields[f] = ''; });
            }
        },

        async advance(payload) {
            this.acting = true;
            this.errors = {};
            try {
                await post(`/brand/deals/${this.dealId}/advance`, payload);
                await this.refresh();
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally {
                this.acting = false;
            }
        },

        submitForm() { this.advance({ fields: this.form.fields || {} }); },
        approve() { this.advance({ note: this.form.note || '' }); },
        sign() { this.advance({ signed: true, signatory: this.form.signatory || '' }); },
        pay() { this.advance({ confirmed: true }); },
        acknowledge() { this.advance({}); },
        schedule() { this.advance({ start_date: this.form.start_date, end_date: this.form.end_date || null }); },
        deliver() {
            const list = (this.form.attachments || '').split('\n').map((s) => s.trim()).filter(Boolean);
            this.advance({ attachments: list });
        },
        sendStepMessage() { this.advance({ body: this.form.body || '' }); },

        async reject() {
            this.acting = true;
            try {
                await post(`/brand/deals/${this.dealId}/reject`, { reason: this.form.note || '' });
                await this.refresh();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            } finally {
                this.acting = false;
            }
        },

        async skip() {
            this.acting = true;
            try {
                await post(`/brand/deals/${this.dealId}/skip`, {});
                await this.refresh();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            } finally {
                this.acting = false;
            }
        },

        async sendMessage() {
            if (!this.messageBody.trim()) return;
            this.sending = true;
            try {
                await post(`/brand/deals/${this.dealId}/message`, { body: this.messageBody });
                this.messageBody = '';
                await this.refresh();
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            } finally {
                this.sending = false;
            }
        },
    }));
});
