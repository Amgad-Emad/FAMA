{{-- Comments, inline under a post in the feed (port of posts.js .pcomments):
     a box to add one, the latest few, and the way to the full thread. State
     lives on the parent postFrame (loads once on first open). Each comment
     takes a Reply (13 Nov 2026): the box opens under it, starting with the
     person's @username, with Cancel beside it; signed-in actors only. The
     full thread is the post page. --}}
@php
    $me = $currentActor ?? null;
    $meName = $me?->feedDisplayName() ?? '';
    $meInitials = collect(preg_split('/\s+/', trim($meName)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
<div class="pcomments" id="pc-{{ $post->id }}" x-show="commentsOpen" x-cloak>
    {{-- Visitors get the box too (handoff spec): sending answers 401, the
         comment is captured as a pending intent, the sign-in dialog opens and
         the comment posts after login. --}}
        <form class="cadd" @submit.prevent="sendComment()" novalidate>
            <span class="av sm" aria-hidden="true">@if ($me?->feedAvatarUrl())<img src="{{ $me->feedAvatarUrl() }}" alt="">@else{{ $meInitials }}@endif</span>
            <label class="fx-vh" for="ci-{{ $post->id }}">{{ __('Add a comment…') }}</label>
            <span class="cpill">
                <span class="mwrap" x-data="famaMention()" x-modelable="value" x-model="draft">
                    <input x-ref="field" id="ci-{{ $post->id }}" maxlength="2200" placeholder="{{ __('Add a comment…') }}" autocomplete="off"
                           x-model="value" @input="onInput($event)" @keydown="onKeydown($event)" @blur="onBlur()"
                           role="combobox" aria-autocomplete="list" :aria-expanded="open.toString()" :aria-controls="listId">
                    <x-feed.mention-field />
                </span>
                <button class="csend" :disabled="!draft.trim() || posting" aria-label="{{ __('Post') }}"><svg class="icon" aria-hidden="true"><use href="#i-send-up"/></svg></button>
            </span>
        </form>
        <p class="err" role="alert" x-show="commentError" x-cloak x-text="commentError"></p>

    {{-- 13 Nov 2026 review: every comment takes a Reply (one level deep). The replies sit under the
         comment they answer, indented on a thin line, so each conversation reads as one thread. --}}
    <ul class="clist" x-show="comments.length" x-cloak>
        <template x-for="c in comments.slice(0, 3)" :key="c.id">
            <li class="cthread" :class="{ pending: c.is_pending }">
                @include('feed.partials.comment-item', ['var' => 'c', 'small' => false])
                <div class="cfoot" x-show="(!isGuest && !c.is_tombstone && !c.is_pending) || (c.replies?.length) || replyTo === c.id">
                    <button type="button" class="creply" :data-cid="c.id" x-show="!isGuest && !c.is_tombstone && !c.is_pending" @click="openReply(c)"
                            :aria-label="@js(__('Reply')) + ': ' + (c.author?.display_name ?? '')">{{ __('Reply') }}</button>
                    <div class="creplies" x-show="c.replies?.length || replyTo === c.id" :aria-label="repliesLabel(c)">
                        <ul class="clist" x-show="c.replies?.length">
                            <template x-for="r in (c.replies ?? [])" :key="r.id">
                                <li :class="{ pending: r.is_pending }">
                                    @include('feed.partials.comment-item', ['var' => 'r', 'small' => true])
                                </li>
                            </template>
                        </ul>
                        <a class="link" x-show="c.remaining_replies > 0" x-cloak href="{{ route('posts.show', $post) }}#comments"
                           x-text="@js(__('View :count more replies')).replace(':count', c.remaining_replies)"></a>
                        <template x-if="replyTo === c.id">
                            <form class="cadd crform" @submit.prevent="sendReply(c)" novalidate>
                                <label class="fx-vh" :for="'cr-' + id" x-text="@js(__('Reply to @:user…')).replace(':user', c.author?.username ?? '')"></label>
                                <span class="cpill">
                                    <span class="mwrap" x-data="famaMention()" x-modelable="value" x-model="replyDraft">
                                        <input x-ref="field" :id="'cr-' + id" maxlength="2200" autocomplete="off"
                                               :placeholder="@js(__('Reply to @:user…')).replace(':user', c.author?.username ?? '')"
                                               x-model="value" @input="onInput($event)" @keydown="onKeydown($event)" @blur="onBlur()"
                                               @keydown.escape="cancelReply(c)"
                                               role="combobox" aria-autocomplete="list" :aria-expanded="open.toString()" :aria-controls="listId">
                                        <x-feed.mention-field />
                                    </span>
                                    <button class="csend" :disabled="!replyBody(c) || posting" aria-label="{{ __('Post') }}"><svg class="icon" aria-hidden="true"><use href="#i-send-up"/></svg></button>
                                </span>
                                <button type="button" class="creply" @click="cancelReply(c)">{{ __('Cancel') }}</button>
                            </form>
                        </template>
                    </div>
                </div>
            </li>
        </template>
    </ul>
    <p class="meta cnone" x-show="commentsLoaded && !comments.length" x-cloak>{{ __('No comments yet. Start the conversation.') }}</p>
    <a class="link call" href="{{ route('posts.show', $post) }}#comments" x-show="commentsCount > Math.min(3, comments.length)" x-cloak x-text="@js(__('View all :count comments')).replace(':count', commentsCount.toLocaleString())"></a>
</div>
