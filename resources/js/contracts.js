/**
 * Contract-room + inbox Alpine components (talent side). Everything goes through the
 * shared http.js wrapper (JSON envelope); the room is turn-aware and renders the
 * action panel by the current step's step_type.
 */
import { get, post, ApiError } from './http';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // --- Contracts inbox --------------------------------------------------------
    Alpine.data('contractsInbox', () => ({
        contracts: [],
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
                this.contracts = await this.fetch(page);
            } finally {
                this.loading = false;
            }
        },

        // Quiet background refresh — keeps the current filter/page, no loading flag.
        async poll() {
            if (document.hidden) return;
            try {
                this.contracts = await this.fetch(this.page);
            } catch (e) { /* ignore transient poll errors */ }
        },

        async fetch(page) {
            const params = new URLSearchParams();
            if (this.status) params.set('status', this.status);
            params.set('page', page);
            const { data, meta } = await get(`/talent/contracts/data?${params.toString()}`);
            this.meta = meta;
            this.page = page;
            return data;
        },

        setStatus(status) {
            this.status = status;
            this.load();
        },
    }));

    // --- Contract room ----------------------------------------------------------
    Alpine.data('contractRoom', (contractId) => ({
        contractId,
        contract: null,
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

        async refresh() {
            this.loading = true;
            try {
                const { data } = await get(`/talent/contracts/${this.contractId}/thread`);
                this.contract = data.contract;
                this.steps = data.steps;
                this.messages = data.messages;
                this.canAct = data.can_act;
                this.resetForm();
            } finally {
                this.loading = false;
            }
        },

        // Quiet background refresh — no loading flag, and it only resets the action
        // form when the active step actually changed (so it never wipes what the
        // user is typing).
        async poll() {
            if (document.hidden) return;
            try {
                const { data } = await get(`/talent/contracts/${this.contractId}/thread`);
                const prevStepId = this.currentStep?.id ?? null;
                this.contract = data.contract;
                this.steps = data.steps;
                this.messages = data.messages;
                this.canAct = data.can_act;
                if ((this.currentStep?.id ?? null) !== prevStepId) this.resetForm();
            } catch (e) {
                /* ignore transient poll errors */
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
                await post(`/talent/contracts/${this.contractId}/advance`, payload);
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
                await post(`/talent/contracts/${this.contractId}/reject`, { reason: this.form.note || '' });
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
                await post(`/talent/contracts/${this.contractId}/skip`, {});
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
                await post(`/talent/contracts/${this.contractId}/message`, { body: this.messageBody });
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
