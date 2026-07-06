<x-talent-layout :title="__('Reviews')">
    <div x-data="crudList({ endpoint: '/talent/reviews' })" class="space-y-6">
        <x-ui.section :title="__('Moderation queue')" :eyebrow="__('Approve or reject testimonials')">
            <div x-show="loading" class="space-y-2">
                <div class="h-24 animate-pulse rounded-lg bg-surface"></div>
                <div class="h-24 animate-pulse rounded-lg bg-surface"></div>
            </div>
            <div x-show="!loading" class="space-y-3">
                <template x-for="r in items" :key="r.id">
                    <x-ui.card>
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-gold" x-text="'★'.repeat(r.rating) + '☆'.repeat(5 - r.rating)"></span>
                                    <span class="rounded-pill px-2 py-0.5 text-[10px] font-mono uppercase tracking-wider"
                                          :class="{ 'bg-warn-weak text-warn': r.status === 'pending', 'bg-success-weak text-success': r.status === 'approved', 'bg-danger-weak text-danger': r.status === 'rejected' }"
                                          x-text="r.status"></span>
                                </div>
                                <p class="mt-2 text-ink" x-text="'“' + r.body + '”'"></p>
                                <div class="mt-2 font-mono text-[11px] uppercase tracking-wider text-subtle" x-text="r.reviewer_name + (r.reviewer_company ? ' · ' + r.reviewer_company : '')"></div>
                            </div>
                            <div class="flex shrink-0 flex-col gap-2">
                                <button x-show="r.status !== 'approved'" @click="act(r.id, 'approve')" class="rounded-pill bg-success-weak px-3 py-1 text-xs font-medium text-success">{{ __('Approve') }}</button>
                                <button x-show="r.status !== 'rejected'" @click="act(r.id, 'reject')" class="rounded-pill border border-line px-3 py-1 text-xs font-medium text-muted hover:text-danger">{{ __('Reject') }}</button>
                            </div>
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
        </x-ui.section>
    </div>
</x-talent-layout>
