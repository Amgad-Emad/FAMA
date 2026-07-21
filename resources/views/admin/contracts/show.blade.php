<x-admin-layout :title="__('Contract intervention')">
    <div x-data="adminContract({{ $contract->id }})" x-cloak class="grid gap-8 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <a href="{{ route('admin.contracts') }}" class="text-xs text-muted hover:text-ink">← {{ __('All contracts') }}</a>
            <div x-show="loading" class="h-40 animate-pulse rounded-lg bg-surface"></div>

            <template x-if="contract">
                <div class="space-y-4">
                    <x-ui.card>
                        <div class="font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="contract.reference"></div>
                        <div class="font-display text-2xl text-ink" x-text="contract.title"></div>
                        <div class="mt-1 text-sm text-muted"><span x-text="contract.brand?.name"></span> ↔ <span x-text="contract.talent?.display_name"></span></div>
                        <span class="mt-3 inline-flex" :class="$pill(contract.status)" x-text="$statusLabel(contract.status)"></span>
                    </x-ui.card>

                    {{-- Stepper --}}
                    <ol class="rounded-lg border border-line bg-surface p-3">
                        <template x-for="step in steps" :key="step.id">
                            <li class="flex items-center gap-3 rounded-md px-2 py-2" :class="step.is_current ? 'bg-accent-weak' : ''">
                                <span class="grid h-6 w-6 place-items-center rounded-pill text-[11px] font-medium" :class="step.status === 'completed' ? 'bg-success text-white' : (step.is_current ? 'bg-accent text-on-accent' : 'border border-line text-subtle')" x-text="step.status === 'completed' ? '✓' : step.position + 1"></span>
                                <span class="flex-1 text-sm" :class="step.is_current ? 'font-medium text-ink' : 'text-muted'" x-text="$stepLabel(step)"></span>
                                <span class="font-mono text-[10px] uppercase text-subtle" x-text="$actorLabel(step.actor)"></span>
                            </li>
                        </template>
                    </ol>

                    {{-- Intervention actions --}}
                    <x-ui.card class="space-y-3">
                        <h3 class="font-display text-lg">{{ __('Intervene') }}</h3>
                        <textarea x-model="note" rows="2" placeholder="{{ __('Note / reason (optional)') }}" class="w-full rounded-md border-line bg-elevated text-ink"></textarea>
                        <div class="flex flex-wrap gap-2">
                            <x-ui.button size="sm" variant="accent" x-on:click="act('advance', {})" x-bind:disabled="acting">{{ __('Advance as admin') }}</x-ui.button>
                            <x-ui.button size="sm" variant="outline" x-on:click="act('override', { note })" x-bind:disabled="acting">{{ __('Override step') }}</x-ui.button>
                            <x-ui.button size="sm" variant="outline" x-on:click="act('nudge', { note })" x-bind:disabled="acting || !note">{{ __('Nudge') }}</x-ui.button>
                            <x-ui.button size="sm" variant="outline" x-on:click="$confirm({ title: '{{ __('Cancel this contract?') }}', message: '{{ __('The contract will be cancelled for both parties. This cannot be undone.') }}', confirmLabel: '{{ __('Cancel contract') }}', cancelLabel: '{{ __('Keep contract') }}' }).then(ok => ok && act('cancel', { reason: note }))" x-bind:disabled="acting">{{ __('Cancel contract') }}</x-ui.button>
                        </div>
                    </x-ui.card>
                </div>
            </template>
        </div>

        {{-- Timeline --}}
        <div class="rounded-lg border border-line bg-surface p-4">
            <div class="mb-3 font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Timeline') }}</div>
            <div class="max-h-[30rem] space-y-3 overflow-y-auto">
                <template x-for="m in messages" :key="m.id">
                    <div class="text-sm">
                        <template x-if="m.is_system"><div class="text-center text-xs text-subtle" x-text="m.body"></div></template>
                        <template x-if="!m.is_system"><div class="rounded-lg border border-line bg-elevated px-3 py-2" x-text="m.body"></div></template>
                    </div>
                </template>
                <p x-show="!messages.length" x-cloak class="text-center text-xs text-subtle">{{ __('No activity yet.') }}</p>
            </div>
        </div>
    </div>
</x-admin-layout>
