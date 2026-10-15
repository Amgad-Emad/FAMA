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
        // --- Bulk: select rows, then delete / export (approve / reject on reviews) ---
        get canBulk() { return ['talents', 'brands', 'reviews', 'recognitions'].includes(this.tab); },
        get allSelected() { return this.rows.length > 0 && this.rows.every((r) => this.selected.includes(r.id)); },
        toggleAll() {
            this.selected = this.allSelected ? [] : this.rows.map((r) => r.id);
        },
        /** CSV download: the selection, or everything matching the current filters. */
        exportUrl(selectedOnly) {
            const params = new URLSearchParams();
            if (selectedOnly && this.selected.length) params.set('ids', this.selected.join(','));
            if (!selectedOnly) {
                if (this.q) params.set('q', this.q);
                if (this.statusFilter) params.set('status', this.statusFilter);
            }
            const path = `/admin/moderation/export/${this.tab}?${params.toString()}`;
            return window.fama?.localizeUrl ? window.fama.localizeUrl(path) : path;
        },
        async bulkDelete() {
            const n = this.selected.length;
            if (!n) return;
            const ok = await this.$store.confirm.ask({
                title: n === 1 ? this.$root.dataset.delOne : this.$root.dataset.delMany.replace(':n', n),
                message: this.$root.dataset.delBody,
                confirmLabel: this.$root.dataset.delOk,
            });
            if (!ok) return;
            try {
                await post(`/admin/moderation/bulk/${this.tab}/delete`, { ids: this.selected });
                this.selected = [];
                await this.load(this.meta?.pagination?.current_page ?? 1);
            } catch (e) {
                if (e instanceof ApiError) window.alert(e.message);
            }
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

        // --- Profile preview panel (talents) -------------------------------
        // View opens the talent's PUBLIC profile in a wide side panel, exactly as
        // a visitor sees it (staff may see private profiles: ?staff_preview=1),
        // with Verify / Suspend in its header. Mounted, then slid in a tick later
        // (no x-transition on a teleported node — see CLAUDE.md).
        preview: { row: null, shown: false, loaded: false },
        openPreview(row) {
            this.preview = { row, shown: false, loaded: false };
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => requestAnimationFrame(() => { this.preview.shown = true; }));
            setTimeout(() => { if (this.preview.row) this.preview.shown = true; }, 60);
        },
        closePreview() {
            if (!this.preview.row) return;
            this.preview.shown = false;
            document.body.style.overflow = '';
            setTimeout(() => { if (!this.preview.shown) this.preview.row = null; }, 280);
        },
        previewUrl(row) {
            return row?.slug ? `${window.fama?.localizeUrl ? window.fama.localizeUrl('/' + row.slug) : '/' + row.slug}?staff_preview=1` : '';
        },

        // --- Verify / Suspend email composer -------------------------------
        // The decision opens a modal with the email the account will get: the
        // server suggests the copy and renders the REAL email view as the
        // preview (iframe srcdoc), re-rendered as the admin edits. "Send email"
        // can be unticked to take the decision silently.
        notice: { open: false, row: null, kind: '', type: '', scheme: 'light', send: true, email: '', subject: '', heading: '', message: '', cta_label: null, cta_url: null, html: '', loading: false, sending: false, errors: {} },
        noticeTimer: null,
        async openNotice(row, kind, type) {
            this.notice = { ...this.notice, open: true, row, kind, type, send: true, html: '', loading: true, sending: false, errors: {} };
            try {
                const { data } = await post(`/admin/moderation/${kind}/${row.id}/notice/${type}`, {});
                Object.assign(this.notice, data.defaults, { html: data.html });
            } catch (e) {
                this.notice.errors = { _: [e.message || 'Could not load the email.'] };
            } finally {
                this.notice.loading = false;
            }
        },
        closeNotice() { if (!this.notice.sending) this.notice.open = false; },
        /**
         * The preview document. The email carries a dark theme for dark-mode
         * mail apps (`@media (prefers-color-scheme: dark)`), which an iframe
         * follows from the OS — so the preview pins it: Light switches that
         * block off, "Dark mail app" switches it on.
         */
        get noticeDoc() {
            const query = /@media\s*\(prefers-color-scheme:\s*dark\)/g;
            return (this.notice.html || '').replace(query, this.notice.scheme === 'dark' ? '@media all' : '@media not all');
        },
        /** Re-render the preview a moment after the admin stops typing. */
        noticeEdited() {
            clearTimeout(this.noticeTimer);
            this.noticeTimer = setTimeout(async () => {
                const n = this.notice;
                try {
                    const { data } = await post(`/admin/moderation/${n.kind}/${n.row.id}/notice/${n.type}`, { subject: n.subject, heading: n.heading, message: n.message });
                    if (this.notice.open) this.notice.html = data.html;
                } catch (e) { /* keep the last good preview */ }
            }, 350);
        },
        /** Take the decision, with the edited email when "Send email" is ticked. */
        async submitNotice() {
            const n = this.notice;
            if (n.sending) return;
            n.sending = true;
            n.errors = {};
            const notify = n.send
                ? { send: true, email: n.email, subject: n.subject, heading: n.heading, message: n.message, cta_label: n.cta_label, cta_url: n.cta_url }
                : { send: false };
            try {
                await patch(`/admin/moderation/${n.kind}/${n.row.id}/${n.type}`, { notify, reason: n.type === 'suspend' && n.send ? n.message : undefined });
                n.open = false;
                this.closeDetail();
                this.closePreview();
                await this.load();
            } catch (e) {
                if (e instanceof ApiError && e.status === 422) n.errors = e.errors || {};
                else n.errors = { _: [e.message || 'Something went wrong.'] };
            } finally {
                n.sending = false;
            }
        },
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

    // --- Dashboard insights ----------------------------------------------
    // KPI tiles (totals + change vs the previous period + sparklines) and one
    // large column chart for the selected metric, over 7d / 30d / 90d / 12m.
    // The first range is server-rendered; others are fetched once and cached.
    // Bars grow in (CSS height transition) on load, range and metric switch.
    Alpine.data('adminInsights', ({ initial, strings = {} }) => ({
        S: strings,
        data: initial,
        range: initial.range,
        metric: 'signups',
        chartMetrics: ['signups', 'verified', 'posts', 'views'],
        colors: { signups: '#14110F', verified: '#AD50D3', posts: '#8A2FA8', views: '#1B6B52' },
        cache: { [initial.range]: initial },
        loading: false,
        error: false,
        hover: null,
        grown: false,

        init() {
            this.grow();
            this.$watch('metric', () => this.grow());
        },
        /** Replay the grow-in (rAF + timeout fallback for hidden tabs). */
        grow() {
            this.grown = false;
            this.$nextTick(() => {
                let done = false;
                const go = () => { if (!done) { done = true; this.grown = true; } };
                requestAnimationFrame(() => requestAnimationFrame(go));
                setTimeout(go, 80);
            });
        },
        async load(range) {
            if (range === this.range) return;
            this.range = range;
            this.error = false;
            if (this.cache[range]) { this.data = this.cache[range]; this.grow(); return; }
            this.loading = true;
            try {
                const { data } = await get(`/admin/dashboard/insights?range=${range}`);
                this.cache[range] = data;
                if (this.range === range) { this.data = data; this.grow(); }
            } catch (e) {
                this.error = true;
            } finally {
                this.loading = false;
            }
        },
        get rangeNote() {
            const l = this.data.labels;
            return l.length ? `${l[0]} – ${l[l.length - 1]}` : '';
        },
        fmt(n) { return Number(n || 0).toLocaleString('en-GB', { maximumFractionDigits: 1 }); },
        deltaText(t) { return t.delta_pct === null ? (t.value ? this.S.noPrev : '–') : `${t.delta_pct > 0 ? '▲' : (t.delta_pct < 0 ? '▼' : '')} ${Math.abs(t.delta_pct)}%`; },
        deltaClass(p) { return p === null ? 'bg-elevated text-muted' : (p >= 0 ? 'bg-success-weak text-success' : 'bg-danger-weak text-danger'); },
        /** A nice axis step so 3 steps reach the series max. */
        get step() {
            const max = Math.max(0, ...this.data.series[this.metric]);
            const nice = [1, 2, 5, 10, 20, 25, 50, 100, 200, 250, 500, 1000, 2000, 2500, 5000, 10000, 25000, 50000];
            return nice.find((s) => s * 3 >= max) || Math.ceil(max / 3);
        },
        get yTicks() { return [3, 2, 1, 0].map((i) => i * this.step); },
        pct(v) { return (v / (this.step * 3)) * 100; },
        /** Stagger: the sweep runs left to right, capped at ~400ms. */
        delay(i) { const n = this.data.series[this.metric].length; return n > 1 ? Math.round((i / (n - 1)) * 400) : 0; },
        get xPicks() {
            const n = this.data.labels.length;
            if (n <= 7) return [...Array(n).keys()];
            return [...new Set([0, Math.round(n / 4), Math.round(n / 2), Math.round((3 * n) / 4), n - 1])];
        },
        /** Sparkline path (0–100 × 0–32 box). */
        sparkPoints(series) {
            const max = Math.max(1, ...series);
            const n = series.length;
            return series.map((v, i) => [n > 1 ? (i / (n - 1)) * 100 : 50, 30 - (v / max) * 26]);
        },
        sparkLine(series) { return this.sparkPoints(series).map(([x, y], i) => `${i ? 'L' : 'M'}${x.toFixed(2)} ${y.toFixed(2)}`).join(' '); },
        sparkArea(series) { const p = this.sparkPoints(series); return p.length ? `${this.sparkLine(series)} L100 32 L0 32 Z` : ''; },
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
        editorOpen: false,
        search: '',
        filter: 'all',

        async init() { await this.load(); },
        /** Cards after the search + filter chips (client-side over the loaded page). */
        get shown() {
            const q = this.search.trim().toLowerCase();
            return this.rows.filter((r) => {
                if (this.filter === 'universal' && r.availability !== 'universal') return false;
                if (this.filter === 'scoped' && r.availability === 'universal') return false;
                if (this.filter === 'inactive' && r.is_active) return false;
                return !q || this.blockName(r).toLowerCase().includes(q) || r.key.includes(q);
            });
        },
        /** A small icon per block key (Lucide-style paths). */
        icon(key) {
            const icons = {
                gallery: '<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>',
                brand_collabs: '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/>',
                reviews: '<path d="M11.5 2.8a.5.5 0 0 1 1 0l2.3 4.7 5.2.8a.5.5 0 0 1 .3.9l-3.8 3.7.9 5.2a.5.5 0 0 1-.7.5L12 16.2l-4.7 2.4a.5.5 0 0 1-.7-.5l.9-5.2-3.8-3.7a.5.5 0 0 1 .3-.9l5.2-.8z"/>',
                recognitions: '<circle cx="12" cy="8" r="6"/><path d="M15.5 12.9 17 22l-5-3-5 3 1.5-9.1"/>',
                interests: '<path d="m12 3 1.9 5.8H20l-4.9 3.6 1.9 5.8L12 14.6l-5 3.6 1.9-5.8L4 8.8h6.1z"/>',
                comp_card: '<rect width="16" height="20" x="4" y="2" rx="2"/><circle cx="12" cy="9" r="3"/><path d="M8 17h8"/>',
                look_types: '<path d="M12 3a4 4 0 0 0-4 4c0 2.2 1.8 4 4 4s4-1.8 4-4a4 4 0 0 0-4-4ZM4 21a8 8 0 0 1 16 0"/>',
                digitals: '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>',
                showreel: '<rect width="18" height="14" x="3" y="5" rx="2"/><path d="m10 9 5 3-5 3z"/>',
                equipment: '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-6.9 6.9a2.1 2.1 0 0 1-3-3l6.9-6.9a6 6 0 0 1 7.9-7.9z"/>',
                projects: '<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.7-.9L9.6 3.9A2 2 0 0 0 7.9 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>',
                software_stack: '<rect width="20" height="14" x="2" y="3" rx="2"/><path d="M8 21h8M12 17v4"/>',
                hero: '<rect width="18" height="12" x="3" y="6" rx="2"/><path d="M7 14h6"/>',
            };
            return icons[key] || '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/>';
        },
        openNew() { this.form = emptyBlockForm(); this.errors = {}; this.editorOpen = true; },
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
            if (row.availability === 'by_category') return row.categories.map(c => this.catLabel(c)).join(', ');
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
            this.editorOpen = true;
        },
        reset() { this.form = emptyBlockForm(); this.errors = {}; this.editorOpen = false; },
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
    // Activity log: a day-grouped timeline with readable sentences, category
    // chips, relative times and an expandable old → new diff.
    Alpine.data('adminActivity', ({ logs = {}, verbs = {}, today = 'Today', yesterday = 'Yesterday' } = {}) => ({
        rows: [],
        meta: null,
        loading: true,
        q: '',
        log: '',
        range: '',
        logs,
        verbs,

        async init() { await this.load(); },
        async load(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.q) params.set('q', this.q);
                if (this.log) params.set('log', this.log);
                if (this.range) params.set('range', this.range);
                params.set('page', page);
                const { data, meta } = await get(`/admin/activity/data?${params.toString()}`);
                this.rows = data;
                this.meta = meta;
            } finally { this.loading = false; }
        },
        /** Rows grouped under Today / Yesterday / the date. */
        get groups() {
            const out = [];
            const day = (d) => new Date(d).toDateString();
            const t = new Date();
            const y = new Date(Date.now() - 864e5);
            for (const row of this.rows) {
                const key = day(row.created_at);
                let g = out.find((x) => x.key === key);
                if (!g) {
                    const label = key === t.toDateString() ? today : (key === y.toDateString() ? yesterday
                        : new Date(row.created_at).toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
                    g = { key, label, rows: [] };
                    out.push(g);
                }
                g.rows.push(row);
            }
            return out;
        },
        sentence(row) {
            return this.verbs[row.description] || row.description.replace(/[._]/g, ' ');
        },
        initials(name) {
            return String(name).split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('');
        },
        /** Category colours: the chip and the avatar share a tone. */
        tone(log) {
            const map = {
                moderation: ['bg-accent-weak text-accent-ink', 'bg-accent-weak text-accent-ink'],
                feed_moderation: ['bg-warn-weak text-warn', 'bg-warn-weak text-warn'],
                settings: ['bg-gold-weak text-gold', 'bg-gold-weak text-gold'],
                admin_users: ['bg-success-weak text-success', 'bg-success-weak text-success'],
            };
            const [chip, avatar] = map[log] || ['bg-elevated text-muted', 'bg-elevated text-ink'];
            return { chip, avatar };
        },
        ago(iso) {
            const s = Math.round((Date.now() - new Date(iso).getTime()) / 1000);
            if (s < 60) return 'just now';
            if (s < 3600) return `${Math.floor(s / 60)} min ago`;
            if (s < 86400) return `${Math.floor(s / 3600)} h ago`;
            return new Date(iso).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        },
        reason(row) { return row.properties?.reason || ''; },
        /** Attribute diffs (old → new) plus any other properties. */
        changeRows(row) {
            const out = [];
            const attrs = row.changes?.attributes || {};
            const old = row.changes?.old || {};
            for (const key of Object.keys(attrs)) out.push({ key, old: key in old ? old[key] : undefined, new: attrs[key] });
            for (const [key, value] of Object.entries(row.properties || {})) {
                if (key === 'attributes' || key === 'old') continue;
                out.push({ key, old: undefined, new: value });
            }
            return out;
        },
        hasChanges(row) { return this.changeRows(row).length > 0; },
        show(v) {
            if (v === null || v === undefined || v === '') return '—';
            if (typeof v === 'object') return JSON.stringify(v);
            return String(v);
        },
    }));

    // --- Posts module (feed moderation) --------------------------------------
    Alpine.data('adminPosts', ({ queue = 'posts', base = '', reasons = {} } = {}) => ({
        tab: queue,
        base,
        reasons,
        rows: [],
        pagination: null,
        loading: false,
        page: 1,
        status: '',
        state: '',
        q: '',
        selected: [],
        notice: '',
        reason: { open: false, row: null, kind: 'post', text: '', send: true, email: '', subject: '', message: '', html: '', scheme: 'light', loading: false, sending: false, errors: {} },
        reasonTimer: null,

        init() { this.load(); },
        openTab(tab) {
            this.tab = tab;
            this.page = 1;
            this.status = ''; this.state = ''; this.q = ''; this.selected = [];
            const url = new URL(window.location);
            url.searchParams.set('queue', tab);
            window.history.replaceState(null, '', url);
            this.load();
        },
        get kind() { return this.tab === 'comments' ? 'comment' : 'post'; },
        async load() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ page: this.page });
                if (this.tab === 'reports') params.set('status', this.status === '' ? 'open' : (this.status === 'all' ? '' : this.status));
                if (this.tab !== 'reports' && this.state) params.set('state', this.state);
                if (this.q) params.set('q', this.q);
                const { data, meta } = await get(`${this.base}/${this.tab}?${params.toString()}`);
                this.rows = data;
                this.pagination = meta?.pagination ?? null;
            } finally { this.loading = false; }
        },
        initials(name) { return String(name || '?').split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join(''); },
        stateLabel(row) { return row.deleted ? 'Deleted' : this.$statusLabel(row.state); },
        statePill(row) { return row.deleted ? 'rounded-pill bg-danger-weak px-2 py-0.5 text-xs font-semibold text-danger' : this.$pill(row.state); },
        toggle(id) { const i = this.selected.indexOf(id); i >= 0 ? this.selected.splice(i, 1) : this.selected.push(id); },
        get allSelected() { return this.rows.length > 0 && this.rows.every((r) => this.selected.includes(r.id)); },
        toggleAll() { this.selected = this.allSelected ? [] : this.rows.map((r) => r.id); },
        /** One action on one row (`row.kind` set for report targets). */
        async act(row, action, reason = '', notify = null) {
            const kind = row.kind || this.kind;
            if (action === 'delete') {
                const ok = await this.$store.confirm.ask({ title: this.$root.dataset.delTitle.replace(':n', kind), message: this.$root.dataset.delBody, confirmLabel: 'Delete' });
                if (!ok) return;
            }
            try {
                await patch(`${this.base}/${kind}s/${row.id}/${action}`, notify ? { reason, notify } : { reason });
                await this.load();
                return true;
            } catch (e) {
                if (notify && e instanceof ApiError && e.status === 422) throw e;
                if (e instanceof ApiError) window.alert(e.message);
                return false;
            }
        },
        /** Open the remove composer: suggested email + preview of the real view. */
        async openReason(row) {
            const kind = row.kind || this.kind;
            this.reason = { ...this.reason, open: true, row, kind, text: '', send: true, html: '', loading: true, sending: false, errors: {} };
            try {
                const { data } = await post(`${this.base}/${kind}s/${row.id}/notice`, {});
                Object.assign(this.reason, data.defaults, { html: data.html });
            } catch (e) {
                this.reason.errors = { _: [e.message || 'Could not load the email.'] };
            } finally { this.reason.loading = false; }
        },
        noticeEdited() {
            clearTimeout(this.reasonTimer);
            this.reasonTimer = setTimeout(async () => {
                const r = this.reason;
                try {
                    const { data } = await post(`${this.base}/${r.kind}s/${r.row.id}/notice`, { subject: r.subject, message: r.message, reason: r.text });
                    if (this.reason.open) this.reason.html = data.html;
                } catch (e) { /* keep the last preview */ }
            }, 350);
        },
        /** Pin the preview to the light email; the toggle shows the dark-mail-app version. */
        get reasonDoc() {
            return (this.reason.html || '').replace(/@media\s*\(prefers-color-scheme:\s*dark\)/g, this.reason.scheme === 'dark' ? '@media all' : '@media not all');
        },
        async submitReason() {
            const r = this.reason;
            r.sending = true;
            r.errors = {};
            const notify = r.send ? { send: true, email: r.email, subject: r.subject, message: r.message } : { send: false };
            try {
                await this.act({ id: r.row.id, kind: r.kind }, 'remove', r.text.trim(), notify);
                r.open = false;
            } catch (e) {
                r.errors = e.errors || { _: [e.message] };
            } finally { r.sending = false; }
        },
        async resolve(row, resolution) {
            await patch(`${this.base}/reports/${row.id}/${resolution}`, {});
            await this.load();
        },
        async bulk(action) {
            const n = this.selected.length;
            if (!n) return;
            if (action === 'delete') {
                const ok = await this.$store.confirm.ask({ title: this.$root.dataset.delTitle.replace(':n', n + ' ' + this.kind + (n > 1 ? 's' : '')), message: this.$root.dataset.delBody, confirmLabel: 'Delete' });
                if (!ok) return;
            }
            try {
                const { data } = await post(`${this.base}/bulk`, { kind: this.kind, action, ids: this.selected });
                this.selected = [];
                this.notice = data.skipped ? `${data.done} done · ${data.skipped} skipped` : `${data.done} done`;
                setTimeout(() => { this.notice = ''; }, 3500);
                await this.load();
            } catch (e) { if (e instanceof ApiError) window.alert(e.message); }
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
