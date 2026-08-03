{{-- The dashboard FOLLOWING / FOLLOWERS management surface (Prompt 5-follow).
     Reuses the 5X collection shell — filter row, undo toast, skeleton, empty
     state, pagination — but renders the RAIL card language (portrait talents,
     square brands; the shape is the type signal, no colour) on a Daylight
     surface so Fraunces display, mono handles and --ink-faint apply exactly as
     they do in the rails. Two management-only signals: FOLLOWS YOU BACK
     (mutual) and QUIET SINCE MARCH (>90 days silent). Fully RTL via logical
     properties. --}}
@php
    /** @var string $tab following|followers */
    /** @var bool $showTypeFilter */
    /** @var array $skills present-in-set skills */
    /** @var string $dataUrl */
@endphp

<div data-surface="daylight"
     x-data="feedFollows({
        tab: @js($tab),
        dataUrl: @js($dataUrl),
        showTypeFilter: @js($showTypeFilter),
        followsUrl: @js(url('/feed/follows')),
        undoLabel: @js(__('Unfollowed.')),
     })"
     class="space-y-6">

    {{-- Sub-tabs: FOLLOWING · FOLLOWERS --}}
    <div class="flex gap-2" role="tablist" aria-label="{{ __('Follow graph') }}">
        <button type="button" role="tab" class="dl-chip" :class="{ 'is-active': tab === 'following' }"
                :aria-selected="tab === 'following'" @click="switchTab('following')">{{ __('FOLLOWING') }}</button>
        <button type="button" role="tab" class="dl-chip" :class="{ 'is-active': tab === 'followers' }"
                :aria-selected="tab === 'followers'" @click="switchTab('followers')">{{ __('FOLLOWERS') }}</button>
    </div>

    {{-- Controls --}}
    <div class="flex flex-wrap items-center gap-2">
        <input type="search" x-model="q" @input.debounce.200ms="reload()"
               class="well t-body px-3 py-1.5" style="min-inline-size: 14rem;"
               placeholder="{{ __('Search name or handle…') }}" aria-label="{{ __('Search') }}">

        <template x-if="showTypeFilter">
            <div class="flex gap-1" role="group" aria-label="{{ __('Type') }}">
                <button type="button" class="dl-chip" :class="{ 'is-active': type === 'all' }" @click="type = 'all'; reload()">{{ __('ALL') }}</button>
                <button type="button" class="dl-chip" :class="{ 'is-active': type === 'talents' }" @click="type = 'talents'; reload()">{{ __('TALENTS') }}</button>
                <button type="button" class="dl-chip" :class="{ 'is-active': type === 'brands' }" @click="type = 'brands'; reload()">{{ __('BRANDS') }}</button>
            </div>
        </template>

        @if (count($skills) > 0)
            <select x-model="skill" @change="reload()" class="well t-body px-3 py-1.5" aria-label="{{ __('Filter by Skill') }}">
                <option value="">{{ __('All Skills') }}</option>
                @foreach ($skills as $skillOption)
                    <option value="{{ $skillOption['slug'] }}">{{ $skillOption['name'] }}</option>
                @endforeach
            </select>
        @endif

        <select x-model="sort" @change="reload()" class="well t-body px-3 py-1.5" aria-label="{{ __('Sort') }}">
            <option value="recent">{{ __('Recently followed') }}</option>
            <option value="alpha">{{ __('Alphabetical') }}</option>
            <option value="least_active">{{ __('Least active') }}</option>
        </select>
    </div>

    {{-- Undo toast --}}
    <div x-show="undo.row !== null" x-cloak
         class="flex items-center justify-between rounded-md border border-seam px-4 py-2.5 t-body"
         style="background: var(--dl-surface-sunk);">
        <span x-text="undo.label"></span>
        <button type="button" class="dl-btn is-ready" @click="undoUnfollow()">{{ __('Undo') }}</button>
    </div>

    {{-- Skeleton --}}
    <div x-show="loading" class="feed-follows-grid" aria-hidden="true">
        <template x-for="n in 8" :key="n">
            <div class="frame-shadow"><div class="frame skeleton" style="block-size: 15rem;"></div></div>
        </template>
    </div>

    {{-- Empty state, in the feed's voice --}}
    <div x-show="!loading && rows.length === 0" x-cloak
         class="rounded-md border border-seam p-10 text-center" style="background: var(--dl-surface-sunk);">
        <p class="t-body" x-show="tab === 'following'">
            {{ __('You are not following anyone yet. Follow a few people and your feed fills up.') }}
        </p>
        <p class="t-body" x-show="tab === 'followers'">
            {{ __('No one yet. Post something and they will find you.') }}
        </p>
        <a x-show="tab === 'following'" href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::localizeUrl('/feed') }}"
           class="dl-btn is-ready mt-4 inline-flex">{{ __('Go to the feed') }} →</a>
    </div>

    {{-- Rows --}}
    <div x-show="!loading && rows.length > 0" class="feed-follows-grid">
        <template x-for="row in rows" :key="row.follow_id">
            <div class="frame-shadow follow-card" :class="{ 'is-leaving': row.removing }">
                <div class="frame" :class="row.type === 'brand' ? 'rail-card--square' : 'rail-card--portrait'">
                    {{-- Avatar / brand mark: portrait for talents, square for brands --}}
                    <a :href="profileUrl(row)" class="block">
                        <template x-if="row.type === 'brand'">
                            <span class="flex aspect-square w-full items-center justify-center" style="background: var(--dl-surface-sunk);">
                                <img x-show="row.avatar_url" :src="row.avatar_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                <span x-show="!row.avatar_url" class="avatar-fallback t-display" :style="tint(row)" x-text="(row.display_name ?? '?').slice(0, 1)"></span>
                            </span>
                        </template>
                        <template x-if="row.type !== 'brand'">
                            <span class="block aspect-[4/5] w-full overflow-hidden">
                                <img x-show="row.avatar_url" :src="row.avatar_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                <span x-show="!row.avatar_url" class="avatar-fallback flex h-full w-full items-center justify-center" :style="tint(row)">
                                    <span class="t-display" x-text="(row.display_name ?? '?').slice(0, 1)"></span>
                                </span>
                            </span>
                        </template>
                    </a>

                    <div class="p-3">
                        <a :href="profileUrl(row)" class="block">
                            <span class="block truncate" style="font-family: var(--dl-font-display); font-size: 15px;" x-text="row.display_name"></span>
                        </a>
                        <p class="rail-handle t-meta truncate"><bdi x-text="'@' + row.username"></bdi></p>

                        {{-- Talent: top two Skills · Brand: sector + city --}}
                        <template x-if="row.type !== 'brand'">
                            <p class="mt-1 flex flex-wrap gap-1">
                                <template x-for="skillTag in (row.skills ?? [])" :key="skillTag.slug">
                                    <span class="rounded-sm border border-seam px-1 t-meta" x-text="skillTag.name"></span>
                                </template>
                            </p>
                        </template>
                        <template x-if="row.type === 'brand'">
                            <p class="mt-1 truncate t-meta">
                                <span x-show="row.sector" x-text="(row.sector ?? '').replaceAll('_', ' ')"></span><span x-show="row.sector && row.city"> · </span><span x-show="row.city" x-text="row.city"></span>
                            </p>
                        </template>

                        <p class="mt-1 t-meta tabular-nums" x-text="row.followed_at"></p>

                        {{-- Mutual badge --}}
                        <p x-show="row.mutual" x-cloak class="comment-badge mt-2 inline-block">{{ __('FOLLOWS YOU BACK') }}</p>

                        {{-- Quiet-since prune signal --}}
                        <p x-show="row.quiet_since" x-cloak class="mt-1 t-meta" style="color: var(--dl-ink-faint);" x-text="row.quiet_since"></p>

                        {{-- Control --}}
                        <div class="mt-3">
                            <button x-show="row.can_unfollow" type="button" class="dl-btn"
                                    @click="unfollow(row)">{{ __('UNFOLLOW') }}</button>
                            <button x-show="row.can_follow_back" type="button" class="dl-btn is-ready"
                                    :disabled="row.busy" @click="followBack(row)">{{ __('FOLLOW BACK') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Pagination --}}
    <div x-show="pagination && pagination.last_page > 1" x-cloak class="flex items-center gap-3 t-meta">
        <button @click="page = Math.max(1, page - 1); load()" :disabled="page <= 1" class="dl-btn disabled:opacity-40">‹ {{ __('Prev') }}</button>
        <span x-text="page + ' / ' + (pagination?.last_page ?? 1)"></span>
        <button @click="page = Math.min(pagination.last_page, page + 1); load()" :disabled="pagination && page >= pagination.last_page" class="dl-btn disabled:opacity-40">{{ __('Next') }} ›</button>
    </div>
</div>
