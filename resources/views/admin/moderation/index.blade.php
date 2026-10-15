<x-admin-layout :title="__('Moderation')">
    <div x-data="adminModeration(@js($queue))" data-queue="{{ $queue }}" class="space-y-5"
         data-del-one="{{ __('Delete this item?') }}" data-del-many="{{ __('Delete :n items?') }}"
         data-del-body="{{ __('They are soft-deleted: hidden everywhere and restorable for 30 days, after which their photos and files are removed for good.') }}"
         data-del-ok="{{ __('Delete') }}">
        @php
            // Tabs: brand-owned queues only exist with the brands feature (V1 off).
            $tabs = ['talents' => __('Talents'), 'recognitions' => __('Recognitions'), 'reviews' => __('Talent reviews')];
            if (\App\Support\Features::brands()) {
                $tabs += ['brands' => __('Brands'), 'brand-reviews' => __('Brand reviews'), 'projects' => __('Projects')];
            }
            $tabs['all-reviews'] = __('All reviews');
            $tabHints = ['talents' => __('waiting for verification'), 'recognitions' => __('pending'), 'reviews' => __('pending'), 'brands' => __('unverified'), 'brand-reviews' => __('pending'), 'all-reviews' => __('pending')];
        @endphp
        {{-- Queue switcher + filters, one card --}}
        <div class="rounded-[20px] border border-line bg-surface p-2 sm:p-2.5">
            <div class="flex gap-1 overflow-x-auto" role="tablist" aria-label="{{ __('Queues') }}">
                @foreach ($tabs as $key => $label)
                    @php $n = $waiting[$key] ?? null; @endphp
                    <button type="button" role="tab" @click="openTab('{{ $key }}')" :aria-selected="(tab === '{{ $key }}').toString()"
                            :class="tab === '{{ $key }}' ? 'bg-primary text-on-primary' : 'text-muted hover:bg-elevated hover:text-ink'"
                            class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-pill px-4 py-2.5 text-sm font-semibold transition"
                            @if ($n) title="{{ $n }} {{ $tabHints[$key] ?? '' }}" @endif>
                        {{ $label }}
                        @if ($n)
                            <span class="min-w-6 rounded-pill px-1.5 py-0.5 text-center text-[11px] font-bold leading-none"
                                  :class="tab === '{{ $key }}' ? 'bg-white/20 text-on-primary' : 'bg-gold-weak text-gold'">{{ $n > 99 ? '99+' : $n }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-2 border-t border-line px-1 pt-3 sm:px-2">
                <div class="relative min-w-[220px] flex-1">
                    <svg class="pointer-events-none absolute inset-y-0 start-4 my-auto h-[18px] w-[18px] text-subtle" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                    <input type="search" x-model="q" @input="onSearch()" @search="load()"
                           class="h-11 w-full rounded-pill border-line bg-bg ps-11 pe-4 text-[15px] text-ink placeholder:text-subtle"
                           :placeholder="tab.includes('review') ? '{{ __('Search reviews…') }}' : (tab === 'projects' ? '{{ __('Search projects…') }}' : '{{ __('Search by name, email or username…') }}')"
                           aria-label="{{ __('Search') }}">
                </div>
                {{-- Status filter — options depend on the active queue --}}
                <select x-show="statusOptions().length" x-cloak @change="setStatusFilter($event.target.value)"
                        class="h-11 rounded-pill border-line bg-bg ps-4 text-[15px] text-ink" aria-label="{{ __('Filter by status') }}">
                    <option value="">{{ __('All statuses') }}</option>
                    <template x-for="s in statusOptions()" :key="s">
                        <option :value="s" x-text="$statusLabel(s)" :selected="s === statusFilter"></option>
                    </template>
                </select>
                <span class="ms-auto hidden text-sm text-muted sm:inline" x-show="!loading && meta?.pagination" x-cloak
                      x-text="(meta?.pagination?.total ?? 0) + ' {{ __('results') }}'"></span>
                {{-- Active-filter clear --}}
                <button x-show="q || statusFilter" x-cloak @click="q=''; statusFilter=''; load()"
                        class="inline-flex h-11 items-center gap-1.5 rounded-pill border border-line px-4 text-sm font-semibold text-ink hover:border-ink">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>{{ __('Clear') }}</button>
            </div>
        </div>
        <p class="px-1 text-sm text-muted">{{ __('Review and act on everything users publish — open a row for full details.') }}</p>

        <template x-if="loading"><div><x-admin.skeleton :rows="5" /></div></template>

        {{-- Bulk bar: select-all + actions on the selection; export all (filters) any time --}}
        <div x-show="canBulk && !loading && rows.length" x-cloak
             class="sticky top-24 z-10 flex flex-wrap items-center gap-2 rounded-[16px] border px-4 py-2.5 transition"
             :class="selected.length ? 'border-ink bg-primary text-on-primary' : 'border-line bg-surface text-ink'">
            <label class="inline-flex cursor-pointer items-center gap-2.5 pe-2 text-sm font-semibold">
                <input type="checkbox" :checked="allSelected" x-effect="$el.indeterminate = selected.length > 0 && !allSelected" @change="toggleAll()" class="h-[18px] w-[18px] rounded">
                <span x-text="selected.length ? selected.length + ' {{ __('selected') }}' : '{{ __('Select all on this page') }}'"></span>
            </label>
            <template x-if="selected.length">
                <span class="flex flex-wrap items-center gap-2">
                    <template x-if="tab === 'reviews'">
                        <span class="flex gap-2">
                            <button type="button" @click="batch('approve')" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-success px-4 text-[13px] font-semibold text-white hover:opacity-90">{{ __('Approve') }}</button>
                            <button type="button" @click="batch('reject')" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-white/15 px-4 text-[13px] font-semibold text-on-primary hover:bg-white/25">{{ __('Reject') }}</button>
                        </span>
                    </template>
                    <a :href="exportUrl(true)" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-white/15 px-4 text-[13px] font-semibold text-on-primary hover:bg-white/25">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v11m0 0-4-4m4 4 4-4M5 19h14"/></svg>{{ __('Export selected') }}</a>
                    <button type="button" @click="bulkDelete()" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-danger px-4 text-[13px] font-semibold text-white hover:opacity-90">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>{{ __('Delete') }}</button>
                    <button type="button" @click="selected = []" class="h-9 px-2 text-[13px] font-semibold underline-offset-2 hover:underline">{{ __('Clear selection') }}</button>
                </span>
            </template>
            <a :href="exportUrl(false)" x-show="!selected.length" class="ms-auto inline-flex h-9 items-center gap-1.5 rounded-pill border border-line px-4 text-[13px] font-semibold text-ink hover:border-ink">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v11m0 0-4-4m4 4 4-4M5 19h14"/></svg>
                <span x-text="(q || statusFilter) ? '{{ __('Export filtered (CSV)') }}' : '{{ __('Export all (CSV)') }}'"></span></a>
        </div>

        {{-- Rows --}}
        <div x-show="!loading" class="space-y-2">
            <template x-for="row in rows" :key="row.id">
                <div @click="openDetail(row)" role="button" tabindex="0" @keydown.enter="openDetail(row)"
                     class="flex cursor-pointer flex-wrap items-center gap-3 rounded-[16px] border border-line bg-surface px-4 py-3 text-sm transition hover:border-line-strong hover:shadow-e2 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent sm:px-5"
                     :class="{ 'border-gold-weak ring-1 ring-gold-weak': (tab === 'talents' || tab === 'brands') && !row.is_verified && row.steps_completed && !row.trashed }">
                    <template x-if="canBulk"><input type="checkbox" @click.stop @change="toggle(row.id)" :checked="selected.includes(row.id)" class="h-[18px] w-[18px] shrink-0 rounded" aria-label="{{ __('Select') }}"></template>
                    {{-- Row title. On the global queue it names the entity BEING reviewed
                         (brand for a brand review, talent for a talent review). --}}
                    {{-- Accounts (talents / brands): avatar + name + who-they-are line + state pills --}}
                    <template x-if="tab === 'talents' || tab === 'brands'">
                        <div class="flex min-w-0 flex-1 items-center gap-3.5">
                            <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-pill bg-accent-weak text-sm font-semibold text-accent-ink"
                                  :class="{ 'rounded-[12px]': tab === 'brands' }">
                                <template x-if="row.avatar_url"><img :src="row.avatar_url" alt="" class="h-full w-full object-cover"></template>
                                <template x-if="!row.avatar_url"><span x-text="(row.display_name || row.name || row.email || '?').split(/[\s@.]+/).filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('')"></span></template>
                            </span>
                            <span class="min-w-0">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="truncate text-[15px] font-semibold text-ink" x-text="row.display_name || row.name || row.email || ('#' + row.id)"></span>
                                    <template x-if="row.status"><span :class="$pill(row.status)" x-text="$statusLabel(row.status)"></span></template>
                                    <template x-if="row.is_verified">
                                        <span class="inline-flex items-center gap-1 rounded-pill bg-accent-weak px-2 py-0.5 text-xs font-semibold text-accent-ink">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>{{ __('Verified') }}</span>
                                    </template>
                                    <template x-if="!row.is_verified && row.steps_completed && !row.trashed">
                                        <span class="inline-flex items-center gap-1.5 rounded-pill bg-gold-weak px-2 py-0.5 text-xs font-semibold text-gold">
                                            <span class="h-1.5 w-1.5 animate-pulse rounded-pill bg-gold"></span>{{ __('Ready for review') }}</span>
                                    </template>
                                    <template x-if="row.trashed"><span class="rounded-pill bg-danger-weak px-2 py-0.5 text-xs font-semibold text-danger">{{ __('Deleted') }}</span></template>
                                </span>
                                <span class="mt-0.5 block truncate text-[13px] text-muted"
                                      x-text="[row.slug ? '@' + row.slug : null, row.email && row.email !== (row.display_name || row.name) ? row.email : null, [row.city, row.country].filter(Boolean).join(', ') || null, row.joined ? '{{ __('joined') }} ' + row.joined : null].filter(Boolean).join('  ·  ')"></span>
                            </span>
                        </div>
                    </template>
                    <template x-if="tab !== 'talents' && tab !== 'brands'">
                        <span class="flex flex-wrap items-center gap-3">
                            <span class="font-medium text-ink"
                                  x-text="tab === 'all-reviews' ? ((row.kind === 'brand' ? row.brand : row.talent) || ('#'+row.id)) : (row.display_name || row.name || row.title || row.talent || ('#'+row.id))"></span>
                            <template x-if="row.status"><span :class="$pill(row.status)" x-text="$statusLabel(row.status)"></span></template>
                        </span>
                    </template>
                    {{-- Global queue: tag each row with its kind + the reviewer --}}
                    <template x-if="tab === 'recognitions'">
                        <span class="text-xs text-subtle">
                            <span x-text="row.author"></span> → <span x-text="row.talent"></span>
                        </span>
                    </template>
                    <template x-if="tab === 'all-reviews'">
                        <span class="rounded-pill px-2 py-0.5 text-xs font-medium"
                              :class="row.kind === 'brand' ? 'bg-accent-weak text-accent-ink' : 'bg-elevated text-muted'"
                              x-text="row.kind === 'brand' ? '{{ __('brand review') }}' : '{{ __('talent review') }}'"></span>
                    </template>
                    <template x-if="tab === 'all-reviews' && row.kind === 'brand' && row.talent">
                        <span class="text-xs text-subtle">{{ __('by') }} <span x-text="row.talent"></span></span>
                    </template>
                    {{-- Projects: admin always sees the budget; tagged "budget hidden"
                         when the brand keeps it off the public side. This is the
                         `budget_is_public` flag — distinct from the project's own
                         public/private listing state (the Make private/public toggle). --}}
                    <template x-if="tab === 'projects' && row.budget_min !== null">
                        <span class="rounded-pill bg-elevated px-2 py-0.5 text-xs text-muted">
                            <span x-text="`${row.budget_min}–${row.budget_max} ${row.currency ?? ''}`"></span>
                            <template x-if="!row.budget_is_public"><span class="ms-1 text-danger">{{ __('budget hidden') }}</span></template>
                        </span>
                    </template>
                    {{-- Projects: the project's own listing visibility (`is_public`) —
                         public = listed on public pages/discovery/opportunity feed,
                         private = unlisted. Distinct from the budget flag above. --}}
                    <template x-if="tab === 'projects'">
                        <span class="rounded-pill px-2 py-0.5 text-xs font-medium"
                              :class="row.is_public ? 'bg-success-weak text-success' : 'bg-elevated text-muted'"
                              x-text="row.is_public ? '{{ __('public') }}' : '{{ __('private') }}'"></span>
                    </template>

                    <div class="ms-auto flex flex-wrap items-center gap-2" @click.stop>
                        {{-- Every queue: open the detail drawer (the whole row is also clickable) --}}
                        <button @click="tab === 'talents' && row.slug && !row.trashed ? openPreview(row) : openDetail(row)" class="inline-flex h-9 items-center gap-1.5 rounded-pill border border-line bg-surface px-3.5 text-[13px] font-semibold text-ink transition hover:border-ink">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7Z"/><circle cx="12" cy="12" r="2.8"/></svg>{{ __('View') }}</button>
                        {{-- Talents --}}
                        <template x-if="tab === 'talents'">
                            <span class="flex flex-wrap items-center gap-2">
                                @include('admin.moderation.partials.account-actions', ['obj' => 'row', 'kind' => 'talents'])
                            </span>
                        </template>
                        {{-- Reviews / brand reviews / the global queue --}}
                        <template x-if="tab === 'reviews' || tab === 'brand-reviews' || tab === 'all-reviews'">
                            <span class="flex items-center gap-2">
                                <button @click="$confirm({ title: '{{ __('Approve this review?') }}', message: '{{ __('The review becomes publicly visible.') }}', confirmLabel: '{{ __('Approve') }}', tone: 'accent' }).then(ok => ok && action(row,'approve'))" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-success px-4 text-[13px] font-semibold text-white transition hover:opacity-90"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>{{ __('Approve') }}</button>
                                <button @click="$confirm({ title: '{{ __('Reject this review?') }}', message: '{{ __('The review is hidden and will not be published.') }}', confirmLabel: '{{ __('Reject') }}' }).then(ok => ok && action(row,'reject'))" class="inline-flex h-9 items-center gap-1.5 rounded-pill border border-line bg-surface px-4 text-[13px] font-semibold text-danger transition hover:border-danger hover:bg-danger-weak"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>{{ __('Reject') }}</button>
                            </span>
                        </template>
                        {{-- Recognitions (ADR-Z). Peer vouches, so the copy says
                             "vouch", not "review" — a moderator judging one is
                             weighing whether the stated relationship is real. --}}
                        <template x-if="tab === 'recognitions'">
                            <span class="flex items-center gap-2">
                                <button @click="$confirm({ title: '{{ __('Approve this recognition?') }}', message: '{{ __('The vouch becomes publicly visible on their profile.') }}', confirmLabel: '{{ __('Approve') }}', tone: 'accent' }).then(ok => ok && action(row,'approve'))" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-success px-4 text-[13px] font-semibold text-white transition hover:opacity-90"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>{{ __('Approve') }}</button>
                                <button @click="$confirm({ title: '{{ __('Reject this recognition?') }}', message: '{{ __('The vouch is hidden and will not be published.') }}', confirmLabel: '{{ __('Reject') }}' }).then(ok => ok && action(row,'reject'))" class="inline-flex h-9 items-center gap-1.5 rounded-pill border border-line bg-surface px-4 text-[13px] font-semibold text-danger transition hover:border-danger hover:bg-danger-weak"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>{{ __('Reject') }}</button>
                            </span>
                        </template>
                        {{-- Brands --}}
                        <template x-if="tab === 'brands'">
                            <span class="flex flex-wrap items-center gap-2">
                                @include('admin.moderation.partials.account-actions', ['obj' => 'row', 'kind' => 'brands'])
                            </span>
                        </template>
                        {{-- Projects. Visibility is a state-aware toggle
                             (Make private ⇄ Make public), both confirmed. Cancel
                             is terminal (no reverse transition) so it only shows
                             while the project is still active. --}}
                        <template x-if="tab === 'projects'">
                            <span class="flex gap-1.5">
                                <button x-show="row.is_public"
                                        @click="$confirm({ title: '{{ __('Make this project private?') }}', message: '{{ __('It is unlisted from public brand pages but stays editable.') }}', confirmLabel: '{{ __('Make private') }}', tone: 'accent' }).then(ok => ok && action(row,'private'))"
                                        class="rounded-pill border border-line px-2.5 py-1 text-xs text-muted hover:text-ink">{{ __('Make private') }}</button>
                                <button x-show="!row.is_public"
                                        @click="$confirm({ title: '{{ __('Make this project public?') }}', message: '{{ __('It becomes visible on public brand pages.') }}', confirmLabel: '{{ __('Make public') }}', tone: 'accent' }).then(ok => ok && action(row,'public'))"
                                        class="rounded-pill border border-line px-2.5 py-1 text-xs text-success hover:border-success">{{ __('Make public') }}</button>
                                <button x-show="['draft','open','in_progress'].includes(row.status)"
                                        @click="$confirm({ title: '{{ __('Cancel this project?') }}', message: row.title || '', confirmLabel: '{{ __('Cancel project') }}', cancelLabel: '{{ __('Keep it') }}' }).then(ok => ok && action(row,'cancel'))" class="rounded-pill border border-line px-2.5 py-1 text-xs text-danger">{{ __('Cancel') }}</button>
                            </span>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="!rows.length"><p class="rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('Nothing to moderate here.') }}</p></template>
            <x-admin.pagination />
        </div>

        {{-- Detail drawer (all queues). Animated via the house pattern: mount
             (x-show="detail") → $nextTick flips `drawerOpen` → CSS transitions
             slide/fade in; closing flips it back, and `detail` is cleared only
             after the leave transition so content doesn't blank mid-slide.
             pointer-events-none while closing — a leaving overlay must never
             trap clicks.

             Motion design: the panel uses the sheet curve cubic-bezier(.32,.72,0,1)
             — fast launch, long gentle settle — with an asymmetric 500ms enter /
             300ms exit (leaving should always be quicker than arriving). The
             content layer trails the panel by 80ms (fade + 12px inline drift),
             which reads as depth; on exit it just fades fast with no delay.
             All of it collapses under motion-reduce. --}}
        @php
            $drawerStagger = 'transition-[opacity,translate] motion-reduce:transition-none motion-reduce:translate-x-0';
        @endphp
        {{-- Teleported to <body>: inside the page an ancestor became the fixed
             containing block and left an uncovered strip at the bottom. --}}
        <template x-teleport="body">
        <div x-show="detail" x-cloak @keydown.escape.window="closeDetail()" data-surface="fama-admin"
             class="fixed inset-0 z-50" :class="drawerOpen ? '' : 'pointer-events-none'">
            <div @click="closeDetail()"
                 class="absolute inset-0 bg-black/40 backdrop-blur-[2px] transition-opacity ease-out motion-reduce:transition-none"
                 :class="drawerOpen ? 'opacity-100 duration-500' : 'opacity-0 duration-300'"></div>
            <aside class="absolute inset-y-0 end-0 flex w-full max-w-lg transform flex-col border-s border-line bg-surface shadow-e2 will-change-transform transition-transform ease-[cubic-bezier(0.32,0.72,0,1)] motion-reduce:transition-none"
                   :class="drawerOpen ? 'translate-x-0 duration-500' : 'translate-x-full rtl:-translate-x-full duration-300'"
                   role="dialog" aria-modal="true" :aria-label="detail?.display_name || detail?.name || detail?.title || '{{ __('Details') }}'">
                <header class="flex h-14 shrink-0 items-center justify-between border-b border-line px-5 {{ $drawerStagger }}"
                        :class="drawerOpen ? 'opacity-100 translate-x-0 duration-[450ms] delay-[80ms] ease-out' : 'opacity-0 translate-x-3 rtl:-translate-x-3 duration-150 delay-0 ease-in'">
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-subtle">{{ __('Details') }}</p>
                    <button @click="closeDetail()" class="grid h-8 w-8 place-items-center rounded-pill text-muted transition hover:bg-elevated hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-accent" aria-label="{{ __('Close') }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </header>

                <div class="min-h-0 flex-1 overflow-y-auto {{ $drawerStagger }}"
                     :class="drawerOpen ? 'opacity-100 translate-x-0 duration-[450ms] delay-[80ms] ease-out' : 'opacity-0 translate-x-3 rtl:-translate-x-3 duration-150 delay-0 ease-in'">
                    <div x-show="detailLoading" class="space-y-3 p-6">
                        <div class="h-20 animate-pulse rounded-xl bg-elevated"></div>
                        <div class="h-24 animate-pulse rounded-xl bg-elevated"></div>
                        <div class="h-32 animate-pulse rounded-xl bg-elevated"></div>
                    </div>

                    <template x-if="!detailLoading && detail">
                        <div>
                            {{-- Hero: gradient band + avatar/logo + identity + status --}}
                            <div class="relative overflow-hidden border-b border-line px-6 pb-5 pt-6">
                                <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-gradient-to-b from-accent-weak/60 to-transparent"></div>
                                <div class="relative flex items-start gap-4">
                                    <template x-if="detail.avatar_url || detail.logo_url || detail.cover_url">
                                        <img :src="detail.avatar_url || detail.logo_url || detail.cover_url" alt="" class="h-16 w-16 shrink-0 rounded-2xl border border-line-strong object-cover shadow-e1">
                                    </template>
                                    <template x-if="!(detail.avatar_url || detail.logo_url || detail.cover_url)">
                                        <span class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl border border-line-strong bg-elevated font-display text-2xl text-muted shadow-e1" x-text="(detail.display_name || detail.name || detail.title || '?').charAt(0)"></span>
                                    </template>
                                    <div class="min-w-0 flex-1 pt-0.5">
                                        <h2 class="truncate font-display text-xl text-ink" x-text="detail.display_name || detail.name || detail.title || ('{{ __('Review') }} #' + (detail.id ?? ''))"></h2>
                                        <template x-if="detail.slug"><p class="truncate font-mono text-xs text-muted" x-text="'@' + detail.slug"></p></template>
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            <template x-if="detail.status"><span :class="$pill(detail.status)" x-text="$statusLabel(detail.status)"></span></template>
                                            <template x-if="detail.is_verified"><span class="rounded-pill bg-accent-weak px-2 py-0.5 text-xs font-medium text-accent-ink">{{ __('verified') }}</span></template>
                                            <template x-if="detail.trashed"><span class="rounded-pill bg-danger-weak px-2 py-0.5 text-xs font-medium text-danger">{{ __('deleted') }}</span></template>
                                        </div>
                                    </div>
                                </div>
                                <template x-if="detail.public_url">
                                    <a :href="detail.public_url" target="_blank" rel="noopener"
                                       class="relative mt-4 inline-flex items-center gap-1.5 rounded-pill border border-line px-3 py-1.5 text-xs font-medium text-accent-ink transition hover:border-accent">
                                        {{ __('Open public page') }}
                                        <svg class="h-3.5 w-3.5 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                    </a>
                                </template>
                            </div>

                            <div class="space-y-6 p-6 text-sm">
                                {{-- About: review body / bio / description --}}
                                <template x-if="detail.body || detail.bio || detail.description">
                                    <section>
                                        <p class="mb-2 font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="detail.body ? '{{ __('Review') }}' : '{{ __('About') }}'"></p>
                                        <blockquote class="whitespace-pre-line break-words rounded-xl border border-line bg-bg p-4 text-ink" x-text="detail.body || detail.bio || detail.description"></blockquote>
                                    </section>
                                </template>

                                {{-- Ratings (reviews) --}}
                                <template x-if="detail.rating || detail.average_rating">
                                    <section>
                                        <p class="mb-2 font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Ratings') }}</p>
                                        <div class="flex flex-wrap gap-2 text-xs">
                                            <template x-if="detail.rating"><span class="rounded-pill bg-elevated px-2.5 py-1 text-muted">{{ __('Rating') }}: <span class="text-ink" x-text="detail.rating"></span>/5</span></template>
                                            <template x-if="detail.communication_rating"><span class="rounded-pill bg-elevated px-2.5 py-1 text-muted">{{ __('Communication') }}: <span class="text-ink" x-text="detail.communication_rating"></span></span></template>
                                            <template x-if="detail.fairness_rating"><span class="rounded-pill bg-elevated px-2.5 py-1 text-muted">{{ __('Fairness') }}: <span class="text-ink" x-text="detail.fairness_rating"></span></span></template>
                                            <template x-if="detail.creative_respect_rating"><span class="rounded-pill bg-elevated px-2.5 py-1 text-muted">{{ __('Creative respect') }}: <span class="text-ink" x-text="detail.creative_respect_rating"></span></span></template>
                                            <template x-if="detail.average_rating"><span class="rounded-pill bg-accent-weak px-2.5 py-1 font-medium text-accent-ink">{{ __('Average') }}: <span x-text="detail.average_rating"></span></span></template>
                                        </div>
                                    </section>
                                </template>

                                {{-- Skills (talent) --}}
                                <template x-if="detail.skills && detail.skills.length">
                                    <section>
                                        <p class="mb-2 font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Skills') }}</p>
                                        <div class="flex flex-wrap gap-1.5">
                                            <template x-for="skill in detail.skills" :key="skill"><span class="rounded-pill bg-accent-weak px-2.5 py-1 text-xs font-medium text-accent-ink" x-text="skill"></span></template>
                                        </div>
                                    </section>
                                </template>

                                {{-- Contact: email / phone / website as proper links (LTR, truncated) --}}
                                <template x-if="detail.email || detail.phone || detail.website">
                                    <section>
                                        <p class="mb-2 font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Contact') }}</p>
                                        <div class="divide-y divide-line overflow-hidden rounded-xl border border-line">
                                            <template x-if="detail.email">
                                                <a :href="`mailto:${detail.email}`" :title="detail.email"
                                                   class="flex items-center gap-3 px-4 py-2.5 transition hover:bg-elevated">
                                                    <svg class="h-4 w-4 shrink-0 text-subtle" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                                                    <span dir="ltr" class="truncate text-start font-mono text-xs text-accent-ink" x-text="detail.email"></span>
                                                </a>
                                            </template>
                                            <template x-if="detail.phone">
                                                <a :href="`tel:${detail.phone}`"
                                                   class="flex items-center gap-3 px-4 py-2.5 transition hover:bg-elevated">
                                                    <svg class="h-4 w-4 shrink-0 text-subtle" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                                                    <span dir="ltr" class="truncate text-start font-mono text-xs text-ink" x-text="detail.phone"></span>
                                                </a>
                                            </template>
                                            <template x-if="detail.website">
                                                <a :href="detail.website" target="_blank" rel="noopener" :title="detail.website"
                                                   class="flex items-center gap-3 px-4 py-2.5 transition hover:bg-elevated">
                                                    <svg class="h-4 w-4 shrink-0 text-subtle" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0a8.949 8.949 0 004.951-1.488M12 21a8.949 8.949 0 01-4.951-1.488M3.6 9h16.8M3.6 15h16.8M12 3a8.998 8.998 0 00-3.6 7.5c0 2.9 1.373 5.48 3.6 7.5"/></svg>
                                                    <span dir="ltr" class="truncate text-start text-xs text-accent-ink" x-text="detail.website"></span>
                                                </a>
                                            </template>
                                        </div>
                                    </section>
                                </template>

                                {{-- Budget (project — admin always sees it) --}}
                                <template x-if="detail.budget_min !== null && detail.budget_min !== undefined">
                                    <section class="rounded-xl border border-line bg-bg p-4">
                                        <p class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Budget') }}</p>
                                        <p class="mt-1 font-display text-lg text-ink">
                                            <span x-text="`${detail.budget_min}–${detail.budget_max} ${detail.currency ?? ''}`"></span>
                                            <template x-if="!detail.budget_is_public"><span class="ms-1 align-middle text-xs font-medium text-danger">({{ __('hidden from public') }})</span></template>
                                        </p>
                                    </section>
                                </template>

                                {{-- Fact grid: every scalar the payload carries --}}
                                <section>
                                    <p class="mb-2 font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Details') }}</p>
                                    <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-line bg-line">
                                        @foreach ([
                                            'headline' => __('Headline'),
                                            'reviewer_name' => __('Reviewer'), 'reviewer_role' => __('Reviewer role'), 'reviewer_company' => __('Reviewer company'),
                                            'talent' => __('Talent'), 'brand' => __('Brand'), 'contract_reference' => __('Contract'),
                                            'role' => __('Role sought'), 'type' => __('Type'), 'project_type' => __('Project type'),
                                            'industry' => __('Industry'), 'stage' => __('Stage'),
                                            'founded_year' => __('Founded'), 'company_size' => __('Company size'),
                                            'city' => __('City'), 'country' => __('Country'), 'rate' => __('Pricing rate'),
                                            'start_date' => __('Starts'), 'end_date' => __('Ends'),
                                            'view_count' => __('Views'), 'blocks_count' => __('Blocks'), 'projects_count' => __('Projects'),
                                            'reviews_count' => __('Reviews'), 'gallery_count' => __('Gallery items'), 'contracts_count' => __('Contracts'),
                                            'completed_projects' => __('Completed projects'), 'response_rate_pct' => __('Response rate %'),
                                            'created_at' => __('Created'),
                                        ] as $key => $label)
                                            <template x-if="detail['{{ $key }}'] !== null && detail['{{ $key }}'] !== undefined && detail['{{ $key }}'] !== ''">
                                                <div class="bg-surface p-3 {{ $key === 'created_at' ? 'col-span-2' : '' }}">
                                                    <dt class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ $label }}</dt>
                                                    <dd class="mt-0.5 break-words text-ink" x-text="detail['{{ $key }}']"></dd>
                                                </div>
                                            </template>
                                        @endforeach
                                    </dl>
                                </section>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Drawer actions: same moderation actions, kind-aware --}}
                <footer x-show="!detailLoading && detail" class="flex shrink-0 flex-wrap gap-1.5 border-t border-line px-5 py-3 {{ $drawerStagger }}"
                        :class="drawerOpen ? 'opacity-100 translate-x-0 duration-[450ms] delay-[80ms] ease-out' : 'opacity-0 translate-x-3 rtl:-translate-x-3 duration-150 delay-0 ease-in'">
                    <template x-if="detailKind === 'talents'">
                        <span class="flex flex-wrap gap-1.5">
                            @include('admin.moderation.partials.account-actions', ['obj' => 'detail', 'kind' => 'talents'])
                        </span>
                    </template>
                    <template x-if="detailKind === 'reviews' || detailKind === 'brand-reviews'">
                        <span class="flex gap-1.5">
                            <button @click="$confirm({ title: '{{ __('Approve this review?') }}', message: '{{ __('The review becomes publicly visible.') }}', confirmLabel: '{{ __('Approve') }}', tone: 'accent' }).then(ok => ok && action(detail,'approve'))" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-success px-4 text-[13px] font-semibold text-white transition hover:opacity-90"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>{{ __('Approve') }}</button>
                            <button @click="$confirm({ title: '{{ __('Reject this review?') }}', message: '{{ __('The review is hidden and will not be published.') }}', confirmLabel: '{{ __('Reject') }}' }).then(ok => ok && action(detail,'reject'))" class="inline-flex h-9 items-center gap-1.5 rounded-pill border border-line bg-surface px-4 text-[13px] font-semibold text-danger transition hover:border-danger hover:bg-danger-weak"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>{{ __('Reject') }}</button>
                            {{-- Delete a talent review with a notice email (compose + live preview). --}}
                            <button x-show="detailKind === 'reviews'" @click="openDeleteReview(detail)" class="rounded-pill bg-danger px-2.5 py-1 text-xs text-white hover:opacity-90">{{ __('Delete…') }}</button>
                        </span>
                    </template>
                    <template x-if="detailKind === 'recognitions'">
                        <span class="flex gap-1.5">
                            <button @click="$confirm({ title: '{{ __('Approve this recognition?') }}', message: '{{ __('The vouch becomes publicly visible on their profile.') }}', confirmLabel: '{{ __('Approve') }}', tone: 'accent' }).then(ok => ok && action(detail,'approve'))" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-success px-4 text-[13px] font-semibold text-white transition hover:opacity-90"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>{{ __('Approve') }}</button>
                            <button @click="$confirm({ title: '{{ __('Reject this recognition?') }}', message: '{{ __('The vouch is hidden and will not be published.') }}', confirmLabel: '{{ __('Reject') }}' }).then(ok => ok && action(detail,'reject'))" class="inline-flex h-9 items-center gap-1.5 rounded-pill border border-line bg-surface px-4 text-[13px] font-semibold text-danger transition hover:border-danger hover:bg-danger-weak"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>{{ __('Reject') }}</button>
                        </span>
                    </template>
                    <template x-if="detailKind === 'brands'">
                        <span class="flex flex-wrap gap-1.5">
                            @include('admin.moderation.partials.account-actions', ['obj' => 'detail', 'kind' => 'brands'])
                        </span>
                    </template>
                    <template x-if="detailKind === 'projects'">
                        <span class="flex gap-1.5">
                            <button x-show="detail?.is_public"
                                    @click="$confirm({ title: '{{ __('Make this project private?') }}', message: '{{ __('It is unlisted from public brand pages but stays editable.') }}', confirmLabel: '{{ __('Make private') }}', tone: 'accent' }).then(ok => ok && action(detail,'private'))"
                                    class="rounded-pill border border-line px-2.5 py-1 text-xs text-muted hover:text-ink">{{ __('Make private') }}</button>
                            <button x-show="detail && !detail.is_public"
                                    @click="$confirm({ title: '{{ __('Make this project public?') }}', message: '{{ __('It becomes visible on public brand pages.') }}', confirmLabel: '{{ __('Make public') }}', tone: 'accent' }).then(ok => ok && action(detail,'public'))"
                                    class="rounded-pill border border-line px-2.5 py-1 text-xs text-success hover:border-success">{{ __('Make public') }}</button>
                            <button x-show="['draft','open','in_progress'].includes(detail?.status)"
                                    @click="$confirm({ title: '{{ __('Cancel this project?') }}', message: detail.title || '', confirmLabel: '{{ __('Cancel project') }}', cancelLabel: '{{ __('Keep it') }}' }).then(ok => ok && action(detail,'cancel'))" class="rounded-pill border border-line px-2.5 py-1 text-xs text-danger">{{ __('Cancel') }}</button>
                        </span>
                    </template>
                    <button @click="closeDetail()" class="ms-auto rounded-pill border border-line px-2.5 py-1 text-xs text-muted">{{ __('Close') }}</button>
                </footer>
            </aside>
        </div>
        </template>

        {{-- ── Profile preview panel: the public profile as a visitor sees it ── --}}
        <template x-teleport="body">
            <div x-show="preview.row" x-cloak class="fixed inset-0 z-[60]" data-surface="fama-admin" role="dialog" aria-modal="true" aria-labelledby="pv-title"
                 @keydown.escape.window="preview.row && !notice.open && closePreview()">
                <div class="absolute inset-0 transition-opacity duration-300" style="background: var(--scrim);" :class="preview.shown ? 'opacity-100' : 'opacity-0'" @click="closePreview()"></div>
                <aside class="absolute inset-y-0 end-0 flex w-full max-w-[1180px] flex-col bg-bg shadow-e3 transition-transform duration-300 ease-out sm:w-[88vw]"
                       :class="preview.shown ? 'translate-x-0' : 'translate-x-full rtl:-translate-x-full'">
                    <header class="flex flex-wrap items-center gap-3 border-b border-line bg-surface px-5 py-4 sm:px-7">
                        <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-pill bg-accent-weak text-sm font-semibold text-accent-ink">
                            <template x-if="preview.row?.avatar_url"><img :src="preview.row.avatar_url" alt="" class="h-full w-full object-cover"></template>
                            <template x-if="!preview.row?.avatar_url"><span x-text="(preview.row?.display_name || preview.row?.email || '?').split(/[\s@.]+/).filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('')"></span></template>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[12px] font-semibold uppercase tracking-[0.14em] text-accent-ink">{{ __('Profile preview') }}</p>
                            <h2 id="pv-title" class="flex flex-wrap items-center gap-2 truncate font-display text-2xl text-ink">
                                <span x-text="preview.row?.display_name || preview.row?.email"></span>
                                <template x-if="preview.row?.status"><span :class="$pill(preview.row.status)" class="font-sans text-xs" x-text="$statusLabel(preview.row.status)"></span></template>
                                <template x-if="preview.row?.is_verified"><span class="rounded-pill bg-accent-weak px-2 py-0.5 font-sans text-xs font-semibold text-accent-ink">{{ __('Verified') }}</span></template>
                            </h2>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <template x-if="preview.row">
                                <span class="flex flex-wrap items-center gap-2">
                                    @include('admin.moderation.partials.account-actions', ['obj' => 'preview.row', 'kind' => 'talents'])
                                </span>
                            </template>
                            <a :href="previewUrl(preview.row)" target="_blank" rel="noopener" class="inline-flex h-9 items-center gap-1.5 rounded-pill border border-line bg-surface px-3.5 text-[13px] font-semibold text-ink hover:border-ink">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>{{ __('New tab') }}</a>
                            <button type="button" @click="const r = preview.row; closePreview(); openDetail(r)" class="inline-flex h-9 items-center rounded-pill border border-line bg-surface px-3.5 text-[13px] font-semibold text-ink hover:border-ink">{{ __('Details') }}</button>
                            <button type="button" @click="closePreview()" aria-label="{{ __('Close') }}" class="grid h-9 w-9 place-items-center rounded-pill border border-line bg-surface text-ink hover:border-ink">✕</button>
                        </div>
                    </header>
                    <div class="relative min-h-0 flex-1">
                        <div x-show="!preview.loaded" class="absolute inset-0 grid place-items-center text-sm text-muted">{{ __('Loading the profile…') }}</div>
                        <template x-if="preview.row">
                            <iframe :src="previewUrl(preview.row)" title="{{ __('Profile preview') }}" class="h-full w-full border-0 bg-bg transition-opacity duration-300" :class="preview.loaded ? 'opacity-100' : 'opacity-0'" @load="preview.loaded = true"></iframe>
                        </template>
                    </div>
                </aside>
            </div>
        </template>

        {{-- ── Verify / Suspend composer: the decision + the email the account gets ── --}}
        <template x-teleport="body">
            <div x-show="notice.open" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4 sm:p-6" data-surface="fama-admin"
                 role="dialog" aria-modal="true" aria-labelledby="notice-title" @keydown.escape.window="notice.open && closeNotice()">
                <div class="absolute inset-0" style="background: var(--scrim);" @click="closeNotice()"></div>

                <div class="relative flex max-h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded-[20px] border border-line bg-surface shadow-e3">
                    <div class="flex items-start justify-between gap-4 border-b border-line px-7 py-5">
                        <div>
                            <p class="text-[12px] font-semibold uppercase tracking-[0.14em]" :class="notice.type === 'verify' ? 'text-accent-ink' : 'text-warn'"
                               x-text="notice.type === 'verify' ? '{{ __('Verify account') }}' : '{{ __('Suspend account') }}'"></p>
                            <h2 id="notice-title" class="mt-1 font-display text-2xl text-ink" x-text="(notice.row?.display_name || notice.row?.name || notice.row?.email || '')"></h2>
                            <p class="mt-1 max-w-2xl text-sm text-muted" x-show="notice.type === 'verify'">{{ __('Verification approves the account to go public. It is one-way — hide the account later with Suspend or Unpublish.') }}</p>
                            <p class="mt-1 max-w-2xl text-sm text-muted" x-show="notice.type === 'suspend'" x-cloak>{{ __('The account is hidden from the public until it is reinstated.') }}</p>
                        </div>
                        <button type="button" @click="closeNotice()" aria-label="{{ __('Close') }}" class="grid h-10 w-10 shrink-0 place-items-center rounded-pill border border-line text-lg text-ink transition hover:border-ink">✕</button>
                    </div>

                    <div class="grid min-h-0 flex-1 overflow-hidden md:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
                        {{-- Compose --}}
                        <div class="space-y-5 overflow-y-auto border-b border-line p-7 md:border-b-0 md:border-e">
                            <label class="flex cursor-pointer items-center justify-between gap-4 rounded-[14px] border border-line bg-bg px-4 py-3">
                                <span>
                                    <span class="block text-[15px] font-semibold text-ink">{{ __('Send an email') }}</span>
                                    <span class="block text-sm text-muted">{{ __('Untick to take the decision without emailing them.') }}</span>
                                </span>
                                <input type="checkbox" x-model="notice.send" class="h-5 w-5 rounded border-line-strong text-accent focus:ring-accent">
                            </label>
                            <fieldset class="space-y-4" :disabled="!notice.send" :class="{ 'opacity-50': !notice.send }">
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-ink" for="nt-email">{{ __('To') }}</label>
                                    <input id="nt-email" type="email" x-model="notice.email" class="w-full text-ink" dir="ltr">
                                    <p x-show="notice.errors['notify.email']" x-cloak class="mt-1 text-sm text-danger" x-text="notice.errors['notify.email']?.[0]"></p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-ink" for="nt-subject">{{ __('Subject') }}</label>
                                    <input id="nt-subject" type="text" maxlength="150" x-model="notice.subject" @input="noticeEdited()" class="w-full text-ink">
                                    <p x-show="notice.errors['notify.subject']" x-cloak class="mt-1 text-sm text-danger" x-text="notice.errors['notify.subject']?.[0]"></p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-semibold text-ink" for="nt-heading">{{ __('Heading') }}</label>
                                    <input id="nt-heading" type="text" maxlength="120" x-model="notice.heading" @input="noticeEdited()" class="w-full text-ink">
                                    <p x-show="notice.errors['notify.heading']" x-cloak class="mt-1 text-sm text-danger" x-text="notice.errors['notify.heading']?.[0]"></p>
                                </div>
                                <div>
                                    <label class="mb-1.5 flex items-center justify-between text-sm font-semibold text-ink" for="nt-message"><span>{{ __('Message') }}</span><span class="text-xs font-normal text-subtle" x-text="(notice.message || '').length + ' / 3000'"></span></label>
                                    <textarea id="nt-message" rows="9" maxlength="3000" x-model="notice.message" @input="noticeEdited()" class="w-full text-ink" dir="auto"></textarea>
                                    <p class="mt-1 text-xs text-subtle">{{ __('Leave a blank line between paragraphs.') }}</p>
                                    <p x-show="notice.errors['notify.message']" x-cloak class="mt-1 text-sm text-danger" x-text="notice.errors['notify.message']?.[0]"></p>
                                </div>
                            </fieldset>
                        </div>

                        {{-- Preview: the real email view, rendered by the server --}}
                        <div class="flex min-h-[420px] flex-col bg-bg">
                            <div class="flex items-center justify-between gap-3 border-b border-line px-6 py-3">
                                <p class="text-[12px] font-semibold uppercase tracking-[0.14em] text-subtle">{{ __('Email preview') }}</p>
                                <p class="min-w-0 flex-1 truncate text-sm text-muted"><span class="text-subtle">{{ __('Subject') }}:</span> <span class="text-ink" x-text="notice.subject || '—'"></span></p>
                                {{-- The email has a dark theme for dark-mode mail apps; preview light by default
                                     (noticeDoc rewrites the email's dark-mode media query to off / always on). --}}
                                <div class="inline-flex shrink-0 rounded-pill border border-line bg-surface p-0.5 text-xs font-semibold" role="group" aria-label="{{ __('Preview theme') }}">
                                    <button type="button" @click="notice.scheme = 'light'" :class="notice.scheme !== 'dark' ? 'bg-primary text-on-primary' : 'text-muted'" class="rounded-pill px-2.5 py-1">{{ __('Light') }}</button>
                                    <button type="button" @click="notice.scheme = 'dark'" :class="notice.scheme === 'dark' ? 'bg-primary text-on-primary' : 'text-muted'" class="rounded-pill px-2.5 py-1">{{ __('Dark mail app') }}</button>
                                </div>
                            </div>
                            <div class="relative min-h-0 flex-1">
                                <div x-show="notice.loading" class="absolute inset-0 grid place-items-center text-sm text-muted">{{ __('Loading preview…') }}</div>
                                <iframe x-show="!notice.loading" :srcdoc="noticeDoc" title="{{ __('Email preview') }}" class="h-full min-h-[420px] w-full border-0 bg-white" sandbox="allow-same-origin"
                                        :class="{ 'opacity-40 grayscale': !notice.send }"></iframe>
                                <p x-show="!notice.send" x-cloak class="absolute inset-x-0 top-4 mx-auto w-max rounded-pill bg-primary px-4 py-1.5 text-sm font-semibold text-on-primary">{{ __('No email will be sent') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-line px-7 py-4">
                        <p x-show="notice.errors._" x-cloak class="me-auto text-sm text-danger" x-text="notice.errors._?.[0]"></p>
                        <button type="button" @click="closeNotice()" class="min-h-11 rounded-pill border border-line px-5 text-sm font-semibold text-ink hover:border-ink">{{ __('Cancel') }}</button>
                        <button type="button" @click="submitNotice()" :disabled="notice.sending || notice.loading"
                                class="inline-flex min-h-11 items-center gap-2 rounded-pill px-5 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-60"
                                :class="notice.type === 'verify' ? 'bg-primary' : 'bg-danger'">
                            <span x-show="notice.sending" class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            <span x-text="notice.type === 'verify'
                                ? (notice.send ? '{{ __('Verify & send email') }}' : '{{ __('Verify without email') }}')
                                : (notice.send ? '{{ __('Suspend & send email') }}' : '{{ __('Suspend without email') }}')"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        {{-- ── Delete-review composer (email + reason + LIVE PREVIEW) ──────── --}}
        <template x-teleport="body">
            <div x-show="del.open" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4 sm:p-6"
                 role="dialog" aria-modal="true" aria-labelledby="del-title" @keydown.escape.window="del.open && closeDeleteReview()">
                <div class="absolute inset-0" style="background: var(--scrim);" @click="closeDeleteReview()"></div>

                <div class="relative flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-e4">
                    <div class="flex items-start justify-between gap-4 border-b border-line px-6 py-4">
                        <div>
                            <h2 id="del-title" class="font-display text-xl text-ink">{{ __('Delete review & notify') }}</h2>
                            <p class="mt-0.5 text-sm text-muted">{{ __('The review is removed and this email is sent explaining why.') }}</p>
                        </div>
                        <button type="button" @click="closeDeleteReview()" aria-label="{{ __('Close') }}" class="grid h-9 w-9 shrink-0 place-items-center rounded-pill text-lg text-subtle transition hover:bg-elevated hover:text-ink">✕</button>
                    </div>

                    <div class="grid min-h-0 flex-1 gap-0 overflow-hidden md:grid-cols-2">
                        {{-- Compose --}}
                        <div class="space-y-4 overflow-y-auto border-b border-line p-6 md:border-b-0 md:border-e">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink">{{ __('To (email)') }}</label>
                                <input type="email" x-model="del.email" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent" placeholder="name@example.com">
                                <p x-show="del.errors.email" x-cloak class="mt-1 text-xs text-danger" x-text="del.errors.email?.[0]"></p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink">{{ __('Subject') }}</label>
                                <input type="text" x-model="del.subject" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                                <p x-show="del.errors.subject" x-cloak class="mt-1 text-xs text-danger" x-text="del.errors.subject?.[0]"></p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-ink">{{ __('Reason for removal') }}</label>
                                <textarea x-model="del.reason" rows="6" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent" placeholder="{{ __('Explain why this review is being removed…') }}"></textarea>
                                <p x-show="del.errors.reason" x-cloak class="mt-1 text-xs text-danger" x-text="del.errors.reason?.[0]"></p>
                            </div>
                        </div>

                        {{-- Live preview — mirrors emails/review-removed.blade.php on the shared
                             components/email/layout (cream ground, logo, yellow→orchid band, card). --}}
                        <div class="min-h-0 overflow-y-auto bg-bg p-6">
                            <p class="mb-3 font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Live preview') }}</p>
                            <p class="mb-3 text-[13px] text-subtle">{{ __('Subject') }}: <span class="text-ink" x-text="del.subject || '—'"></span></p>
                            <div class="rounded-2xl bg-[#FAF6E0] p-5 text-sm text-[#231A25]">
                                <img src="{{ asset('images/email/fama-logo.png') }}" alt="{{ config('app.name') }}" class="h-10 w-auto">
                                <div class="mt-4 overflow-hidden rounded-[20px] border border-[#EDE6CB] bg-white">
                                    <div class="flex h-1.5"><span class="w-16 bg-[#FFE72F]"></span><span class="flex-1 bg-[#AD50D3]"></span></div>
                                    <div class="p-6">
                                        <p class="font-mono text-[10px] uppercase tracking-[0.14em] text-[#8B2FA8]">{{ __('Moderation notice') }}</p>
                                        <h3 class="mt-2 font-display text-xl font-bold tracking-tight">{{ __('A review was removed') }}</h3>
                                        <p class="mt-2 text-[13px] leading-relaxed text-[#695E6D]">{{ __('Our team removed a review on Fama. Here is why:') }}</p>
                                        <div class="mt-4 rounded-xl border-s-4 border-[#AD50D3] bg-[#F7E0FF] px-4 py-3 text-[13px] leading-relaxed text-[#8B2FA8]">
                                            <p class="font-mono text-[9px] font-semibold uppercase tracking-[0.14em]">{{ __('Reason') }}</p>
                                            <p class="mt-1 whitespace-pre-wrap" dir="auto" x-text="del.reason || '{{ __('Your reason will appear here…') }}'"></p>
                                        </div>
                                        <template x-if="del.review?.body">
                                            <div>
                                                <p class="mt-5 font-mono text-[9px] uppercase tracking-[0.14em] text-[#98909D]">{{ __('The removed review') }}</p>
                                                <div class="mt-2 rounded-[14px] border border-[#EDE6CB] bg-[#FDFBEF] p-4 text-[13px]">
                                                    <div class="tracking-[3px]"><span class="text-[#7E6600]" x-text="'★'.repeat(del.review?.rating || 0)"></span><span class="text-[#D8CEAC]" x-text="'★'.repeat(5 - (del.review?.rating || 0))"></span></div>
                                                    <p class="mt-1" dir="auto" x-text="'“' + (del.review?.body || '') + '”'"></p>
                                                    <p class="mt-2 text-xs text-[#98909D]" x-text="'— ' + (del.review?.reviewer_name || '') + (del.review?.talent ? ' · {{ __('on') }} ' + del.review.talent : '')"></p>
                                                </div>
                                            </div>
                                        </template>
                                        <p class="mt-5 text-[13px] leading-relaxed text-[#695E6D]">{{ __('If you believe this was a mistake, reply to this email and our team will take another look.') }}</p>
                                    </div>
                                </div>
                                <p class="mt-4 text-[11px] text-[#98909D]">{{ __('You are receiving this because a review on your Fama profile was moderated.') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-line px-6 py-4">
                        <button type="button" @click="closeDeleteReview()" class="rounded-pill border border-line-strong px-4 py-2 text-sm font-medium text-ink hover:bg-elevated">{{ __('Cancel') }}</button>
                        <button type="button" @click="submitDeleteReview()" :disabled="del.sending"
                                class="inline-flex items-center gap-2 rounded-pill bg-danger px-4 py-2 text-sm font-medium text-white transition hover:opacity-90 disabled:opacity-60">
                            <span x-show="del.sending" class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            {{ __('Delete & send email') }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-admin-layout>
