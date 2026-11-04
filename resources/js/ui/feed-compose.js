/**
 * fama-ui writing surfaces (feed.html "New post" dialog + post.html thread).
 *
 * famaMention   — any field: "@" credits someone (/mentions/search, people you
 *                 follow first), "#" tags a skill when a catalog is given; the
 *                 ul.sugg list sits under the caret. Bind with x-modelable.
 * feedComposer  — the New post / Edit post dialog: post or share-a-project,
 *                 media tiles (drag to reorder, alt text), link chips, counter,
 *                 "what's missing" footer, discard confirm.
 * commentThread — the post page thread: newest first, 10 a page, replies one
 *                 level deep, likes, 15-minute edit countdown, tombstones,
 *                 Retry on a failed send.
 */
import { get, post, patch, del, ApiError } from '../http';
import { L, fmt, initials, ago, profileUrl, toast, gate, asVisitor, platformName } from './feed-util';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;
    let seq = 0;

    // ----- @ mentions + # skill tags ------------------------------------------

    Alpine.data('famaMention', ({ catalog = null, grow = 0 } = {}) => ({
        value: '',
        open: false,
        mode: 'at',
        people: [],
        tags: [],
        active: 0,
        listId: `sugg-${++seq}`,
        timer: null,
        initials,

        /** Text before the caret. */
        beforeCaret() {
            const el = this.$refs.field;
            return this.value.slice(0, el?.selectionStart ?? this.value.length);
        },

        /** On every keystroke: grow, then look for an @ or # token at the caret. */
        onInput() {
            const el = this.$refs.field;
            if (grow && el) {
                el.style.height = 'auto';
                el.style.height = Math.min(el.scrollHeight, grow) + 'px';
            }
            const before = this.beforeCaret();
            clearTimeout(this.timer);

            const hash = catalog ? before.match(/(?:^|\s)#([a-z0-9]*)$/i) : null;
            if (hash) { this.showTags(hash[1].toLowerCase()); return; }

            const at = before.match(/(?:^|[^\p{L}\p{N}_@])@([A-Za-z0-9-]*)$/u);
            if (!at || at[1].length < 1) { this.close(); return; }
            this.timer = setTimeout(() => this.search(at[1]), 200);
        },

        /** # tags: any skill, yours first; a speciality match names itself. */
        showTags(q) {
            const hit = (name) => (' ' + String(name).toLowerCase()).includes(' ' + q);
            this.tags = catalog.map((k) => {
                if (!q || k.tag.toLowerCase().startsWith(q) || hit(k.name)) return { ...k, sub: k.own ? L('yours', 'yours') : '' };
                const spec = (k.specs ?? []).find((s) => hit(s));
                return spec ? { ...k, sub: spec } : null;
            }).filter(Boolean).sort((a, b) => Number(b.own) - Number(a.own)).slice(0, 5);
            this.mode = 'tag';
            this.active = 0;
            this.open = this.tags.length > 0;
            this.place();
        },

        /** @ people, ranked by the server (followed first). */
        async search(q) {
            try {
                const { data } = await get(`/mentions/search?q=${encodeURIComponent(q)}`);
                this.people = [...(data.talents ?? []), ...(data.brands ?? [])].slice(0, 5);
                this.mode = 'at';
                this.active = 0;
                this.open = this.people.length > 0;
                this.place();
            } catch {
                this.close();
            }
        },

        /** Arrows move, Enter/Tab pick, Escape closes (without closing a dialog). */
        onKeydown(event) {
            if (!this.open) return;
            const list = this.mode === 'tag' ? this.tags : this.people;
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                this.active = (this.active + (event.key === 'ArrowDown' ? 1 : -1) + list.length) % list.length;
            } else if (event.key === 'Enter' || event.key === 'Tab') {
                event.preventDefault();
                this.mode === 'tag' ? this.pickTag(list[this.active]) : this.pick(list[this.active]);
            } else if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                this.close();
            }
        },

        /** Close shortly after blur so a click on a suggestion lands first. */
        onBlur() { setTimeout(() => this.close(), 150); },

        /** Close the list. */
        close() { this.open = false; },

        /** Replace the token before the caret and put the caret after it. */
        replaceToken(pattern, insert) {
            const el = this.$refs.field;
            const caret = el.selectionStart ?? this.value.length;
            const before = this.value.slice(0, caret).replace(pattern, insert);
            this.value = before + this.value.slice(caret);
            this.close();
            this.$nextTick(() => { el.focus(); el.setSelectionRange(before.length, before.length); });
        },

        /** Insert @username and tell the composer who was credited. */
        pick(p) {
            if (!p) return;
            this.replaceToken(/@[A-Za-z0-9-]*$/u, '@' + p.username + ' ');
            this.$root.dispatchEvent(new CustomEvent('mention-picked', { detail: { actor: p }, bubbles: true }));
        },

        /** Insert #SkillTag. */
        pickTag(t) {
            if (!t) return;
            this.replaceToken(/#[a-z0-9]*$/i, '#' + t.tag + ' ');
        },

        /** Place the list just under the caret (textareas); inputs drop below. */
        place() {
            const el = this.$refs.field;
            if (!el || el.tagName !== 'TEXTAREA') return;
            // Positioned imperatively: a :style binding beside x-show can
            // rewrite the display x-show manages and keep the list hidden.
            this.$nextTick(() => {
                const parent = el.offsetParent;
                if (!parent) return;
                const cs = getComputedStyle(el);
                const mirror = document.createElement('div');
                ['fontFamily', 'fontSize', 'fontWeight', 'lineHeight', 'letterSpacing', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft', 'borderTopWidth', 'borderLeftWidth', 'boxSizing']
                    .forEach((k) => { mirror.style[k] = cs[k]; });
                Object.assign(mirror.style, { position: 'absolute', visibility: 'hidden', whiteSpace: 'pre-wrap', wordWrap: 'break-word', width: el.offsetWidth + 'px', top: '0', left: '0' });
                mirror.textContent = this.beforeCaret();
                const mark = document.createElement('span');
                mark.textContent = '​';
                mirror.appendChild(mark);
                parent.appendChild(mirror);
                const x = mark.offsetLeft;
                const y = mark.offsetTop + (parseFloat(cs.lineHeight) || 20);
                mirror.remove();
                const list = this.$root.querySelector('.sugg');
                const width = list?.offsetWidth ?? 240;
                const left = Math.max(0, Math.min(el.offsetLeft + x - 8, parent.clientWidth - width - 8));
                if (list) {
                    list.style.left = left + 'px';
                    list.style.top = (el.offsetTop + y - el.scrollTop + 4) + 'px';
                    list.style.insetInlineStart = 'auto';
                }
            });
        },
    }));

    // ----- the New post dialog ------------------------------------------------

    Alpine.data('feedComposer', ({ catalog = [], projects = [], published = false, limits = {} } = {}) => ({
        catalog,
        projects,
        published,
        limits,
        mode: 'post',
        editing: null,
        body: '',
        media: [],
        chips: [],
        seen: new Set(),
        picked: [],
        selectedProject: null,
        mediaError: '',
        error: '',
        submitting: false,
        dirty: false,
        dragFrom: null,

        /** Typing marks the draft dirty and refreshes the link chips. */
        init() {
            this.$watch('body', () => { this.dirty = true; this.detectUrls(); });
            this.$root.addEventListener('mention-picked', (e) => this.picked.push(e.detail.actor));
            // The side bar's Create menu lands on /feed?new=post (or ?new=photo, which focuses the
            // media drop): open the composer, then drop the flag so a reload or Back doesn't reopen it.
            const url = new URL(window.location.href);
            const wanted = url.searchParams.get('new');
            if (wanted === 'post' || wanted === 'photo') {
                url.searchParams.delete('new');
                window.history.replaceState(window.history.state, '', url.toString());
                this.$nextTick(() => this.open(wanted === 'photo' ? { mode: 'photo' } : {}));
            }
        },

        /** A fresh post (mode photo/video focuses the media drop; project → share). */
        open({ mode = 'post' } = {}) {
            this.reset();
            this.mode = mode === 'project' ? 'share' : 'post';
            this.$refs.dlg.showModal();
            this.$nextTick(() => {
                this.dirty = false;
                const target = (mode === 'photo' || mode === 'video') ? this.$root.querySelector('.drop') : this.$root.querySelector('#npText');
                target?.focus();
            });
        },

        /** Edit a post's text + skills inside its window. */
        openEdit({ id, body }) {
            this.reset();
            this.editing = { id };
            this.body = body ?? '';
            this.$refs.dlg.showModal();
            this.$nextTick(() => { this.dirty = false; this.$root.querySelector('#npText')?.focus(); });
        },

        /** Clear the draft. */
        reset() {
            this.media.forEach((m) => m.preview && URL.revokeObjectURL(m.preview));
            Object.assign(this, { editing: null, body: '', media: [], chips: [], picked: [], selectedProject: null, mediaError: '', error: '', submitting: false });
            this.seen = new Set();
        },

        /** Cancel / Escape / ×: confirm before losing what was written. */
        requestClose() {
            const hasWork = this.body.trim() !== '' || this.media.length > 0 || this.selectedProject !== null;
            if (!this.dirty || !hasWork) { this.close(); return; }
            this.$store.confirm.ask({
                title: L('closeComposerTitle', 'Discard this post?'),
                message: L('closeComposerBody', 'What you wrote will be lost.'),
                confirmLabel: L('closeLabel', 'Discard'),
                tone: 'danger',
            }).then((ok) => ok && this.close());
        },

        /** Close the dialog. */
        close() {
            this.dirty = false;
            this.$refs.dlg.close();
        },

        /** The textarea prompt for the current mode. */
        placeholder() {
            return this.mode === 'share'
                ? L('projTextPh', 'Say something about it (optional). Type @ to credit someone, # to tag a skill.')
                : L('textPh', 'What are you working on? Type @ to credit someone, # to tag a skill.');
        },

        /** "123 / 2,200". */
        counter() { return fmt(L('counter', ':count / 2,200'), { count: this.body.length.toLocaleString() }); },

        /** What's missing before Publish works ('' when ready). */
        need() {
            if (this.body.length > (this.limits.body ?? 2200)) return L('needMax', "That's over 2,200 characters.");
            if (this.editing) return '';
            if (this.mode === 'share') return this.selectedProject === null ? L('needProj', 'Pick a project to share.') : '';
            return this.body.trim() || this.media.length ? '' : L('need', 'Add text or media to publish.');
        },

        /** Pasted links become chips (the instant parse; enrichment is async). */
        detectUrls() {
            const urls = (this.body.match(/https?:\/\/[^\s<>"']+/gu) ?? []).map((u) => u.replace(/[.,;:!?)…»”'"]+$/u, ''));
            urls.forEach(async (url) => {
                if (this.seen.has(url)) return;
                this.seen.add(url);
                try {
                    const { data } = await post('/links/preview', { url });
                    this.chips.push({ url, label: platformName(data.provider, url) });
                } catch {
                    this.seen.delete(url);
                }
            });
            this.chips = this.chips.filter((c) => this.body.includes(c.url));
        },

        /** Add picked/dropped files within the type, size and count caps. */
        addFiles(list) {
            this.mediaError = '';
            for (const file of Array.from(list ?? [])) {
                if (this.media.length >= this.limits.media) { this.mediaError = L('tooManyMedia', 'That is more media than a post can carry.'); break; }
                const isImage = ['image/jpeg', 'image/png', 'image/webp'].includes(file.type);
                const isVideo = ['video/mp4', 'video/quicktime'].includes(file.type);
                if (!isImage && !isVideo) { this.mediaError = L('badMediaType', 'That file type is not supported.'); continue; }
                if (file.size > (isImage ? this.limits.imageKb : this.limits.videoKb) * 1024) { this.mediaError = L('mediaTooBig', 'That file is too large.'); continue; }
                this.media.push({ key: `${file.name}-${file.size}-${Math.random().toString(36).slice(2)}`, file, preview: isImage ? URL.createObjectURL(file) : '', alt: '' });
                this.dirty = true;
            }
        },

        /** Files dropped anywhere on the dialog. */
        onFileDrop(event) {
            if (this.mode === 'post' && !this.editing && event.dataTransfer?.files?.length) this.addFiles(event.dataTransfer.files);
        },

        /** Remove one tile. */
        removeMedia(i) {
            const [gone] = this.media.splice(i, 1);
            if (gone?.preview) URL.revokeObjectURL(gone.preview);
        },

        /** Drag a tile onto another to reorder. */
        dropOn(i) {
            if (this.dragFrom === null || this.dragFrom === i) return;
            const [item] = this.media.splice(this.dragFrom, 1);
            this.media.splice(i, 0, item);
            this.dragFrom = null;
        },

        /** The post's skills: the author's OWN skills named by #tag in the text. */
        taggedSkillIds() {
            const tags = new Set((this.body.match(/#([a-z0-9]+)/gi) ?? []).map((t) => t.slice(1).toLowerCase()));
            return this.catalog.filter((k) => k.own && tags.has(k.tag.toLowerCase())).map((k) => k.id);
        },

        /** Publish (or save the edit), then put the server-rendered card in place. */
        async submit() {
            if (this.need() || this.submitting) return;
            this.submitting = true;
            this.error = '';
            const skillIds = this.taggedSkillIds();
            try {
                if (this.editing) {
                    const id = this.editing.id;
                    await patch(`/posts/${id}`, { body: this.body, skill_ids: skillIds });
                    const old = document.getElementById(`post-${id}`);
                    const { data } = await get(`/posts/${id}/fragment${old?.dataset.full ? '?full=1' : ''}`);
                    if (old && data.html) old.outerHTML = data.html;
                    this.close();
                    toast(L('updated', 'Post updated'));
                    return;
                }

                let created;
                if (this.mode === 'share') {
                    ({ data: created } = await post(`/projects/${this.selectedProject}/share`, { body: this.body || null, skill_ids: skillIds }));
                } else {
                    const form = new FormData();
                    form.append('post_type', this.media.length ? 'media' : 'note');
                    if (this.body.trim()) form.append('body', this.body);
                    skillIds.forEach((id) => form.append('skill_ids[]', id));
                    this.media.forEach((m) => { form.append('media[]', m.file); form.append('media_alt[]', m.alt ?? ''); });
                    ({ data: created } = await post('/posts', form));
                }

                const named = [...new Map(this.picked.filter((p) => this.body.includes('@' + p.username)).map((p) => [p.username, p.display_name])).values()];
                const { data } = await get(`/posts/${created.id}/fragment`);
                window.dispatchEvent(new CustomEvent('post-created', { detail: { html: data.html } }));
                this.close();
                this.reset();
                toast(named.length ? this.postedNotice(named) : L('posted', 'Posted to the feed'));
            } catch (error) {
                if (gate(error, 'post')) { this.close(); return; }
                this.error = error instanceof ApiError
                    ? (error.errors ? Object.values(error.errors).flat().join(' ') : error.message)
                    : L('publishFailed', 'Could not publish — try again.');
            } finally {
                this.submitting = false;
            }
        },

        /** "Posted. Lina and Omar are notified." */
        postedNotice(names) {
            const and = L('and', 'and');
            const who = names.length === 1 ? names[0]
                : names.length === 2 ? `${names[0]} ${and} ${names[1]}`
                    : `${names[0]} ${and} ${fmt(L('others', ':count others'), { count: names.length - 1 })}`;
            return fmt(L('postedN', 'Posted. :names :verb notified.'), { names: who, verb: names.length === 1 ? L('is', 'is') : L('are', 'are') });
        },
    }));

    // ----- the post-page thread -----------------------------------------------

    Alpine.data('commentThread', ({ postId, count = 0, isGuest = false }) => ({
        postId,
        count,
        isGuest,
        comments: [],
        nextPage: null,
        loaded: false,
        rootDraft: '',
        replyDraft: '',
        replyingTo: null,
        replyUser: '',
        replyName: '',
        editingId: null,
        editDraft: '',
        sending: false,
        rootError: '',
        replyError: '',
        sentTimes: [],
        now: Date.now(),
        initials,
        ago,
        profileUrl,

        /** Load the newest page; tick the edit countdowns. */
        init() {
            this.fetchPage(1);
            setInterval(() => { this.now = Date.now(); }, 1000);
        },

        /** One page of top-level comments (newest first, 10 each). */
        async fetchPage(page) {
            try {
                const { data, meta } = await get(`/posts/${this.postId}/comments?page=${page}`);
                this.comments = page === 1 ? data : [...this.comments, ...data];
                const p = meta?.pagination ?? {};
                this.nextPage = (p.current_page ?? 1) < (p.last_page ?? 1) ? (p.current_page ?? 1) + 1 : null;
            } catch (error) {
                console.warn('thread: load failed', error);
            } finally {
                this.loaded = true;
            }
        },

        /** "Show older comments". */
        loadMore() { if (this.nextPage !== null) this.fetchPage(this.nextPage); },

        /** "123 / 2,200". */
        counter(n) { return fmt(L('counter', ':count / 2,200'), { count: n.toLocaleString() }); },

        /** "Reply to @lina-haddad". */
        replyPlaceholder() { return fmt(L('replyTo', 'Reply to @:u'), { u: this.replyUser }); },

        /** "View 3 more replies". */
        moreRepliesLabel(n) {
            return fmt(n === 1 ? L('moreReply1', 'View :count more reply') : L('moreReplyN', 'View :count more replies'), { count: n.toLocaleString() });
        },

        /**
         * Reply under the top-level comment, starting with the person's @username.
         * A visitor gets the sign-in dialog instead (prototype: Reply → signInPrompt).
         */
        startReply(c, top) {
            if (this.isGuest) { window.famaSignIn('comment'); return; }
            this.replyingTo = top.id;
            this.replyUser = c.author?.username ?? '';
            this.replyName = c.author?.display_name ?? '';
            this.replyDraft = this.replyUser ? '@' + this.replyUser + ' ' : '';
            this.replyError = '';
            setTimeout(() => {
                const el = document.getElementById('ta' + top.id);
                if (el) { el.focus(); el.setSelectionRange(el.value.length, el.value.length); }
            }, 30);
        },

        /** Drop the reply. */
        cancelReply() { this.replyingTo = null; this.replyDraft = ''; },

        /** Send a comment (parent = null) or a reply; pending until confirmed. */
        async send(parent) {
            const body = (parent ? this.replyDraft : this.rootDraft).trim();
            const errorKey = parent ? 'replyError' : 'rootError';
            if (!body || this.sending) return;
            if (body.length > 2200) { this[errorKey] = L('commentMax', 'Up to 2,200 characters.'); return; }
            this.sentTimes = this.sentTimes.filter((t) => Date.now() - t < 60000);
            if (this.sentTimes.length >= 10) { this[errorKey] = L('commentSlow', "You're commenting fast. Wait a moment and try again."); return; }
            this.sentTimes.push(Date.now());
            this[errorKey] = '';
            this.sending = true;

            const temp = {
                id: 'tmp-' + Date.now(), is_pending: true, is_failed: false, is_mine: true, body,
                body_html: body.replace(/&/g, '&amp;').replace(/</g, '&lt;'), author: window.famaMe ?? null,
                likes_count: 0, replies: [], remaining_replies: 0, created_at: new Date().toISOString(),
            };
            let row;
            if (parent) {
                parent.replies = [...(parent.replies ?? []), temp];
                row = parent.replies[parent.replies.length - 1];
                this.replyDraft = '';
                this.replyingTo = null;
            } else {
                this.comments.unshift(temp);
                row = this.comments[0];
                this.rootDraft = '';
            }

            try {
                const { data } = await post(`/posts/${this.postId}/comments`, { body, parent_id: parent?.id ?? null });
                Object.assign(row, data, { is_pending: false });
                this.bump(1);
            } catch (error) {
                const drop = () => {
                    if (parent) parent.replies = parent.replies.filter((r) => r !== row);
                    else this.comments = this.comments.filter((c) => c !== row);
                    if (parent) { this.replyingTo = parent.id; this.replyDraft = body; } else { this.rootDraft = body; }
                };
                if (gate(error, 'comment')) {
                    drop();
                } else if (error instanceof ApiError && (error.status === 429 || error.status === 422)) {
                    drop();
                    this[errorKey] = error.status === 429
                        ? L('commentSlow', "You're commenting fast. Wait a moment and try again.")
                        : (error.errors ? Object.values(error.errors).flat().join(' ') : error.message);
                } else {
                    Object.assign(row, { is_pending: false, is_failed: true, retryParent: parent });
                }
            } finally {
                this.sending = false;
            }
        },

        /** Re-send a failed comment. */
        retry(c) {
            const parent = c.retryParent ?? null;
            if (parent) { parent.replies = parent.replies.filter((r) => r !== c); this.replyDraft = c.body; }
            else { this.comments = this.comments.filter((x) => x !== c); this.rootDraft = c.body; }
            this.send(parent);
        },

        /** Move the post's comment count (the card above listens). */
        bump(delta) {
            this.count = Math.max(0, this.count + delta);
            window.dispatchEvent(new CustomEvent('comment-count', { detail: { postId: this.postId, delta } }));
        },

        /** Like / unlike a comment, optimistic. */
        async toggleLike(c) {
            if (await asVisitor('like', () => post(`/comments/${c.id}/like`))) return;
            const was = !!c.liked_by_viewer;
            c.liked_by_viewer = !was;
            c.likes_count = (c.likes_count ?? 0) + (was ? -1 : 1);
            try {
                const { data } = was ? await del(`/comments/${c.id}/like`) : await post(`/comments/${c.id}/like`);
                c.liked_by_viewer = data.liked;
                c.likes_count = data.likes_count;
            } catch (error) {
                c.liked_by_viewer = was;
                c.likes_count += was ? 1 : -1;
                if (!gate(error, 'like')) console.warn('thread: like failed', error);
            }
        },

        /** Milliseconds left in the author's edit window. */
        editLeft(c) {
            if (!c.is_mine || !c.editable_until || c.is_pending) return 0;
            return Math.max(0, new Date(c.editable_until).getTime() - this.now);
        },

        /** "edit 12:41 left". */
        editTimer(c) {
            const left = this.editLeft(c);
            const time = Math.floor(left / 60000) + ':' + String(Math.floor((left % 60000) / 1000)).padStart(2, '0');
            return fmt(L('commentEditLeft', 'edit :time left'), { time });
        },

        /** Edit in place. */
        startEdit(c) {
            this.editingId = c.id;
            this.editDraft = c.body ?? '';
            this.$nextTick(() => document.getElementById('e' + c.id)?.focus());
        },

        /** Save the edit. */
        async saveEdit(c) {
            const body = this.editDraft.trim();
            if (!body) return;
            try {
                const { data } = await patch(`/comments/${c.id}`, { body });
                Object.assign(c, data);
                this.editingId = null;
            } catch (error) {
                console.warn('thread: edit failed', error);
            }
        },

        /** Delete (confirmed); a comment with replies stays as "Comment deleted". */
        remove(c, parent) {
            this.$store.confirm.ask({
                title: L('commentDelT', 'Delete this comment?'),
                message: L('commentDelB', 'Replies to it stay, under a “Comment deleted” note.'),
                confirmLabel: L('del', 'Delete'),
                tone: 'danger',
            }).then(async (ok) => {
                if (!ok) return;
                try {
                    await del(`/comments/${c.id}`);
                    if (parent) parent.replies = parent.replies.filter((r) => r !== c);
                    else if ((c.replies_count ?? 0) > 0 || (c.replies ?? []).length) c.is_tombstone = true;
                    else this.comments = this.comments.filter((x) => x !== c);
                    this.bump(-1);
                } catch (error) {
                    console.warn('thread: delete failed', error);
                }
            });
        },

        /** Report a comment. */
        report(c) {
            if (this.isGuest) { window.famaSignIn('report'); return; }
            window.dispatchEvent(new CustomEvent('open-report', { detail: { type: 'comment', id: c.id } }));
        },

        /** "View more replies": page through the rest, oldest first. */
        async loadReplies(c) {
            const page = c._replyPage ?? 1;
            try {
                const { data, meta } = await get(`/comments/${c.id}/replies?page=${page}`);
                c.replies = page === 1 ? data : [...(c.replies ?? []), ...data];
                const p = meta?.pagination ?? {};
                c._replyPage = page + 1;
                c.remaining_replies = Math.max(0, (p.total ?? c.replies.length) - c.replies.length);
            } catch (error) {
                console.warn('thread: replies load failed', error);
            }
        },
    }));
});
