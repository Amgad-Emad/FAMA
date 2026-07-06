<x-talent-layout :title="__('Deal room')">
    <div x-data="dealRoom({{ $deal->id }})" x-cloak class="grid gap-8 lg:grid-cols-3">

        {{-- Left: header + stepper + action panel --}}
        <div class="space-y-4 lg:col-span-2">
            <div x-show="loading" class="h-40 animate-pulse rounded-lg bg-surface"></div>

            <template x-if="deal">
                <div class="space-y-4">
                    {{-- Header --}}
                    <div class="rounded-lg border border-line bg-surface p-5">
                        <div class="font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="deal.reference"></div>
                        <div class="font-display text-2xl text-ink" x-text="deal.title"></div>
                        <div class="mt-1 text-sm text-muted" x-text="deal.brand?.name"></div>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="rounded-pill border border-line bg-surface px-2 py-0.5 text-[10px] font-medium text-muted" x-text="deal.status.replaceAll('_', ' ')"></span>
                            <span x-show="deal.agreed_amount" class="font-display text-lg text-ink" x-text="deal.agreed_amount + ' ' + deal.currency"></span>
                        </div>
                    </div>

                    {{-- Stepper --}}
                    <ol class="rounded-lg border border-line bg-surface p-3">
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

                    {{-- Action panel --}}
                    <div class="rounded-lg border border-line bg-surface p-5">
                        <template x-if="!currentStep">
                            <p class="text-sm text-subtle">{{ __('This deal is finished.') }}</p>
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
                </div>
            </template>
        </div>

        {{-- Right: timeline + free messaging --}}
        <div class="space-y-4">
            <div class="rounded-lg border border-line bg-surface p-4">
                <div class="mb-3 font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Timeline') }}</div>
                <div class="max-h-[28rem] space-y-3 overflow-y-auto">
                    <template x-for="m in messages" :key="m.id">
                        <div>
                            <template x-if="m.is_system">
                                <div class="text-center text-xs text-subtle" x-text="m.body"></div>
                            </template>
                            <template x-if="!m.is_system">
                                <div class="flex" :class="m.sender_role === 'talent' ? 'justify-end' : 'justify-start'">
                                    <div class="max-w-[80%] rounded-lg px-3 py-2 text-sm"
                                         :class="m.sender_role === 'talent' ? 'bg-accent text-on-accent' : 'border border-line bg-elevated text-ink'"
                                         x-text="m.body"></div>
                                </div>
                            </template>
                        </div>
                    </template>
                    <p x-show="messages.length === 0" x-cloak class="text-center text-xs text-subtle">{{ __('No messages yet.') }}</p>
                </div>
                <div class="mt-3 flex gap-2">
                    <input x-model="messageBody" @keydown.enter="sendMessage()" placeholder="{{ __('Message…') }}" class="flex-1 rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    <x-ui.button variant="primary" x-on:click="sendMessage()" x-bind:disabled="sending">{{ __('Send') }}</x-ui.button>
                </div>
            </div>
            <a href="{{ route('talent.deals') }}" class="block text-center text-sm text-muted hover:text-ink">← {{ __('All deals') }}</a>
        </div>
    </div>
</x-talent-layout>
