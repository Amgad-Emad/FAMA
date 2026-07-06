<x-talent-layout :title="__('Deals')">
    <div x-data="dealsInbox()" class="space-y-6">
        <div class="flex flex-wrap gap-2">
            @foreach (['' => __('All'), 'awaiting_talent' => __('Your turn'), 'awaiting_brand' => __('Awaiting brand'), 'completed' => __('Completed')] as $value => $label)
                <button @click="setStatus('{{ $value }}')"
                        class="rounded-pill px-3 py-1.5 text-xs font-medium"
                        :class="status === '{{ $value }}' ? 'bg-primary text-on-primary' : 'border border-line text-muted hover:text-ink'">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div x-show="loading" class="space-y-2">
            <div class="h-24 animate-pulse rounded-lg bg-surface"></div>
            <div class="h-24 animate-pulse rounded-lg bg-surface"></div>
        </div>

        <div x-show="!loading" class="space-y-2">
            <template x-for="deal in deals" :key="deal.id">
                <a :href="'/talent/deals/' + deal.id"
                   class="block rounded-lg border border-line bg-surface p-4 transition hover:border-line-strong"
                   :class="deal.is_talent_turn ? 'ring-1 ring-accent' : ''">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="deal.reference"></div>
                            <div class="font-display text-lg text-ink" x-text="deal.title"></div>
                            <div class="text-sm text-muted" x-text="deal.brand?.name"></div>
                        </div>
                        <div class="shrink-0 text-end">
                            <span class="rounded-pill px-2 py-1 text-[10px] font-medium"
                                  :class="deal.is_talent_turn ? 'bg-accent text-on-accent' : 'border border-line bg-surface text-muted'"
                                  x-text="deal.is_talent_turn ? '{{ __('Your turn') }}' : deal.status.replaceAll('_', ' ')"></span>
                            <div class="mt-1 text-xs text-subtle" x-show="deal.current_step" x-text="deal.current_step?.name"></div>
                        </div>
                    </div>
                </a>
            </template>
            <p x-show="deals.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-8 text-center text-sm text-subtle">{{ __('No deals yet.') }}</p>
        </div>

        <div x-show="meta?.pagination && meta.pagination.last_page > 1" x-cloak class="flex items-center justify-center gap-2 text-sm">
            <button @click="load(meta.pagination.current_page - 1)" :disabled="meta.pagination.current_page <= 1" class="rounded-md border border-line px-3 py-1 disabled:opacity-40">‹</button>
            <span class="text-muted" x-text="meta.pagination.current_page + ' / ' + meta.pagination.last_page"></span>
            <button @click="load(meta.pagination.current_page + 1)" :disabled="meta.pagination.current_page >= meta.pagination.last_page" class="rounded-md border border-line px-3 py-1 disabled:opacity-40">›</button>
        </div>
    </div>
</x-talent-layout>
