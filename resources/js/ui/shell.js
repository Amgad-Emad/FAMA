/**
 * fama-ui shell behaviour: the side bar (`famaSide`), the top bar (`famaNav`),
 * the shared notifications store (`famaNotifs`) and the global toast store (`famaToast`).
 * Port of the prototype's assets/js/shell.js + ds.js toast, on Alpine + http.js.
 */
document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    /** Toast store: show(message, undo?) — 5s, optional Undo callback. */
    Alpine.store('famaToast', {
        visible: false,
        message: '',
        undo: null,
        _t: null,

        /**
         * Show a toast; `undo` (function) adds an Undo button. It hides after 6s, but
         * stays while the pointer or keyboard is on it so Undo can be reached (30 Oct 2026).
         */
        show(message, undo = null) {
            this.message = message;
            this.undo = undo;
            this.visible = true;
            this._arm();
        },

        /** (Re)start the hide timer, deferring while the toast is hovered or focused. */
        _arm() {
            clearTimeout(this._t);
            this._t = setTimeout(() => {
                if (document.querySelector('.toast:hover, .toast:focus-within')) return this._arm();
                this.visible = false;
                this.undo = null;
            }, 6000);
        },

        /** Run the pending Undo callback and hide. */
        runUndo() {
            const fn = this.undo;
            this.visible = false;
            this.undo = null;
            if (fn) fn();
        },
    });
    window.famaToast = (m, u) => Alpine.store('famaToast').show(m, u);
    // Alt+Z presses the visible toast's Undo.
    document.addEventListener('keydown', (e) => {
        const s = Alpine.store('famaToast');
        if (e.altKey && e.code === 'KeyZ' && s.visible && s.undo) { e.preventDefault(); s.runUndo(); }
    });

    /** Notification type → sprite icon id (matches the prototype's NI map). */
    const ICONS = {
        MentionedInPost: 'fx-i-at', MentionedInComment: 'fx-i-at', Mentioned: 'fx-i-at',
        PostCommented: 'fx-i-msg', CommentReplied: 'fx-i-msg', NewComment: 'fx-i-msg',
        NewFollower: 'fx-i-uplus', PostLiked: 'fx-i-heart', CommentLiked: 'fx-i-heart',
        CreditRequested: 'fx-i-award', CreditAccepted: 'fx-i-award', CreditDeclined: 'fx-i-award',
        ContentRemoved: 'fx-i-check',
    };

    /** Compact relative time: 12m · 3h · 1d · 2w. */
    const ago = (iso) => {
        if (!iso) return '';
        const s = Math.max(0, (Date.now() - new Date(iso).getTime()) / 1000);
        if (s < 60) return 'now';
        if (s < 3600) return Math.floor(s / 60) + 'm';
        if (s < 86400) return Math.floor(s / 3600) + 'h';
        if (s < 604800) return Math.floor(s / 86400) + 'd';
        return Math.floor(s / 604800) + 'w';
    };
    window.famaAgo = ago;
    window.famaNotifIcon = (n) => {
        const t = n.type || '';
        if (ICONS[t]) return ICONS[t];
        if (/Mention/.test(t)) return 'fx-i-at';
        if (/Comment|Repl/.test(t)) return 'fx-i-msg';
        if (/Follow/.test(t)) return 'fx-i-uplus';
        if (/Like/.test(t)) return 'fx-i-heart';
        if (/Credit/.test(t)) return 'fx-i-award';
        if (/Recogni/.test(t)) return 'fx-i-star';
        return 'fx-i-check';
    };

    /** Bold lead name of a notification (the actor, else Fama). */
    window.famaNotifWho = (n) => n.actor?.name || 'Fama';
    /** The message with the actor's name stripped from its start (it is bolded separately). */
    window.famaNotifRest = (n) => {
        const who = window.famaNotifWho(n);
        const m = n.message || '';
        return m.startsWith(who) ? m.slice(who.length) : ' ' + m;
    };

    /**
     * The talent's notifications, shared by the side bar (hover popover + badge)
     * and the top bar (bell popover): the unread count loads once on boot, the
     * latest list loads the first time either popover opens.
     */
    Alpine.store('famaNotifs', {
        unread: 0,
        items: [],
        loaded: false,
        _cfg: null,

        /** Wire the endpoints (from fama-head) and paint the badge. */
        boot(cfg) {
            if (this._cfg || !cfg) return;
            this._cfg = cfg;
            window.fama.get(cfg.unread).then((r) => { this.unread = r.data?.count ?? 0; }).catch(() => {});
        },

        /** Badge text: 9+ past nine. */
        badge() { return this.unread > 9 ? '9+' : String(this.unread); },

        /** Fetch the latest notifications (once). */
        load() {
            if (this.loaded || this._loading || !this._cfg) return;
            this._loading = true;
            window.fama.get(this._cfg.list).then((r) => {
                this.items = Array.isArray(r.data) ? r.data : (r.data?.data || []);
            }).catch(() => {}).finally(() => { this.loaded = true; this._loading = false; });
        },

        /** Mark one notification read (fire-and-forget; navigation follows). */
        markRead(n) {
            if (n.read_at) return;
            n.read_at = new Date().toISOString();
            this.unread = Math.max(0, this.unread - 1);
            window.fama.post(this._cfg.read, { ids: [n.id] }).catch(() => {});
        },

        /** Mark everything read. */
        readAll() {
            this.items.forEach((n) => { n.read_at = n.read_at || new Date().toISOString(); });
            this.unread = 0;
            window.fama.post(this._cfg.read, {}).catch(() => {});
        },
    });

    const SIDE_KEY = 'fama-side-folded';

    /**
     * The wide-screen side bar (≥1101px): fold/unfold (remembered in this browser,
     * folded by default), the account + Create menus opening beside the bar lined
     * up with their button, and Notifications opening on hover after a short wait.
     */
    Alpine.data('famaSide', () => ({
        folded: document.documentElement.classList.contains('fx-folded'),
        open: null,
        popTop: 0,
        _t: null,

        /** Boot the shared notifications store. */
        init() { Alpine.store('famaNotifs').boot(window.famaNotifUrls); },

        /** Fold ⇄ unfold; the fold animates (the first paint does not), banners refit after. */
        toggleFold() {
            this.folded = !this.folded;
            const html = document.documentElement;
            html.classList.add('fx-side-anim');
            html.classList.toggle('fx-folded', this.folded);
            try { localStorage.setItem(SIDE_KEY, this.folded ? '1' : '0'); } catch (e) { /* storage blocked */ }
            setTimeout(() => window.dispatchEvent(new Event('resize')), 260);
        },

        /** Toggle a menu beside the bar, lined up with the button that opened it. */
        openAt(name, btn, event = null) {
            if (this.open === name) return this.close();
            this.popTop = btn.getBoundingClientRect().top;
            this.open = name;
            // Keyboard only: a mouse click leaves no focus ring on the first item.
            if (!event || event.detail === 0) this.$nextTick(() => this.$root.querySelector(name === 'me' ? '#fxSPop a' : '#fxSNewPop a')?.focus());
        },

        /** Arrow keys walk the Create menu. */
        cycle(dir) {
            const items = [...this.$root.querySelectorAll('#fxSNewPop a')];
            const i = items.indexOf(document.activeElement);
            items[(i + dir + items.length) % items.length]?.focus();
        },

        /** Open the notifications popover beside the Notifications link. */
        openNotifs(focus = false) {
            clearTimeout(this._t);
            const link = this.$refs.nlink;
            if (!link) return;
            const r = link.getBoundingClientRect();
            this.popTop = Math.max(8, Math.min(r.top - 8, window.innerHeight - 560));
            this.open = 'notifs';
            Alpine.store('famaNotifs').load();
            if (focus) this.$nextTick(() => this.$root.querySelector('#fxSNPop a')?.focus());
        },

        /** Hover in opens after 120ms (passing over does nothing); hover out closes after 220ms. */
        hoverNotifs(entering) {
            clearTimeout(this._t);
            if (entering) {
                if (this.open !== 'notifs') this._t = setTimeout(() => this.openNotifs(), 120);
            } else {
                this._t = setTimeout(() => { if (this.open === 'notifs') this.close(); }, 220);
            }
        },

        /** Close whatever is open. */
        close() { clearTimeout(this._t); this.open = null; },
        closeAll() { this.close(); },
    }));

    /**
     * The talent top bar (≤1100px): avatar menu, bell popover, the phone menu
     * (over the page; locks with html.fx-menu-open), and the lift shadow once
     * the page scrolls under it.
     */
    Alpine.data('famaNav', () => ({
        meOpen: false,
        bellOpen: false,
        drawer: false,
        lifted: window.scrollY > 4,

        /** Boot the shared store; watch scroll (lift) and width (the drawer closes past 1100px). */
        init() {
            Alpine.store('famaNotifs').boot(window.famaNotifUrls);
            window.addEventListener('scroll', () => { this.lifted = window.scrollY > 4; }, { passive: true });
            window.addEventListener('resize', () => { if (this.drawer && window.innerWidth > 1100) this.drawer = false; });
            this.$watch('drawer', (open) => document.documentElement.classList.toggle('fx-menu-open', open));
        },

        /** Close every popover (and the drawer). */
        closeAll() { this.meOpen = false; this.bellOpen = false; this.drawer = false; },

        /** Toggle the avatar menu. */
        toggleMe() { this.bellOpen = false; this.meOpen = !this.meOpen; },

        /** Toggle the notifications popover, loading the list the first time. */
        toggleBell() {
            this.meOpen = false;
            this.bellOpen = !this.bellOpen;
            if (this.bellOpen) Alpine.store('famaNotifs').load();
        },
    }));
});
