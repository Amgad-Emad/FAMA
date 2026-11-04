/**
 * The views card on Profile structure — port of the prototype's views.js on
 * real data. 24 Oct 2026: the card is a heading, the number and one line; the
 * chart opens on request (and only then fetches its series).
 */
import { get } from '../http';
import { fmt } from './profile-helpers';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // =====================================================================
    // Views card (Profile structure) — port of views.js on real data.
    // =====================================================================
    Alpine.data('profileViews', ({ strings = {} } = {}) => ({
        S: strings,
        range: '30d',
        series: [],
        total: 0,
        loading: false,
        error: false,
        cache: {},
        grown: false,
        snap: false,
        open: false,

        /** Open / close the chart; the first open loads the default range. */
        toggle() {
            this.open = !this.open;
            if (!this.open) return;
            if (this.cache[this.range]) this.apply(this.cache[this.range]);
            else if (!this.loading) this.load(this.range);
        },
        /** Switch range (cached per preset). */
        async load(range) {
            this.range = range;
            if (this.cache[range]) { this.apply(this.cache[range]); return; }
            this.loading = true;
            this.error = false;
            try {
                const { data } = await get(`/talent/dashboard/views?range=${range}`);
                this.cache[range] = data;
                this.apply(data);
            } catch (e) {
                this.error = true;
            } finally {
                this.loading = false;
            }
        },
        /**
         * Take a series payload and replay the grow-in: the bars snap to 0
         * height, then rise to their value on the next frame (CSS transition).
         * setTimeout backs up rAF, which never fires in a hidden tab.
         */
        apply(d) {
            // `snap` turns the height transition off so the reset to 0 is instant —
            // otherwise a replay (reopen, cached range) animates down then back up
            // and the bars never visibly rise.
            this.snap = true;
            this.grown = false;
            this.series = d.series || [];
            this.total = d.total || 0;
            this.$nextTick(() => {
                void this.$refs.area?.offsetHeight; // commit the 0 height before re-enabling the transition
                this.snap = false;
                let done = false;
                const grow = () => { if (!done) { done = true; this.grown = true; } };
                requestAnimationFrame(() => requestAnimationFrame(grow));
                setTimeout(grow, 80);
            });
        },
        /** Stagger: bars rise left to right, the whole sweep capped at ~350ms. */
        delay(i) { return this.series.length > 1 ? Math.round((i / (this.series.length - 1)) * 350) : 0; },
        /** The axis step: the smallest nice number so 3 steps reach the max. */
        get step() {
            const max = Math.max(0, ...this.series.map((x) => x.views));
            return [1, 2, 5, 10, 20, 25, 50, 100, 200, 250, 500, 1000, 2000, 2500, 5000].find((s) => s * 3 >= max) || Math.ceil(max / 3);
        },
        /** The top of the plot (3 steps). */
        get top() { return this.step * 3; },
        /** Y-axis labels, top down. */
        get yTicks() { return [3, 2, 1, 0].map((i) => (i * this.step).toLocaleString('en-GB')); },
        /** Which buckets get an x-axis label. */
        get xPicks() {
            const n = this.series.length;
            if (!n) return [];
            if (n <= 7) return this.series.map((_, i) => i);
            return [...new Set([0, Math.round(n / 4), Math.round(n / 2), Math.round((3 * n) / 4), n - 1])];
        },
        /** "1,234 views in this period". */
        get sumText() { return fmt(this.S.sum, { n: this.total.toLocaleString('en-GB') }); },
        /** Bar height in percent. */
        height(v) { return this.top ? (v / this.top) * 100 : 0; },
        /** Show the tooltip over a bar (positioned like views.js). */
        tip(e, i) {
            const x = this.series[i];
            const tip = this.$refs.tip;
            const area = this.$refs.area;
            if (!x || !tip || !area) return;
            const bt = e.currentTarget;
            const r = bt.getBoundingClientRect();
            const a = area.getBoundingClientRect();
            const s = bt.firstElementChild.getBoundingClientRect();
            tip.innerHTML = '';
            const b = document.createElement('b');
            b.textContent = fmt(this.S.nViews, { n: x.views.toLocaleString('en-GB') });
            tip.append(b, document.createTextNode(' · ' + x.label));
            const w = tip.offsetWidth / 2;
            const cx = Math.min(Math.max(r.left - a.left + r.width / 2, w), a.width - w);
            tip.style.left = cx + 'px';
            tip.style.top = (s.top - a.top) + 'px';
            tip.style.opacity = 1;
        },
        /** Hide the tooltip. */
        untip() { if (this.$refs.tip) this.$refs.tip.style.opacity = 0; },
    }));

});
