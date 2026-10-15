{{-- /posts/{id} — Post & comments (port of post.html): the card, then the
     titled thread. Newest comments first, 10 at a time; each shows its preview
     replies + "View more replies"; replies go one level deep and start with
     the person's @username; comments can be liked; authors edit for 15
     minutes (a countdown shows) and delete any time; a deleted comment with
     replies stays as "Comment deleted"; a failed send shows Retry.
     Behaviour = postFrame (card) + commentThread (thread). --}}
@php
    $authorName = $post->author?->feedDisplayName() ?? 'Fama';
    // Open Graph / Twitter card so the permalink unfurls when shared.
    $ogTitle = $authorName.' · '.__('on Fama');
    $ogDesc = \Illuminate\Support\Str::of(strip_tags((string) $post->body))->squish()->limit(160)->value()
        ?: __('A post on the Fama creative feed.');
    $ogImage = $post->getFirstMediaUrl('post_media', 'feed')
        ?: $post->getFirstMediaUrl('post_media')
        ?: ($post->author?->feedAvatarUrl() ?? '');
    $isGuest = ($currentActor ?? null) === null;
@endphp

<x-fama.layout :title="$authorName" :css="['posts', 'pages/post', 'pages/feed-common']">
    <x-slot:head>
        <meta property="og:type" content="article">
        <meta property="og:site_name" content="Fama">
        <meta property="og:title" content="{{ $ogTitle }}">
        <meta property="og:description" content="{{ $ogDesc }}">
        <meta property="og:url" content="{{ route('posts.show', $post) }}">
        @if ($ogImage)
            <meta property="og:image" content="{{ $ogImage }}">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:image" content="{{ $ogImage }}">
        @else
            <meta name="twitter:card" content="summary">
        @endif
        <meta name="twitter:title" content="{{ $ogTitle }}">
        <meta name="twitter:description" content="{{ $ogDesc }}">
        @include('feed.partials.lang')
    </x-slot:head>

    @include('feed.partials.glyphs')

    <main class="wrap main"><div class="col" id="page">
        <a class="back" href="{{ route('feed') }}"><svg class="icon bk" aria-hidden="true"><use href="#i-arrow"/></svg>{{ __('Back to the feed') }}</a>

        @include('feed.partials.post', ['post' => $post, 'full' => true, 'eagerMedia' => true])

        <section class="thread" id="comments" aria-label="{{ trans_choice(':count comment|:count comments', $post->comments_count, ['count' => $post->comments_count]) }}"
                 x-data="commentThread({ postId: {{ $post->id }}, count: {{ (int) $post->comments_count }}, isGuest: @js($isGuest) })">
            <h2 class="thread-h">{{ __('Comments') }}<span x-text="count.toLocaleString()">{{ number_format($post->comments_count) }}</span></h2>
            <ul class="cm" style="margin-top:0">
                @include('feed.partials.comment-composer', ['parent' => null])
            </ul>
            <ul class="cm">
                <template x-for="c in comments" :key="c.id">
                    @include('feed.partials.comment-row', ['v' => 'c'])
                </template>
            </ul>
            <p class="meta" x-show="loaded && !comments.length" x-cloak style="margin-top:var(--s4)">{{ __('No comments yet. Start the conversation.') }}</p>
            <button type="button" class="btn btn-2 loadmore" x-show="nextPage !== null" x-cloak @click="loadMore()">{{ __('Show older comments') }}</button>
        </section>
    </div></main>

    @if (($composer ?? null) !== null)
        @include('feed.partials.composer', ['composer' => $composer])
    @endif
    @include('feed.partials.dialogs')
</x-fama.layout>
