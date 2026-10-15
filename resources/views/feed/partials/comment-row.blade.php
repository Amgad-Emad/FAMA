{{-- One comment row on the post page (port of post.html commentHTML), as an
     Alpine template body. $v = the JS variable holding the comment, $reply =
     whether it is a reply row, $top = the JS expression for its top-level
     comment (what a reply attaches to). Lives inside commentThread. --}}
@php $reply = $reply ?? false; $top = $top ?? $v; @endphp
<li class="c" :class="{ reply: {{ $reply ? 'true' : 'false' }}, deleted: {{ $v }}.is_tombstone, failed: {{ $v }}.is_failed }" :id="'comment-' + {{ $v }}.id">
    <span class="av" aria-hidden="true"><template x-if="{{ $v }}.is_tombstone"><span>—</span></template><template x-if="!{{ $v }}.is_tombstone && {{ $v }}.author?.avatar_url"><img :src="{{ $v }}.author.avatar_url" alt=""></template><template x-if="!{{ $v }}.is_tombstone && !{{ $v }}.author?.avatar_url"><span x-text="initials({{ $v }}.author?.display_name)"></span></template></span>
    <div>
        <template x-if="{{ $v }}.is_tombstone"><p class="tx">{{ __('Comment deleted') }}</p></template>
        <template x-if="!{{ $v }}.is_tombstone">
            <div>
                <div class="bubble"><a class="who" :href="profileUrl({{ $v }}.author)" style="color:inherit;text-decoration:none"><b dir="auto" x-text="{{ $v }}.author?.display_name"></b></a><span class="meta"><span dir="ltr" x-text="'@' + ({{ $v }}.author?.username ?? '')"></span> · <span x-text="{{ $v }}.is_pending ? @js(__('now')) : ago({{ $v }}.created_at)"></span><span x-show="{{ $v }}.edited_at"> · {{ __('edited') }}</span></span></div>
                <form class="editbox" x-show="editingId === {{ $v }}.id" @submit.prevent="saveEdit({{ $v }})">
                    <label class="vh" :for="'e' + {{ $v }}.id">{{ __('Edit') }}</label>
                    <textarea class="ta" :id="'e' + {{ $v }}.id" maxlength="2200" x-model="editDraft"></textarea>
                    <div class="row"><button type="button" class="btn btn-2" @click="editingId = null">{{ __('Cancel') }}</button><button class="btn">{{ __('Save') }}</button></div>
                </form>
                <p class="tx" dir="auto" x-show="editingId !== {{ $v }}.id" x-html="{{ $v }}.body_html"></p>
                <div class="acts">
                    <span class="fail" x-show="{{ $v }}.is_failed">{{ __("Couldn't send.") }}</span>
                    <button type="button" x-show="{{ $v }}.is_failed" @click="retry({{ $v }})">{{ __('Retry') }}</button>
                    <button type="button" x-show="!{{ $v }}.is_failed && !{{ $v }}.is_pending" @click="toggleLike({{ $v }})" :aria-pressed="(!!{{ $v }}.liked_by_viewer).toString()"><svg class="icon" aria-hidden="true"><use :href="{{ $v }}.liked_by_viewer ? '#i-heart-f' : '#i-heart'"/></svg><span x-text="{{ $v }}.likes_count ? {{ $v }}.likes_count.toLocaleString() : @js(__('Like'))"></span></button>
                    <button type="button" x-show="!{{ $v }}.is_failed && !{{ $v }}.is_pending" @click="startReply({{ $v }}, {{ $top }})">{{ __('Reply') }}</button>
                    <button type="button" x-show="{{ $v }}.is_mine && editLeft({{ $v }}) > 0" @click="startEdit({{ $v }})">{{ __('Edit') }}</button>
                    <span class="timer" x-show="{{ $v }}.is_mine && editLeft({{ $v }}) > 0" x-text="editTimer({{ $v }})"></span>
                    <button type="button" x-show="{{ $v }}.is_mine && !{{ $v }}.is_pending" @click="remove({{ $v }}, {{ $reply ? $top : 'null' }})">{{ __('Delete') }}</button>
                    <button type="button" x-show="!{{ $v }}.is_mine && !{{ $v }}.is_pending" @click="report({{ $v }})">{{ __('Report') }}</button>
                </div>
            </div>
        </template>
        @unless ($reply)
            <ul class="replies" x-show="({{ $v }}.replies ?? []).length">
                <template x-for="r in ({{ $v }}.replies ?? [])" :key="r.id">
                    @include('feed.partials.comment-row', ['v' => 'r', 'reply' => true, 'top' => $v])
                </template>
            </ul>
            <button type="button" class="moreR" x-show="({{ $v }}.remaining_replies ?? 0) > 0" @click="loadReplies({{ $v }})" x-text="moreRepliesLabel({{ $v }}.remaining_replies)"></button>
            <ul class="replies" x-show="replyingTo === {{ $v }}.id" x-cloak>
                <template x-if="replyingTo === {{ $v }}.id">
                    @include('feed.partials.comment-composer', ['parent' => $v])
                </template>
            </ul>
        @endunless
    </div>
</li>
