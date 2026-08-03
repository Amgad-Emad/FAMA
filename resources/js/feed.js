import Alpine from 'alpinejs';
import { get, post, patch, del, ApiError } from './http';

/**
 * Daylight feed components (Prompts 5M–5O).
 *
 * feedStream  — the spine: IntersectionObserver infinite scroll over
 *               /feed/items (opaque cursor, server-rendered frames), casting
 *               strip injection, light-bar scroll progress, double-fetch guard.
 * postFrame   — one frame: optimistic glint (like) with the shadow sweep,
 *               inline comments (5O), share, guest gating (ADR-F8) — the
 *               tapped control expands in place, scroll is never lost.
 * castingCard — a strip card's follow toggle (5P).
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('feedStream', ({ nextCursor = null, castingEvery = 6, skill = null, following = false } = {}) => ({
        nextCursor,
        skill,
        following,
        loading: false,
        finished: nextCursor === null,
        observer: null,

        scrollDir: 'down',
        lastScrollY: 0,
        pastHeader: false,
        reducedMotion: false,
        enterObserver: null,
        lightObserver: null,
        staggerCount: 0,
        staggerReset: null,

        init() {
            this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Light bar + scroll direction + condensing chrome (5Y) — one
            // passive listener, no layout reads beyond scrollY.
            const onScroll = () => {
                const root = document.documentElement;
                const y = window.scrollY;
                const progress = root.scrollHeight > root.clientHeight
                    ? y / (root.scrollHeight - root.clientHeight)
                    : 0;
                this.$root.style.setProperty('--scroll-progress', String(Math.min(1, Math.max(0, progress))));

                // Past the header, the sticky filter chrome hands off to the
                // Filters FAB in the bottom corner (the chrome hides).
                this.pastHeader = y > ((this.$refs.chrome?.offsetHeight ?? 180) + 40);

                if (Math.abs(y - this.lastScrollY) > 4) {
                    this.scrollDir = y > this.lastScrollY ? 'down' : 'up';
                    const condensed = this.scrollDir === 'down' && y > 120;
                    this.$refs.chrome?.classList.toggle('is-condensed', condensed);
                    document.querySelector('.composer-trigger')?.classList.toggle('is-condensed', condensed);
                    this.lastScrollY = y;
                }
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();

            // Infinite scroll on the sentinel; guarded against double-fetch.
            this.observer = new IntersectionObserver((entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    this.loadMore();
                }
            }, { rootMargin: '600px 0px' });
            this.observer.observe(this.$refs.sentinel);

            this.initMotion();
        },

        // ----- 5Y: the motion layer ------------------------------------------

        initMotion() {
            if (this.reducedMotion) return;

            // Entrance: frames rise + fade as they enter, direction-aware,
            // staggered 40ms capped at 6. Frames already visible at first
            // paint never animate (LCP is sacred).
            this.enterObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    const el = entry.target;

                    if (entry.isIntersecting) {
                        el.classList.remove('dl-leave', 'dl-leave--up');

                        if (el.dataset.entered === '1') return;
                        el.dataset.entered = '1';

                        const delay = Math.min(this.staggerCount++, 5) * 40;
                        clearTimeout(this.staggerReset);
                        this.staggerReset = setTimeout(() => (this.staggerCount = 0), 200);

                        el.style.setProperty('--enter-delay', `${delay}ms`);
                        el.classList.add('dl-enter');
                        if (this.scrollDir === 'up') el.classList.add('dl-enter--up');
                        el.style.willChange = 'transform, opacity';

                        requestAnimationFrame(() => {
                            el.classList.add('dl-enter-active');
                            setTimeout(() => {
                                el.style.willChange = '';
                                el.classList.remove('dl-enter', 'dl-enter--up', 'dl-enter-active');
                            }, 320 + delay);
                        });
                    } else if (el.dataset.entered === '1') {
                        // Leaving reverses, subtler.
                        el.classList.add('dl-leave');
                        el.classList.toggle('dl-leave--up', this.scrollDir === 'up');
                    }
                });
            }, { rootMargin: '10% 0px' });

            // The light response: the middle 40% band of the viewport toggles
            // the pre-rendered lifted shadow + surface veil (opacity only).
            this.lightObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    entry.target.classList.toggle('in-the-light', entry.isIntersecting);
                });
            }, { rootMargin: '-30% 0px -30% 0px' });

            this.observeFrames(this.$refs.spine.querySelectorAll('.frame-shadow'), true);
        },

        observeFrames(frames, firstPaint = false) {
            if (this.reducedMotion) return;

            frames.forEach((frame) => {
                // First-paint frames inside the viewport never animate.
                if (firstPaint && frame.getBoundingClientRect().top < window.innerHeight) {
                    frame.dataset.entered = '1';
                }
                this.enterObserver?.observe(frame);
                this.lightObserver?.observe(frame);
            });
        },

        async loadMore() {
            if (this.loading || this.finished || this.nextCursor === null) return;
            this.loading = true;

            try {
                const { data } = await get(`/feed/items?cursor=${encodeURIComponent(this.nextCursor)}`);

                const frames = data.html ?? [];
                const positions = data.casting_positions ?? [];

                frames.forEach((html, index) => {
                    if (positions.includes(index) && data.casting_html && this.allowStripInjection()) {
                        this.append(data.casting_html);
                    }
                    this.append(html);
                });

                this.nextCursor = data.next_cursor;
                this.finished = this.nextCursor === null;
            } catch (error) {
                // A failed page keeps the sentinel armed; scrolling retries.
                console.warn('feed: page load failed', error);
            } finally {
                this.loading = false;
            }
        },

        // 5AA: with a rail on screen the inline strip halves to every 12
        // slots (three discovery surfaces is one too many); every 6 otherwise.
        stripCounter: 0,

        allowStripInjection() {
            this.stripCounter += 1;
            const railVisible = window.matchMedia('(min-width: 1100px)').matches;

            return railVisible ? this.stripCounter % 2 === 0 : true;
        },

        append(html) {
            const holder = document.createElement('template');
            holder.innerHTML = html.trim();
            const nodes = [...holder.content.childNodes];
            this.$refs.spine.append(...nodes);

            // Re-arm the motion layer for the swapped-in batch (5Y).
            nodes.forEach((node) => {
                if (node.nodeType === 1) {
                    const frames = node.classList?.contains('frame-shadow')
                        ? [node]
                        : [...(node.querySelectorAll?.('.frame-shadow') ?? [])];
                    this.observeFrames(frames);
                }
            });
        },

        // Filtered modes (5P): swap the stream in place, keep the seeded
        // ordering contract (filters ride the cursor), sync the URL.
        async setFilter(skill, following) {
            if (this.loading) return;
            this.skill = skill;
            this.following = following;
            this.nextCursor = null;
            this.finished = false;
            this.loading = true;
            this.$refs.spine.innerHTML = '';

            const params = new URLSearchParams();
            if (skill) params.set('skill', skill);
            if (following) params.set('following', '1');
            const query = params.toString();

            window.history.pushState({}, '', window.location.pathname + (query ? '?' + query : ''));

            try {
                const { data } = await get('/feed/items' + (query ? '?' + query : ''));
                (data.html ?? []).forEach((html, index) => {
                    if ((data.casting_positions ?? []).includes(index) && data.casting_html) {
                        this.append(data.casting_html);
                    }
                    this.append(html);
                });
                this.nextCursor = data.next_cursor;
                this.finished = this.nextCursor === null;
            } catch (error) {
                console.warn('feed: filter swap failed', error);
            } finally {
                this.loading = false;
            }
        },
    }));

    Alpine.data('postFrame', ({ id, liked = false, saved = false, likes = 0, comments = 0, isAuthor = false, editable = false, permalink = '', authorName = '', savedUrl = null }) => ({
        id,
        liked,
        saved,
        likes,
        commentsCount: comments,
        isAuthor,
        editable,
        permalink,
        shared: false,
        shareOpen: false,
        commentsOpen: false,
        reportOpen: false,
        reportReason: '',
        reportNote: '',
        toast: '',
        gatePrompt: false,
        gateMessage: '',
        busy: false,

        likesLabel() {
            return String(this.likes);
        },

        async toggleLike() {
            if (this.busy) return;
            this.busy = true;

            // Optimistic: the glint lights and the shadow sweeps immediately.
            const wasLiked = this.liked;
            this.liked = !wasLiked;
            this.likes += this.liked ? 1 : -1;
            this.sweep();

            try {
                const { data } = this.liked
                    ? await post(`/posts/${this.id}/like`)
                    : await del(`/posts/${this.id}/like`);
                this.liked = data.liked;
                this.likes = data.likes_count;
            } catch (error) {
                // Roll back cleanly; a guest gets the inline prompt (ADR-F8).
                this.liked = wasLiked;
                this.likes += this.liked ? 1 : -1;
                this.gate(error, window.famaLang?.signInLike ?? 'Sign in to like this');
            } finally {
                this.busy = false;
            }
        },

        toggleComments() {
            // The nested commentThread component lazy-loads on first open.
            this.commentsOpen = !this.commentsOpen;
        },

        async toggleBookmark() {
            const wasSaved = this.saved;
            this.saved = !wasSaved;

            try {
                const { data } = this.saved
                    ? await post(`/posts/${this.id}/bookmark`)
                    : await del(`/posts/${this.id}/bookmark`);
                this.saved = data.saved;

                // The save toast (5AF): Undo + a road to the collection.
                Alpine.store('feedToasts').push({
                    message: this.saved
                        ? (window.famaLang?.savedToast ?? 'Saved')
                        : (window.famaLang?.unsavedToast ?? 'Removed from saved'),
                    undo: () => this.toggleBookmark(),
                    href: this.saved ? savedUrl : null,
                    hrefLabel: window.famaLang?.viewSaved ?? 'View saved',
                });
            } catch (error) {
                this.saved = wasSaved;
                this.gate(error, window.famaLang?.signInSave ?? 'Sign in to save this');
            }
        },

        doubleTapLike() {
            if (!this.liked) this.toggleLike();
            else this.sweep();
        },

        async share() {
            try {
                await navigator.clipboard.writeText(this.permalink);
                this.shared = true;
                setTimeout(() => (this.shared = false), 1600);
            } catch {
                window.prompt('URL', this.permalink);
            }
        },

        // Instagram has NO web share-intent URL (unlike X / WhatsApp / LinkedIn).
        // So the useful, honest action is: copy the link and show a clear toast
        // with an "Open Instagram" link — NOT a blank pop-up tab that steals
        // focus and hides the result (the previous behaviour). On mobile the
        // native "Share…" item reaches Instagram directly.
        async shareInstagram() {
            let copied = false;
            try {
                await navigator.clipboard.writeText(this.permalink);
                copied = true;
            } catch {
                copied = false;
            }

            window.Alpine.store('feedToasts')?.push({
                message: copied
                    ? (window.famaLang?.igCopied ?? 'Link copied — paste it into your Instagram story, bio, or DM.')
                    : (window.famaLang?.igManual ?? 'Copy this post’s link, then paste it into Instagram.'),
                href: 'https://www.instagram.com',
                hrefLabel: window.famaLang?.openInstagram ?? 'Open Instagram',
                hrefBlank: true,
            });
        },

        report() {
            window.dispatchEvent(new CustomEvent('open-report', {
                detail: { type: 'post', id: this.id },
            }));
        },

        async submitReport() {
            try {
                const { message } = await post(`/posts/${this.id}/report`, {
                    reason: this.reportReason,
                    note: this.reportNote || null,
                });
                this.reportOpen = false;
                this.reportReason = '';
                this.reportNote = '';
                this.showToast(message ?? 'Thanks — our team will take a look.');
            } catch (error) {
                this.reportOpen = false;
                if (error instanceof ApiError && error.status === 403) {
                    this.showToast(window.famaLang?.alreadyReported ?? 'You already reported this.');
                    return;
                }
                this.gate(error, window.famaLang?.signInReport ?? 'Sign in to report this');
            }
        },

        showToast(message) {
            this.toast = message;
            setTimeout(() => (this.toast = ''), 2600);
        },

        edit() {
            window.location.href = this.permalink;
        },

        async destroy() {
            try {
                await del(`/posts/${this.id}`);
                this.$root.closest('.frame-shadow')?.remove();
            } catch (error) {
                console.warn('feed: delete failed', error);
            }
        },

        sweep() {
            const wrapper = this.$root;
            wrapper.classList.remove('is-sweeping');
            void wrapper.offsetWidth; // restart the animation
            wrapper.classList.add('is-sweeping');
        },

        gate(error, message) {
            if (error instanceof ApiError && error.status === 401) {
                this.gateMessage = message;
                this.gatePrompt = true;
                return;
            }
            console.warn('feed: action failed', error);
        },

        loginUrl(role) {
            return window.fama.localizeUrl(`/login?role=${role}`);
        },
    }));


    // ----- 5U: THE mention-aware field — one component, mounted everywhere ---
    let mentionFieldSeq = 0;

    Alpine.data('mentionField', ({ maxRows = 6 } = {}) => ({
        value: '',
        open: false,
        loading: false,
        results: { talents: [], brands: [] },
        active: 0,
        dropUp: false,
        announcement: '',
        listId: `mention-list-${++mentionFieldSeq}`,
        queryTimer: null,
        popStyle: '',
        repositionHandler: null,

        flat() {
            return [...this.results.talents, ...this.results.brands];
        },

        onInput(event) {
            this.autogrow(event.target);
            this.$dispatch('mention-input', { value: this.value });

            const token = this.caretToken();
            clearTimeout(this.queryTimer);

            // Query fires after ONE character; space/no-token closes.
            if (token === null || token.length < 2) {
                this.close();
                return;
            }

            this.loading = true;
            this.open = true;
            this.positionList();
            this.queryTimer = setTimeout(() => this.search(token.slice(1)), 200);
        },

        onKeydown(event) {
            if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
                event.preventDefault();
                this.$dispatch('submit-intent');
                return;
            }

            if (!this.open) return;

            const options = this.flat();

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                this.active = (this.active + 1) % Math.max(1, options.length);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.active = (this.active - 1 + options.length) % Math.max(1, options.length);
            } else if (event.key === 'Enter' || event.key === 'Tab') {
                // While the list is open, Enter/Tab insert — they never submit.
                event.preventDefault();
                this.pick(options[this.active]);
            } else if (event.key === 'Escape') {
                event.stopPropagation();
                this.close();
            } else if (event.key === ' ') {
                this.close();
            }
        },

        onBlur() {
            // Small delay so a mousedown on an option lands first.
            setTimeout(() => this.close(), 120);
        },

        caretToken() {
            const el = this.$refs.field;
            if (!el) return null;

            const upToCaret = this.value.slice(0, el.selectionStart ?? this.value.length);
            const match = upToCaret.match(/(?:^|[^\p{L}\p{N}_@])(@[A-Za-z0-9-]*)$/u);

            return match ? match[1] : null;
        },

        async search(query) {
            try {
                const { data } = await get(`/mentions/search?q=${encodeURIComponent(query)}`);
                this.results = { talents: data.talents ?? [], brands: data.brands ?? [] };
                this.active = 0;
                this.open = true;
                this.positionList();

                const count = this.flat().length;
                this.announcement = count === 0
                    ? (window.famaLang?.noMentions ?? 'No one found')
                    : `${count} ${window.famaLang?.mentionResults ?? 'suggestions available'}`;
            } catch {
                this.close();
            } finally {
                this.loading = false;
            }
        },

        // 5AH: the list is portalled to <body>, so it positions itself from
        // the field's rect (fixed), flips above when short on room, and
        // repositions on scroll/resize while open. Under 640px the stylesheet
        // pins it as a bottom sheet regardless.
        positionList() {
            const rect = this.$refs.field.getBoundingClientRect();
            this.dropUp = window.innerHeight - rect.bottom < 300 && rect.top > 320;

            const top = this.dropUp ? 'auto' : `${rect.bottom + 4}px`;
            const bottom = this.dropUp ? `${window.innerHeight - rect.top + 4}px` : 'auto';

            this.popStyle = `inset-inline-start: ${rect.left}px; inline-size: ${rect.width}px; top: ${top}; bottom: ${bottom};`;

            if (!this.repositionHandler) {
                this.repositionHandler = () => this.open && this.positionList();
                window.addEventListener('scroll', this.repositionHandler, { passive: true, capture: true });
                window.addEventListener('resize', this.repositionHandler, { passive: true });
            }
        },

        pick(actor) {
            if (!actor) return;

            const el = this.$refs.field;
            const caret = el.selectionStart ?? this.value.length;
            const upToCaret = this.value.slice(0, caret);
            const replaced = upToCaret.replace(/@[A-Za-z0-9-]*$/u, '@' + actor.username + ' ');

            this.value = replaced + this.value.slice(caret);
            this.close();
            // Dispatch from the component ROOT, not $el: a mouse pick runs
            // inside the teleported pop, whose events bubble from <body> and
            // would never pass the field wrapper the parent listens on (5AH).
            this.$root.dispatchEvent(new CustomEvent('mention-picked', { detail: { actor }, bubbles: true }));
            this.$nextTick(() => {
                el.focus();
                el.selectionStart = el.selectionEnd = replaced.length;
                this.autogrow(el);
            });
        },

        close() {
            this.open = false;
            this.loading = false;
            this.results = { talents: [], brands: [] };
        },

        isActive(actor) {
            const current = this.flat()[this.active];
            return current?.type === actor.type && current?.id === actor.id;
        },

        optionId(actor) {
            return `${this.listId}-${actor.type}-${actor.id}`;
        },

        activeOptionId() {
            const current = this.flat()[this.active];
            return current ? this.optionId(current) : null;
        },

        autogrow(el, cap = maxRows) {
            const line = parseFloat(getComputedStyle(el).lineHeight) || 24;
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, cap * line + 24) + 'px';
        },
    }));

    Alpine.data('feedComposer', ({ skills = [], projects = [], username = '', displayName = '', avatarUrl = null, limits = {} }) => ({
        isOpen: false,
        mode: 'post',
        body: '',
        skills,
        projects,
        username,
        displayName,
        avatarUrl,
        limits,
        hintVisible: false,
        selectedSkills: [],
        selectedProject: null,
        media: [],
        chips: [],
        mentions: [],
        mediaError: '',
        error: '',
        submitting: false,
        previousFocus: null,
        seenUrls: new Set(),

        init() {
            // The FAB is the second entry point to the same composer (5AC).
            window.addEventListener('open-composer', () => this.open());
        },

        open() {
            this.previousFocus = document.activeElement;
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
            document.body.classList.add('modal-open');
            this.$nextTick(() => this.$refs.bodyField?.querySelector('textarea')?.focus());
        },

        // Escape/backdrop ask before discarding visible work (5AI); the draft
        // still survives in memory either way.
        requestClose() {
            if (!this.isOpen) return;

            const dirty = this.body.trim() !== '' || this.media.length > 0 || this.selectedProject !== null;

            if (!dirty) {
                this.close();
                return;
            }

            window.Alpine.store('confirm')
                .ask({
                    title: window.famaLang?.closeComposerTitle ?? 'Close the composer?',
                    message: window.famaLang?.closeComposerBody ?? 'Your draft is kept for this page visit.',
                    confirmLabel: window.famaLang?.closeLabel ?? 'Close',
                    tone: 'accent',
                })
                .then((ok) => ok && this.close());
        },

        close() {
            // The draft persists in memory — closing is never destructive.
            this.isOpen = false;
            document.body.style.overflow = '';
            document.body.classList.remove('modal-open');
            this.previousFocus?.focus?.();
        },

        // 5T: auto-grow wells from 1 row to a cap, then scroll. No handle.
        autogrow(el, maxRows = 6) {
            const line = parseFloat(getComputedStyle(el).lineHeight) || 24;
            const pad = 24; // block padding
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, maxRows * line + pad) + 'px';
        },

        canSubmit() {
            if (this.mode === 'share') return this.selectedProject !== null;

            return (this.body.trim() !== '' || this.media.length > 0)
                && this.body.length <= this.limits.body;
        },

        // The button says what's missing instead of sulking (5AI).
        publishHint() {
            if (this.mode === 'share' && this.selectedProject === null) {
                return window.famaLang?.hintPickProject ?? 'Pick a Project to share';
            }
            if (this.body.length > this.limits.body) {
                return window.famaLang?.hintTooLong ?? 'The body is over the limit';
            }
            if (this.body.trim() === '' && this.media.length === 0) {
                return window.famaLang?.hintEmpty ?? 'Write something or add media';
            }
            return '';
        },

        toggleAlt(index) {
            this.media[index].altOpen = !this.media[index].altOpen;
        },

        removeMedia(index) {
            const [removed] = this.media.splice(index, 1);
            if (removed?.preview) URL.revokeObjectURL(removed.preview);
        },

        // ----- body: URL chips (mentions live in the shared field, 5U) --------

        onBodyChanged() {
            this.detectUrls();
        },

        detectUrls() {
            const urls = this.body.match(/https?:\/\/[^\s<>"']+/gu) ?? [];

            urls.map((url) => url.replace(/[.,;:!?)…»”'"]+$/u, '')).forEach(async (url) => {
                if (this.seenUrls.has(url)) return;
                this.seenUrls.add(url);

                try {
                    // The chip renders from the instant parse even when
                    // enrichment later fails (ADR-F4).
                    const { data } = await post('/links/preview', { url });
                    this.chips.push({ url, provider: data.provider, handle: data.handle });
                } catch {
                    this.seenUrls.delete(url);
                }
            });

            this.chips = this.chips.filter((chip) => this.body.includes(chip.url));
        },

        // ----- media -----------------------------------------------------------

        addFiles(fileList) {
            this.mediaError = '';

            for (const file of Array.from(fileList)) {
                if (this.media.length >= this.limits.media) {
                    this.mediaError = window.famaLang?.tooManyMedia ?? `At most ${this.limits.media} items.`;
                    break;
                }

                const isImage = ['image/jpeg', 'image/png', 'image/webp'].includes(file.type);
                const isVideo = ['video/mp4', 'video/quicktime'].includes(file.type);

                if (!isImage && !isVideo) {
                    this.mediaError = window.famaLang?.badMediaType ?? 'Only jpg, png, webp, mp4 or mov.';
                    continue;
                }

                const maxKb = isImage ? this.limits.imageKb : this.limits.videoKb;

                if (file.size > maxKb * 1024) {
                    this.mediaError = window.famaLang?.mediaTooBig ?? 'That file is too large.';
                    continue;
                }

                this.media.push({
                    key: `${file.name}-${file.size}-${Math.random().toString(36).slice(2)}`,
                    file,
                    preview: isImage ? URL.createObjectURL(file) : '',
                    alt: '',
                    altOpen: false,
                });
            }
        },

        reorder(from, to) {
            const [item] = this.media.splice(from, 1);
            this.media.splice(to, 0, item);
        },

        toggleSkill(id) {
            this.selectedSkills = this.selectedSkills.includes(id)
                ? this.selectedSkills.filter((skillId) => skillId !== id)
                : [...this.selectedSkills, id];
        },

        // ----- the live credits preview ---------------------------------------

        creditsPreview() {
            const credits = this.selectedSkills.map((id) => ({
                label: (this.skills.find((skill) => skill.id === id)?.name ?? '').toUpperCase(),
                handle: this.username,
            }));

            this.mentions
                .filter((actor) => this.body.includes('@' + actor.username))
                .forEach((actor) => credits.push({ label: window.famaLang?.with ?? 'WITH', handle: actor.username }));

            return credits;
        },

        // ----- submit: optimistic insert + rollback ---------------------------

        async submit() {
            if (!this.canSubmit() || this.submitting) return;
            this.submitting = true;
            this.error = '';

            try {
                let created;

                if (this.mode === 'share') {
                    ({ data: created } = await post(`/projects/${this.selectedProject}/share`, {
                        body: this.body || null,
                        skill_ids: this.selectedSkills,
                    }));
                } else {
                    const form = new FormData();
                    form.append('post_type', this.media.length > 0 ? 'media' : 'note');
                    if (this.body.trim() !== '') form.append('body', this.body);
                    this.selectedSkills.forEach((id) => form.append('skill_ids[]', id));
                    this.media.forEach((item) => {
                        form.append('media[]', item.file);
                        form.append('media_alt[]', item.alt ?? '');
                    });

                    ({ data: created } = await post('/posts', form));
                }

                // Server-rendered frame replaces the optimistic slot.
                const { data: fragment } = await get(`/posts/${created.id}/fragment`);
                const spine = document.querySelector('[x-ref="spine"]');

                if (spine && fragment.html) {
                    const holder = document.createElement('template');
                    holder.innerHTML = fragment.html.trim();
                    const nodes = [...holder.content.childNodes];
                    spine.prepend(...nodes);
                    nodes.forEach((node) => node.nodeType === 1 && node.classList?.add('dl-insert'));
                }

                this.resetDraft();
                this.close();
            } catch (error) {
                this.error = error instanceof ApiError
                    ? (error.errors ? Object.values(error.errors).flat().join(' ') : error.message)
                    : (window.famaLang?.publishFailed ?? 'Publishing failed — your draft is kept, try again.');
            } finally {
                this.submitting = false;
            }
        },

        resetDraft() {
            this.body = '';
            this.media.forEach((item) => item.preview && URL.revokeObjectURL(item.preview));
            this.media = [];
            this.chips = [];
            this.mentions = [];
            this.selectedSkills = [];
            this.selectedProject = null;
            this.seenUrls = new Set();
        },
    }));


    Alpine.data('commentThread', ({ postId, isGuest = false }) => ({
        postId,
        comments: [],
        nextPage: null,
        loaded: false,
        posting: false,
        draft: '',
        replyTo: null,
        editingId: null,
        editDraft: '',
        gate: false,
        announcement: '',
        composerExpanded: false,
        isGuest,

        loadOnce() {
            if (this.loaded) return;
            this.loaded = true;
            this.fetchPage(1);
        },

        // 5AG: the composer stays a quiet 40px row until focused; guests get
        // the gate instead of an expansion.
        expandComposer() {
            if (this.isGuest) {
                this.gate = true;
                this.$refs.composer?.querySelector('textarea')?.blur();
                return;
            }
            this.composerExpanded = true;
        },

        collapseComposer() {
            if (this.draft.trim() === '') this.composerExpanded = false;
        },

        // 5T: auto-grow wells from 1 row to a cap, then scroll. No handle.
        autogrow(el, maxRows = 6) {
            const line = parseFloat(getComputedStyle(el).lineHeight) || 24;
            const pad = 24; // block padding
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, maxRows * line + pad) + 'px';
        },

        async fetchPage(page) {
            try {
                const { data, meta } = await get(`/posts/${this.postId}/comments?page=${page}`);
                this.comments = page === 1 ? data : [...this.comments, ...data];
                const pagination = meta?.pagination ?? {};
                this.nextPage = (pagination.current_page ?? 1) < (pagination.last_page ?? 1)
                    ? (pagination.current_page ?? 1) + 1
                    : null;
            } catch (error) {
                console.warn('feed: comments load failed', error);
            }
        },

        loadMore() {
            if (this.nextPage !== null) this.fetchPage(this.nextPage);
        },

        reply(comment, parent = null) {
            // Replying pre-fills the @username and focuses the field (5O);
            // depth-two re-attach happens server-side (ADR-F7).
            this.replyTo = { ...comment.author, parentId: (parent ?? comment).id };
            if (comment.author && !this.draft.startsWith('@' + comment.author.username)) {
                this.draft = '@' + comment.author.username + ' ' + this.draft;
            }
            this.$refs.composer?.querySelector('textarea')?.focus();
        },

        clearReply() {
            this.replyTo = null;
        },

        async submit() {
            if (this.posting || this.draft.trim() === '') return;
            this.posting = true;

            // Optimistic insert (5AG): a subdued row leads the thread until
            // the server confirms; failure keeps it with a retry affordance.
            const body = this.draft;
            const parentId = this.replyTo?.parentId ?? null;
            const temp = {
                id: 'tmp-' + Date.now(),
                is_pending: true,
                is_failed: false,
                body,
                body_html: body.replace(/&/g, '&amp;').replace(/</g, '&lt;'),
                author: null,
                likes_count: 0,
                replies: [],
                remaining_replies: 0,
                created_at: new Date().toISOString(),
            };
            this.comments = [temp, ...this.comments];
            this.draft = '';
            this.replyTo = null;

            try {
                await post(`/posts/${this.postId}/comments`, { body, parent_id: parentId });
                this.commentsCount += 1; // parent postFrame scope
                this.announcement = window.famaLang?.commentPosted ?? 'Comment posted';
                await this.fetchPage(1);
            } catch (error) {
                if (error instanceof ApiError && error.status === 401) {
                    this.comments = this.comments.filter((row) => row.id !== temp.id);
                    this.draft = body;
                    this.gate = true;
                } else {
                    temp.is_pending = false;
                    temp.is_failed = true;
                    temp.retryBody = body;
                    temp.retryParent = parentId;
                }
            } finally {
                this.posting = false;
            }
        },

        retry(row) {
            this.comments = this.comments.filter((item) => item.id !== row.id);
            this.draft = row.retryBody ?? row.body ?? '';
            this.submit();
        },

        async toggleCommentLike(comment) {
            const wasLiked = comment.liked_by_viewer;
            comment.liked_by_viewer = !wasLiked;
            comment.likes_count += comment.liked_by_viewer ? 1 : -1;

            try {
                const { data } = wasLiked
                    ? await del(`/comments/${comment.id}/like`)
                    : await post(`/comments/${comment.id}/like`);
                comment.liked_by_viewer = data.liked;
                comment.likes_count = data.likes_count;
            } catch (error) {
                comment.liked_by_viewer = wasLiked;
                comment.likes_count += wasLiked ? 1 : -1;
                if (error instanceof ApiError && error.status === 401) this.gate = true;
            }
        },

        async loadReplies(comment) {
            try {
                const { data } = await get(`/comments/${comment.id}/replies`);
                comment.replies = data;
                comment.remaining_replies = 0;
            } catch (error) {
                console.warn('feed: replies load failed', error);
            }
        },

        startEdit(comment) {
            this.editingId = comment.id;
            this.editDraft = comment.body ?? '';
        },

        async saveEdit(comment) {
            try {
                const { data } = await patch(`/comments/${comment.id}`, { body: this.editDraft });
                Object.assign(comment, data);
                this.editingId = null;
                this.announcement = window.famaLang?.commentUpdated ?? 'Comment updated';
            } catch (error) {
                console.warn('feed: edit failed', error);
            }
        },

        async destroy(comment) {
            try {
                await del(`/comments/${comment.id}`);
                if ((comment.replies_count ?? 0) > 0) {
                    comment.is_tombstone = true; // the thread survives (D12)
                } else {
                    this.comments = this.comments.filter((row) => row.id !== comment.id);
                }
                this.commentsCount = Math.max(0, this.commentsCount - 1);
            } catch (error) {
                console.warn('feed: delete failed', error);
            }
        },

        relativeTime(iso) {
            if (!iso) return '';
            const seconds = Math.max(1, (Date.now() - new Date(iso).getTime()) / 1000);
            if (seconds < 60) return Math.floor(seconds) + 's';
            if (seconds < 3600) return Math.floor(seconds / 60) + 'm';
            if (seconds < 86400) return Math.floor(seconds / 3600) + 'h';
            return Math.floor(seconds / 86400) + 'd';
        },

        countdown(until) {
            if (!until) return '';
            const remaining = new Date(until).getTime() - Date.now();
            if (remaining <= 0) return '';
            const minutes = Math.floor(remaining / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000);
            return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        },
    }));


    // ----- 5X: the private SAVED / LIKED dashboard collections ----------------
    Alpine.data('feedCollection', ({ mode = 'saved', dataUrl = '' } = {}) => ({
        mode,
        dataUrl,
        rows: [],
        pagination: null,
        loading: true,
        page: 1,
        skill: '',
        q: '',
        undo: { id: null, label: '', timer: null },

        init() {
            this.load();
        },

        async load() {
            this.loading = true;

            try {
                const params = new URLSearchParams({ mode: this.mode, page: this.page });
                if (this.skill) params.set('skill', this.skill);
                if (this.q) params.set('q', this.q);

                const { data, meta } = await get(`${this.dataUrl}?${params}`);
                this.rows = data;
                this.pagination = meta?.pagination ?? null;
            } catch (error) {
                console.warn('collection: load failed', error);
            } finally {
                this.loading = false;
            }
        },

        // Optimistic removal with an UNDO window (5X).
        async remove(row) {
            this.rows = this.rows.filter((item) => item.id !== row.id);
            clearTimeout(this.undo.timer);
            this.undo = {
                id: row.id,
                label: this.mode === 'saved'
                    ? (window.famaLang?.unsaved ?? 'Removed from Saved.')
                    : (window.famaLang?.unliked ?? 'Removed from Liked.'),
                timer: setTimeout(() => (this.undo = { id: null, label: '', timer: null }), 6000),
            };

            try {
                if (this.mode === 'saved') {
                    await del(`/posts/${row.id}/bookmark`);
                } else {
                    await del(`/posts/${row.id}/like`);
                }
            } catch (error) {
                console.warn('collection: remove failed', error);
                this.load();
            }
        },

        async undoRemove() {
            const id = this.undo.id;
            if (id === null) return;
            clearTimeout(this.undo.timer);
            this.undo = { id: null, label: '', timer: null };

            try {
                if (this.mode === 'saved') {
                    await post(`/posts/${id}/bookmark`);
                } else {
                    await post(`/posts/${id}/like`);
                }
            } finally {
                this.load();
            }
        },
    }));


    // ----- Prompt 5-follow: the FOLLOWING / FOLLOWERS management surface -------
    // Reuses the collection shell's shape over the follow graph. Unfollow is
    // optimistic — the row dims, slides out (280ms), counters move — with a
    // 5s UNDO that restores the row IN PLACE rather than re-sorting the list.
    Alpine.data('feedFollows', ({ tab = 'following', dataUrl = '', showTypeFilter = false, followsUrl = '', undoLabel = 'Unfollowed.' } = {}) => ({
        tab,
        dataUrl,
        showTypeFilter,
        followsUrl,
        undoLabel,
        rows: [],
        pagination: null,
        loading: true,
        page: 1,
        q: '',
        type: 'all',
        skill: '',
        sort: 'recent',
        undo: { row: null, index: null, label: '', timer: null },

        init() {
            // Honour a deep-linked ?tab=followers on first paint.
            const params = new URLSearchParams(window.location.search);
            if (params.get('tab') === 'followers') this.tab = 'followers';
            this.load();
        },

        // Switch sub-tab with a full navigation so the server re-renders the
        // shell (its Skill options belong to the visible side). Navigate the
        // CURRENT path — talent and brand mount this at different URLs, and the
        // locale prefix is already there — only the ?tab query changes.
        switchTab(next) {
            if (next === this.tab) return;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', next);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        },

        reload() {
            this.page = 1;
            this.load();
        },

        async load() {
            this.loading = true;

            try {
                const params = new URLSearchParams({ tab: this.tab, page: this.page, sort: this.sort });
                if (this.q) params.set('q', this.q);
                if (this.showTypeFilter && this.type !== 'all') params.set('type', this.type);
                if (this.skill) params.set('skill', this.skill);

                const { data, meta } = await get(`${this.dataUrl}?${params}`);
                this.rows = data.map((row) => ({ ...row, removing: false, busy: false }));
                this.pagination = meta?.pagination ?? null;
            } catch (error) {
                console.warn('follows: load failed', error);
            } finally {
                this.loading = false;
            }
        },

        profileUrl(row) {
            return window.fama.localizeUrl(row.type === 'brand' ? `/brands/${row.username}` : `/${row.username}`);
        },

        // Deterministic avatar-fallback tint from the username hash (matches
        // the rails), so a missing avatar still reads as designed.
        tint(row) {
            let hash = 0;
            for (const char of (row.username ?? '')) hash = (hash * 31 + char.charCodeAt(0)) >>> 0;
            return `background: color-mix(in oklch, var(--dl-surface-sunk), var(--dl-veil) ${20 + (hash % 40)}%);`;
        },

        // Optimistic unfollow: slide the row out over 280ms, then remove; the
        // DELETE fires immediately; a 5s UNDO restores it at its old index.
        async unfollow(row) {
            const index = this.rows.indexOf(row);
            row.removing = true;
            setTimeout(() => { this.rows = this.rows.filter((item) => item !== row); }, 280);

            try {
                await del(`/follow/${row.type}/${row.id}`);
            } catch (error) {
                console.warn('follows: unfollow failed', error);
                row.removing = false;
                this.load();
                return;
            }

            clearTimeout(this.undo.timer);
            this.undo = {
                row,
                index,
                label: this.undoLabel,
                timer: setTimeout(() => { this.undo = { row: null, index: null, label: '', timer: null }; }, 5000),
            };
        },

        async undoUnfollow() {
            const { row, index } = this.undo;
            if (row === null) return;
            clearTimeout(this.undo.timer);
            this.undo = { row: null, index: null, label: '', timer: null };

            row.removing = false;
            // Restore in place — not re-sorted to the top of the list.
            this.rows.splice(Math.min(index, this.rows.length), 0, row);

            try {
                await post(`/follow/${row.type}/${row.id}`);
            } catch (error) {
                console.warn('follows: undo failed', error);
                this.load();
            }
        },

        // FOLLOWERS tab: follow back an account that is not yet mutual.
        async followBack(row) {
            row.busy = true;

            try {
                await post(`/follow/${row.type}/${row.id}`);
                row.mutual = true;
                row.can_follow_back = false;
            } catch (error) {
                console.warn('follows: follow-back failed', error);
            } finally {
                row.busy = false;
            }
        },
    }));


    // ----- 5AA: the follow-suggestion rails -----------------------------------

    // ----- 5AC: the floating actions ------------------------------------------

    // ----- 5AE: the media lightbox --------------------------------------------

    // ----- 5AF: the toast rack -------------------------------------------------
    let toastSeq = 0;

    Alpine.store('feedToasts', {
        items: [],

        push({ message, undo = null, href = null, hrefLabel = '', hrefBlank = false }) {
            const id = ++toastSeq;
            const timer = setTimeout(() => this.dismiss(id), 5000);
            this.items.push({ id, message, undo, href, hrefLabel, hrefBlank, timer, remaining: 5000, startedAt: Date.now() });

            // Stack cap: the oldest toast makes room.
            if (this.items.length > 3) this.dismiss(this.items[0].id);
        },

        dismiss(id) {
            const toast = this.items.find((item) => item.id === id);
            if (toast) clearTimeout(toast.timer);
            this.items = this.items.filter((item) => item.id !== id);
        },

        pause(id) {
            const toast = this.items.find((item) => item.id === id);
            if (!toast) return;
            clearTimeout(toast.timer);
            toast.remaining -= Date.now() - toast.startedAt;
        },

        resume(id) {
            const toast = this.items.find((item) => item.id === id);
            if (!toast) return;
            toast.startedAt = Date.now();
            toast.timer = setTimeout(() => this.dismiss(id), Math.max(600, toast.remaining));
        },
    });

    // ----- 5AF: the shared report dialog --------------------------------------
    Alpine.data('reportDialog', () => ({
        open: false,
        confirmed: false,
        busy: false,
        type: 'post',
        id: null,
        quote: '',
        reason: '',
        note: '',
        error: '',
        opener: null,

        show({ type, id, quote = '' }) {
            this.type = type;
            this.id = id;
            this.quote = quote;
            this.reason = '';
            this.note = '';
            this.error = '';
            this.confirmed = false;
            this.opener = document.activeElement;
            this.open = true;
            document.body.classList.add('modal-open');
        },

        close() {
            this.open = false;
            document.body.classList.remove('modal-open');
            this.opener?.focus?.();
        },

        canSubmit() {
            return this.reason !== '' && (this.reason !== 'other' || this.note.trim() !== '');
        },

        async submit() {
            if (!this.canSubmit() || this.busy) return;
            this.busy = true;
            this.error = '';

            try {
                await post(`/${this.type}s/${this.id}/report`, {
                    reason: this.reason,
                    note: this.note || null,
                });
                this.confirmed = true;
            } catch (error) {
                if (error instanceof ApiError && error.status === 403) {
                    // Already reported — acknowledgement, not an error (5AF).
                    this.confirmed = true;
                } else if (error instanceof ApiError && error.status === 401) {
                    this.error = window.famaLang?.signInReport ?? 'Sign in to report this';
                } else {
                    this.error = error?.message ?? 'Something went wrong.';
                }
            } finally {
                this.busy = false;
            }
        },
    }));

    Alpine.data('mediaLightbox', ({ items = [], caption = '' } = {}) => ({
        items,
        caption,
        open: false,
        current: 0,
        touchX: null,
        opener: null,

        openAt(index) {
            this.opener = document.activeElement;
            this.current = Math.max(0, Math.min(items.length - 1, index));
            this.open = true;
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            this.preload();
            this.$nextTick(() => this.$refs.box?.focus());
        },

        close() {
            this.open = false;
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            this.opener?.focus?.();
        },

        next() {
            this.current = (this.current + 1) % this.items.length;
            this.preload();
        },

        prev() {
            this.current = (this.current - 1 + this.items.length) % this.items.length;
            this.preload();
        },

        onSwipe(endX) {
            if (this.touchX === null) return;
            const delta = endX - this.touchX;
            if (Math.abs(delta) > 40) (delta < 0 ? this.next() : this.prev());
            this.touchX = null;
        },

        // Adjacent images preload so paging never flashes.
        preload() {
            [this.current + 1, this.current - 1].forEach((index) => {
                const item = this.items[(index + this.items.length) % this.items.length];
                if (item && !item.is_video) new Image().src = item.full_url;
            });
        },
    }));

    Alpine.data('fabStack', ({ canPost = false, isGuest = false } = {}) => ({
        showTop: false,
        gate: false,
        filtersOpen: false,

        init() {
            // Appears after 1.5 viewport heights — IO on the marker strip,
            // never a scroll listener.
            const marker = document.querySelector('.fab-scroll-marker');
            if (marker) {
                new IntersectionObserver((entries) => {
                    this.showTop = entries.every((entry) => !entry.isIntersecting);
                }).observe(marker);
            }
        },

        newPost() {
            if (isGuest) {
                this.gate = !this.gate;
                return;
            }
            // One composer, two entry points (the top trigger and this FAB).
            window.dispatchEvent(new CustomEvent('open-composer'));
        },

        toTop() {
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const behavior = reduced ? 'auto' : 'smooth';
            // Scroll whichever element actually owns the scroll — window and the
            // scrolling element can differ across engines; hit both.
            window.scrollTo({ top: 0, left: 0, behavior });
            document.scrollingElement?.scrollTo?.({ top: 0, left: 0, behavior });
            document.documentElement?.scrollTo?.({ top: 0, behavior });
            // Move focus for keyboard/SR — WITHOUT letting focus() scroll, which
            // would otherwise snap the page back down to the heading and cancel
            // the smooth scroll to the very top.
            const h1 = document.querySelector('h1');
            if (h1) {
                h1.setAttribute('tabindex', '-1');
                h1.focus({ preventScroll: true });
            }
        },
    }));

    // The post overflow (⋯) menu. The Daylight frame clips its own box with a
    // clip-path (the notch), so an in-frame dropdown is invisible — this menu
    // is TELEPORTED to <body> and positioned fixed from the trigger rect, the
    // same escape the mention-pop uses. menuStyle is an OBJECT (not a string)
    // so the binding coexists with x-show without wiping its display:none.
    Alpine.data('overflowMenu', () => ({
        open: false,
        menuStyle: { position: 'fixed', top: '-9999px', insetInlineStart: '0px', width: '208px', zIndex: 'var(--z-popover)', background: 'transparent' },

        toggle() {
            this.open ? this.close() : this.show();
        },

        show() {
            this.open = true;
            this.$nextTick(() => this.position());
            this._onDoc = (event) => {
                if (!this.$refs.trigger.contains(event.target) && !this.$refs.menu?.contains(event.target)) this.close();
            };
            this._onMove = () => { if (this.open) this.position(); };
            // Defer binding so the opening click doesn't immediately close it.
            setTimeout(() => {
                document.addEventListener('click', this._onDoc, true);
                window.addEventListener('scroll', this._onMove, true);
                window.addEventListener('resize', this._onMove);
            }, 0);
        },

        close() {
            this.open = false;
            if (this._onDoc) document.removeEventListener('click', this._onDoc, true);
            if (this._onMove) {
                window.removeEventListener('scroll', this._onMove, true);
                window.removeEventListener('resize', this._onMove);
            }
        },

        position() {
            const rect = this.$refs.trigger.getBoundingClientRect();
            const width = 208;
            const rtl = document.documentElement.dir === 'rtl';
            // Align the menu's inline-end to the trigger's inline-end.
            let left = rtl ? rect.left : rect.right - width;
            left = Math.max(8, Math.min(left, window.innerWidth - width - 8));
            // Open downward, or flip up when the viewport bottom is close.
            const estimated = 220;
            const top = (window.innerHeight - rect.bottom > estimated)
                ? rect.bottom + 6
                : Math.max(8, rect.top - estimated - 6);
            this.menuStyle = { ...this.menuStyle, top: `${top}px`, left: `${left}px` };
        },
    }));

    Alpine.data('suggestionRail', ({ type = 'talents' } = {}) => ({
        type,
        loading: true,
        collapsed: false,
        nextOffset: null,
        announcement: '',
        undo: { active: false, label: '', kind: null, detail: null, timer: null },

        init() {
            // Rails hydrate AFTER the feed's first paint (5AA) — but rAF never
            // fires in a hidden tab, so a timeout fallback guarantees the rail
            // still loads when the page opens in the background.
            let fired = false;
            const kick = () => {
                if (fired) return;
                fired = true;
                this.load(0, 10);
            };
            requestAnimationFrame(kick);
            setTimeout(kick, 250);
        },

        async load(offset, limit) {
            this.loading = true;

            try {
                const { data } = await get(`/feed/suggestions/${this.type}?format=html&offset=${offset}&limit=${limit}`);

                if (offset === 0 && (data.items ?? []).length === 0) {
                    // Nothing to suggest: the rail collapses — never an empty box.
                    this.collapsed = true;
                    return;
                }

                if (data.html) {
                    const holder = document.createElement('template');
                    holder.innerHTML = data.html.trim();
                    const cards = [...holder.content.children];
                    this.$refs.list.append(...cards);
                    this.enterCards(cards);
                }

                this.nextOffset = data.next_offset;
            } catch (error) {
                console.warn('rail: load failed', error);
                if (offset === 0) this.collapsed = true;
            } finally {
                this.loading = false;
            }
        },

        showMore() {
            if (this.nextOffset !== null) this.load(this.nextOffset, 5);
        },

        // 8px rise entrances, staggered 40ms, capped at 5 — rails never get
        // the light response (that stays exclusive to feed frames).
        enterCards(cards) {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            cards.forEach((card, index) => {
                card.style.setProperty('--enter-delay', `${Math.min(index, 4) * 40}ms`);
                card.classList.add('rail-enter');
                requestAnimationFrame(() => {
                    card.classList.add('rail-enter-active');
                    setTimeout(() => card.classList.remove('rail-enter', 'rail-enter-active'), 400 + Math.min(index, 4) * 40);
                });
            });
        },

        onFollow(detail) {
            this.announcement = `${detail.name} — ${window.famaLang?.nowFollowing ?? 'now following'}`;
            this.arm('follow', detail, window.famaLang?.followedUndo ?? 'Followed.');
            // The card slides out itself after 2s; pull a replacement.
            setTimeout(() => this.replenish(), 2400);
        },

        onDismiss(detail) {
            this.announcement = window.famaLang?.dismissed ?? 'Suggestion dismissed';
            this.arm('dismiss', detail, window.famaLang?.dismissedUndo ?? 'Dismissed.');
            this.replenish();
        },

        arm(kind, detail, label) {
            clearTimeout(this.undo.timer);
            this.undo = {
                active: true,
                label,
                kind,
                detail,
                timer: setTimeout(() => this.settle(), 5000),
            };
        },

        // Dismissals only POST when the undo window lapses (the server-side
        // dismissal is permanent by design).
        async settle() {
            const { kind, detail } = this.undo;
            this.undo = { active: false, label: '', kind: null, detail: null, timer: null };

            if (kind === 'dismiss' && detail) {
                try {
                    await post('/feed/suggestions/dismiss', { type: detail.type, id: detail.id });
                } catch (error) {
                    console.warn('rail: dismiss failed', error);
                }
            }
        },

        async undoLast() {
            const { kind, detail, timer } = this.undo;
            clearTimeout(timer);
            this.undo = { active: false, label: '', kind: null, detail: null, timer: null };

            if (!detail) return;

            if (kind === 'follow') {
                try {
                    await del(`/follow/${detail.type}/${detail.id}`);
                } catch (error) {
                    console.warn('rail: unfollow failed', error);
                }
            }

            // Bring the card back (follow-undo or dismiss-undo alike).
            detail.restore?.();
        },

        replenish() {
            if (this.nextOffset !== null) this.load(this.nextOffset, 1);
        },
    }));

    Alpine.data('suggestionCard', (card) => ({
        card,
        // A TOP-LEVEL reactive mirror of the follower count — Alpine tracks a
        // plain component property reliably, whereas a deep card.* mutation
        // sometimes doesn't repaint. The card view binds to `followers`.
        followers: Number(card.followers_count ?? 0),
        following: false,
        leaving: false,
        removed: false,
        gated: false,
        busy: false,

        get profileUrl() {
            return window.fama.localizeUrl(card.type === 'brand' ? `/brands/${card.username}` : `/${card.username}`);
        },

        // Deterministic fallback tint from the username hash (round 3).
        fallbackTint() {
            let hash = 0;
            for (const char of (this.card.username ?? '')) hash = (hash * 31 + char.charCodeAt(0)) >>> 0;
            return `background: color-mix(in oklch, var(--dl-surface-sunk), var(--dl-veil) ${20 + (hash % 40)}%);`;
        },

        skillName(skill) {
            const name = skill?.name;
            if (name && typeof name === 'object') {
                return name[document.documentElement.lang] ?? name.en ?? Object.values(name)[0] ?? '';
            }
            return name ?? '';
        },

        detail() {
            return {
                type: this.card.type,
                id: this.card.id,
                name: this.card.display_name,
                restore: () => {
                    this.removed = false;
                    this.leaving = false;
                    this.following = false;
                    // Roll back the optimistic +1 (the follow was undone).
                    this.followers = Math.max(0, this.followers - 1);
                },
            };
        },

        async follow() {
            // Brand card = the notify-bell: a clean TOGGLE with a confirming
            // toast — NO rail "Followed. UNDO" box, NO slide-out (the card just
            // fills/unfills).
            if (this.card.type === 'brand') {
                return this.toggleNotify();
            }

            if (this.following) return;
            this.following = true; // optimistic
            this.followers += 1; // count updates instantly

            try {
                await post(`/follow/${this.card.type}/${this.card.id}`);
                this.$dispatch('rail-follow', this.detail());

                // Reads "Following" for 2s, then slides out (280ms house curve).
                setTimeout(() => {
                    this.leaving = true;
                    setTimeout(() => (this.removed = true), 300);
                }, 2000);
            } catch (error) {
                this.following = false;
                this.followers = Math.max(0, this.followers - 1); // roll back
                if (error instanceof ApiError && error.status === 401) {
                    // The inline sign-in prompt — the intent is already
                    // captured server-side and replays after login (ADR-F8).
                    this.gated = true;
                }
            }
        },

        async toggleNotify() {
            if (this.busy) return;
            this.busy = true;
            const was = this.following;
            this.following = !was; // optimistic fill/unfill
            this.followers = Math.max(0, this.followers + (was ? -1 : 1));

            try {
                if (was) {
                    await del(`/follow/brand/${this.card.id}`);
                } else {
                    await post(`/follow/brand/${this.card.id}`);
                }

                const name = this.card.display_name ?? '';
                window.Alpine.store('feedToasts')?.push({
                    message: was
                        ? (window.famaLang?.notifyOff ?? 'Opportunity alerts off for :name.').replaceAll(':name', name)
                        : (window.famaLang?.notifyOn ?? 'You will be notified when :name posts an opportunity.').replaceAll(':name', name),
                });
            } catch (error) {
                this.following = was;
                this.followers = Math.max(0, this.followers + (was ? 1 : -1)); // roll back
                if (error instanceof ApiError && error.status === 401) {
                    this.gated = true;
                }
            } finally {
                this.busy = false;
            }
        },

        dismiss() {
            // Immediate slide; the POST happens after the undo window (rail).
            this.leaving = true;
            setTimeout(() => (this.removed = true), 300);
            this.$dispatch('rail-dismiss', this.detail());
        },
    }));

    Alpine.data('castingCard', ({ id, following = false }) => ({
        id,
        following,
        busy: false,

        async toggle() {
            if (this.busy) return;
            this.busy = true;

            const was = this.following;
            this.following = !was;

            try {
                if (this.following) {
                    await post(`/follow/talent/${this.id}`);
                } else {
                    await del(`/follow/talent/${this.id}`);
                }
            } catch (error) {
                this.following = was;
                if (error instanceof ApiError && error.status === 401) {
                    window.location.href = window.fama.localizeUrl('/login?role=brand');
                }
            } finally {
                this.busy = false;
            }
        },
    }));
});
