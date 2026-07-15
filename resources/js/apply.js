/**
 * Apply-to-project modal (public project detail). A talent writes a rich-text brief
 * (why they're a fit, @-mentioning their own projects) and attaches files; on submit
 * it POSTs to the applications endpoint, which opens the talent↔brand contract and lands
 * the talent in the contract room. The brief is sanitized server-side before it is ever
 * stored or rendered. Teleported, scroll-locked, focus-trapped.
 */
import { get, post, ApiError } from './http';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.data('applyModal', (config = {}) => ({
        projectId: config.projectId,
        labels: config.labels || {},

        modalOpen: false,
        modalActive: false,
        submitting: false,
        error: '',
        isEmpty: true,
        files: [],
        _scrollY: 0,
        triggerEl: null,

        // @-mention state
        mentionOpen: false,
        mentionItems: [],
        mentionIndex: 0,
        mentionQuery: '',
        mentionTop: 0,
        mentionLeft: 0,
        _mentionTimer: null,

        open() {
            if (this.modalOpen) return;
            this.error = '';
            this.triggerEl = document.activeElement;
            this.modalOpen = true;
            this.lockScroll();
            this.$nextTick(() => {
                this.modalActive = true;
                if (this.$refs.editor) this.$refs.editor.innerHTML = '';
                this.files = [];
                this.isEmpty = true;
                this.$refs.editor?.focus();
            });
        },
        close() {
            if (!this.modalOpen) return;
            this.modalActive = false;
            this.closeMentions();
            this.unlockScroll();
            const ms = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 200;
            const done = () => { this.modalOpen = false; this.$nextTick(() => this.triggerEl?.focus?.()); };
            if (ms === 0) done();
            else setTimeout(done, ms + 20);
        },

        // --- Rich editor (contenteditable) ----------------------------------
        format(cmd) {
            this.$refs.editor?.focus();
            document.execCommand(cmd, false, null);
            this.syncEmpty();
        },
        syncEmpty() {
            const el = this.$refs.editor;
            if (!el) return;
            this.isEmpty = el.textContent.trim() === '' && !el.querySelector('.mention');
        },
        onInput() {
            this.syncEmpty();
            this.detectMention();
        },
        onKeydown(e) {
            if (!this.mentionOpen) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); this.mentionIndex = Math.min(this.mentionIndex + 1, this.mentionItems.length - 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); this.mentionIndex = Math.max(this.mentionIndex - 1, 0); }
            else if (e.key === 'Enter' || e.key === 'Tab') { if (this.mentionItems[this.mentionIndex]) { e.preventDefault(); this.pickMention(this.mentionItems[this.mentionIndex]); } }
            else if (e.key === 'Escape') { e.preventDefault(); this.closeMentions(); }
        },

        // --- @-mentions -----------------------------------------------------
        detectMention() {
            const sel = window.getSelection();
            if (!sel || !sel.rangeCount) return this.closeMentions();
            const range = sel.getRangeAt(0);
            const node = range.startContainer;
            if (node.nodeType !== Node.TEXT_NODE) return this.closeMentions();
            const before = node.textContent.slice(0, range.startOffset);
            const m = before.match(/@([^\s@]{0,30})$/);
            if (!m) return this.closeMentions();
            this.mentionQuery = m[1];
            this.positionMentions(range);
            this.fetchMentions();
        },
        positionMentions(range) {
            const rect = range.getClientRects()[0] || range.getBoundingClientRect();
            const wrap = this.$refs.editorWrap.getBoundingClientRect();
            this.mentionTop = (rect && rect.height ? rect.bottom : wrap.top + 28) - wrap.top + 4;
            this.mentionLeft = Math.max(0, (rect ? rect.left : wrap.left) - wrap.left);
        },
        fetchMentions() {
            clearTimeout(this._mentionTimer);
            this._mentionTimer = setTimeout(async () => {
                try {
                    const { data } = await get(`/talent/applications/mentions?q=${encodeURIComponent(this.mentionQuery)}`);
                    this.mentionItems = data.projects || [];
                    this.mentionIndex = 0;
                    this.mentionOpen = this.mentionItems.length > 0;
                } catch { this.closeMentions(); }
            }, 150);
        },
        closeMentions() { this.mentionOpen = false; this.mentionItems = []; },

        pickMention(project) {
            const sel = window.getSelection();
            if (!sel || !sel.rangeCount) return;
            const range = sel.getRangeAt(0);
            const node = range.startContainer;
            if (node.nodeType !== Node.TEXT_NODE) return;
            const text = node.textContent;
            const caret = range.startOffset;
            const m = text.slice(0, caret).match(/@([^\s@]{0,30})$/);
            if (!m) return;
            const start = caret - m[0].length;
            const afterText = text.slice(caret);
            const parent = node.parentNode;

            node.textContent = text.slice(0, start);
            const span = document.createElement('span');
            span.className = 'mention';
            span.setAttribute('contenteditable', 'false');
            span.textContent = '@' + project.title;
            const space = document.createTextNode(' ');
            const afterNode = document.createTextNode(afterText);
            parent.insertBefore(afterNode, node.nextSibling);
            parent.insertBefore(space, afterNode);
            parent.insertBefore(span, space);

            const r = document.createRange();
            r.setStartAfter(space);
            r.collapse(true);
            sel.removeAllRanges();
            sel.addRange(r);
            this.closeMentions();
            this.syncEmpty();
            this.$refs.editor?.focus();
        },

        // --- Attachments ----------------------------------------------------
        onFiles(e) {
            for (const f of Array.from(e.target.files || [])) {
                if (this.files.length >= 5) break;
                if (f.size > 10 * 1024 * 1024) { this.error = this.labels.tooBig || 'A file is too large (max 10MB).'; continue; }
                this.files.push(f);
            }
            e.target.value = '';
        },
        removeFile(i) { this.files.splice(i, 1); },
        fileSize(f) { return f.size > 1048576 ? `${(f.size / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(f.size / 1024))} KB`; },

        // --- Submit ---------------------------------------------------------
        async submit() {
            this.syncEmpty();
            if (this.isEmpty) { this.error = this.labels.empty || 'Write a short brief before applying.'; return; }
            this.submitting = true;
            this.error = '';
            const body = new FormData();
            body.append('brief', this.$refs.editor.innerHTML);
            this.files.forEach((f) => body.append('attachments[]', f));
            try {
                const { data } = await post(`/talent/applications/${this.projectId}`, body);
                window.location.href = data.contract_url;
            } catch (e) {
                this.error = e instanceof ApiError ? (e.errors?.brief?.[0] || e.errors?.['attachments.0']?.[0] || e.message) : 'Something went wrong.';
                this.submitting = false;
            }
        },

        // --- Modal machinery (scroll-lock + focus-trap) ---------------------
        lockScroll() {
            this._scrollY = window.scrollY;
            const b = document.body;
            b.style.position = 'fixed';
            b.style.top = `-${this._scrollY}px`;
            b.style.insetInlineStart = '0';
            b.style.width = '100%';
        },
        unlockScroll() {
            const b = document.body;
            b.style.position = '';
            b.style.top = '';
            b.style.insetInlineStart = '';
            b.style.width = '';
            window.scrollTo(0, this._scrollY || 0);
        },
        trapFocus(e) {
            if (e.key !== 'Tab' || !this.modalOpen) return;
            const dialog = this.$refs.dialog;
            if (!dialog) return;
            const f = Array.from(dialog.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), textarea, [contenteditable="true"], [tabindex]:not([tabindex="-1"])')).filter((el) => el.offsetParent !== null);
            if (!f.length) return;
            const first = f[0];
            const last = f[f.length - 1];
            const a = document.activeElement;
            if (e.shiftKey && a === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && a === last) { e.preventDefault(); first.focus(); }
        },
    }));
});
