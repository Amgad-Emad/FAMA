/**
 * fama-ui feed pages beyond the stream:
 *
 * famaNotifications — notifications.html: All / Unread / Mentions (server-side
 *                     ?filter=), grouped Today / This week / Earlier, Mark all
 *                     as read (the header bell's badge follows), Show older.
 * famaActivity      — following.html: Saved · Liked (full post cards over
 *                     ?format=html) · Following, with Following / Followers
 *                     rows, search, skill field, sort, Unfollow + Undo, Follow back.
 */
import { get, post, del } from '../http';
import { L, fmt, initials, ago, profileUrl, toast, nodesFrom } from './feed-util';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // ----- notifications -----------------------------------------------------

    Alpine.data('famaNotifications', ({ listUrl, readUrl, unreadUrl, groups = ['Today', 'This week', 'Earlier'] }) => ({
        show: 'all',
        items: [],
        page: 1,
        lastPage: 1,
        loaded: false,
        unread: 0,
        ago,

        /** First page + the unread count. */
        init() {
            this.load();
            get(unreadUrl).then((r) => { this.unread = r.data?.count ?? 0; }).catch(() => {});
        },

        /** Load a page for the current segment (more = append the next page). */
        async load(more = false) {
            const page = more ? this.page + 1 : 1;
            try {
                const filter = this.show === 'all' ? '' : `&filter=${this.show}`;
                const { data, meta } = await get(`${listUrl}?page=${page}${filter}`);
                this.items = more ? [...this.items, ...data] : data;
                this.page = meta?.pagination?.current_page ?? page;
                this.lastPage = meta?.pagination?.last_page ?? page;
            } catch (error) {
                console.warn('notifications: load failed', error);
            } finally {
                this.loaded = true;
            }
        },

        /** Switch segment. */
        setShow(value) {
            if (value === this.show) return;
            this.show = value;
            this.loaded = false;
            this.load();
        },

        /** Rows grouped by calendar day: Today · This week · Earlier. */
        grouped() {
            const start = new Date();
            start.setHours(0, 0, 0, 0);
            const days = (n) => Math.floor((start - new Date(new Date(n.created_at).setHours(0, 0, 0, 0))) / 86400000);
            const tests = [(d) => d <= 0, (d) => d > 0 && d < 7, (d) => d >= 7];
            return groups.map((label, i) => ({ label, items: this.items.filter((n) => tests[i](days(n))) }))
                .filter((g) => g.items.length);
        },

        /** Mark one read (navigation follows). */
        markRead(n) {
            if (n.read_at) return;
            n.read_at = new Date().toISOString();
            this.unread = Math.max(0, this.unread - 1);
            this.syncBell();
            post(readUrl, { ids: [n.id] }).catch(() => {});
        },

        /** Mark everything read. */
        async readAll() {
            try {
                await post(readUrl, {});
                this.items.forEach((n) => { n.read_at = n.read_at || new Date().toISOString(); });
                if (this.show === 'unread') this.items = [];
                this.unread = 0;
                this.syncBell();
                toast(L('markedAll', 'All marked as read'));
            } catch (error) {
                console.warn('notifications: mark all failed', error);
            }
        },

        /** Keep the header bell's badge in step with this page. */
        syncBell() {
            const nav = document.querySelector('.fx-nav[x-data]');
            const state = nav ? Alpine.$data(nav) : null;
            if (state && 'unread' in state) state.unread = this.unread;
        },

        icon: (n) => window.famaNotifIcon?.(n) ?? 'fx-i-check',
        who: (n) => window.famaNotifWho?.(n) ?? (n.actor?.name || 'Fama'),
        rest: (n) => window.famaNotifRest?.(n) ?? (' ' + (n.message ?? '')),
    }));

    // ----- Activity ------------------------------------------------------------

    Alpine.data('famaActivity', ({ view = 'following', who = 'following', counts = {}, collectionUrl, followsUrl, skills = {} }) => ({
        view,
        who,
        counts: { saved: 0, liked: 0, following: 0, followers: 0, ...counts },
        lists: {
            saved: { loaded: false, loading: false, page: 0, last: 0, count: 0 },
            liked: { loaded: false, loading: false, page: 0, last: 0, count: 0 },
        },
        rows: [],
        peopleLoaded: false,
        peoplePage: 1,
        peopleLast: 1,
        q: '',
        sort: 'recent',
        skill: '',
        skillQuery: '',
        skillOpen: false,
        skillFilter: '',
        skillActive: 0,
        initials,
        profileUrl,

        /** Load the visible tab; unsaving / unliking drops a card from its list. */
        init() {
            this.skillQuery = this.allSkills();
            this.loadView();
            window.addEventListener('post-save-changed', (e) => this.onToggle('saved', e.detail.saved, e.detail));
            window.addEventListener('post-like-changed', (e) => this.onToggle('liked', e.detail.liked, e.detail));
        },

        /** "All skills" (the empty choice of the skill field). */
        allSkills() { return window.famaLang?.allSkills ?? 'All skills'; },

        /** Switch the top tab in place (the URL keeps ?view= for deep links). */
        setView(v) {
            this.view = v;
            const url = new URL(window.location.href);
            url.searchParams.set('view', v);
            window.history.replaceState(null, '', url);
            this.loadView();
        },

        /** Lazy-load whatever the visible tab needs. */
        loadView() {
            if (this.view === 'following') { if (!this.peopleLoaded) this.loadPeople(); }
            else if (!this.lists[this.view].loaded) this.loadPosts(this.view);
        },

        // --- Saved / Liked ---

        /** One page of full post cards (12 a page). */
        async loadPosts(mode, more = false) {
            const list = this.lists[mode];
            if (list.loading) return;
            list.loading = true;
            try {
                const page = more ? list.page + 1 : 1;
                const { data, meta } = await get(`${collectionUrl}?mode=${mode}&page=${page}&format=html`);
                const box = this.$refs[mode + 'List'];
                if (!more) box.innerHTML = '';
                data.forEach((row) => box.append(...nodesFrom(row.html)));
                list.page = meta?.pagination?.current_page ?? page;
                list.last = meta?.pagination?.last_page ?? page;
                list.count = box.querySelectorAll('.post').length;
            } catch (error) {
                console.warn('activity: posts load failed', error);
            } finally {
                list.loading = false;
                list.loaded = true;
            }
        },

        /** Unsave / unlike from inside a list: the card leaves, with Undo. */
        onToggle(mode, on, { id, el }) {
            const box = this.$refs[mode + 'List'];
            if (!box || !box.contains(el)) {
                // Toggled elsewhere (the other tab): just move the count.
                this.counts[mode] = Math.max(0, this.counts[mode] + (on ? 1 : -1));
                if (this.lists[mode].loaded && on) this.lists[mode].loaded = false;
                return;
            }
            if (on) return;
            const next = el.nextSibling;
            el.remove();
            this.counts[mode] = Math.max(0, this.counts[mode] - 1);
            this.lists[mode].count = box.querySelectorAll('.post').length;
            toast(mode === 'saved' ? L('unsaved', 'Removed from saved') : L('unliked', 'Removed from liked'), async () => {
                try {
                    await post(`/posts/${id}/${mode === 'saved' ? 'bookmark' : 'like'}`);
                    const state = Alpine.$data(el);
                    if (state) { if (mode === 'saved') state.saved = true; else { state.liked = true; state.likes += 1; } }
                    box.insertBefore(el, next && next.parentNode === box ? next : null);
                    this.counts[mode] += 1;
                    this.lists[mode].count = box.querySelectorAll('.post').length;
                } catch (error) {
                    console.warn('activity: undo failed', error);
                }
            });
        },

        // --- Following / Followers ---

        /** Switch Following / Followers (each side has its own skills). */
        setWho(w) {
            if (w === this.who) return;
            this.who = w;
            this.skill = '';
            this.skillQuery = this.allSkills();
            const url = new URL(window.location.href);
            url.searchParams.set('tab', w);
            window.history.replaceState(null, '', url);
            this.reloadPeople();
        },

        /** Back to page 1 with the current search / skill / sort. */
        reloadPeople() { this.loadPeople(false); },

        /** One page of people rows (24 a page). */
        async loadPeople(more = false) {
            const page = more ? this.peoplePage + 1 : 1;
            const params = new URLSearchParams({ tab: this.who, page, sort: this.sort });
            if (this.q.trim()) params.set('q', this.q.trim());
            if (this.skill) params.set('skill', this.skill);
            try {
                const { data, meta } = await get(`${followsUrl}?${params}`);
                const fresh = data.map((r) => ({ ...r, busy: false }));
                this.rows = more ? [...this.rows, ...fresh] : fresh;
                this.peoplePage = meta?.pagination?.current_page ?? page;
                this.peopleLast = meta?.pagination?.last_page ?? page;
            } catch (error) {
                console.warn('activity: people load failed', error);
            } finally {
                this.peopleLoaded = true;
            }
        },

        /** The row's first skill. */
        skillOf(p) {
            const name = p.skills?.[0]?.name;
            if (name && typeof name === 'object') return name[document.documentElement.lang] ?? name.en ?? '';
            return name ?? (p.sector ? String(p.sector).replaceAll('_', ' ') : '');
        },

        /** "Follows you · Posted 3 days ago" / "Quiet since May". */
        status(p) {
            const parts = [];
            if (p.mutual) parts.push(L('followsYou', 'Follows you'));
            if (p.quiet_since) parts.push(p.quiet_since);
            else if (p.last_posted_at) {
                const d = Math.max(0, Math.floor((Date.now() - new Date(p.last_posted_at).getTime()) / 86400000));
                parts.push(d === 0 ? L('postedToday', 'Posted today') : d === 1 ? L('postedYesterday', 'Posted yesterday') : fmt(L('postedDays', 'Posted :count days ago'), { count: d }));
            }
            return parts.join(' · ');
        },

        /** Unfollow with a 5-second Undo that restores the row in place. */
        async unfollow(p) {
            const index = this.rows.indexOf(p);
            this.rows.splice(index, 1);
            this.counts.following = Math.max(0, this.counts.following - 1);
            try {
                await del(`/follow/${p.type}/${p.id}`);
            } catch (error) {
                console.warn('activity: unfollow failed', error);
                this.rows.splice(index, 0, p);
                this.counts.following += 1;
                return;
            }
            toast(fmt(L('unfollowed', 'Unfollowed :name'), { name: p.display_name }), async () => {
                try {
                    await post(`/follow/${p.type}/${p.id}`);
                    this.rows.splice(Math.min(index, this.rows.length), 0, p);
                    this.counts.following += 1;
                } catch (error) {
                    console.warn('activity: undo failed', error);
                }
            });
        },

        /** Follow a follower back. */
        async followBack(p) {
            p.busy = true;
            try {
                await post(`/follow/${p.type}/${p.id}`);
                p.mutual = true;
                p.can_follow_back = false;
                this.counts.following += 1;
                toast(fmt(L('followedNow', 'You follow :name now'), { name: p.display_name }));
            } catch (error) {
                console.warn('activity: follow back failed', error);
            } finally {
                p.busy = false;
            }
        },

        // --- the skill field (skillfield.js behaviour) ---

        /** "All skills" + the side's skills, narrowed by what's typed. */
        skillOptions() {
            const q = this.skillFilter.trim().toLowerCase();
            const hit = (name) => (' ' + String(name).toLowerCase().replace(/[^a-z0-9]+/g, ' ')).includes(' ' + q);
            const list = (skills[this.who] ?? []).filter((s) => !q || hit(s.name));
            return q ? list : [{ slug: '', name: this.allSkills() }, ...list];
        },

        /** Open the list (all = on focus, showing everything). */
        openSkills(all) {
            this.skillFilter = all ? '' : this.skillQuery;
            this.skillOpen = true;
            this.skillActive = 0;
        },

        /** Arrows / Enter / Escape in the skill field. */
        skillKey(e) {
            const opts = this.skillOptions();
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!this.skillOpen) { this.openSkills(true); return; }
                this.skillActive = Math.max(0, Math.min(opts.length - 1, this.skillActive + (e.key === 'ArrowDown' ? 1 : -1)));
            } else if (e.key === 'Enter') {
                if (this.skillOpen && opts[this.skillActive]) { e.preventDefault(); this.pickSkill(opts[this.skillActive]); }
            } else if (e.key === 'Escape' && this.skillOpen) {
                e.preventDefault();
                this.skillOpen = false;
                this.skillQuery = this.skillLabel();
            }
        },

        /** Leaving without picking puts the last choice back. */
        skillBlur() {
            setTimeout(() => { this.skillOpen = false; this.skillQuery = this.skillLabel(); }, 120);
        },

        /** The chosen skill's name, or "All skills". */
        skillLabel() {
            return (skills[this.who] ?? []).find((s) => s.slug === this.skill)?.name ?? this.allSkills();
        },

        /** Pick a skill and filter. */
        pickSkill(o) {
            this.skillOpen = false;
            this.skillQuery = o.slug ? o.name : this.allSkills();
            if (o.slug === this.skill) return;
            this.skill = o.slug;
            this.reloadPeople();
        },
    }));
});
