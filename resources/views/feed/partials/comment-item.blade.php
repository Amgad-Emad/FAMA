{{-- One comment (or reply) inside the inline feed thread — avatar + bubble.
     $var is the Alpine loop variable ('c' a comment, 'r' a reply); $small
     gives replies the smaller avatar (posts.js cItem). --}}
<template x-if="{{ $var }}.is_tombstone">
    <div class="cb"><p class="meta">{{ __('Comment deleted') }}</p></div>
</template>
<template x-if="!{{ $var }}.is_tombstone">
    <a class="av sm{{ $small ? ' xs' : '' }}" :href="profileUrl({{ $var }}.author)" aria-hidden="true" tabindex="-1"><img x-show="{{ $var }}.author?.avatar_url" :src="{{ $var }}.author?.avatar_url" alt=""><span x-show="!{{ $var }}.author?.avatar_url" x-text="initials({{ $var }}.author?.display_name)"></span></a>
</template>
<template x-if="!{{ $var }}.is_tombstone">
    <div class="cb">
        <p><a class="who" :href="profileUrl({{ $var }}.author)"><b dir="auto" x-text="{{ $var }}.author?.display_name"></b></a> <span class="meta" x-text="{{ $var }}.is_pending ? @js(__('now')) : ago({{ $var }}.created_at)"></span></p>
        <p dir="auto" x-html="{{ $var }}.body_html"></p>
        <p x-show="{{ $var }}.is_failed" x-cloak><span class="fail">{{ __("Couldn't send.") }}</span> <button type="button" class="link" @click="{{ $var === 'r' ? 'retryReply(c, r)' : 'retryComment(c)' }}">{{ __('Retry') }}</button></p>
    </div>
</template>
