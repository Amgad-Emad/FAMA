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
                                {{-- Phase event — a distinct card (not a chat bubble) so a step
                                     completion is unmistakably a phase, with the submitted details. --}}
                                <template x-if="m.is_system">
                                    <div class="my-1 flex justify-center">
                                        <div class="w-full max-w-md rounded-xl border border-line bg-elevated/70 px-4 py-2.5">
                                            <div class="flex items-center gap-2">
                                                <span class="grid h-5 w-5 shrink-0 place-items-center rounded-pill bg-accent-weak text-[10px] text-accent-ink">✦</span>
                                                <span class="font-mono text-[9px] uppercase tracking-wider text-subtle">{{ __('Phase') }}</span>
                                                <span class="flex-1 text-xs font-medium text-ink" x-text="m.body"></span>
                                            </div>
                                            <template x-if="m.details && m.details.length">
                                                <dl class="mt-2 space-y-1 border-t border-line/70 pt-2">
                                                    <template x-for="d in m.details" :key="d.label">
                                                        <div class="flex justify-between gap-4 text-xs">
                                                            <dt class="shrink-0 text-muted" x-text="d.label"></dt>
                                                            <dd class="text-end text-ink" x-text="d.value"></dd>
                                                        </div>
                                                    </template>
                                                </dl>
                                            </template>
                                        </div>
                                    </div>
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

                                    {{-- form — date fields render as date pickers, pricing_unit as a select --}}
                                    <template x-if="currentStep.step_type === 'form'">
                                        <div class="space-y-2">
                                            <template x-for="field in (currentStep.fields || [])" :key="field">
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium capitalize text-ink" x-text="field.replaceAll('_', ' ')"></label>
                                                    <template x-if="field === 'pricing_unit'">
                                                        <select x-model="form.fields[field]" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                                                            <option value="">{{ __('Select…') }}</option>
                                                            <option value="hour">{{ __('Per hour') }}</option>
                                                            <option value="day">{{ __('Per day') }}</option>
                                                            <option value="item">{{ __('Per item') }}</option>
                                                            <option value="project">{{ __('Flat project fee') }}</option>
                                                        </select>
                                                    </template>
                                                    <template x-if="field !== 'pricing_unit'">
                                                        <input :type="field.endsWith('_date') ? 'date' : 'text'" x-model="form.fields[field]" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                                                    </template>
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

                                    {{-- contract — review the document (built from the earlier steps) then sign --}}
                                    <template x-if="currentStep.step_type === 'contract'">
                                        <div class="space-y-3">
                                            <div x-html="documentHtml"></div>
                                            <input x-model="form.signatory" placeholder="{{ __('Type your full name to sign') }}" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                                            <x-ui.button variant="accent" x-on:click="sign()" x-bind:disabled="acting">{{ __('Sign contract') }}</x-ui.button>
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

                        {{-- (b) Phases stepper (below the action panel). Clicking a phase opens
                             the contract document (ADR-V). --}}
                        <div class="rounded-lg border border-line bg-surface p-3">
                            <div class="mb-1 flex items-center justify-between px-2">
                                <span class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Phases') }}</span>
                                <span class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('tap to view contract') }}</span>
                            </div>
                            <ol>
                                <template x-for="step in steps" :key="step.id">
                                    <li @click="openDoc()" role="button" tabindex="0" @keydown.enter="openDoc()"
                                        class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-2 transition hover:bg-elevated" :class="step.is_current ? 'bg-accent-weak' : ''">
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
                            {{-- Open the contract document from below the phases --}}
                            <button @click="openDoc()" class="mt-2 flex w-full items-center justify-center gap-2 rounded-md border border-line px-3 py-2 text-sm font-medium text-ink transition hover:border-line-strong hover:bg-elevated">
                                <svg class="h-4 w-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ __('View contract') }}
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Contract document modal (opened from the phases stepper, ADR-V) --}}
        <template x-teleport="body">
            <div x-show="docModalOpen" x-cloak class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto p-4 sm:p-8"
                 role="dialog" aria-modal="true" @keydown.escape.window="docModalOpen && (docModalOpen = false)">
                <div class="absolute inset-0" style="background: var(--scrim);" @click="docModalOpen = false"></div>
                <div class="relative w-full max-w-2xl">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <span class="font-mono text-[10px] uppercase tracking-[0.15em] text-white/80">{{ __('Contract') }}</span>
                        <div class="flex items-center gap-2">
                            <a :href="'/talent/contracts/' + contractId + '/document.pdf'"
                               class="inline-flex items-center gap-1.5 rounded-pill bg-white px-3 py-1.5 text-xs font-semibold text-ink shadow-sm transition hover:bg-white/90">
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
</x-talent-layout>
