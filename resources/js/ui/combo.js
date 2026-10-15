/**
 * famaCombo — a searchable drop list for the fama-ui pages (Country with
 * flags, City per country). The value lives in the PARENT: `get`/`set` read and
 * write it directly (x-modelable's entangle missed updates when two combos'
 * values changed in the same tick, e.g. Discard restoring country + city).
 *
 *   get / set    () => value, (v) => …  — the bound model
 *   items        () => [{ value, label, code? }]  — read live from the parent scope
 *   flags        show a flag for items carrying an ISO `code`
 *   allowCustom  offer "Use “typed text”" when nothing matches (City: small towns)
 *   max          how many matches render at once (search narrows the rest)
 *
 * A user pick dispatches `combo-pick` (detail = the item) from the root, so a
 * parent can react to real choices without watching programmatic resets.
 */
document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    /** Lowercase, accents stripped: "Aswān" matches "aswan". */
    const norm = (s) => String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();

    Alpine.data('famaCombo', ({ get = () => '', set = () => {}, items = () => [], flags = false, allowCustom = false, max = 200, flagBase = '/fama-ui/img/flags/' } = {}) => ({
        open: false,
        q: '',
        active: 0,
        flags,
        allowCustom,

        /** The bound value (lives in the parent). */
        get value() { return get() ?? ''; },
        set value(v) { set(v); },
        /** All items, as given by the parent. */
        get all() { return items() || []; },
        /** The item matching the current value (null for a free-typed or legacy value). */
        get current() { return this.all.find((o) => o.value === this.value) || null; },
        /** Matches for the search: prefix matches first, then anywhere; capped at `max`. */
        get filtered() {
            const q = norm(this.q);
            if (!q) return this.all.slice(0, max);
            const starts = [];
            const has = [];
            for (const o of this.all) {
                const l = norm(o.label);
                if (l.startsWith(q)) starts.push(o);
                else if (l.includes(q)) has.push(o);
                if (starts.length >= max) break;
            }
            return starts.concat(has).slice(0, max);
        },
        /** The "Use “…”" row: typed text that is not exactly an item. */
        get customText() {
            const t = this.q.trim();
            if (!this.allowCustom || !t) return '';
            return this.all.some((o) => norm(o.label) === norm(t)) ? '' : t;
        },
        /** Rows the keyboard walks: matches, then the custom row. */
        get rowCount() { return this.filtered.length + (this.customText ? 1 : 0); },

        flagSrc(code) { return code ? `${flagBase}${String(code).toLowerCase()}.svg` : ''; },

        toggle() { this.open ? this.close() : this.show(); },
        /** Open with the search focused and the current item in view. */
        show() {
            this.q = '';
            this.open = true;
            this.active = Math.max(0, this.filtered.findIndex((o) => o.value === this.value));
            this.$nextTick(() => {
                this.$refs.q?.focus();
                this.scrollActive();
            });
        },
        /** Close; `refocus` puts focus back on the button (keyboard flows). */
        close(refocus = false) {
            if (!this.open) return;
            this.open = false;
            if (refocus) this.$refs.btn?.focus();
        },
        /** Choose an item (or free text) and tell the parent. */
        pick(item) {
            this.value = item.value;
            this.close(true);
            this.$root.dispatchEvent(new CustomEvent('combo-pick', { detail: item, bubbles: true }));
        },
        pickCustom() {
            const t = this.customText;
            if (t) this.pick({ value: t, label: t });
        },
        /** Arrow keys / Enter / Escape inside the search box. */
        onKey(e) {
            const n = this.rowCount;
            if (e.key === 'ArrowDown') { e.preventDefault(); this.active = n ? (this.active + 1) % n : 0; this.scrollActive(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); this.active = n ? (this.active - 1 + n) % n : 0; this.scrollActive(); }
            else if (e.key === 'Enter') {
                e.preventDefault();
                if (this.active < this.filtered.length) this.pick(this.filtered[this.active]);
                else this.pickCustom();
            } else if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); this.close(true); }
            else if (e.key === 'Tab') this.close();
        },
        /** Keep the highlighted row visible while walking the list. */
        scrollActive() {
            this.$nextTick(() => this.$refs.list?.querySelector('[data-active="true"]')?.scrollIntoView({ block: 'nearest' }));
        },
    }));
});
