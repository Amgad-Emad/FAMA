@php
    // Open Graph / Twitter card so the post unfurls with a preview when its
    // permalink is shared (LinkedIn / X / WhatsApp / Facebook). Needs a
    // publicly reachable host + a crawlable URL to render on the target side.
    $ogTitle = ($post->author?->feedDisplayName() ?? 'Fama').' · '.__('on Fama');
    $ogDesc = \Illuminate\Support\Str::of(strip_tags((string) $post->body))->squish()->limit(160)->value()
        ?: __('A post on the Fama creative feed.');
    $ogImage = $post->getFirstMediaUrl('post_media', 'feed')
        ?: $post->getFirstMediaUrl('post_media')
        ?: ($post->author?->feedAvatarUrl() ?? '');
@endphp

<x-public-layout :title="($post->author?->feedDisplayName() ?? 'Fama').' — '.__('Post')">
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
    </x-slot:head>

    @include('feed.partials.daylight-head')
    @include('feed.partials.glyphs')
    @include('feed.partials.report-dialog')

    <div data-surface="daylight" class="min-h-screen">
        <div class="grain" aria-hidden="true"></div>

        <div class="mx-auto max-w-5xl px-5 py-10">
            <p class="mx-auto mb-8 w-full max-w-[640px]">
                <a href="{{ route('feed') }}" class="t-meta hover:text-daylight">← {{ __('BACK TO THE FEED') }}</a>
            </p>

            <div class="flex flex-col items-center">
                @include('feed.partials.post', ['post' => $post])
            </div>
        </div>
    </div>
</x-public-layout>
