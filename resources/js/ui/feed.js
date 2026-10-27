/**
 * fama-ui feed (port of the prototype's feed.html + posts.js on real endpoints).
 *
 * feedStream        — the stream: cursor pages from /feed/items (server-rendered
 *                     cards, double-fetch guard), casting strip injection, the
 *                     narrow-screen "Talent to follow" strip, filters dialog
 *                     (All / Following / skill — ?following=1 / ?skill=), FABs.
 * postFrame         — one post card: optimistic like/save, inline comments, share
 *                     menu, More menu (edit / pin / delete / report), media swipe
 *                     counter + viewer, guest sign-in prompt on 401 (intent replay).
 * castingCard       — a "Talent to discover" follow toggle.
 * $store.famaPeople — "Talent to follow" (side card + strip share one list).
 * famaSignInDialog / famaReportDialog / famaViewerDialog — the shared dialogs.
 *
 * The composer, mention field and post-page thread live in ui/feed-compose.js;
 * the notifications + Activity pages in ui/feed-pages.js.
 */
import { get, post, del, ApiError } from '../http';
import { L, fmt, initials, ago, profileUrl, toast, gate, asVisitor, nodesFrom } from './feed-util';
import './feed-compose';
import './feed-pages';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // ----- shared dialogs ------------------------------------------------------

    /** Guest sign-in prompt state; window.famaSignIn(verb) opens it. */
    Alpine.store('famaSignIn', {
        open: false,
        verb: '',
        from: null,

        /** Open the prompt for an action ("like", "save", "comment", …). */
        show(verb) {
            this.from = document.activeElement;
            this.verb = verb;
            this.open = true;
        },
    });
    // A signed-in admin is not a visitor: explain the feed is read-only for staff
    // instead of asking them to sign in again.
    window.famaSignIn = (verb) => (window.famaStaff
        ? window.famaToast?.(window.famaStaffNote)
        : Alpine.store('famaSignIn').show(verb));

    /** The centred sign-in dialog bound to $store.famaSignIn. */
    Alpine.data('famaSignInDialog', () => ({
        /** Mirror the store onto the native dialog. */
        init() {
            this.$watch('$store.famaSignIn.open', (open) => {
                const d = this.$refs.d;
                if (open && !d.open) {
                    d.showModal();
                    this.$nextTick(() => this.$refs.first?.focus());
                } else if (!open && d.open) {
                    d.close();
                }
            });
        },

        /** "Sign in to like this". */
        title() {
            return fmt(L('signin', 'Sign in to :action this'), { action: this.verbLabel() });
        },

        /** "Log in and we'll finish the like for you." */
        body() {
            return fmt(L('signinP', "Log in and we'll finish the :action for you."), { action: this.verbLabel() });
        },

        /** The localized verb for the pending action. */
        verbLabel() {
            const verb = Alpine.store('famaSignIn').verb;
            return window.famaLang?.verbs?.[verb] ?? verb;
        },

        /** Reset the store and hand focus back to what was pressed. */
        closed() {
            const s = Alpine.store('famaSignIn');
            s.open = false;
            s.from?.focus?.();
        },
    }));

    /** The report sheet: pick a reason, add a note ("other" requires one), send. */
    Alpine.data('famaReportDialog', () => ({
        type: 'post',
        id: null,
        reason: '',
        note: '',
        error: '',
        busy: false,
        sent: false,
        from: null,

        /** Open for {type: post|comment, id}. */
        show({ type, id }) {
            Object.assign(this, { type, id, reason: '', note: '', error: '', busy: false, sent: false });
            this.from = document.activeElement;
            this.$refs.d.showModal();
        },

        /** Restore focus when the sheet closes. */
        closed() {
            this.from?.focus?.();
        },

        /** Validate and send the report (a duplicate report still thanks). */
        async submit() {
            if (this.busy) return;
            if (!this.reason) { this.error = L('rNeed', 'Pick a reason first.'); return; }
            if (this.reason === 'other' && !this.note.trim()) {
                this.error = L('rNeedNote', 'Tell us what’s wrong, in a line or two.');
                this.$refs.note?.focus();
                return;
            }
            this.busy = true;
            this.error = '';
            try {
                await post(`/${this.type}s/${this.id}/report`, { reason: this.reason, note: this.note || null });
                this.done();
            } catch (error) {
                if (error instanceof ApiError && error.status === 403) {
                    this.done();
                } else if (error instanceof ApiError && error.status === 401) {
                    this.$refs.d.close();
                    window.famaSignIn('report');
                } else {
                    this.error = error?.message ?? 'Something went wrong.';
                }
            } finally {
                this.busy = false;
            }
        },

        /** Swap to the thank-you panel and tell the reported card. */
        done() {
            this.sent = true;
            window.dispatchEvent(new CustomEvent('content-reported', { detail: { type: this.type, id: this.id } }));
            this.$nextTick(() => this.$refs.ok?.focus());
        },
    }));

    /** The media viewer: arrows / swipe / Escape, counter, alt caption. */
    Alpine.data('famaViewerDialog', () => ({
        items: [],
        index: 0,
        touchX: null,
        from: null,

        /** Open at {items, index}. */
        show({ items, index }) {
            this.items = items;
            this.index = Math.max(0, Math.min(items.length - 1, index));
            this.from = document.activeElement;
            this.$refs.d.showModal();
        },

        /** Focus returns to the opening tile. */
        closed() { this.from?.focus?.(); },

        /** Next image (wraps). */
        next() { if (this.items.length) this.index = (this.index + 1) % this.items.length; },

        /** Previous image (wraps). */
        prev() { if (this.items.length) this.index = (this.index - 1 + this.items.length) % this.items.length; },

        /** A horizontal swipe pages the viewer. */
        swipe(endX) {
            if (this.touchX === null) return;
            const delta = endX - this.touchX;
            if (Math.abs(delta) > 40) (delta < 0 ? this.next() : this.prev());
            this.touchX = null;
        },
    }));

    // ----- "Talent to follow" ----------------------------------------------------

    /** One suggestion list shared by the side card and the narrow-screen strip. */
    Alpine.store('famaPeople', {
        items: [],
        loaded: false,
        loading: false,

        /** Fetch the first suggestions (once). */
        async load() {
            if (this.loaded || this.loading) return;
            this.loading = true;
            try {
                const { data } = await get('/feed/suggestions/talents?limit=10');
                this.items = (data.items ?? []).map((p) => ({ ...p, following: false }));
            } catch {
                this.items = [];
            } finally {
                this.loaded = true;
                this.loading = false;
            }
        },

        /** Profile URL for a suggestion. */
        url: (p) => profileUrl(p),

        /** Avatar initials. */
        initials: (p) => initials(p.display_name),

        /** The first skill's name (translatable names arrive as objects). */
        skill(p) {
            const name = p.skills?.[0]?.name;
            if (name && typeof name === 'object') return name[document.documentElement.lang] ?? name.en ?? Object.values(name)[0] ?? '';
            return name ?? '';
        },

        /** Why they are suggested (the server's reason); a visitor sees their skill. */
        why(p) { return (window.famaMe ? p.reason : '') || this.skill(p); },

        /** Follow / unfollow, optimistic; a guest gets the sign-in prompt. */
        async follow(p) {
            if (await asVisitor('follow', () => post(`/follow/${p.type}/${p.id}`))) return;
            const was = p.following;
            p.following = !was;
            try {
                if (was) await del(`/follow/${p.type}/${p.id}`);
                else await post(`/follow/${p.type}/${p.id}`);
            } catch (error) {
                p.following = was;
                if (!gate(error, 'follow')) console.warn('people: follow failed', error);
            }
        },

        /** Hide a suggestion with a 5s Undo; the permanent dismiss posts after it. */
        dismiss(p) {
            const index = this.items.indexOf(p);
            if (index < 0) return;
            this.items.splice(index, 1);
            let undone = false;
            const timer = setTimeout(() => {
                if (!undone) post('/feed/suggestions/dismiss', { type: p.type, id: p.id }).catch(() => {});
            }, 5000);
            toast(fmt(L('hidden', ":name won't be suggested again"), { name: p.display_name }), () => {
                undone = true;
                clearTimeout(timer);
                this.items.splice(Math.min(index, this.items.length), 0, p);
            });
        },
    });

    /** "Talent to discover" card follow toggle. */
    Alpine.data('castingCard', ({ id, following = false }) => ({
        id,
        following,
        busy: false,

        /** Follow / unfollow the talent. */
        async toggle() {
            if (this.busy) return;
            this.busy = true;
            if (await asVisitor('follow', () => post(`/follow/talent/${this.id}`))) { this.busy = false; return; }
            const was = this.following;
            this.following = !was;
            try {
                if (was) await del(`/follow/talent/${this.id}`);
                else await post(`/follow/talent/${this.id}`);
            } catch (error) {
                this.following = was;
                if (!gate(error, 'follow')) console.warn('casting: follow failed', error);
            } finally {
                this.busy = false;
            }
        },
    }));

    // ----- the stream -----------------------------------------------------------

    Alpine.data('feedStream', ({ nextCursor = null, filter = 'all', count = 0, isGuest = false, skillNames = {} } = {}) => ({
        nextCursor,
        filter,
        count,
        isGuest,
        skillNames,
        loading: false,
        finished: nextCursor === null,
        showTop: false,
        pick: filter,
        fq: '',

        /** Arm infinite scroll, the back-to-top FAB, the strip and the people list. */
        init() {
            new IntersectionObserver((entries) => {
                if (entries.some((e) => e.isIntersecting)) this.loadMore();
            }, { rootMargin: '600px 0px' }).observe(this.$refs.sentinel);

            const onScroll = () => { this.showTop = window.scrollY >= 600; };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();

            this.insertFollowStrip();
            Alpine.store('famaPeople').load();

            window.addEventListener('post-created', (e) => this.onCreated(e.detail));
            window.addEventListener('post-removed', () => { this.count = Math.max(0, this.count - 1); });
        },

        /** Fetch the next cursor page and append its cards (guarded). */
        async loadMore() {
            if (this.loading || this.finished || this.nextCursor === null) return;
            this.loading = true;
            try {
                const { data } = await get(`/feed/items?cursor=${encodeURIComponent(this.nextCursor)}`);
                this.appendPage(data);
            } catch (error) {
                console.warn('feed: page load failed', error); // the sentinel stays armed; scrolling retries
            } finally {
                this.loading = false;
            }
        },

        /** Append one /feed/items page: casting strips at their slots, then cards. */
        appendPage(data) {
            const positions = data.casting_positions ?? [];
            (data.html ?? []).forEach((html, index) => {
                if (positions.includes(index) && data.casting_html) this.$refs.spine.append(...nodesFrom(data.casting_html));
                this.$refs.spine.append(...nodesFrom(html));
            });
            this.count += (data.html ?? []).length;
            this.nextCursor = data.next_cursor;
            this.finished = this.nextCursor === null;
            this.insertFollowStrip();
        },

        /** Put the narrow-screen follow strip after the second post (once per stream). */
        insertFollowStrip() {
            const spine = this.$refs.spine;
            if (spine.querySelector('.mobile-follow')) return;
            const second = spine.querySelectorAll(':scope > .post')[1];
            if (!second) return;
            second.after(...nodesFrom(this.$refs.followStrip.innerHTML));
        },

        /** Switch filter in place: the cursor carries it; the link updates. */
        async setFilter(key) {
            if (this.loading) return;
            this.filter = key;
            this.nextCursor = null;
            this.finished = false;
            this.loading = true;
            this.count = 0;
            this.$refs.spine.innerHTML = '';

            const params = new URLSearchParams();
            if (key === 'following') params.set('following', '1');
            else if (key !== 'all') params.set('skill', key);
            const query = params.toString();
            window.history.replaceState({}, '', window.location.pathname + (query ? '?' + query : ''));

            try {
                const { data } = await get('/feed/items' + (query ? '?' + query : ''));
                this.appendPage(data);
            } catch (error) {
                console.warn('feed: filter swap failed', error);
                this.finished = true;
            } finally {
                this.loading = false;
            }
        },

        /** A freshly published post goes on top (back to All first if filtered). */
        async onCreated({ html }) {
            if (this.filter !== 'all') await this.setFilter('all');
            this.$refs.spine.prepend(...nodesFrom(html));
            this.count += 1;
            window.scrollTo({ top: this.$refs.spine.getBoundingClientRect().top + window.scrollY - 120, behavior: 'smooth' });
        },

        /** The human name of a filter key. */
        filterName(key) {
            if (key === 'all') return L('all', 'All');
            if (key === 'following') return L('following', 'Following');
            return this.skillNames[key] ?? key;
        },

        /** "Showing: Styling". */
        showingLabel() { return fmt(L('showing', 'Showing: :filter'), { filter: this.filterName(this.filter) }); },

        /** The Filters FAB label/tooltip ("Filters · Styling" while filtered). */
        filterTip() {
            const base = L('filters', 'Filters');
            return this.filter === 'all' ? base : base + ' · ' + this.filterName(this.filter);
        },

        /** Open the filters dialog with the current filter picked. */
        openFilters() {
            this.pick = this.filter;
            this.fq = '';
            this.$refs.filterDlg.showModal();
        },

        /** All and Following always show; skills narrow as you type. */
        pickVisible(slug, name) {
            const q = this.fq.trim().toLowerCase();
            return !q || this.pick === slug || String(name).toLowerCase().includes(q);
        },

        /** "Show posts": apply the picked filter. */
        applyPick() {
            this.$refs.filterDlg.close();
            if (this.pick !== this.filter) this.setFilter(this.pick);
        },

        /** Back to top; focus the logo without letting focus() scroll. */
        toTop() {
            window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
            document.querySelector('.fx-logo')?.focus({ preventScroll: true });
        },
    }));

    // ----- one post card ----------------------------------------------------------

    Alpine.data('postFrame', (cfg) => ({
        ...cfg,
        commentsCount: cfg.comments,
        comments: [],
        commentsLoaded: false,
        commentsOpen: false,
        draft: '',
        posting: false,
        commentError: '',
        moreOpen: false,
        shareOpen: false,
        reported: false,
        slide: 0,
        busy: false,
        now: Date.now(),
        canNativeShare: typeof navigator !== 'undefined' && !!navigator.share,

        /** Cross-card events: reported, comment counts from the thread, single pin. */
        init() {
            window.addEventListener('content-reported', (e) => {
                if (e.detail.type === 'post' && e.detail.id === this.id) this.reported = true;
            });
            window.addEventListener('comment-count', (e) => {
                if (e.detail.postId === this.id) this.commentsCount = Math.max(0, this.commentsCount + e.detail.delta);
            });
            window.addEventListener('post-pinned', (e) => {
                if (this.isAuthor && e.detail.id !== this.id) this.pinned = false;
            });
        },

        /** "128 likes". */
        likesLabel() {
            return fmt(this.likes === 1 ? L('like1', ':count like') : L('likeN', ':count likes'), { count: this.likes.toLocaleString() });
        },

        /** "14 comments". */
        commentsLabel() {
            const n = this.commentsCount;
            return fmt(n === 1 ? L('comment1', ':count comment') : L('commentN', ':count comments'), { count: n.toLocaleString() });
        },

        initials,
        ago,
        profileUrl,

        /** Like / unlike, optimistic; a guest gets the sign-in prompt. */
        async toggleLike() {
            if (this.busy) return;
            this.busy = true;
            if (await asVisitor('like', () => post(`/posts/${this.id}/like`))) { this.busy = false; return; }
            const was = this.liked;
            this.liked = !was;
            this.likes += this.liked ? 1 : -1;
            try {
                const { data } = was ? await del(`/posts/${this.id}/like`) : await post(`/posts/${this.id}/like`);
                this.liked = data.liked;
                this.likes = data.likes_count;
                window.dispatchEvent(new CustomEvent('post-like-changed', { detail: { id: this.id, liked: this.liked, el: this.$root } }));
            } catch (error) {
                this.liked = was;
                this.likes += was ? 1 : -1;
                if (!gate(error, 'like')) console.warn('post: like failed', error);
            } finally {
                this.busy = false;
            }
        },

        /** Double-tap the photos to like (never unlikes). */
        doubleTapLike() {
            if (!this.liked) this.toggleLike(); // a visitor's 401 opens the sign-in dialog
        },

        /** Save / unsave, optimistic. */
        async toggleSave() {
            if (await asVisitor('save', () => post(`/posts/${this.id}/bookmark`))) return;
            const was = this.saved;
            this.saved = !was;
            try {
                const { data } = was ? await del(`/posts/${this.id}/bookmark`) : await post(`/posts/${this.id}/bookmark`);
                this.saved = data.saved;
                window.dispatchEvent(new CustomEvent('post-save-changed', { detail: { id: this.id, saved: this.saved, el: this.$root } }));
            } catch (error) {
                this.saved = was;
                if (!gate(error, 'save')) console.warn('post: save failed', error);
            }
        },

        /** Open/close the inline comments; the first open loads the latest. */
        toggleComments() {
            this.commentsOpen = !this.commentsOpen;
            if (!this.commentsOpen) return;
            if (!this.commentsLoaded) this.loadComments();
            this.$nextTick(() => this.$root.querySelector('.pcomments input, .pcomments a')?.focus());
        },

        /** The newest page of comments. */
        async loadComments() {
            try {
                const { data } = await get(`/posts/${this.id}/comments?page=1`);
                this.comments = data ?? [];
            } catch (error) {
                console.warn('post: comments load failed', error);
            } finally {
                this.commentsLoaded = true;
            }
        },

        /** Add a comment on top (pending until the server confirms; Retry on failure). */
        async sendComment() {
            const body = this.draft.trim();
            if (!body || this.posting) return;
            if (body.length > 2200) { this.commentError = L('commentMax', 'Up to 2,200 characters.'); return; }
            this.posting = true;
            this.commentError = '';
            this.comments.unshift({
                id: 'tmp-' + Date.now(), is_pending: true, is_failed: false, body,
                body_html: body.replace(/&/g, '&amp;').replace(/</g, '&lt;'),
                author: window.famaMe ?? null, created_at: new Date().toISOString(),
            });
            const row = this.comments[0];
            this.draft = '';
            try {
                const { data } = await post(`/posts/${this.id}/comments`, { body, parent_id: null });
                Object.assign(row, data, { is_pending: false });
                this.commentsCount += 1;
            } catch (error) {
                if (gate(error, 'comment')) {
                    this.comments = this.comments.filter((c) => c !== row);
                    this.draft = body;
                } else if (error instanceof ApiError && error.status === 429) {
                    this.comments = this.comments.filter((c) => c !== row);
                    this.draft = body;
                    this.commentError = L('commentSlow', "You're commenting fast. Wait a moment and try again.");
                } else if (error instanceof ApiError && error.status === 422) {
                    this.comments = this.comments.filter((c) => c !== row);
                    this.draft = body;
                    this.commentError = error.errors ? Object.values(error.errors).flat().join(' ') : error.message;
                } else {
                    row.is_pending = false;
                    row.is_failed = true;
                }
            } finally {
                this.posting = false;
            }
        },

        /** Re-send a failed comment. */
        retryComment(row) {
            this.comments = this.comments.filter((c) => c !== row);
            this.draft = row.body;
            this.sendComment();
        },

        /** More menu (a guest gets the sign-in prompt — the only item is Report). */
        toggleMore() {
            if (this.isGuest) { window.famaSignIn('report'); return; }
            this.shareOpen = false;
            this.now = Date.now();
            this.moreOpen = !this.moreOpen;
            if (this.moreOpen) this.$nextTick(() => this.$root.querySelector('.more-wrap .pmenu button:not([disabled])')?.focus());
        },

        /** Close both menus (Escape returns focus to the trigger). */
        closeMenus(refocus = false) {
            const wasMore = this.moreOpen;
            const wasShare = this.shareOpen;
            this.moreOpen = false;
            this.shareOpen = false;
            if (refocus && wasMore) this.$refs.moreBtn?.focus();
            if (refocus && wasShare) this.$refs.shareBtn?.focus();
        },

        /** Whether the 15-minute edit window is still open. */
        canEdit() {
            return this.editable && this.editableUntil && new Date(this.editableUntil).getTime() > this.now;
        },

        /** "Edit · 12 min left". */
        editLabel() {
            const min = Math.max(1, Math.ceil((new Date(this.editableUntil).getTime() - this.now) / 60000));
            return fmt(L('editLeft', 'Edit · :min min left'), { min });
        },

        /** Open the composer in edit mode (text + skills; media locked). */
        edit() {
            window.dispatchEvent(new CustomEvent('edit-post', { detail: { id: this.id, body: this.body } }));
        },

        /** Pin to the top of my posts (one pin per talent) / unpin. */
        async togglePin() {
            const replacing = !this.pinned && [...document.querySelectorAll('.post')]
                .some((el) => el !== this.$root && Alpine.$data(el)?.isAuthor && Alpine.$data(el)?.pinned);
            try {
                const { data } = await post(`/posts/${this.id}/pin`);
                this.pinned = !!data.is_pinned;
                if (this.pinned) {
                    window.dispatchEvent(new CustomEvent('post-pinned', { detail: { id: this.id } }));
                    toast(replacing ? L('pinReplace', 'Pinned. Your previous pin was replaced.') : L('pinT', 'Pinned. It stays at the top of your posts.'));
                }
            } catch (error) {
                console.warn('post: pin failed', error);
            }
        },

        /** Delete (confirmed): off the feed and the profile, with its comments. */
        remove() {
            this.$store.confirm.ask({
                title: L('delT', 'Delete this post?'),
                message: L('delB', 'It comes off the feed and your profile, with its comments.'),
                confirmLabel: L('delOk', 'Delete post'),
                tone: 'danger',
            }).then(async (ok) => {
                if (!ok) return;
                try {
                    await del(`/posts/${this.id}`);
                    this.$root.remove();
                    window.dispatchEvent(new CustomEvent('post-removed', { detail: { id: this.id } }));
                    toast(L('deleted', 'Post deleted'));
                } catch (error) {
                    console.warn('post: delete failed', error);
                }
            });
        },

        /** Report (one per person per post). */
        report() {
            window.dispatchEvent(new CustomEvent('open-report', { detail: { type: 'post', id: this.id } }));
        },

        /** Share menu (opens above the action row). */
        toggleShare() {
            this.moreOpen = false;
            this.shareOpen = !this.shareOpen;
            if (this.shareOpen) this.$nextTick(() => this.$root.querySelector('.smenu [role=menuitem]')?.focus());
        },

        /** Arrow keys move through a menu's items. */
        moveFocus(event, dir) {
            const items = [...event.currentTarget.querySelectorAll('[role=menuitem]')].filter((el) => el.offsetParent !== null);
            const i = items.indexOf(document.activeElement);
            items[(i + dir + items.length) % items.length]?.focus();
        },

        /** Copy the permalink and confirm with a toast. */
        async copyLink() {
            this.shareOpen = false;
            try { await navigator.clipboard.writeText(this.permalink); } catch { /* insecure context */ }
            toast(L('copied', 'Link copied') + ': ' + this.permalink.replace(/^https?:\/\//, ''));
        },

        /** The OS share sheet. */
        nativeShare() {
            this.shareOpen = false;
            navigator.share({ url: this.permalink }).catch(() => {});
        },

        /** Open the viewer on an image (videos play in place). */
        openViewer(index) {
            const images = this.media.filter((m) => !m.is_video);
            const at = images.indexOf(this.media[index]);
            window.dispatchEvent(new CustomEvent('open-viewer', { detail: { items: images, index: Math.max(0, at) } }));
        },

        /** Keep the phone swipe counter and dots in step. */
        onTrack(event) {
            const track = event.target;
            const width = (track.firstElementChild?.offsetWidth ?? 1) + 8;
            this.slide = Math.min(this.media.length - 1, Math.round(Math.abs(track.scrollLeft) / width));
        },

        /** Post page: the Comment action focuses the thread's box. */
        focusComment() {
            document.getElementById('taroot')?.focus();
        },
    }));
});
