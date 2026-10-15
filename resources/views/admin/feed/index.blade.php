{{--
    Posts module (feed moderation, Phase 5Q rebuilt): All posts · Reported ·
    Comments. Rich rows (author, excerpt, photo, likes / comments / open reports,
    state), single actions (hide / unhide / remove with a reason that is sent to
    the author / delete) and bulk hide / unhide / delete. Every action goes
    through FeedModerationService (state machines + audit log).
    Behaviour: adminPosts (resources/js/admin.js).
--}}
@php
    $tabs = [
        'posts' => [__('All posts'), $counts['posts'] ?? null, false],
        'reports' => [__('Reported'), $counts['reports'] ?? null, true],
        'comments' => [__('Comments'), $counts['comments'] ?? null, false],
        'deleted' => [__('Deleted'), $counts['deleted'] ?? null, false],
    ];
    $reasonLabels = collect(config('feed.report_reasons', []))->mapWithKeys(fn ($r) => [$r => __(ucfirst(str_replace('_', ' ', $r)))])->all();
@endphp
<x-admin-layout :title="__('Posts')">
    <div x-data="adminPosts({ queue: @js($queue), base: @js(route('admin.moderation.feed')), reasons: @js($reasonLabels), canPurge: @js($canPurge) })" class="space-y-5"
         data-purge-title="{{ __('Delete :n forever?') }}" data-purge-body="{{ __('This can’t be undone. Comments, likes, saves, mentions and photos / videos go with it.') }}"
         data-del-title="{{ __('Delete :n?') }}" data-del-body="{{ __('Soft-deleted: gone from the feed and restorable for 30 days, after which photos and videos are removed for good.') }}">

        {{-- Tabs + filters --}}
        <div class="rounded-[20px] border border-line bg-surface p-2.5">
            <div class="flex gap-1 overflow-x-auto" role="tablist">
                @foreach ($tabs as $key => [$label, $n, $alert])
                    <button type="button" role="tab" @click="openTab('{{ $key }}')" :aria-selected="(tab === '{{ $key }}').toString()"
                            :class="tab === '{{ $key }}' ? 'bg-primary text-on-primary' : 'text-muted hover:bg-elevated hover:text-ink'"
                            class="inline-flex shrink-0 items-center gap-2 rounded-pill px-4 py-2.5 text-sm font-semibold transition">
                        {{ $label }}
                        @if ($n !== null)
                            <span class="min-w-6 rounded-pill px-1.5 py-0.5 text-center text-[11px] font-bold leading-none"
                                  :class="tab === '{{ $key }}' ? 'bg-white/20 text-on-primary' : '{{ $alert && $n ? 'bg-danger-weak text-danger' : 'bg-elevated text-muted' }}'">{{ $n > 999 ? '999+' : $n }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-2 border-t border-line px-1 pt-3 sm:px-2">
                <div class="relative min-w-[220px] flex-1" x-show="tab !== 'reports'">
                    <svg class="pointer-events-none absolute inset-y-0 start-4 my-auto h-[18px] w-[18px] text-subtle" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                    <input type="search" x-model="q" @input.debounce.400ms="page = 1; load()" placeholder="{{ __('Search what people wrote…') }}"
                           class="h-11 w-full rounded-pill border-line bg-bg ps-11 pe-4 text-[15px] text-ink placeholder:text-subtle" aria-label="{{ __('Search') }}">
                </div>
                <div x-show="tab === 'deleted'" x-cloak class="inline-flex rounded-pill border border-line bg-bg p-1" role="group">
                    <button type="button" @click="deletedKind = 'posts'; page = 1; selected = []; load()" :class="deletedKind === 'posts' ? 'bg-primary text-on-primary' : 'text-muted hover:text-ink'" class="rounded-pill px-3.5 py-1.5 text-sm font-semibold">{{ __('Posts') }}</button>
                    <button type="button" @click="deletedKind = 'comments'; page = 1; selected = []; load()" :class="deletedKind === 'comments' ? 'bg-primary text-on-primary' : 'text-muted hover:text-ink'" class="rounded-pill px-3.5 py-1.5 text-sm font-semibold">{{ __('Comments') }}</button>
                </div>
                <select x-show="tab !== 'reports' && tab !== 'deleted'" x-model="state" @change="page = 1; load()" class="h-11 rounded-pill border-line bg-bg ps-4 text-[15px] text-ink" aria-label="{{ __('State') }}">
                    <option value="">{{ __('All states') }}</option>
                    <option value="published">{{ __('Published') }}</option>
                    <option value="hidden">{{ __('Hidden') }}</option>
                    <option value="removed">{{ __('Removed') }}</option>
                </select>
                <div x-show="tab === 'reports'" x-cloak class="inline-flex rounded-pill border border-line bg-bg p-1" role="group" aria-label="{{ __('Status') }}">
                    @foreach (['' => __('Needs action'), 'actioned' => __('Actioned'), 'dismissed' => __('Dismissed'), 'all' => __('All')] as $st => $label)
                        <button type="button" @click="status = '{{ $st }}'; page = 1; load()" :class="status === '{{ $st }}' ? 'bg-primary text-on-primary' : 'text-muted hover:text-ink'"
                                class="rounded-pill px-3.5 py-1.5 text-sm font-semibold transition">{{ $label }}</button>
                    @endforeach
                </div>
                <span class="ms-auto text-sm text-muted" x-show="pagination" x-cloak x-text="(pagination?.total ?? 0) + ' {{ __('results') }}'"></span>
            </div>
        </div>

        {{-- Bulk bar (posts / comments) --}}
        <div x-show="tab !== 'reports' && !loading && rows.length" x-cloak data-bulk
             class="sticky top-24 z-10 flex flex-wrap items-center gap-2 rounded-[16px] border px-4 py-2.5 transition"
             :class="selected.length ? 'border-ink bg-primary text-on-primary' : 'border-line bg-surface text-ink'">
            <label class="inline-flex cursor-pointer items-center gap-2.5 pe-2 text-sm font-semibold">
                <input type="checkbox" :checked="allSelected" x-effect="$el.indeterminate = selected.length > 0 && !allSelected" @change="toggleAll()" class="h-[18px] w-[18px] rounded">
                <span x-text="selected.length ? selected.length + ' {{ __('selected') }}' : '{{ __('Select all on this page') }}'"></span>
            </label>
            <template x-if="selected.length">
                <span class="flex flex-wrap items-center gap-2">
                    <template x-if="tab === 'deleted'">
                        <span class="flex gap-2">
                            <button type="button" @click="trashBulk('restore')" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-white px-4 text-[13px] font-semibold text-ink hover:opacity-90">{{ __('Restore') }}</button>
                            <button type="button" x-show="canPurge" @click="trashBulk('purge')" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-danger px-4 text-[13px] font-semibold text-white hover:opacity-90">{{ __('Delete forever') }}</button>
                        </span>
                    </template>
                    <button type="button" x-show="tab !== 'deleted'" @click="bulk('hide')" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-white/15 px-4 text-[13px] font-semibold hover:bg-white/25">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.6 5.1A9.7 9.7 0 0 1 12 5c6 0 9.5 7 9.5 7a17 17 0 0 1-2.6 3.4M6.6 6.6C3.9 8.4 2.5 12 2.5 12s3.5 7 9.5 7a9.6 9.6 0 0 0 5.4-1.6"/></svg>{{ __('Hide') }}</button>
                    <button type="button" x-show="tab !== 'deleted'" @click="bulk('restore')" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-white/15 px-4 text-[13px] font-semibold hover:bg-white/25">{{ __('Unhide') }}</button>
                    <button type="button" x-show="tab !== 'deleted'" @click="bulk('delete')" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-danger px-4 text-[13px] font-semibold text-white hover:opacity-90">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>{{ __('Delete') }}</button>
                    <button type="button" @click="selected = []" class="h-9 px-2 text-[13px] font-semibold hover:underline">{{ __('Clear selection') }}</button>
                </span>
            </template>
            <span x-show="notice" x-cloak class="ms-auto text-sm font-semibold" x-text="notice"></span>
        </div>

        <template x-if="loading"><div><x-admin.skeleton :rows="5" /></div></template>

        {{-- Posts & comments --}}
        <div x-show="!loading && tab !== 'reports' && tab !== 'deleted'" class="space-y-2">
            <template x-for="row in rows" :key="tab + row.id">
                <article class="flex items-start gap-3.5 rounded-[16px] border bg-surface px-4 py-4 transition hover:shadow-e2 sm:px-5"
                         :class="row.open_reports ? 'border-danger-weak' : 'border-line'">
                    <input type="checkbox" class="mt-3 h-[18px] w-[18px] shrink-0 rounded" :checked="selected.includes(row.id)" @change="toggle(row.id)" aria-label="{{ __('Select') }}">
                    <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-pill bg-accent-weak text-sm font-semibold text-accent-ink">
                        <template x-if="row.author_avatar"><img :src="row.author_avatar" alt="" class="h-full w-full object-cover"></template>
                        <template x-if="!row.author_avatar"><span x-text="initials(row.author)"></span></template>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-2 text-sm">
                            <b class="text-[15px] font-semibold text-ink" x-text="row.author || '—'"></b>
                            <span class="text-muted" x-show="row.author_username" x-text="'@' + row.author_username"></span>
                            <span class="text-subtle">·</span>
                            <span class="text-muted" x-text="row.ago"></span>
                            <span :class="statePill(row)" x-text="stateLabel(row)"></span>
                            <template x-if="row.open_reports"><span class="inline-flex items-center gap-1 rounded-pill bg-danger-weak px-2 py-0.5 text-xs font-semibold text-danger">⚑ <span x-text="row.open_reports + ' {{ __('open reports') }}'"></span></span></template>
                        </p>
                        <p class="mt-1.5 line-clamp-2 text-[15px] leading-relaxed text-ink" dir="auto" x-text="row.excerpt || '{{ __('(no text)') }}'"></p>
                        <p class="mt-2 flex flex-wrap items-center gap-3 text-[13px] text-muted" x-show="tab === 'posts'">
                            <span>♥ <span x-text="row.likes ?? 0"></span></span>
                            <span>💬 <span x-text="row.comments ?? 0"></span></span>
                            <span x-show="row.media_count" x-text="row.media_count + ' {{ __('photos / videos') }}'"></span>
                        </p>
                    </div>
                    <template x-if="row.thumb"><img :src="row.thumb" alt="" class="hidden h-16 w-16 shrink-0 rounded-[12px] object-cover sm:block"></template>
                    <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                        <a :href="row.permalink" target="_blank" rel="noopener" class="inline-flex h-9 items-center gap-1.5 rounded-pill border border-line px-3.5 text-[13px] font-semibold text-ink hover:border-ink">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7Z"/><circle cx="12" cy="12" r="2.8"/></svg>{{ __('View') }}</a>
                        <template x-if="!row.deleted && row.state === 'published'">
                            <button type="button" @click="act(row, 'hide')" class="inline-flex h-9 items-center gap-1.5 rounded-pill border border-line px-3.5 text-[13px] font-semibold text-warn hover:border-warn hover:bg-warn-weak">{{ __('Hide') }}</button>
                        </template>
                        <template x-if="!row.deleted && row.state === 'hidden'">
                            <button type="button" @click="act(row, 'restore')" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-primary px-3.5 text-[13px] font-semibold text-on-primary hover:opacity-90">{{ __('Unhide') }}</button>
                        </template>
                        <div class="relative" x-data="{ more: false }" x-show="!row.deleted" @click.outside="more = false">
                            <button type="button" @click="more = !more" aria-label="{{ __('More actions') }}" class="grid h-9 w-9 place-items-center rounded-pill border border-line text-ink hover:border-ink">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg></button>
                            <div x-show="more" x-cloak class="absolute end-0 top-[calc(100%+6px)] z-30 w-60 rounded-[14px] border border-line bg-surface p-1.5 shadow-e3">
                                <button type="button" x-show="row.state !== 'removed'" @click="more = false; openReason(row)" class="flex w-full items-center gap-2.5 rounded-[10px] px-3 py-2 text-start text-sm font-medium text-ink hover:bg-elevated">{{ __('Remove & tell the author…') }}</button>
                                <button type="button" @click="more = false; act(row, 'delete')" class="flex w-full items-center gap-2.5 rounded-[10px] px-3 py-2 text-start text-sm font-medium text-danger hover:bg-danger-weak">{{ __('Delete') }}</button>
                            </div>
                        </div>
                    </div>
                </article>
            </template>
        </div>

        {{-- Deleted posts / comments (Trash endpoints) --}}
        <div x-show="!loading && tab === 'deleted'" class="space-y-2">
            <template x-for="row in rows" :key="'d' + deletedKind + row.id">
                <article class="flex flex-wrap items-center gap-3.5 rounded-[16px] border border-line bg-surface px-4 py-3.5 sm:px-5">
                    <input type="checkbox" class="h-[18px] w-[18px] shrink-0 rounded" :checked="selected.includes(row.id)" @change="toggle(row.id)" aria-label="{{ __('Select') }}">
                    <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-pill bg-elevated text-sm font-semibold text-muted grayscale">
                        <template x-if="row.avatar"><img :src="row.avatar" alt="" class="h-full w-full object-cover"></template>
                        <template x-if="!row.avatar"><span x-text="initials(row.subtitle)"></span></template>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="line-clamp-2 text-[15px] text-ink" dir="auto" x-text="row.title"></p>
                        <p class="mt-0.5 text-[13px] text-muted" x-text="row.subtitle + '  ·  {{ __('deleted') }} ' + row.deleted_ago"></p>
                        <p class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                            <span class="rounded-pill px-2 py-0.5 font-semibold" :class="row.purge_in_days <= 3 ? 'bg-danger-weak text-danger' : 'bg-elevated text-muted'" x-text="'{{ __('Gone for good in') }} ' + row.purge_in_days + ' {{ __('days') }}'"></span>
                            <template x-if="row.blocked"><span class="font-semibold text-warn" x-text="row.blocked"></span></template>
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <button type="button" @click="trashRestore(row)" :disabled="!!row.blocked" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-primary px-4 text-[13px] font-semibold text-on-primary hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 3-6.7L3 8m0-5v5h5"/></svg>{{ __('Restore') }}</button>
                        <button type="button" x-show="canPurge" @click="trashPurge(row)" class="inline-flex h-9 items-center rounded-pill border border-line px-4 text-[13px] font-semibold text-danger hover:border-danger hover:bg-danger-weak">{{ __('Delete forever') }}</button>
                    </div>
                </article>
            </template>
        </div>

        {{-- Reported posts & comments --}}
        <div x-show="!loading && tab === 'reports'" class="space-y-2">
            <template x-for="row in rows" :key="'r' + row.id">
                <article class="rounded-[16px] border border-line bg-surface px-5 py-4">
                    <div class="flex flex-wrap items-start gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-[12px] bg-danger-weak text-danger">⚑</span>
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="rounded-pill bg-danger-weak px-2 py-0.5 text-xs font-semibold text-danger" x-text="reasons[row.reason] || row.reason"></span>
                                <span class="rounded-pill bg-elevated px-2 py-0.5 text-xs font-semibold text-muted" x-text="row.target_type === 'post' ? '{{ __('Post') }}' : '{{ __('Comment') }}'"></span>
                                <span :class="$pill(row.status)" x-text="$statusLabel(row.status)"></span>
                                <template x-if="row.auto_hidden"><span class="rounded-pill bg-warn-weak px-2 py-0.5 text-xs font-semibold text-warn">{{ __('Auto-hidden') }}</span></template>
                                <span class="text-muted">{{ __('by') }} <b class="font-semibold text-ink" x-text="row.reporter || '—'"></b> · <span x-text="row.created_at"></span></span>
                            </p>
                            <blockquote class="mt-2 rounded-[12px] border-s-4 border-line-strong bg-bg px-4 py-2.5 text-[15px] text-ink" dir="auto" x-text="row.content ?? '{{ __('(content no longer exists)') }}'"></blockquote>
                            <p x-show="row.note" class="mt-2 text-sm text-muted">{{ __('Reporter’s note:') }} “<span x-text="row.note"></span>”</p>
                            <p x-show="row.resolved_by" class="mt-1 text-xs text-subtle">{{ __('Resolved by') }} <span x-text="row.resolved_by"></span></p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <a x-show="row.permalink" :href="row.permalink" target="_blank" rel="noopener" class="inline-flex h-9 items-center rounded-pill border border-line px-3.5 text-[13px] font-semibold text-ink hover:border-ink">{{ __('View') }}</a>
                            <template x-if="row.status === 'open' || row.status === 'reviewing'">
                                <span class="flex flex-wrap gap-2">
                                    <button type="button" x-show="row.content_state === 'published'" @click="act({ id: row.target_id, kind: row.target_type }, 'hide')" class="inline-flex h-9 items-center rounded-pill border border-line px-3.5 text-[13px] font-semibold text-warn hover:border-warn hover:bg-warn-weak">{{ __('Hide') }}</button>
                                    <button type="button" x-show="row.content_state !== 'removed'" @click="openReason({ id: row.target_id, kind: row.target_type })" class="inline-flex h-9 items-center rounded-pill bg-danger px-3.5 text-[13px] font-semibold text-white hover:opacity-90">{{ __('Remove…') }}</button>
                                    <button type="button" @click="resolve(row, 'actioned')" class="inline-flex h-9 items-center rounded-pill bg-primary px-3.5 text-[13px] font-semibold text-on-primary hover:opacity-90">{{ __('Mark actioned') }}</button>
                                    <button type="button" @click="resolve(row, 'dismissed')" class="inline-flex h-9 items-center rounded-pill border border-line px-3.5 text-[13px] font-semibold text-muted hover:border-ink hover:text-ink">{{ __('Dismiss') }}</button>
                                </span>
                            </template>
                        </div>
                    </div>
                </article>
            </template>
        </div>

        <template x-if="!loading && !rows.length">
            <div class="rounded-[20px] border border-dashed border-line py-14 text-center">
                <p class="font-display text-xl text-ink" x-text="tab === 'reports' ? '{{ __('No reports to act on') }}' : '{{ __('Nothing here') }}'"></p>
                <p class="mt-1 text-sm text-muted">{{ __('All clear.') }}</p>
            </div>
        </template>

        {{-- Pagination --}}
        <div x-show="pagination && pagination.last_page > 1" x-cloak class="flex items-center justify-center gap-3 text-sm text-muted">
            <button @click="page = Math.max(1, page - 1); load()" :disabled="page <= 1" class="h-9 rounded-pill border border-line px-4 font-semibold disabled:opacity-40">‹ {{ __('Prev') }}</button>
            <span x-text="page + ' / ' + (pagination?.last_page ?? 1)"></span>
            <button @click="page = Math.min(pagination.last_page, page + 1); load()" :disabled="pagination && page >= pagination.last_page" class="h-9 rounded-pill border border-line px-4 font-semibold disabled:opacity-40">{{ __('Next') }} ›</button>
        </div>

        {{-- Remove & tell the author: reason + the email (live preview of the real view) --}}
        <template x-teleport="body">
            <div x-show="reason.open" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4 sm:p-6" data-surface="fama-admin" role="dialog" aria-modal="true" aria-labelledby="rm-title" @keydown.escape.window="reason.open && !reason.sending && (reason.open = false)">
                <div class="absolute inset-0" style="background: var(--scrim)" @click="!reason.sending && (reason.open = false)"></div>
                <div class="relative flex max-h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded-[20px] border border-line bg-surface shadow-e3">
                    <div class="flex items-start justify-between gap-4 border-b border-line px-7 py-5">
                        <div>
                            <p class="text-[12px] font-semibold uppercase tracking-[0.14em] text-danger" x-text="reason.kind === 'comment' ? '{{ __('Remove comment') }}' : '{{ __('Remove post') }}'"></p>
                            <h2 id="rm-title" class="mt-1 font-display text-2xl text-ink">{{ __('Remove and tell the author') }}</h2>
                            <p class="mt-1 text-sm text-muted">{{ __('Removal is final. The author gets an in-app notice with your reason, and this email if you send it.') }}</p>
                        </div>
                        <button type="button" @click="reason.open = false" aria-label="{{ __('Close') }}" class="grid h-10 w-10 shrink-0 place-items-center rounded-pill border border-line text-lg text-ink hover:border-ink">✕</button>
                    </div>
                    <div class="grid min-h-0 flex-1 overflow-hidden md:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
                        <div class="space-y-5 overflow-y-auto border-b border-line p-7 md:border-b-0 md:border-e">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-ink" for="rm-reason">{{ __('Reason') }} <span class="text-danger">*</span></label>
                                <textarea id="rm-reason" x-model="reason.text" @input="noticeEdited()" rows="3" maxlength="500" class="w-full text-ink" dir="auto" placeholder="{{ __('e.g. It breaks our community guidelines on nudity.') }}"></textarea>
                                <p class="mt-1 text-xs text-subtle">{{ __('Shown in the in-app notice and highlighted in the email.') }}</p>
                            </div>
                            <label class="flex cursor-pointer items-center justify-between gap-4 rounded-[14px] border border-line bg-bg px-4 py-3">
                                <span><span class="block text-[15px] font-semibold text-ink">{{ __('Send an email') }}</span><span class="block text-sm text-muted">{{ __('Untick to only send the in-app notice.') }}</span></span>
                                <input type="checkbox" x-model="reason.send" class="h-5 w-5 rounded">
                            </label>
                            <fieldset class="space-y-4" :disabled="!reason.send" :class="{ 'opacity-50': !reason.send }">
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-ink" for="rm-email">{{ __('To') }}</label>
                                    <input id="rm-email" type="email" x-model="reason.email" dir="ltr" class="w-full text-ink">
                                    <p x-show="reason.errors['notify.email']" x-cloak class="mt-1 text-sm text-danger" x-text="reason.errors['notify.email']?.[0]"></p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-ink" for="rm-subject">{{ __('Subject') }}</label>
                                    <input id="rm-subject" type="text" maxlength="150" x-model="reason.subject" @input="noticeEdited()" class="w-full text-ink">
                                </div>
                                <div>
                                    <label class="mb-1.5 flex items-center justify-between text-sm font-semibold text-ink" for="rm-message"><span>{{ __('Message') }}</span><span class="text-xs font-normal text-subtle" x-text="(reason.message || '').length + ' / 3000'"></span></label>
                                    <textarea id="rm-message" rows="7" maxlength="3000" x-model="reason.message" @input="noticeEdited()" class="w-full text-ink" dir="auto"></textarea>
                                    <p class="mt-1 text-xs text-subtle">{{ __('Leave a blank line between paragraphs.') }}</p>
                                </div>
                            </fieldset>
                        </div>
                        <div class="flex min-h-[420px] flex-col bg-bg">
                            <div class="flex items-center gap-3 border-b border-line px-6 py-3">
                                <p class="text-[12px] font-semibold uppercase tracking-[0.14em] text-subtle">{{ __('Email preview') }}</p>
                                <p class="min-w-0 flex-1 truncate text-sm text-muted"><span class="text-subtle">{{ __('Subject') }}:</span> <span class="text-ink" x-text="reason.subject || '—'"></span></p>
                                <div class="inline-flex shrink-0 rounded-pill border border-line bg-surface p-0.5 text-xs font-semibold" role="group">
                                    <button type="button" @click="reason.scheme = 'light'" :class="reason.scheme !== 'dark' ? 'bg-primary text-on-primary' : 'text-muted'" class="rounded-pill px-2.5 py-1">{{ __('Light') }}</button>
                                    <button type="button" @click="reason.scheme = 'dark'" :class="reason.scheme === 'dark' ? 'bg-primary text-on-primary' : 'text-muted'" class="rounded-pill px-2.5 py-1">{{ __('Dark mail app') }}</button>
                                </div>
                            </div>
                            <div class="relative min-h-0 flex-1">
                                <div x-show="reason.loading" class="absolute inset-0 grid place-items-center text-sm text-muted">{{ __('Loading preview…') }}</div>
                                <iframe x-show="!reason.loading" :srcdoc="reasonDoc" title="{{ __('Email preview') }}" class="h-full min-h-[420px] w-full border-0 bg-white" sandbox="allow-same-origin" :class="{ 'opacity-40 grayscale': !reason.send }"></iframe>
                                <p x-show="!reason.send" x-cloak class="absolute inset-x-0 top-4 mx-auto w-max rounded-pill bg-primary px-4 py-1.5 text-sm font-semibold text-on-primary">{{ __('No email will be sent') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-line px-7 py-4">
                        <p x-show="reason.errors._" x-cloak class="me-auto text-sm text-danger" x-text="reason.errors._?.[0]"></p>
                        <button type="button" @click="reason.open = false" class="min-h-11 rounded-pill border border-line px-5 text-sm font-semibold text-ink hover:border-ink">{{ __('Cancel') }}</button>
                        <button type="button" @click="submitReason()" :disabled="!reason.text.trim() || reason.sending || reason.loading"
                                class="inline-flex min-h-11 items-center gap-2 rounded-pill bg-danger px-5 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-50">
                            <span x-show="reason.sending" class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            <span x-text="reason.send ? '{{ __('Remove & send email') }}' : '{{ __('Remove without email') }}'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-admin-layout>
