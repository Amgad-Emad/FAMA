<x-admin-layout :title="__('Deal console')">
    <div x-data="adminDeals()" class="space-y-5">
        <div class="flex flex-wrap gap-2">
            @foreach (['' => __('All'), 'awaiting_admin' => __('Awaiting admin'), 'awaiting_brand' => __('Awaiting brand'), 'awaiting_talent' => __('Awaiting talent'), 'completed' => __('Completed')] as $key => $label)
                <button @click="setStatus('{{ $key }}')" :class="status === '{{ $key }}' ? 'bg-accent text-on-primary' : 'border border-line text-muted hover:text-ink'" class="rounded-pill px-3 py-1.5 text-xs">{{ $label }}</button>
            @endforeach
        </div>

        <div x-show="loading" class="py-10 text-center text-sm text-muted">{{ __('Loading…') }}</div>
        <div x-show="!loading" class="space-y-2">
            <template x-for="deal in deals" :key="deal.id">
                <a :href="`/admin/deals/${deal.id}`" class="flex items-center justify-between rounded-lg border border-line bg-surface px-4 py-3 hover:border-line-strong">
                    <div>
                        <div class="text-sm font-medium" x-text="deal.title"></div>
                        <div class="text-xs text-muted"><span x-text="deal.brand?.name"></span> ↔ <span x-text="deal.talent?.display_name"></span> · <span x-text="deal.reference"></span></div>
                    </div>
                    <span class="rounded-pill bg-elevated px-2 py-0.5 text-xs text-muted" x-text="deal.status.replaceAll('_',' ')"></span>
                </a>
            </template>
            <template x-if="!deals.length"><p class="rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('No deals here.') }}</p></template>
        </div>
    </div>
</x-admin-layout>
