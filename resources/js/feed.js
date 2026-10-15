import Alpine from 'alpinejs';
import { get, post, del } from './http';

/**
 * Feed collections shared with the DASHBOARDS (the brand dashboard still
 * mounts these; the talent Activity page moved to the fama-ui design in
 * ui/feed.js):
 *
 * feedCollection — the private SAVED / LIKED lists (5X).
 * feedFollows    — the FOLLOWING / FOLLOWERS management surface (5-follow).
 * feedToasts     — the stacked toast store (public-layout + dashboards).
 *
 * The public feed's own components (stream, post card, comments, composer,
 * mentions, dialogs) live in resources/js/ui/feed.js since the fama-ui
 * redesign (2026-10-12).
 */

document.addEventListener('alpine:init', () => {
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
});
