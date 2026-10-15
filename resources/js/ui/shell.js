/**
 * fama-ui shell behaviour: the header (`famaNav` — avatar menu, burger drawer,
 * live notification bell) and the global toast store (`famaToast`).
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

        /** Show a toast; `undo` (function) adds an Undo button. */
        show(message, undo = null) {
            this.message = message;
            this.undo = undo;
            this.visible = true;
            clearTimeout(this._t);
            this._t = setTimeout(() => { this.visible = false; this.undo = null; }, 5000);
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
     * The talent header: menus close on outside click / Escape; the bell
     * polls the unread count once on load and fetches the latest list on open.
     */
    Alpine.data('famaNav', (cfg) => ({
        meOpen: false,
        bellOpen: false,
        drawer: false,
        unread: 0,
        items: [],
        loaded: false,

        /** Paint the unread badge on load. */
        init() {
            window.fama.get(cfg.unreadUrl).then((r) => { this.unread = r.data?.count ?? 0; }).catch(() => {});
        },

        /** Close every popover. */
        closeAll() { this.meOpen = false; this.bellOpen = false; },

        /** Toggle the avatar menu. */
        toggleMe() { this.bellOpen = false; this.meOpen = !this.meOpen; },

        /** Toggle the notifications popover, loading the list the first time. */
        toggleBell() {
            this.meOpen = false;
            this.bellOpen = !this.bellOpen;
            if (this.bellOpen && !this.loaded) this.load();
        },

        /** Fetch the latest notifications. */
        load() {
            window.fama.get(cfg.listUrl).then((r) => {
                this.items = Array.isArray(r.data) ? r.data : (r.data?.data || []);
                this.loaded = true;
            }).catch(() => { this.loaded = true; });
        },

        /** Mark one notification read (fire-and-forget; navigation follows). */
        markRead(n) {
            if (n.read_at) return;
            n.read_at = new Date().toISOString();
            this.unread = Math.max(0, this.unread - 1);
            window.fama.post(cfg.readUrl, { ids: [n.id] }).catch(() => {});
        },

        /** Mark everything read. */
        readAll() {
            this.items.forEach((n) => { n.read_at = n.read_at || new Date().toISOString(); });
            this.unread = 0;
            window.fama.post(cfg.readUrl, {}).catch(() => {});
        },

        icon: (n) => window.famaNotifIcon(n),
        who: (n) => window.famaNotifWho(n),
        rest: (n) => window.famaNotifRest(n),
        ago,
    }));
});
