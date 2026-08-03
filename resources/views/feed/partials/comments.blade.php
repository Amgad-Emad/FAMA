{{-- The inline comment thread (Prompt 5O, ADR-F7): expands beneath the frame
     behind a sliding seam — never a modal. Paginated 10 + Load more; each
     comment carries up to 3 preview replies + a remaining count; tombstones
     keep threads intact; replies pre-fill @username; authors get inline
     edit/delete with a quiet mono countdown. Nested inside postFrame — it
     reads commentsOpen/commentsCount from the parent scope. --}}
<div x-data="commentThread({ postId: {{ $post->id }}, isGuest: @js(!isset($currentActor) || $currentActor === null) })" x-show="commentsOpen" x-cloak
     x-effect="commentsOpen && loadOnce()"
     id="comments-{{ $post->id }}" role="region" aria-label="{{ __('Comments') }}">
    <hr class="seam">

    <div class="p-5 sm:px-6">
        {{-- Composer (5AG): collapsed to one 40px row until focused; guests
             get the gate on focus instead of an expansion. --}}
        <div class="comment-composer flex items-start gap-3"
             :class="composerExpanded ? 'is-expanded' : 'is-collapsed'"
             @focusin="expandComposer()"
             @keydown.escape="collapseComposer()">
            @if (isset($currentActor) && $currentActor?->feedAvatarUrl())
                <img src="{{ $currentActor->feedAvatarUrl() }}" alt="" class="comment-avatar mt-1">
            @else
                <span class="comment-avatar mt-1 flex items-center justify-center bg-ground-sunk t-meta">{{ str($currentActor?->feedDisplayName() ?? '?')->substr(0, 1) }}</span>
            @endif
            <x-feed.mention-field x-model="draft" x-ref="composer"
                                  @submit-intent="submit()"
                                  :placeholder="__('Add a comment… type @ to credit someone')" />
            <button type="button" @click="submit()" :disabled="posting || draft.trim() === ''"
                    class="dl-btn composer-send" :class="{ 'is-ready': draft.trim() !== '' && !posting, 'is-busy': posting }">
                <span x-show="posting" class="dl-spinner" aria-hidden="true"></span>
                {{ __('SEND') }}
            </button>
        </div>
        <p class="t-meta mt-1" x-show="replyTo" x-cloak>
            {{ __('Replying to') }} <bdi x-text="'@' + (replyTo?.username ?? '')"></bdi>
            · <button type="button" class="underline" @click="clearReply()">{{ __('cancel') }}</button>
        </p>

        <div x-show="gate" x-cloak class="mt-3 rounded-md border border-seam bg-veil/40 px-4 py-3">
            <p class="t-body">{{ __('Sign in to join the conversation') }}</p>
            <div class="mt-2 flex gap-2">
                <a :href="window.fama.localizeUrl('/login?role=talent')" class="dl-chip">{{ __("I'm a talent") }}</a>
                <a :href="window.fama.localizeUrl('/login?role=brand')" class="dl-chip">{{ __("I'm a brand") }}</a>
            </div>
        </div>

        {{-- Live region announcing thread updates --}}
        <p class="sr-only" aria-live="polite" x-text="announcement"></p>

        {{-- Thread --}}
        <div class="mt-5">
            <template x-for="comment in comments" :key="comment.id">
                <div :id="'comment-' + comment.id" class="thread-row" :class="{ 'is-mine': comment.is_mine, 'is-pending': comment.is_pending }">
                    {{-- Tombstone (D12) --}}
                    <template x-if="comment.is_tombstone">
                        <p class="thread-tombstone t-meta">{{ __('This comment was removed.') }}</p>
                    </template>

                    <template x-if="! comment.is_tombstone">
                        <div>
                            <p class="flex min-w-0 items-center gap-2">
                                <img x-show="comment.author?.avatar_url" :src="comment.author?.avatar_url" alt="" class="comment-avatar">
                                <span x-show="!comment.author?.avatar_url" class="comment-avatar flex items-center justify-center bg-ground-sunk t-meta" x-text="(comment.author?.display_name ?? '?').slice(0, 1)"></span>
                                <bdi class="comment-author truncate" x-text="comment.author ? comment.author.display_name : ''"></bdi>
                                <bdi class="comment-handle truncate" x-text="comment.author ? '@' + comment.author.username : ''"></bdi>
                                <span x-show="comment.author?.type === 'brand'" class="comment-badge">{{ __('BRAND') }}</span>
                                <span class="comment-time" x-text="'· ' + relativeTime(comment.created_at)"></span>
                                <span class="comment-time" x-show="comment.edited_at">· {{ __('edited') }}</span>
                                <span class="comment-time" x-show="comment.is_pending" x-cloak>· {{ __('sending…') }}</span>
                            </p>

                            {{-- Editing swaps the body for a well --}}
                            <template x-if="editingId === comment.id">
                                <div class="mt-1 flex items-start gap-2">
                                    <textarea x-model="editDraft" rows="2" dir="auto" class="well t-comment w-full resize-y border-0 p-2 focus:ring-0"></textarea>
                                    <button type="button" class="t-meta text-daylight" @click="saveEdit(comment)">{{ __('SAVE') }}</button>
                                    <button type="button" class="t-meta" @click="editingId = null">✕</button>
                                </div>
                            </template>
                            <div x-show="editingId !== comment.id" class="t-comment mt-1" dir="auto" x-html="comment.body_html"></div>
                            <p x-show="comment.is_failed" x-cloak class="t-meta mt-1 text-danger">
                                {{ __('Didn’t send.') }}
                                <button type="button" class="underline" @click="retry(comment)">{{ __('Retry') }}</button>
                            </p>

                            <p class="t-meta mt-1.5 flex items-center gap-1">
                                <button type="button" class="action-btn action-btn--sm glint" :class="{ 'is-lit': comment.liked_by_viewer }"
                                        :aria-pressed="comment.liked_by_viewer ? 'true' : 'false'"
                                        :aria-label="'{{ __('Like this comment by') }} ' + (comment.author?.display_name ?? '')"
                                        @click="toggleCommentLike(comment)">
                                    <span class="glint-mark" aria-hidden="true">✦</span>
                                    <span class="action-count" x-show="comment.likes_count > 0" x-text="comment.likes_count"></span>
                                </button>
                                <button type="button" class="action-btn action-btn--sm" @click="reply(comment)">{{ __('REPLY') }}</button>
                                <button type="button" class="action-btn action-btn--sm"
                                        @click="$dispatch('open-report', { type: 'comment', id: comment.id, quote: comment.body })">{{ __('REPORT') }}</button>
                                <template x-if="comment.is_mine && comment.is_editable">
                                    <button type="button" class="action-btn action-btn--sm" @click="startEdit(comment)">
                                        {{ __('EDIT') }} <span class="tabular-nums" x-text="countdown(comment.editable_until)"></span>
                                    </button>
                                </template>
                                <template x-if="comment.is_mine">
                                    <button type="button" class="action-btn action-btn--sm text-danger"
                                            @click="$confirm({ title: '{{ __('Delete comment?') }}', message: '{{ __('Replies stay; the comment becomes a tombstone.') }}', confirmLabel: '{{ __('Delete') }}', tone: 'danger' }).then(ok => ok && destroy(comment))">
                                        {{ __('DELETE') }}
                                    </button>
                                </template>
                            </p>

                            {{-- Preview replies + the rest on demand --}}
                            <div class="thread-replies mt-3 space-y-3">
                                <template x-for="replyRow in comment.replies ?? []" :key="replyRow.id">
                                    <div :id="'comment-' + replyRow.id">
                                        <p class="flex min-w-0 items-center gap-2">
                                            <img x-show="replyRow.author?.avatar_url" :src="replyRow.author?.avatar_url" alt="" class="comment-avatar">
                                            <span x-show="!replyRow.author?.avatar_url" class="comment-avatar flex items-center justify-center bg-ground-sunk t-meta" x-text="(replyRow.author?.display_name ?? '?').slice(0, 1)"></span>
                                            <bdi class="comment-author truncate" x-text="replyRow.author ? replyRow.author.display_name : ''"></bdi>
                                            <span class="comment-time" x-text="'· ' + relativeTime(replyRow.created_at)"></span>
                                        </p>
                                        <div class="t-comment mt-0.5" dir="auto" x-html="replyRow.body_html"></div>
                                        <p class="t-meta mt-1">
                                            <button type="button" class="glint" :class="{ 'is-lit': replyRow.liked_by_viewer }"
                                                    @click="toggleCommentLike(replyRow)">
                                                <span class="glint-mark" aria-hidden="true">✦</span>
                                                <span class="tabular-nums" x-text="replyRow.likes_count"></span>
                                            </button>
                                            <button type="button" class="ms-3" @click="reply(replyRow, comment)">{{ __('REPLY') }}</button>
                                        </p>
                                    </div>
                                </template>
                                <button type="button" x-show="(comment.remaining_replies ?? 0) > 0" x-cloak
                                        class="dl-btn" @click="loadReplies(comment)">
                                    <span x-text="'{{ __('Show') }} ' + comment.remaining_replies + ' {{ __('more replies') }}'"></span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <button type="button" x-show="nextPage !== null" x-cloak
                class="dl-btn mt-3" @click="loadMore()">{{ __('LOAD MORE COMMENTS') }}</button>
    </div>
</div>
