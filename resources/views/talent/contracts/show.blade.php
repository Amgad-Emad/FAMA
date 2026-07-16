<x-talent-layout :title="__('Contract room')">
    <div x-data="contractRoom({{ $contract->id }})" x-cloak class="space-y-6">

        {{-- Header (top, full width): back link + reference, title, counterparty, status, amount --}}
        <div>
            <a href="{{ route('talent.contracts') }}" class="mb-3 inline-flex items-center gap-1 text-sm text-muted hover:text-ink">← {{ __('All contracts') }}</a>

            <div x-show="loading" class="h-28 animate-pulse rounded-lg bg-surface"></div>

            <template x-if="contract">
                <div class="rounded-lg border border-line bg-surface p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="contract.reference"></div>
                            <div class="font-display text-2xl text-ink" x-text="contract.title"></div>
                            <div class="mt-1 text-sm text-muted" x-text="contract.brand?.name"></div>
                        </div>
                        <div class="flex flex-col items-start gap-2 sm:items-end">
                            <span class="rounded-pill border border-line bg-surface px-2 py-0.5 text-[10px] font-medium text-muted" x-text="contract.status.replaceAll('_', ' ')"></span>
                            <span x-show="contract.agreed_amount" class="font-display text-lg text-ink" x-text="contract.agreed_amount + ' ' + contract.currency"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Body: TIMELINE (central, wide) + SIDE PANEL (action + phases) --}}
        <div class="grid gap-8 lg:grid-cols-3">

            {{-- MAIN: the message timeline — the focal conversation view --}}
            <div class="lg:col-span-2">
                <div class="flex h-[32rem] flex-col rounded-lg border border-line bg-surface sm:h-[38rem]">
                    <div class="border-b border-line px-4 py-3 font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Timeline') }}</div>

                    <div class="flex-1 space-y-3 overflow-y-auto p-4">
                        <template x-for="m in messages" :key="m.id">
                            <div>
                                <template x-if="m.is_system">
                                    <div class="text-center text-xs text-subtle" x-text="m.body"></div>
                                </template>
                                <template x-if="!m.is_system">
                                    <div class="flex" :class="m.sender_role === 'talent' ? 'justify-end' : 'justify-start'">
                                        <div class="max-w-[80%] rounded-lg px-3 py-2 text-sm"
                                             :class="m.sender_role === 'talent' ? 'bg-accent text-on-accent' : 'border border-line bg-elevated text-ink'">
                                            {{-- Rich application briefs render sanitized HTML; plain messages stay escaped. --}}
                                            <div x-show="m.is_rich" x-cloak class="brief" x-html="m.body"></div>
                                            <div x-show="!m.is_rich" class="whitespace-pre-line" x-text="m.body"></div>
                                            <template x-if="m.attachments && m.attachments.length">
                                                <div class="mt-2 flex flex-col gap-1.5 border-t pt-2" :class="m.sender_role === 'talent' ? 'border-white/25' : 'border-line'">
                                                    <template x-for="file in m.attachments" :key="file.url">
                                                        <a :href="file.url" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-xs underline underline-offset-2 hover:no-underline">
                                                            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                                            <span x-text="file.name"></span>
                                                        </a>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <p x-show="!loading && messages.length === 0" x-cloak class="pt-10 text-center text-xs text-subtle">{{ __('No messages yet.') }}</p>
                    </div>

                    {{-- Composer (existing send-message endpoint) --}}
                    <div class="border-t border-line p-3">
                        <div class="flex gap-2">
                            <input x-model="messageBody" @keydown.enter="sendMessage()" placeholder="{{ __('Message…') }}" class="flex-1 rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                            <x-ui.button variant="primary" x-on:click="sendMessage()" x-bind:disabled="sending">{{ __('Send') }}</x-ui.button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SIDE: (a) current-step action panel, then (b) phases stepper --}}
            <div class="space-y-4">
                <div x-show="loading" class="h-64 animate-pulse rounded-lg bg-surface"></div>

                <template x-if="contract">
                    <div class="space-y-4">
                        {{-- (a) Action panel (prominent, top) --}}
                        <div class="rounded-lg border border-line bg-surface p-5">
                            <div class="mb-3 font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Current step') }}</div>

                            <template x-if="!currentStep">
                                <p class="text-sm text-subtle">{{ __('This contract is finished.') }}</p>
                            </template>

                            <template x-if="currentStep && !canAct">
                                <div>
                                    <div class="font-display text-lg text-ink" x-text="currentStep.name"></div>
                                    <p class="mt-1 text-sm text-muted">{{ __('Waiting on the') }} <span x-text="currentStep.actor"></span>.</p>
                                </div>
                            </template>

                            <template x-if="currentStep && canAct">
                                <div class="space-y-4">
                                    <div>
                                        <div class="font-display text-lg text-ink" x-text="currentStep.name"></div>
                                        <p class="mt-1 text-sm text-muted" x-text="currentStep.instructions"></p>
                                        <p x-show="errors._" x-cloak class="mt-1 text-xs text-danger" x-text="errors._?.[0]"></p>
                                    </div>

                                    {{-- form --}}
                                    <template x-if="currentStep.step_type === 'form'">
                                        <div class="space-y-2">
                                            <template x-for="field in (currentStep.fields || [])" :key="field">
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium capitalize text-ink" x-text="field.replaceAll('_', ' ')"></label>
                                                    <input x-model="form.fields[field]" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                                                    <p x-show="errors['fields.' + field]" x-cloak class="mt-1 text-xs text-danger" x-text="errors['fields.' + field]?.[0]"></p>
                                                </div>
                                            </template>
                                            <x-ui.button variant="accent" x-on:click="submitForm()" x-bind:disabled="acting">{{ __('Submit') }}</x-ui.button>
                                        </div>
                                    </template>

                                    {{-- approval --}}
                                    <template x-if="currentStep.step_type === 'approval'">
                                        <div class="space-y-2">
                                            <input x-model="form.note" placeholder="{{ __('Optional note') }}" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                                            <div class="flex gap-2">
                                                <x-ui.button variant="accent" x-on:click="approve()" x-bind:disabled="acting">{{ __('Approve') }}</x-ui.button>
                                                <x-ui.button variant="outline" x-on:click="reject()" x-bind:disabled="acting">{{ __('Send back') }}</x-ui.button>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- upload --}}
                                    <template x-if="currentStep.step_type === 'upload'">
                                        <div class="space-y-2">
                                            <textarea x-model="form.attachments" rows="3" placeholder="{{ __('One link per line') }}" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                                            <p x-show="errors.attachments" x-cloak class="text-xs text-danger" x-text="errors.attachments?.[0]"></p>
                                            <x-ui.button variant="accent" x-on:click="deliver()" x-bind:disabled="acting">{{ __('Deliver') }}</x-ui.button>
                                        </div>
                                    </template>

                                    {{-- payment --}}
                                    <template x-if="currentStep.step_type === 'payment'">
                                        <x-ui.button variant="accent" x-on:click="pay()" x-bind:disabled="acting">{{ __('Confirm payment') }}</x-ui.button>
                                    </template>

                                    {{-- contract --}}
                                    <template x-if="currentStep.step_type === 'contract'">
                                        <div class="space-y-2">
                                            <input x-model="form.signatory" placeholder="{{ __('Full name') }}" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                                            <x-ui.button variant="accent" x-on:click="sign()" x-bind:disabled="acting">{{ __('Sign') }}</x-ui.button>
                                        </div>
                                    </template>

                                    {{-- schedule --}}
                                    <template x-if="currentStep.step_type === 'schedule'">
                                        <div class="flex flex-wrap items-end gap-2">
                                            <input type="date" x-model="form.start_date" class="rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                                            <input type="date" x-model="form.end_date" class="rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                                            <x-ui.button variant="accent" x-on:click="schedule()" x-bind:disabled="acting">{{ __('Set dates') }}</x-ui.button>
                                        </div>
                                    </template>

                                    {{-- message --}}
                                    <template x-if="currentStep.step_type === 'message'">
                                        <div class="space-y-2">
                                            <textarea x-model="form.body" rows="2" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                                            <x-ui.button variant="accent" x-on:click="sendStepMessage()" x-bind:disabled="acting">{{ __('Send') }}</x-ui.button>
                                        </div>
                                    </template>

                                    {{-- info --}}
                                    <template x-if="currentStep.step_type === 'info'">
                                        <x-ui.button variant="accent" x-on:click="acknowledge()" x-bind:disabled="acting">{{ __('Acknowledge') }}</x-ui.button>
                                    </template>

                                    <template x-if="currentStep.is_skippable">
                                        <button @click="skip()" class="text-xs text-subtle underline">{{ __('Skip this step') }}</button>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- (b) Phases stepper (below the action panel) --}}
                        <div class="rounded-lg border border-line bg-surface p-3">
                            <div class="mb-1 px-2 font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Phases') }}</div>
                            <ol>
                                <template x-for="step in steps" :key="step.id">
                                    <li class="flex items-center gap-3 rounded-md px-2 py-2" :class="step.is_current ? 'bg-accent-weak' : ''">
                                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-pill text-[11px] font-medium"
                                              :class="{
                                                'bg-success text-white': step.status === 'completed',
                                                'bg-accent text-on-accent': step.is_current,
                                                'border border-line bg-surface text-subtle': step.status === 'pending',
                                                'bg-warn-weak text-warn': step.status === 'skipped' || step.status === 'rejected',
                                              }"
                                              x-text="step.status === 'completed' ? '✓' : (step.status === 'skipped' ? '»' : step.position + 1)"></span>
                                        <span class="flex-1 text-sm" :class="step.is_current ? 'font-medium text-ink' : 'text-muted'" x-text="step.name"></span>
                                        <span class="font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="step.actor"></span>
                                    </li>
                                </template>
                            </ol>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-talent-layout>
