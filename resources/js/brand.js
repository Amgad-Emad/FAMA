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

    // --- Creative-needs editor ----------------------------------------------
    Alpine.data('brandCreativeNeeds', (initial) => ({
        data: initial.data,
        saving: false,
        saved: false,
        errors: {},

        toggle(list, value) { toggleIn(this.data[list], value); },

        async save() {
            this.errors = {};
            this.saving = true;
            this.saved = false;
            try {
                await patch('/brand/creative-needs', this.data);
                this.saved = true;
                setTimeout(() => (this.saved = false), 2000);
            } catch (e) {
                if (e instanceof ApiError) this.errors = e.errors || { _: [e.message] };
            } finally {
                this.saving = false;
            }
        },
    }));

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
    Alpine.data('brandCampaign', (id) => ({
        id,
        campaign: null,
        deals: [],
        loading: true,
        acting: false,
        t,

        async init() { await this.refresh(); },

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

        async init() { await this.load(); },

        async load(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.status) params.set('status', this.status);
                params.set('page', page);
                const { data, meta } = await get(`/brand/deals/data?${params.toString()}`);
                this.deals = data;
                this.meta = meta;
            } finally {
                this.loading = false;
            }
        },

        setStatus(status) { this.status = status; this.load(); },
    }));

    // --- Deal room (brand actor) --------------------------------------------
    Alpine.data('brandDealRoom', (dealId) => ({
        dealId,
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

        async init() { await this.refresh(); },

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

    // Reusable "Start a deal" modal. Opened via a window `open-start-deal` event
    // ({ talentId, talentName, campaignId? }); posts to /brand/deals and redirects
    // to the new deal room. Guard/validation errors surface inline (JSON envelope).
    Alpine.data('brandStartDeal', () => ({
        open: false,
        talentId: null,
        talentName: '',
        campaignId: null,
        brief: '',
        saving: false,
        error: '',

        onOpen(detail) {
            this.talentId = detail.talentId;
            this.talentName = detail.talentName || '';
            this.campaignId = detail.campaignId || null;
            this.brief = '';
            this.error = '';
            this.open = true;
        },

        async start() {
            if (this.saving) return;
            this.saving = true;
            this.error = '';
            try {
                const { data } = await post('/brand/deals', {
                    talent_id: this.talentId,
                    brief: this.brief || null,
                    campaign_id: this.campaignId,
                });
                window.location.href = data.redirect;
            } catch (e) {
                this.error = e instanceof ApiError ? e.message : 'Something went wrong.';
                this.saving = false;
            }
        },
    }));

    // Pending enquiries list + convert-to-deal.
    Alpine.data('brandEnquiries', () => ({
        enquiries: [],
        meta: null,
        loading: true,
        converting: null,

        async init() {
            try {
                const { data, meta } = await get('/brand/enquiries/data');
                this.enquiries = data;
                this.meta = meta;
            } finally {
                this.loading = false;
            }
        },

        async convert(id) {
            this.converting = id;
            try {
                const { data } = await post(`/brand/enquiries/${id}/convert`, {});
                window.location.href = data.redirect;
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
                this.converting = null;
            }
        },
    }));
});
