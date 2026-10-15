{{-- One post — THE canonical card (fama-ui, port of posts.js FAMA.postHTML).
     Rendered by the feed (server page 1 + /feed/items html[]), the post page
     ($full = true), the /posts/{post}/fragment endpoint and the Activity
     Saved/Liked lists. Requires eager: author.media, media, skills,
     project.media, mentions.mentioned, linkedUrls.preview (+ viewerLike /
     viewerBookmark for a signed-in viewer). Behaviour = postFrame (ui/feed.js). --}}
@php
    $full = $full ?? false;
    $renderer = app(App\Services\BodyRenderer::class);
    $author = $post->author;
    $actor = $currentActor ?? null;
    $isGuest = $actor === null;
    $authorName = $author?->feedDisplayName() ?? __('Fama');
    $authorUrl = match (true) {
        $author instanceof \App\Models\Brand => route('brand.public', ['brand' => $author->slug]),
        $author !== null => route('talent.public', ['slug' => $author->feedUsername()]),
        default => '#',
    };
    $initials = collect(preg_split('/\s+/', trim($authorName)))->filter()->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $textHtml = filled($post->body) ? $renderer->renderText($post) : '';
    $chips = $post->post_type === 'project_share' ? [] : $renderer->chips($post);
    $liked = $post->relationLoaded('viewerLike') && $post->viewerLike->isNotEmpty();
    $saved = $post->relationLoaded('viewerBookmark') && $post->viewerBookmark->isNotEmpty();
    $isAuthor = $actor !== null && $author !== null
        && $actor->getMorphClass() === $post->author_type
        && (string) $actor->getKey() === (string) $post->author_id;
    $editable = $isAuthor && $post->post_type !== 'project_share' && $post->isEditable();
    $editableUntil = $editable && $post->published_at
        ? $post->published_at->copy()->addMinutes((int) config('feed.edit_window_minutes'))->toIso8601String()
        : null;
    $showTags = $post->skills->isNotEmpty() && ! \App\Support\SkillTags::bodyHasTag($post->body);
    // Compact age, as the prototype prints it: now · 12m · 3h · 1d · 2w.
    $age = (function () use ($post): string {
        $s = max(0, now()->getTimestamp() - ($post->published_at?->getTimestamp() ?? now()->getTimestamp()));

        return match (true) {
            $s < 60 => __('now'),
            $s < 3600 => intdiv($s, 60).'m',
            $s < 86400 => intdiv($s, 3600).'h',
            $s < 604800 => intdiv($s, 86400).'d',
            default => intdiv($s, 604800).'w',
        };
    })();

    // Media → plain items for the card and the viewer.
    $fallbackAlt = __('Image');
    $mediaItems = $post->post_type === 'media'
        ? $post->getMedia('post_media')->map(fn ($media) => [
            'url' => $media->getUrl(),
            'feed_url' => $media->hasGeneratedConversion('feed') ? $media->getUrl('feed') : $media->getUrl(),
            'full_url' => $media->hasGeneratedConversion('full') ? $media->getUrl('full') : $media->getUrl(),
            'alt' => $media->getCustomProperty('alt') ?: $fallbackAlt,
            'is_video' => str_starts_with((string) $media->mime_type, 'video/'),
            'poster' => $media->hasGeneratedConversion('poster') ? $media->getUrl('poster') : null,
        ])->values()->all()
        : [];
    $n = count($mediaItems);

    // Project share: live Project, else the frozen snapshot (ADR-F2).
    if ($post->post_type === 'project_share') {
        $project = $post->relationLoaded('project') ? $post->project : null;
        $snapshot = $post->shared_snapshot ?? [];
        $projectTitle = $project?->title ?? ($snapshot['title'][app()->getLocale()] ?? $snapshot['title']['en'] ?? __('A Project'));
        $projectCover = $project?->cover_image_url ?? ($snapshot['cover_media_path'] ?? null);
        $projectClient = $project?->client_name ?? ($snapshot['client_name'] ?? null);
        $projectYear = $project?->year ?? ($snapshot['year'] ?? null);
        $projectUrl = ($project !== null && $author !== null)
            ? route('talent.work', ['slug' => $author->feedUsername(), 'project' => $project->id])
            : null;
        $collaborators = $post->mentions->map(fn ($m) => $m->mentioned)->filter()
            ->reject(fn ($m) => $author !== null && $m->getMorphClass().':'.$m->getKey() === $post->author_type.':'.$post->author_id)
            ->unique(fn ($m) => $m->getMorphClass().':'.$m->getKey())
            ->map(fn ($m) => $m->feedDisplayName())->values();
        $projectCredits = collect([$authorName, $post->skills->pluck('name')->implode(', ')])->filter()->implode(' · ')
            .($collaborators->isNotEmpty() ? ' — '.__('with :who', ['who' => $collaborators->implode(', ')]) : '');
    }

    $frame = [
        'id' => $post->id,
        'liked' => $liked,
        'saved' => $saved,
        'likes' => (int) $post->likes_count,
        'comments' => (int) $post->comments_count,
        'isAuthor' => $isAuthor,
        'isGuest' => $isGuest,
        'editable' => $editable,
        'editableUntil' => $editableUntil,
        'pinned' => (bool) $post->is_pinned,
        'edited' => $post->edited_at !== null,
        'permalink' => route('posts.show', $post),
        'authorName' => $authorName,
        'body' => (string) $post->body,
        'skillIds' => $post->skills->pluck('id')->values(),
        'media' => $mediaItems,
        'full' => $full,
    ];
@endphp

<article class="post" id="post-{{ $post->id }}" data-id="{{ $post->id }}" @if ($full) data-full="1" @endif x-data="postFrame(@js($frame))">
    <header class="ph-head">
        <a class="av" href="{{ $authorUrl }}" aria-hidden="true" tabindex="-1">
            @if ($author?->feedAvatarUrl())<img src="{{ $author->feedAvatarUrl() }}" alt="" loading="lazy">@else{{ $initials }}@endif
        </a>
        <div class="grow">
            <a class="who" href="{{ $authorUrl }}"><b dir="auto">{{ $authorName }}</b></a>
            <span class="meta"><span dir="ltr">{{ '@'.$author?->feedUsername() }}</span> · <time datetime="{{ $post->published_at?->toIso8601String() }}" title="{{ $post->published_at?->toDayDateTimeString() }}">{{ $age }}</time><span x-show="pinned" @if (! $post->is_pinned) x-cloak @endif> · <span class="pinned">{{ __('Pinned') }}</span></span><span x-show="edited" @if (! $post->edited_at) x-cloak @endif> · {{ __('edited') }}</span></span>
        </div>
        <div class="more-wrap" @keydown.escape.stop="closeMenus(true)">
            <button type="button" class="iconbtn plain" x-ref="moreBtn" @click="toggleMore()" aria-haspopup="menu" :aria-expanded="moreOpen.toString()" aria-expanded="false" aria-label="{{ __('More') }}"><svg class="icon" aria-hidden="true"><use href="#i-more"/></svg></button>
            <div class="pmenu" role="menu" x-show="moreOpen" x-cloak @click.outside="moreOpen = false">
                <template x-if="isAuthor">
                    <div>
                        <button type="button" role="menuitem" x-show="canEdit()" @click="closeMenus(); edit()" x-text="editLabel()"></button>
                        <button type="button" role="menuitem" @click="closeMenus(); togglePin()" x-text="pinned ? @js(__('Unpin')) : @js(__('Pin to top of my posts'))"></button>
                        <button type="button" role="menuitem" data-m="delete" @click="closeMenus(); remove()">{{ __('Delete') }}</button>
                    </div>
                </template>
                <template x-if="!isAuthor">
                    <button type="button" role="menuitem" data-m="report" :disabled="reported" @click="closeMenus(); report()" x-text="reported ? @js(__('You reported this')) : @js(__('Report'))"></button>
                </template>
            </div>
        </div>
    </header>

    @if ($post->post_type === 'project_share')
        @if ($textHtml !== '')<p class="ptext" dir="auto" x-ref="text">{!! $textHtml !!}</p>@endif
        @if ($projectUrl)<a class="projframe" href="{{ $projectUrl }}">@else<div class="projframe">@endif
            <div class="ph cover">
                @if ($projectCover)<img src="{{ $projectCover }}" alt="" loading="lazy">@else<svg class="icon" aria-hidden="true"><use href="#i-image"/></svg>@endif
            </div>
            <div class="pf-b">
                <span class="label">{{ collect([__('Project'), $projectClient, $projectYear])->filter()->implode(' · ') }}</span>
                <b class="pf-t">{{ $projectTitle }}</b>
                <span class="meta">{{ $projectUrl ? $projectCredits : __('This Project is no longer available.') }}</span>
            </div>
        @if ($projectUrl)</a>@else</div>@endif
    @else
        <p class="ptext" dir="auto" x-ref="text">{!! $textHtml !!}</p>
        @if ($chips !== [])
            <div class="chips lchips">
                @foreach ($chips as $chip)
                    <a class="chip" href="{{ $chip['url'] }}" target="_blank" rel="noopener noreferrer nofollow ugc"><svg class="icon" aria-hidden="true"><use href="#i-link"/></svg>{{ $chip['label'] }}</a>
                @endforeach
            </div>
        @endif
        @if ($n > 0)
            <div @class(['pmedia', 'n'.min($n, 4), 'many' => $n > 1]) data-n="{{ $n }}" @dblclick="doubleTapLike()">
                <div class="ptrack" @scroll.passive="onTrack($event)">
                    @foreach ($mediaItems as $i => $item)
                        @if ($item['is_video'])
                            <div @class(['ph', 'extra' => $i > 3])>
                                <video controls preload="metadata" @if ($item['poster']) poster="{{ $item['poster'] }}" @endif><source src="{{ $item['url'] }}"></video>
                            </div>
                        @else
                            <button type="button" @class(['ph', 'extra' => $i > 3]) @click="openViewer({{ $i }})" aria-label="{{ $item['alt'] }} {{ $i + 1 }} / {{ $n }}">
                                <img src="{{ $item['feed_url'] }}" alt="{{ $item['alt'] }}" @if (! ($eagerMedia ?? false)) loading="lazy" @endif>
                                @if ($i === 3 && $n > 4)<span class="plus">+{{ $n - 4 }}</span>@endif
                            </button>
                        @endif
                    @endforeach
                </div>
                @if ($n > 1)
                    <span class="pcount" aria-hidden="true" x-text="(slide + 1) + ' / {{ $n }}'">1 / {{ $n }}</span>
                    <span class="pdots" aria-hidden="true">@for ($i = 0; $i < $n; $i++)<i :class="{ on: slide === {{ $i }} }" @class(['on' => $i === 0])></i>@endfor</span>
                @endif
            </div>
        @endif
    @endif

    @if ($showTags)
        <div class="chips ptags">
            @foreach ($post->skills as $skill)
                <span class="tag">{{ $skill->name }}</span>
            @endforeach
        </div>
    @endif

    <p class="counts meta">
        <span data-likes x-text="likesLabel()">{{ trans_choice(':count like|:count likes', $post->likes_count, ['count' => number_format($post->likes_count)]) }}</span> ·
        @if ($full)
            <a href="#comments" x-text="commentsLabel()">{{ trans_choice(':count comment|:count comments', $post->comments_count, ['count' => number_format($post->comments_count)]) }}</a>
        @else
            <button type="button" class="clink" @click="toggleComments()" :aria-expanded="commentsOpen.toString()" aria-expanded="false" aria-controls="pc-{{ $post->id }}" x-text="commentsLabel()">{{ trans_choice(':count comment|:count comments', $post->comments_count, ['count' => number_format($post->comments_count)]) }}</button>
        @endif
    </p>

    <div class="pacts" @keydown.escape.stop="closeMenus(true)">
        <button type="button" @click="toggleLike()" aria-label="{{ __('Like') }}" :aria-pressed="liked.toString()" aria-pressed="{{ $liked ? 'true' : 'false' }}">
            <svg class="icon" aria-hidden="true"><use :href="liked ? '#i-heart-f' : '#i-heart'" href="#{{ $liked ? 'i-heart-f' : 'i-heart' }}"/></svg><span>{{ __('Like') }}</span>
        </button>
        @if ($full)
            <button type="button" @click="focusComment()" aria-label="{{ __('Comment') }}"><svg class="icon" aria-hidden="true"><use href="#i-msg"/></svg><span>{{ __('Comment') }}</span></button>
        @else
            <button type="button" @click="toggleComments()" :aria-expanded="commentsOpen.toString()" aria-expanded="false" aria-controls="pc-{{ $post->id }}" aria-label="{{ __('Comment') }}"><svg class="icon" aria-hidden="true"><use href="#i-msg"/></svg><span>{{ __('Comment') }}</span></button>
        @endif
        <button type="button" x-ref="shareBtn" @click="toggleShare()" aria-haspopup="menu" :aria-expanded="shareOpen.toString()" aria-expanded="false" aria-label="{{ __('Share') }}"><svg class="icon" aria-hidden="true"><use href="#i-share"/></svg><span>{{ __('Share') }}</span></button>
        <button type="button" @click="toggleSave()" aria-label="{{ __('Save') }}" :aria-pressed="saved.toString()" aria-pressed="{{ $saved ? 'true' : 'false' }}">
            <svg class="icon" aria-hidden="true"><use :href="saved ? '#i-bm-f' : '#i-bm'" href="#{{ $saved ? 'i-bm-f' : 'i-bm' }}"/></svg><span x-text="saved ? @js(__('Saved')) : @js(__('Save'))">{{ $saved ? __('Saved') : __('Save') }}</span>
        </button>
        <div class="pmenu smenu" role="menu" x-show="shareOpen" x-cloak @click.outside="if (! $refs.shareBtn.contains($event.target)) shareOpen = false" @keydown.arrow-down.prevent="moveFocus($event, 1)" @keydown.arrow-up.prevent="moveFocus($event, -1)">
            <button type="button" role="menuitem" @click="copyLink()"><svg class="icon" aria-hidden="true"><use href="#i-link"/></svg>{{ __('Copy link') }}</button>
            <hr>
            @foreach (['LinkedIn' => 'https://www.linkedin.com/sharing/share-offsite/?url=', 'X' => 'https://x.com/intent/post?url=', 'Facebook' => 'https://www.facebook.com/sharer/sharer.php?u=', 'WhatsApp' => 'https://wa.me/?text='] as $net => $base)
                <a role="menuitem" href="{{ $base.urlencode(route('posts.show', $post)) }}" target="_blank" rel="noopener" @click="shareOpen = false"><svg class="icon" aria-hidden="true"><use href="#i-ext"/></svg>{{ __('Share on :network', ['network' => $net]) }}<span class="fx-vh"> ({{ __('opens in a new tab') }})</span></a>
            @endforeach
            <button type="button" role="menuitem" x-show="canNativeShare" x-cloak @click="nativeShare()"><svg class="icon" aria-hidden="true"><use href="#i-share"/></svg>{{ __('More options') }}</button>
        </div>
    </div>

    @unless ($full)
        @include('feed.partials.comments', ['post' => $post])
    @endunless
</article>
