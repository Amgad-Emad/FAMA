<x-brand-layout :title="__('Deals')">
    <div x-data="brandDealsInbox()" class="space-y-5">
        {{-- Status filter --}}
        <div class="flex flex-wrap gap-2">
            @foreach (['' => __('All'), 'awaiting_brand' => __('Your turn'), 'awaiting_talent' => __('Awaiting talent'), 'completed' => __('Completed')] as $key => $label)
                <button @click="setStatus('{{ $key }}')"
                        :class="status === '{{ $key }}' ? 'bg-accent text-on-primary' : 'border border-line text-muted hover:text-ink'"
                        class="rounded-pill px-3 py-1.5 text-xs">{{ $label }}</button>
            @endforeach
        </div>

        <div x-show="loading" class="py-10 text-center text-sm text-muted">{{ __('Loading…') }}</div>

        <div x-show="!loading" class="space-y-2">
            <template x-for="deal in deals" :key="deal.id">
                <a :href="`/brand/deals/${deal.id}`"
                   class="flex items-center justify-between gap-3 rounded-lg border bg-surface px-4 py-3 transition hover:border-line-strong"
                   :class="deal.unread_count > 0 ? 'border-accent/60 ring-1 ring-accent/25' : 'border-line'">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            {{-- New-message dot: the counterparty has sent messages you haven't read. --}}
                            <span x-show="deal.unread_count > 0" x-cloak class="h-2 w-2 shrink-0 rounded-full bg-accent"></span>
                            <div class="truncate text-sm" :class="deal.unread_count > 0 ? 'font-semibold text-ink' : 'font-medium'" x-text="deal.title"></div>
                        </div>
                        <div class="text-xs text-muted"><span x-text="deal.talent?.display_name"></span> · <span x-text="deal.reference"></span></div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <span x-show="deal.unread_count > 0" x-cloak
                              class="inline-flex min-w-[1.25rem] items-center justify-center rounded-pill bg-accent px-1.5 py-0.5 text-[10px] font-semibold text-on-accent"
                              x-text="deal.unread_count"
                              :aria-label="deal.unread_count + ' {{ __('unread messages') }}'"></span>
                        <span class="rounded-pill px-2 py-0.5 text-xs"
                              :class="deal.is_brand_turn ? 'bg-accent text-on-primary' : 'bg-elevated text-muted'"
                              x-text="deal.is_brand_turn ? '{{ __('Your turn') }}' : deal.status.replaceAll('_', ' ')"></span>
                    </div>
                </a>
            </template>
            <template x-if="!deals.length"><p class="rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('No deals here.') }}</p></template>
        </div>
    </div>
</x-brand-layout>
