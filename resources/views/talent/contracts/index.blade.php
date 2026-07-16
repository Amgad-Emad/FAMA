<x-talent-layout :title="__('Contracts')">
    <div x-data="contractsInbox()" class="space-y-6">
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
            <template x-for="contract in contracts" :key="contract.id">
                <a :href="'/talent/contracts/' + contract.id"
                   class="block rounded-lg border bg-surface p-4 transition hover:border-line-strong"
                   :class="(contract.is_talent_turn || contract.unread_count > 0) ? 'border-accent/60 ring-1 ring-accent/40' : 'border-line'">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="contract.reference"></div>
                            <div class="flex items-center gap-2">
                                {{-- New-message dot: the brand has sent messages you haven't read. --}}
                                <span x-show="contract.unread_count > 0" x-cloak class="h-2 w-2 shrink-0 rounded-full bg-accent"></span>
                                <div class="font-display text-lg text-ink" x-text="contract.title"></div>
                            </div>
                            <div class="text-sm text-muted" x-text="contract.brand?.name"></div>
                        </div>
                        <div class="shrink-0 text-end">
                            <span x-show="contract.unread_count > 0" x-cloak
                                  class="mb-1 inline-flex items-center gap-1 rounded-pill bg-accent px-2 py-0.5 text-[10px] font-semibold text-on-accent">
                                <span x-text="contract.unread_count"></span> {{ __('new') }}
                            </span>
                            <span class="block rounded-pill px-2 py-1 text-[10px] font-medium"
                                  :class="contract.is_talent_turn ? 'bg-accent text-on-accent' : 'border border-line bg-surface text-muted'"
                                  x-text="contract.is_talent_turn ? '{{ __('Your turn') }}' : contract.status.replaceAll('_', ' ')"></span>
                            <div class="mt-1 text-xs text-subtle" x-show="contract.current_step" x-text="contract.current_step?.name"></div>
                        </div>
                    </div>
                </a>
            </template>
            <p x-show="contracts.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-8 text-center text-sm text-subtle">{{ __('No contracts yet.') }}</p>
        </div>

        <div x-show="meta?.pagination && meta.pagination.last_page > 1" x-cloak class="flex items-center justify-center gap-2 text-sm">
            <button @click="load(meta.pagination.current_page - 1)" :disabled="meta.pagination.current_page <= 1" class="rounded-md border border-line px-3 py-1 disabled:opacity-40">‹</button>
            <span class="text-muted" x-text="meta.pagination.current_page + ' / ' + meta.pagination.last_page"></span>
            <button @click="load(meta.pagination.current_page + 1)" :disabled="meta.pagination.current_page >= meta.pagination.last_page" class="rounded-md border border-line px-3 py-1 disabled:opacity-40">›</button>
        </div>
    </div>
</x-talent-layout>
