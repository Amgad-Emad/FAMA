{{-- The talent's reviews — a READ-ONLY list with a star-rating filter (V1:
     approve/reject moved to the admin queue, /admin/moderation/reviews). Shared
     by the standalone Reviews page and the Reviews block panel on the
     profile-content page, so there is ONE reviews list in the product. --}}
<div x-data="crudList({ endpoint: '/talent/reviews' })">
    {{-- Rating filter chips --}}
    <div class="mb-4 flex flex-wrap gap-1.5" role="group" aria-label="{{ __('Filter by rating') }}">
        <button type="button" @click="setQuery('')" :aria-pressed="activeQuery === '' ? 'true' : 'false'"
                class="rounded-pill border px-3.5 py-1.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                :class="activeQuery === '' ? 'border-accent bg-accent text-on-accent' : 'border-line text-muted hover:border-line-strong hover:text-ink'">{{ __('All') }}</button>
        @foreach ([5, 4, 3, 2, 1] as $stars)
            <button type="button" @click="setQuery('?rating={{ $stars }}')" :aria-pressed="activeQuery === '?rating={{ $stars }}' ? 'true' : 'false'"
                    class="rounded-pill border px-3.5 py-1.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                    :class="activeQuery === '?rating={{ $stars }}' ? 'border-accent bg-accent text-on-accent' : 'border-line text-muted hover:border-line-strong hover:text-ink'">{{ $stars }} ★</button>
        @endforeach
    </div>

    <div x-show="loading" class="space-y-2">
        <div class="h-24 animate-pulse rounded-lg bg-surface"></div>
        <div class="h-24 animate-pulse rounded-lg bg-surface"></div>
    </div>
    <div x-show="!loading" class="space-y-3">
        <template x-for="r in items" :key="r.id">
            <x-ui.card>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-gold" x-text="'★'.repeat(r.rating) + '☆'.repeat(5 - r.rating)"></span>
                        <span class="rounded-pill px-2 py-0.5 text-[10px] font-mono uppercase tracking-wider"
                              :class="{ 'bg-warn-weak text-warn': r.status === 'pending', 'bg-success-weak text-success': r.status === 'approved', 'bg-danger-weak text-danger': r.status === 'rejected' }"
                              x-text="$statusLabel(r.status)"></span>
                    </div>
                    <p class="mt-2 text-ink" x-text="'“' + r.body + '”'"></p>
                    <div class="mt-2 font-mono text-[11px] uppercase tracking-wider text-subtle" x-text="r.reviewer_name + (r.reviewer_company ? ' · ' + r.reviewer_company : '')"></div>
                </div>
            </x-ui.card>
        </template>
        <p x-show="items.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-6 text-center text-sm text-subtle">{{ __('No reviews yet.') }}</p>
    </div>

    {{-- pager --}}
    <div x-show="meta?.pagination && meta.pagination.last_page > 1" x-cloak class="mt-4 flex items-center justify-center gap-2 text-sm">
        <button @click="load(meta.pagination.current_page - 1)" :disabled="meta.pagination.current_page <= 1" class="rounded-md border border-line px-3 py-1 disabled:opacity-40">‹</button>
        <span class="text-muted" x-text="meta.pagination.current_page + ' / ' + meta.pagination.last_page"></span>
        <button @click="load(meta.pagination.current_page + 1)" :disabled="meta.pagination.current_page >= meta.pagination.last_page" class="rounded-md border border-line px-3 py-1 disabled:opacity-40">›</button>
    </div>
</div>
