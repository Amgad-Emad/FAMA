/**
 * Deal-room + inbox Alpine components (talent side). Everything goes through the
 * shared http.js wrapper (JSON envelope); the room is turn-aware and renders the
 * action panel by the current step's step_type.
 */
import { get, post, ApiError } from './http';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // --- Deals inbox --------------------------------------------------------
    Alpine.data('dealsInbox', () => ({
        deals: [],
        meta: null,
        loading: true,
        status: '',

        async init() {
            await this.load();
        },

        async load(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.status) params.set('status', this.status);
                params.set('page', page);
                const { data, meta } = await get(`/talent/deals/data?${params.toString()}`);
                this.deals = data;
                this.meta = meta;
            } finally {
                this.loading = false;
            }
        },

        setStatus(status) {
            this.status = status;
            this.load();
        },
    }));

    // --- Deal room ----------------------------------------------------------
    Alpine.data('dealRoom', (dealId) => ({
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

        async init() {
            await this.refresh();
        },

        async refresh() {
            this.loading = true;
            try {
                const { data } = await get(`/talent/deals/${this.dealId}/thread`);
                this.deal = data.deal;
                this.steps = data.steps;
                this.messages = data.messages;
                this.canAct = data.can_act;
                this.resetForm();
            } finally {
                this.loading = false;
            }
        },

        get currentStep() {
            return this.steps.find((s) => s.is_current) || null;
        },

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
                await post(`/talent/deals/${this.dealId}/advance`, payload);
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
                await post(`/talent/deals/${this.dealId}/reject`, { reason: this.form.note || '' });
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
                await post(`/talent/deals/${this.dealId}/skip`, {});
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
                await post(`/talent/deals/${this.dealId}/message`, { body: this.messageBody });
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
