{{-- The post action bar, rebuilt (5AD): real 40px-hit buttons over a seam
     hairline. Like/comment/share group inline-start; save + overflow push
     inline-end. Zero counts render the icon alone. The liked glint is the ONE
     accent-filled control; every button names the author for AT. SHARE opens
     a real popover (copy / X / WhatsApp / LinkedIn / native). --}}
@props(['post'])

@php $authorName = $post->author?->feedDisplayName() ?? __('this talent'); @endphp

<div class="action-bar">
    {{-- Like — the glint --}}
    <button type="button" class="action-btn glint" :aria-pressed="liked ? 'true' : 'false'"
            :class="{ 'is-lit': liked }" @click="toggleLike()"
            aria-label="{{ __('Like this post by :name', ['name' => $authorName]) }}">
        <span class="action-icon glint-mark" aria-hidden="true">✦</span>
        <span class="action-count" x-show="likes > 0" x-cloak x-text="likes">{{ $post->likes_count ?: '' }}</span>
    </button>

    {{-- Comments (expand inline beneath the frame) --}}
    <button type="button" class="action-btn" @click="toggleComments()"
            :aria-expanded="commentsOpen ? 'true' : 'false'" aria-controls="comments-{{ $post->id }}"
            aria-label="{{ __('Comments on this post by :name', ['name' => $authorName]) }}">
        <svg class="action-icon" aria-hidden="true"><use href="#glyph-comment"></use></svg>
        <span class="action-count" x-show="commentsCount > 0" x-cloak x-text="commentsCount">{{ $post->comments_count ?: '' }}</span>
    </button>

    {{-- Share — a real popover --}}
    <div class="relative" @keydown.escape.stop="shareOpen = false" @click.outside="shareOpen = false">
        <button type="button" class="action-btn" @click="shareOpen = !shareOpen"
                :aria-expanded="shareOpen ? 'true' : 'false'" aria-haspopup="menu"
                aria-label="{{ __('Share this post by :name', ['name' => $authorName]) }}">
            <svg class="action-icon" aria-hidden="true"><use href="#glyph-share"></use></svg>
        </button>
        <div x-show="shareOpen" x-cloak class="share-popover" role="menu">
            <button type="button" role="menuitem" @click="share(); shareOpen = false">
                {{-- copy / link --}}
                <svg class="share-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 15a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M15 9a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
                <span x-text="shared ? '{{ __('Copied!') }}' : '{{ __('Copy link') }}'">{{ __('Copy link') }}</span>
            </button>
            <a role="menuitem" target="_blank" rel="noopener"
               :href="'https://x.com/intent/post?url=' + encodeURIComponent(permalink)">
                <svg class="share-icon" aria-hidden="true"><use href="#glyph-x"></use></svg>
                <span>{{ __('Share to X') }}</span>
            </a>
            <a role="menuitem" target="_blank" rel="noopener"
               :href="'https://wa.me/?text=' + encodeURIComponent(permalink)">
                <svg class="share-icon" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.5 15.3L2 22l4.9-1.5A10 10 0 1 0 12 2zm5.5 12.4c-.3-.15-1.7-.85-2-.95-.25-.1-.45-.15-.65.15-.2.3-.75.95-.9 1.15-.15.2-.35.2-.65.05-.3-.15-1.25-.45-2.4-1.5-.9-.8-1.5-1.75-1.65-2.05-.15-.3 0-.45.15-.6.15-.15.3-.35.45-.55.15-.2.2-.3.3-.5.1-.2.05-.4-.05-.55-.1-.15-.65-1.6-.9-2.15-.25-.55-.5-.5-.65-.5h-.55c-.2 0-.5.05-.75.35-.25.3-1 1-1 2.4s1.05 2.8 1.2 3c.15.2 2.05 3.15 5 4.4.7.3 1.25.5 1.65.65.7.2 1.35.2 1.85.1.55-.05 1.7-.7 1.95-1.35.25-.65.25-1.2.15-1.35z"/></svg>
                <span>{{ __('WhatsApp') }}</span>
            </a>
            <a role="menuitem" target="_blank" rel="noopener"
               :href="'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(permalink)">
                <svg class="share-icon" aria-hidden="true"><use href="#glyph-linkedin"></use></svg>
                <span>{{ __('LinkedIn') }}</span>
            </a>
            <button type="button" role="menuitem" @click="shareInstagram(); shareOpen = false">
                <svg class="share-icon" aria-hidden="true"><use href="#glyph-instagram"></use></svg>
                <span>{{ __('Instagram') }}</span>
            </button>
            <button type="button" role="menuitem" x-show="!!navigator.share"
                    @click="navigator.share({ url: permalink }).catch(() => {}); shareOpen = false">
                <svg class="share-icon" aria-hidden="true"><use href="#glyph-share"></use></svg>
                <span>{{ __('Share…') }}</span>
            </button>
        </div>
    </div>

    <div class="action-end">
        {{-- Overflow (⋯): Save, Report, and (author) Edit/Delete. TELEPORTED to
             <body> — the frame's clip-path clips any in-frame dropdown, so it
             would open invisibly. Save lives here now, not on the bar. --}}
        <div x-data="overflowMenu()" @keydown.escape.window="close()">
            <button type="button" x-ref="trigger" class="action-btn" @click="toggle()"
                    :aria-expanded="open ? 'true' : 'false'" aria-haspopup="menu"
                    aria-label="{{ __('More actions for this post by :name', ['name' => $authorName]) }}">
                <span class="action-icon" aria-hidden="true">⋯</span>
            </button>

            <template x-teleport="body">
                <div x-show="open" x-cloak x-ref="menu" data-surface="daylight" role="menu"
                     x-bind:style="menuStyle">
                    <div class="frame-shadow">
                        <div class="frame p-1">
                            {{-- Report (Bookmark now lives in the post's top corner) --}}
                            <button type="button" role="menuitem" @click="close(); report()"
                                    class="block w-full rounded px-3 py-2 text-start t-body hover:bg-ground-sunk">
                                {{ __('Report') }}
                            </button>

                            {{-- Author-only --}}
                            <template x-if="isAuthor">
                                <div>
                                    <button type="button" role="menuitem" @click="close(); edit()" x-show="editable"
                                            class="block w-full rounded px-3 py-2 text-start t-body hover:bg-ground-sunk">
                                        {{ __('Edit') }}
                                    </button>
                                    <button type="button" role="menuitem"
                                            @click="close(); $confirm({ title: '{{ __('Delete post?') }}', message: '{{ __('This removes the post from the feed.') }}', confirmLabel: '{{ __('Delete') }}', tone: 'danger' }).then(ok => ok && destroy())"
                                            class="block w-full rounded px-3 py-2 text-start t-body text-danger hover:bg-ground-sunk">
                                        {{ __('Delete') }}
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

{{-- The inline guest gate (ADR-F8): the tapped control expands into a lit
     prompt; scroll is never lost; the intent is already captured server-side. --}}
<div x-show="gatePrompt" x-cloak id="gate-{{ $post->id }}"
     class="mx-5 mb-4 rounded-md border border-seam bg-veil/40 px-4 py-3 sm:mx-6">
    <p class="t-body" x-text="gateMessage"></p>
    <div class="mt-2 flex gap-2">
        <a :href="loginUrl('talent')" class="dl-chip">{{ __("I'm a talent") }}</a>
        <a :href="loginUrl('brand')" class="dl-chip">{{ __("I'm a brand") }}</a>
    </div>
</div>
