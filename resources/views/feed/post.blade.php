{{-- /posts/{id} — Post & comments (port of post.html): the card, then the
     titled thread. Newest comments first, 10 at a time; each shows its preview
     replies + "View more replies"; replies go one level deep and start with
     the person's @username; comments can be liked; authors edit for 15
     minutes (a countdown shows) and delete any time; a deleted comment with
     replies stays as "Comment deleted"; a failed send shows Retry. Visitors
     read the thread without a comment box.
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
    // Search + share (ADR-AO) — the permalink unfurls with the post's picture.
    $seo = \App\Support\Seo::make()
        ->title($ogTitle)
        ->description($ogDesc)
        ->canonical(route('posts.show', $post))
        ->image($ogImage ?: null)
        ->type('article')
        ->schema([
            '@type' => 'SocialMediaPosting',
            'url' => route('posts.show', $post),
            'headline' => \Illuminate\Support\Str::limit($ogDesc, 110),
            'datePublished' => $post->published_at?->toAtomString() ?? $post->created_at?->toAtomString(),
            'image' => $ogImage ?: null,
            'author' => ['@type' => 'Person', 'name' => $authorName, 'url' => $post->author instanceof \App\Models\Talent ? route('talent.public', ['slug' => $post->author->slug]) : null],
        ]);
    $isGuest = ($currentActor ?? null) === null;
@endphp

<x-fama.layout :title="$authorName" :seo="$seo" :css="['posts', 'pages/post', 'pages/feed-common']">
    <x-slot:head>
        @include('feed.partials.lang')
    </x-slot:head>

    @include('feed.partials.glyphs')

    <main class="wrap main"><div class="col" id="page">
        <a class="back" href="{{ route('feed') }}"><svg class="icon bk" aria-hidden="true"><use href="#i-arrow"/></svg>{{ __('Back to the feed') }}</a>

        @include('feed.partials.post', ['post' => $post, 'full' => true, 'eagerMedia' => true])

        <section class="thread" id="comments" aria-label="{{ trans_choice(':count comment|:count comments', $post->comments_count, ['count' => $post->comments_count]) }}"
                 x-data="commentThread({ postId: {{ $post->id }}, count: {{ (int) $post->comments_count }}, isGuest: @js($isGuest) })">
            <h2 class="thread-h">{{ __('Comments') }}<span x-text="count.toLocaleString()">{{ number_format($post->comments_count) }}</span></h2>
            {{-- A visitor gets no comment box (prototype: GUEST ? "" : composer); Reply / Like on a
                 comment opens the sign-in dialog instead. --}}
            @unless ($isGuest)
                <ul class="cm" style="margin-top:0">
                    @include('feed.partials.comment-composer', ['parent' => null])
                </ul>
            @endunless
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
