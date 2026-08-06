<x-brand-layout :title="__('Contracts')">
    <div x-data="brandContractsInbox()" class="space-y-5">
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
            <template x-for="contract in contracts" :key="contract.id">
                <a :href="`/brand/contracts/${contract.id}`"
                   class="flex items-center justify-between gap-3 rounded-lg border bg-surface px-4 py-3 transition hover:border-line-strong"
                   :class="contract.unread_count > 0 ? 'border-accent/60 ring-1 ring-accent/25' : 'border-line'">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            {{-- New-message dot: the counterparty has sent messages you haven't read. --}}
                            <span x-show="contract.unread_count > 0" x-cloak class="h-2 w-2 shrink-0 rounded-full bg-accent"></span>
                            <div class="truncate text-sm" :class="contract.unread_count > 0 ? 'font-semibold text-ink' : 'font-medium'" x-text="contract.title"></div>
                        </div>
                        <div class="text-xs text-muted"><span x-text="contract.talent?.display_name"></span> · <span x-text="contract.reference"></span></div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        {{-- Completed contracts: view the signed document without leaving the inbox --}}
                        <button x-show="contract.status === 'completed'" @click.prevent.stop="openDoc(contract)"
                                class="rounded-pill border border-line px-2.5 py-0.5 text-xs font-medium text-ink transition hover:border-line-strong hover:bg-elevated">{{ __('See contract') }}</button>
                        <span x-show="contract.unread_count > 0" x-cloak
                              class="inline-flex min-w-[1.25rem] items-center justify-center rounded-pill bg-accent px-1.5 py-0.5 text-[10px] font-semibold text-on-accent"
                              x-text="contract.unread_count"
                              :aria-label="contract.unread_count + ' {{ __('unread messages') }}'"></span>
                        <span class="rounded-pill px-2 py-0.5 text-xs"
                              :class="contract.status === 'completed' ? 'bg-success-weak text-success font-medium' : (contract.is_brand_turn ? 'bg-accent text-on-primary' : 'bg-elevated text-muted')"
                              x-text="contract.is_brand_turn ? '{{ __('Your turn') }}' : contract.status.replaceAll('_', ' ')"></span>
                    </div>
                </a>
            </template>
            <template x-if="!contracts.length"><p class="rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('No contracts here.') }}</p></template>
        </div>

        {{-- Contract document modal (opened from a completed contract's "See contract") --}}
        <template x-teleport="body">
            <div x-show="docModalOpen" x-cloak class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto p-4 sm:p-8"
                 role="dialog" aria-modal="true" @keydown.escape.window="docModalOpen && (docModalOpen = false)">
                <div class="absolute inset-0" style="background: var(--scrim);" @click="docModalOpen = false"></div>
                <div class="relative w-full max-w-2xl">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <span class="font-mono text-[10px] uppercase tracking-[0.15em] text-white/80">{{ __('Contract') }}</span>
                        <div class="flex items-center gap-2">
                            <a x-show="docPdfUrl" :href="docPdfUrl" class="inline-flex items-center gap-1.5 rounded-pill bg-white px-3 py-1.5 text-xs font-semibold text-ink shadow-sm transition hover:bg-white/90">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                                {{ __('Download PDF') }}
                            </a>
                            <button type="button" @click="docModalOpen = false" aria-label="{{ __('Close') }}" class="grid h-8 w-8 place-items-center rounded-pill bg-white/10 text-white hover:bg-white/20">✕</button>
                        </div>
                    </div>
                    <div x-html="documentHtml"></div>
                </div>
            </div>
        </template>
    </div>
</x-brand-layout>
