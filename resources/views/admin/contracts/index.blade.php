<x-admin-layout :title="__('Contract console')">
    <div x-data="adminContracts()" class="space-y-5">
        <p class="text-sm text-muted">{{ __('Every contract platform-wide — filter by status or current step, open one to intervene.') }}</p>
        <div class="flex flex-wrap items-center gap-2">
            @foreach (['' => __('All'), 'awaiting_admin' => __('Awaiting admin'), 'awaiting_brand' => __('Awaiting brand'), 'awaiting_talent' => __('Awaiting talent'), 'completed' => __('Completed')] as $key => $label)
                <button @click="setStatus('{{ $key }}')" :class="status === '{{ $key }}' ? 'bg-accent text-on-primary' : 'border border-line text-muted hover:text-ink'" class="rounded-pill px-3 py-1.5 text-xs">{{ $label }}</button>
            @endforeach
            {{-- Current-step filter (keys from the snapshotted contract_steps) --}}
            <select @change="setStep($event.target.value)" class="ms-auto rounded-pill border-line bg-bg py-1.5 text-xs text-muted" aria-label="{{ __('Filter by step') }}">
                <option value="">{{ __('Any step') }}</option>
                @foreach ($stepKeys as $key)
                    <option value="{{ $key }}">{{ $key }}</option>
                @endforeach
            </select>
        </div>

        <template x-if="loading"><div><x-admin.skeleton :rows="5" /></div></template>
        <div x-show="!loading" class="space-y-2">
            <template x-for="contract in contracts" :key="contract.id">
                <a :href="window.fama.localizeUrl(`/admin/contracts/${contract.id}`)" class="flex items-center gap-3 rounded-lg border border-line bg-surface px-4 py-3 transition hover:border-line-strong">
                    <template x-if="contract.talent?.avatar_url"><img :src="contract.talent.avatar_url" alt="" class="h-9 w-9 shrink-0 rounded-pill object-cover"></template>
                    <template x-if="!contract.talent?.avatar_url"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-pill bg-elevated font-display text-sm text-muted" x-text="(contract.talent?.display_name || '?').charAt(0)"></span></template>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium text-ink" x-text="contract.title"></div>
                        <div class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-muted">
                            <span x-text="contract.brand?.name"></span> ↔ <span x-text="contract.talent?.display_name"></span>
                            <span class="rounded bg-elevated px-1.5 py-0.5 font-mono text-[10px]" x-text="contract.reference"></span>
                        </div>
                    </div>
                    <template x-if="contract.current_step"><span class="hidden rounded-pill border border-line px-2 py-0.5 text-[10px] text-muted sm:inline" x-text="$stepLabel(contract.current_step)"></span></template>
                    <span :class="$pill(contract.status)" x-text="$statusLabel(contract.status)"></span>
                </a>
            </template>
            <template x-if="!contracts.length"><p class="rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('No contracts here.') }}</p></template>
            <x-admin.pagination />
        </div>
    </div>
</x-admin-layout>
