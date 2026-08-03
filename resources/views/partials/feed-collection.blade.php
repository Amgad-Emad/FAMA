{{-- Shared SAVED / LIKED collection surface (5X, ADR-F9 — private to the
     owner). Compact cards (cover, author, date, skills), server-side skill
     filter + body search, inline unsave/unlike with optimistic removal and an
     UNDO toast, paginated 12, Ajax throughout, logical properties for RTL. --}}
@php /** @var string $mode saved|liked */ @endphp

<div x-data="feedCollection({ mode: @js($mode), dataUrl: @js($dataUrl) })" class="space-y-5">
    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-2">
        <input type="search" x-model="q" @input.debounce.400ms="page = 1; load()"
               class="w-full max-w-xs rounded-pill border-line bg-bg px-3 py-1.5 text-xs text-ink placeholder:text-subtle focus:border-accent focus:ring-accent/40"
               placeholder="{{ __('Search saved posts…') }}" aria-label="{{ __('Search') }}">
        <select x-model="skill" @change="page = 1; load()"
                class="rounded-pill border-line bg-bg py-1.5 text-xs text-muted focus:border-accent focus:ring-accent/40"
                aria-label="{{ __('Filter by skill') }}">
            <option value="">{{ __('All skills') }}</option>
            @foreach ($skills as $skillOption)
                <option value="{{ $skillOption->slug }}">{{ $skillOption->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Undo toast --}}
    <div x-show="undo.id !== null" x-cloak
         class="flex items-center justify-between rounded-lg border border-line bg-accent-weak px-4 py-2.5 text-sm text-accent-ink">
        <span x-text="undo.label"></span>
        <button type="button" class="font-medium underline" @click="undoRemove()">{{ __('Undo') }}</button>
    </div>

    {{-- Rows --}}
    <div x-show="loading" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3" aria-hidden="true">
        <template x-for="n in 6" :key="n">
            <div class="overflow-hidden rounded-2xl border border-line bg-surface">
                <div class="aspect-[4/3] w-full animate-pulse bg-elevated"></div>
                <div class="space-y-2 p-4">
                    <div class="h-3 w-2/3 animate-pulse rounded bg-elevated"></div>
                    <div class="h-3 w-1/3 animate-pulse rounded bg-elevated"></div>
                </div>
            </div>
        </template>
    </div>

    <p x-show="!loading && rows.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-8 text-center text-sm text-muted">
        <span x-show="mode === 'saved'">{{ __('Nothing saved yet. Tap Save on any post to keep it here.') }}</span>
        <span x-show="mode === 'liked'">{{ __('Nothing liked yet. The posts you ✦ will gather here.') }}</span>
    </p>

    <div x-show="!loading" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <template x-for="row in rows" :key="row.id">
            <article class="group flex flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-e1 transition duration-300 hover:-translate-y-1 hover:border-accent/40 hover:shadow-e3">
                <a :href="row.permalink" class="block overflow-hidden">
                    {{-- Media post → the cover, zooming gently on hover --}}
                    <div x-show="row.cover_url" x-cloak class="aspect-[4/3] w-full overflow-hidden">
                        <img :src="row.cover_url" alt="" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    </div>
                    {{-- Note post → a pull-quote panel (no more empty box) --}}
                    <div x-show="!row.cover_url" x-cloak
                         class="relative flex aspect-[4/3] w-full flex-col justify-center overflow-hidden bg-gradient-to-br from-elevated to-surface p-6">
                        <span aria-hidden="true" class="font-display text-6xl leading-none text-accent/25">&ldquo;</span>
                        <p class="-mt-2 line-clamp-4 font-display text-lg leading-snug text-ink" x-text="row.excerpt || '—'"></p>
                    </div>
                </a>

                <div class="flex flex-1 flex-col p-4">
                    {{-- media posts repeat the excerpt below; notes already show it --}}
                    <p class="line-clamp-2 text-sm leading-relaxed text-ink" x-show="row.cover_url" x-cloak x-text="row.excerpt || '—'"></p>

                    <div class="mt-2.5 flex items-center gap-2">
                        <img x-show="row.author_avatar" :src="row.author_avatar" alt="" class="h-6 w-6 shrink-0 rounded-full object-cover">
                        <span x-show="!row.author_avatar" class="h-6 w-6 shrink-0 rounded-full bg-elevated"></span>
                        <span class="min-w-0 truncate text-xs text-muted"><bdi x-text="row.author"></bdi> · <span x-text="row.published_at"></span></span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-1" x-show="row.skills.length">
                        <template x-for="skillTag in row.skills" :key="skillTag.slug">
                            <span class="rounded-pill border border-line px-2 py-0.5 text-[10px] uppercase tracking-wide text-muted" x-text="skillTag.name"></span>
                        </template>
                    </div>

                    {{-- Footer: View + a filled-icon remove control (a <template>
                         can't live inside <svg>, so the two icons toggle via
                         x-show on separate svgs). --}}
                    <div class="mt-auto flex items-center justify-between gap-2 border-t border-line pt-3" style="margin-block-start: 1rem;">
                        <a :href="row.permalink" class="inline-flex items-center gap-1 font-mono text-[11px] uppercase tracking-[0.14em] text-subtle transition hover:text-accent-ink">
                            {{ __('View') }}
                            <svg class="h-3 w-3 transition-transform group-hover:translate-x-0.5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>
                        <button type="button" @click="remove(row)"
                                :aria-label="mode === 'saved' ? '{{ __('Unsave') }}' : '{{ __('Unlike') }}'"
                                class="group/rm inline-flex items-center gap-1.5 rounded-pill border border-line-strong px-3 py-1.5 text-xs font-medium text-muted transition-colors duration-200 hover:border-danger hover:bg-danger-weak hover:text-danger focus:outline-none focus-visible:ring-2 focus-visible:ring-danger focus-visible:ring-offset-2 focus-visible:ring-offset-surface">
                            {{-- filled heart (liked) --}}
                            <svg x-show="mode !== 'saved'" class="h-3.5 w-3.5 transition-transform duration-200 group-hover/rm:scale-110" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 21s-7-4.5-9.5-9A5 5 0 0 1 12 6a5 5 0 0 1 9.5 6c-2.5 4.5-9.5 9-9.5 9z"/></svg>
                            {{-- filled bookmark (saved) --}}
                            <svg x-show="mode === 'saved'" class="h-3.5 w-3.5 transition-transform duration-200 group-hover/rm:scale-110" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 2h12a1 1 0 0 1 1 1v18l-7-4-7 4V3a1 1 0 0 1 1-1z"/></svg>
                            <span x-text="mode === 'saved' ? '{{ __('Unsave') }}' : '{{ __('Unlike') }}'"></span>
                        </button>
                    </div>
                </div>
            </article>
        </template>
    </div>

    {{-- Pagination --}}
    <div x-show="pagination && pagination.last_page > 1" x-cloak class="flex items-center gap-3 text-xs text-muted">
        <button @click="page = Math.max(1, page - 1); load()" :disabled="page <= 1" class="disabled:opacity-40 hover:text-ink">‹ {{ __('Prev') }}</button>
        <span x-text="page + ' / ' + (pagination?.last_page ?? 1)"></span>
        <button @click="page = Math.min(pagination.last_page, page + 1); load()" :disabled="pagination && page >= pagination.last_page" class="disabled:opacity-40 hover:text-ink">{{ __('Next') }} ›</button>
    </div>
</div>
