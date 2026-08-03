{{-- One post — the canonical Daylight frame (Prompts 5M–5O). Used by the feed
     spine (server page 1 + Ajax items), the post detail page and the
     /posts/{post}/fragment endpoint. Requires eager: author.media, media,
     skills, project, mentions.mentioned, linkedUrls.preview (+viewerLike). --}}
@php
    $author = $post->author;
    $bodyHtml = filled($post->body) ? app(App\Services\BodyRenderer::class)->render($post) : null;
    $liked = $post->relationLoaded('viewerLike') ? $post->viewerLike->isNotEmpty() : false;
    $saved = $post->relationLoaded('viewerBookmark') ? $post->viewerBookmark->isNotEmpty() : false;
    $isAuthor = isset($currentActor) && $currentActor !== null && $author !== null
        && $currentActor->getMorphClass() === $post->author_type
        && (string) $currentActor->getKey() === (string) $post->author_id;
    $width = match ($post->post_type) {
        'note' => 'note',
        'project_share' => 'spread',
        default => 'media',
    };
    $savedUrl = match (true) {
        isset($currentActor) && $currentActor instanceof \App\Models\Talent => route('talent.feed.saved'),
        isset($currentActor) && $currentActor instanceof \App\Models\Brand => route('brand.feed.saved'),
        default => null,
    };

    // Collaborators = people mentioned who aren't the author (Facebook-style
    // "with X and N others" beside the name). Deduped, author removed.
    $authorKey = $author !== null ? $post->author_type.':'.$post->author_id : null;
    $collaborators = $post->mentions
        ->map(fn ($m) => $m->mentioned)
        ->filter()
        ->unique(fn ($m) => $m->getMorphClass().':'.$m->getKey())
        ->reject(fn ($m) => $m->getMorphClass().':'.$m->getKey() === $authorKey)
        ->values();
    $collabUrl = fn ($m) => $m instanceof \App\Models\Brand
        ? route('brand.public', ['brand' => $m->slug])
        : route('talent.public', ['slug' => $m->slug]);
@endphp

<x-feed.frame :width="$width" :id="'post-'.$post->id"
    x-data="postFrame({
        id: {{ $post->id }},
        liked: {{ $liked ? 'true' : 'false' }},
        saved: {{ $saved ? 'true' : 'false' }},
        likes: {{ $post->likes_count }},
        comments: {{ $post->comments_count }},
        isAuthor: {{ $isAuthor ? 'true' : 'false' }},
        editable: {{ ($isAuthor && $post->isEditable()) ? 'true' : 'false' }},
        permalink: '{{ route('posts.show', $post) }}',
        {{-- WATCH OUT: this attribute lives on a COMPONENT TAG, where Blade
             compiles attributes as strings and only processes {{ }} echoes —
             a @js() directive here ships to the browser literally and breaks
             the whole frame's Alpine tree. Js::from via {{ }} is the safe
             form (e() passes Htmlable through unescaped). --}}
        authorName: {{ \Illuminate\Support\Js::from($author?->feedDisplayName()) }},
        savedUrl: {{ \Illuminate\Support\Js::from($savedUrl) }},
    })">

    {{-- Header --}}
    <header class="flex items-start gap-3 p-5 sm:px-6">
        <a href="{{ $author !== null ? route('talent.public', ['slug' => $author->feedUsername()]) : '#' }}" class="shrink-0" aria-label="{{ $author?->feedDisplayName() }}">
            @if ($author?->feedAvatarUrl())
                <img src="{{ $author->feedAvatarUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">
            @else
                @php $tint = 20 + (crc32((string) $author?->feedUsername()) % 40); @endphp
                <span class="avatar-fallback grid h-10 w-10 place-items-center rounded-full"
                      style="background: color-mix(in oklch, var(--dl-surface-sunk), var(--dl-veil) {{ $tint }}%); font-family: var(--dl-font-display);">{{ str($author?->feedDisplayName() ?? '?')->substr(0, 1) }}</span>
            @endif
        </a>

        <div class="min-w-0 flex-1">
            {{-- Name line with collaborators (Facebook "with X and N others") --}}
            <p class="leading-snug">
                <a href="{{ $author !== null ? route('talent.public', ['slug' => $author->feedUsername()]) : '#' }}"
                   class="font-medium text-ink hover:underline">{{ $author?->feedDisplayName() }}</a>
                @if ($collaborators->isNotEmpty())
                    <span class="text-muted">{{ __('with') }}</span>
                    <a href="{{ $collabUrl($collaborators->first()) }}" class="font-medium text-ink hover:underline">{{ $collaborators->first()->feedDisplayName() }}</a>
                    @if ($collaborators->count() > 1)
                        <span class="text-muted">{{ __('and :count others', ['count' => $collaborators->count() - 1]) }}</span>
                    @endif
                @endif
            </p>
            {{-- Handle · time --}}
            <p class="t-meta">
                <bdi>{{ '@'.$author?->feedUsername() }}</bdi>
                <span aria-hidden="true"> · </span>
                <span title="{{ $post->published_at?->toDayDateTimeString() }}">{{ $post->published_at?->diffForHumans(short: true) }}</span>
                @if ($post->edited_at) · {{ __('edited') }} @endif
                @if ($post->is_pinned) · ⌖ @endif
            </p>
        </div>

        {{-- Bookmark — top corner of the post (inline-end; mirrors in RTL). --}}
        <button type="button" class="bookmark-corner -me-1 ms-1 grid h-8 w-8 shrink-0 place-items-center rounded-full transition"
                :class="saved ? 'is-saved' : ''" :aria-pressed="saved ? 'true' : 'false'"
                @click="toggleBookmark()"
                :aria-label="saved ? '{{ __('Bookmarked') }}' : '{{ __('Bookmark') }}'">
            <svg class="h-5 w-5" viewBox="0 0 24 24" :fill="saved ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1z"/>
            </svg>
        </button>
    </header>

    {{-- sr heading for the article landmark --}}
    <h2 class="sr-only">{{ __('Post by :name', ['name' => $author?->feedDisplayName()]) }}</h2>

    {{-- Content by kind --}}
    @if ($post->post_type === 'note')
        <div class="px-5 pb-5 sm:px-6">
            <div class="t-note" dir="auto">{!! $bodyHtml !!}</div>
        </div>
    @else
        @if ($bodyHtml)
            <div class="px-5 pb-4 sm:px-6">
                <div class="t-body" dir="auto">{!! $bodyHtml !!}</div>
            </div>
        @endif

        @if ($post->post_type === 'media')
            {{-- Double-tap likes the post with the same flare (5O). --}}
            <div @dblclick="doubleTapLike()">
                <x-feed.media :post="$post" :eager="$eagerMedia ?? false" />
            </div>
        @else
            <x-feed.spread :post="$post" />
        @endif
    @endif

    {{-- Credits line --}}
    <x-feed.credits :post="$post" class="px-5 pt-4 pb-3 sm:px-6" />

    {{-- Actions + inline guest gate --}}
    <x-feed.actions :post="$post" />

    {{-- Inline comments (5O — never a modal) --}}
    @include('feed.partials.comments', ['post' => $post])

    {{-- Confirmation toast --}}
    <p x-show="toast" x-cloak aria-live="polite"
       class="border-t border-seam px-5 py-3 t-meta text-daylight sm:px-6" x-text="toast"></p>

</x-feed.frame>
