<x-admin-layout :title="__('Moderation')">
    <div x-data="adminModeration(@js($queue))" data-queue="{{ $queue }}" class="space-y-5">
        <p class="text-sm text-muted">{{ __('Review and act on everything users publish — open a row for full details.') }}</p>
        {{-- Tabs --}}
        <div class="flex flex-wrap items-center gap-2">
            @foreach (['talents' => __('Talents'), 'brands' => __('Brands'), 'projects' => __('Projects'), 'reviews' => __('Talent reviews'), 'brand-reviews' => __('Brand reviews'), 'all-reviews' => __('All reviews')] as $key => $label)
                <button @click="openTab('{{ $key }}')" :class="tab === '{{ $key }}' ? 'bg-accent text-on-primary' : 'border border-line text-muted hover:text-ink'" class="rounded-pill px-3 py-1.5 text-xs">{{ $label }}</button>
            @endforeach
        </div>

        {{-- Search + status filter --}}
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 sm:max-w-xs">
                <svg class="pointer-events-none absolute inset-y-0 start-3 my-auto h-4 w-4 text-subtle" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                <input type="search" x-model="q" @input="onSearch()" @search="load()"
                       class="w-full rounded-pill border-line bg-bg py-1.5 ps-9 pe-3 text-xs text-ink placeholder:text-subtle focus:border-accent focus:ring-accent/40"
                       :placeholder="tab.includes('review') ? '{{ __('Search reviews…') }}' : (tab === 'projects' ? '{{ __('Search projects…') }}' : '{{ __('Search by name, email or username…') }}')"
                       aria-label="{{ __('Search') }}">
            </div>
            {{-- Status filter — options depend on the active queue --}}
            <select x-show="statusOptions().length" x-cloak @change="setStatusFilter($event.target.value)"
                    class="rounded-pill border-line bg-bg py-1.5 text-xs text-muted focus:border-accent focus:ring-accent/40" aria-label="{{ __('Filter by status') }}">
                <option value="">{{ __('All statuses') }}</option>
                <template x-for="s in statusOptions()" :key="s">
                    <option :value="s" x-text="$statusLabel(s)" :selected="s === statusFilter"></option>
                </template>
            </select>
            {{-- Active-filter clear --}}
            <button x-show="q || statusFilter" x-cloak @click="q=''; statusFilter=''; load()"
                    class="rounded-pill border border-line px-3 py-1.5 text-xs text-muted hover:text-ink">{{ __('Clear') }}</button>
        </div>

        <template x-if="loading"><div><x-admin.skeleton :rows="5" /></div></template>

        {{-- Batch bar (reviews) --}}
        <div x-show="tab === 'reviews' && selected.length" x-cloak class="flex items-center gap-3 rounded-md border border-line bg-surface px-4 py-2 text-sm">
            <span><span x-text="selected.length"></span> {{ __('selected') }}</span>
            <button @click="batch('approve')" class="rounded-pill bg-success px-3 py-1 text-xs text-white">{{ __('Approve') }}</button>
            <button @click="batch('reject')" class="rounded-pill bg-danger px-3 py-1 text-xs text-white">{{ __('Reject') }}</button>
        </div>

        {{-- Rows --}}
        <div x-show="!loading" class="space-y-2">
            <template x-for="row in rows" :key="row.id">
                <div @click="openDetail(row)" role="button" tabindex="0" @keydown.enter="openDetail(row)"
                     class="flex cursor-pointer flex-wrap items-center gap-3 rounded-lg border border-line bg-surface px-4 py-3 text-sm transition hover:border-line-strong hover:bg-elevated/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                    <template x-if="tab === 'reviews'"><input type="checkbox" @click.stop @change="toggle(row.id)" class="rounded border-line"></template>
                    {{-- Row title. On the global queue it names the entity BEING reviewed
                         (brand for a brand review, talent for a talent review). --}}
                    <span class="font-medium text-ink"
                          x-text="tab === 'all-reviews' ? ((row.kind === 'brand' ? row.brand : row.talent) || ('#'+row.id)) : (row.display_name || row.name || row.title || row.talent || ('#'+row.id))"></span>
                    <template x-if="row.status"><span :class="$pill(row.status)" x-text="$statusLabel(row.status)"></span></template>
                    <template x-if="row.is_verified"><span class="rounded-pill bg-accent-weak px-2 py-0.5 text-xs text-accent-ink">{{ __('verified') }}</span></template>
                    {{-- Global queue: tag each row with its kind + the reviewer --}}
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

                    <div class="ms-auto flex flex-wrap gap-1.5" @click.stop>
                        {{-- Every queue: open the detail drawer (the whole row is also clickable) --}}
                        <button @click="openDetail(row)" class="rounded-pill border border-line px-2.5 py-1 text-xs text-accent-ink hover:border-accent">{{ __('View') }}</button>
                        {{-- Talents --}}
                        <template x-if="tab === 'talents'">
                            <span class="flex flex-wrap gap-1.5">
                                @include('admin.moderation.partials.account-actions', ['obj' => 'row', 'kind' => 'talents'])
                            </span>
                        </template>
                        {{-- Reviews / brand reviews / the global queue --}}
                        <template x-if="tab === 'reviews' || tab === 'brand-reviews' || tab === 'all-reviews'">
                            <span class="flex gap-1.5">
                                <button @click="$confirm({ title: '{{ __('Approve this review?') }}', message: '{{ __('The review becomes publicly visible.') }}', confirmLabel: '{{ __('Approve') }}', tone: 'accent' }).then(ok => ok && action(row,'approve'))" class="rounded-pill border border-line px-2.5 py-1 text-xs text-success hover:border-success">{{ __('Approve') }}</button>
                                <button @click="$confirm({ title: '{{ __('Reject this review?') }}', message: '{{ __('The review is hidden and will not be published.') }}', confirmLabel: '{{ __('Reject') }}' }).then(ok => ok && action(row,'reject'))" class="rounded-pill border border-line px-2.5 py-1 text-xs text-danger hover:border-danger">{{ __('Reject') }}</button>
                            </span>
                        </template>
                        {{-- Brands --}}
                        <template x-if="tab === 'brands'">
                            <span class="flex flex-wrap gap-1.5">
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
        <div x-show="detail" x-cloak @keydown.escape.window="closeDetail()"
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
                                                <div class="bg-surface p-3">
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
                            <button @click="$confirm({ title: '{{ __('Approve this review?') }}', message: '{{ __('The review becomes publicly visible.') }}', confirmLabel: '{{ __('Approve') }}', tone: 'accent' }).then(ok => ok && action(detail,'approve'))" class="rounded-pill border border-line px-2.5 py-1 text-xs text-success hover:border-success">{{ __('Approve') }}</button>
                            <button @click="$confirm({ title: '{{ __('Reject this review?') }}', message: '{{ __('The review is hidden and will not be published.') }}', confirmLabel: '{{ __('Reject') }}' }).then(ok => ok && action(detail,'reject'))" class="rounded-pill border border-line px-2.5 py-1 text-xs text-danger hover:border-danger">{{ __('Reject') }}</button>
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
    </div>
</x-admin-layout>
