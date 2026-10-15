{{-- Comments, inline under a post in the feed (port of posts.js .pcomments):
     a box to add one, the latest few, and the way to the full thread. State
     lives on the parent postFrame (loads once on first open). Guests get the
     "Log in to comment" link. The full thread is the post page. --}}
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

    <ul class="clist" x-show="comments.length" x-cloak>
        <template x-for="c in comments.slice(0, 3)" :key="c.id">
            <li :class="{ pending: c.is_pending }">
                <template x-if="c.is_tombstone">
                    <div class="cb"><p class="meta">{{ __('Comment deleted') }}</p></div>
                </template>
                <template x-if="!c.is_tombstone">
                    <a class="av sm" :href="profileUrl(c.author)" aria-hidden="true" tabindex="-1"><img x-show="c.author?.avatar_url" :src="c.author?.avatar_url" alt=""><span x-show="!c.author?.avatar_url" x-text="initials(c.author?.display_name)"></span></a>
                </template>
                <template x-if="!c.is_tombstone">
                    <div class="cb">
                        <p><a class="who" :href="profileUrl(c.author)"><b dir="auto" x-text="c.author?.display_name"></b></a> <span class="meta" x-text="c.is_pending ? @js(__('now')) : ago(c.created_at)"></span></p>
                        <p dir="auto" x-html="c.body_html"></p>
                        <p x-show="c.is_failed" x-cloak><span class="fail">{{ __("Couldn't send.") }}</span> <button type="button" class="link" @click="retryComment(c)">{{ __('Retry') }}</button></p>
                    </div>
                </template>
            </li>
        </template>
    </ul>
    <p class="meta cnone" x-show="commentsLoaded && !comments.length" x-cloak>{{ __('No comments yet. Start the conversation.') }}</p>
    <a class="link call" href="{{ route('posts.show', $post) }}#comments" x-show="commentsCount > Math.min(3, comments.length)" x-cloak x-text="@js(__('View all :count comments')).replace(':count', commentsCount.toLocaleString())"></a>
</div>
