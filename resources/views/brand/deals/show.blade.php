<x-brand-layout :title="__('Deal room')">
    <div x-data="brandDealRoom({{ $deal->id }}, { brief: @js(__('Brief')), startDate: @js(__('Start date')), endDate: @js(__('End date')), initiatedBy: @js(__('Initiated by')) })" x-cloak class="mx-auto max-w-6xl space-y-6">

        {{-- Back --}}
        <a href="{{ route('brand.deals') }}" class="inline-flex items-center gap-1.5 text-sm text-muted transition hover:text-ink">
            <svg class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 18-6-6 6-6"/></svg>
            {{ __('All deals') }}
        </a>

        <div x-show="loading" class="h-40 animate-pulse rounded-xl bg-surface"></div>

        <template x-if="deal">
            <div class="space-y-6">
                {{-- ═══ Header (full width) ═══ --}}
                <div class="rounded-xl border border-line bg-surface p-6 shadow-e1">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="deal.reference"></div>
                            <h1 class="mt-0.5 font-display text-2xl leading-tight text-ink" x-text="deal.title"></h1>
                            <template x-if="deal.campaign">
                                <a :href="`/brand/campaigns/${deal.campaign.slug}`"
                                   class="mt-1 inline-flex items-center gap-1.5 text-xs font-medium text-accent-ink hover:underline">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                                    <span x-text="deal.campaign.title"></span>
                                </a>
                            </template>
                        </div>
                        <span class="shrink-0 rounded-pill px-3 py-1 text-[11px] font-semibold uppercase tracking-wide"
                              :class="deal.is_brand_turn ? 'bg-accent text-on-accent' : (deal.status === 'completed' ? 'bg-success-weak text-success' : 'border border-line-strong bg-elevated text-muted')"
                              x-text="deal.is_brand_turn ? '{{ __('Your turn') }}' : deal.status.replaceAll('_', ' ')"></span>
                    </div>

                    {{-- Counterparty + agreed amount --}}
                    <div class="mt-5 flex flex-wrap items-center justify-between gap-4 border-t border-line pt-4">
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-pill border border-line-strong bg-elevated font-display text-sm text-accent-ink">
                                <template x-if="deal.talent?.avatar_url"><img :src="deal.talent.avatar_url" alt="" class="h-full w-full object-cover"></template>
                                <template x-if="!deal.talent?.avatar_url"><span x-text="(deal.talent?.display_name || '·').slice(0,1)"></span></template>
                            </span>
                            <div>
                                <div class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Talent') }}</div>
                                <a :href="`/${deal.talent?.slug}`" class="text-sm font-medium text-ink hover:text-accent-ink" x-text="deal.talent?.display_name || '—'"></a>
                            </div>
                        </div>
                        <template x-if="deal.agreed_amount">
                            <div class="text-end">
                                <div class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Agreed') }}</div>
                                <div class="font-display text-xl text-ink"><span x-text="Number(deal.agreed_amount).toLocaleString()"></span> <span class="text-sm text-muted" x-text="deal.currency"></span></div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- ═══ Body — TIMELINE-FIRST: wide timeline, side panel = action + phases ═══ --}}
                <div class="grid gap-6 lg:grid-cols-3">

                    {{-- Wide: deal details + the message timeline (the centre of the room) --}}
                    <div class="space-y-6 lg:col-span-2">
                        {{-- Deal details --}}
                        <template x-if="detailRows.length">
                            <div class="rounded-xl border border-line bg-surface p-6 shadow-e1">
                                <div class="mb-4 font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Deal details') }}</div>
                                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                                    <template x-for="row in detailRows" :key="row.label">
                                        <div :class="row.wide ? 'sm:col-span-2' : ''">
                                            <dt class="text-xs text-subtle" x-text="row.label"></dt>
                                            <dd class="mt-0.5 text-sm text-ink" :class="row.wide ? '' : 'capitalize'" x-text="row.value"></dd>
                                        </div>
                                    </template>
                                </dl>
                            </div>
                        </template>

                        {{-- Timeline (messages + composer) --}}
                        <div class="flex flex-col overflow-hidden rounded-xl border border-line bg-surface shadow-e1">
                            <div class="border-b border-line px-6 py-4 font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Timeline') }}</div>
                            <div x-ref="timeline" x-effect="messages.length, $nextTick(() => { if ($refs.timeline) $refs.timeline.scrollTop = $refs.timeline.scrollHeight })"
                                 class="max-h-[34rem] min-h-[18rem] flex-1 space-y-3 overflow-y-auto px-6 py-5">
                                <template x-for="m in messages" :key="m.id">
                                    <div>
                                        <template x-if="m.is_system">
                                            <div class="flex justify-center">
                                                <span class="rounded-pill bg-elevated px-3 py-1 text-center text-[11px] text-subtle" x-text="m.body"></span>
                                            </div>
                                        </template>
                                        <template x-if="!m.is_system">
                                            <div class="flex" :class="m.sender_role === 'brand' ? 'justify-end' : 'justify-start'">
                                                <div class="max-w-[78%] rounded-2xl px-3.5 py-2 text-sm shadow-e1"
                                                     :class="m.sender_role === 'brand' ? 'rounded-br-sm bg-accent text-on-accent' : 'rounded-bl-sm border border-line bg-elevated text-ink'"
                                                     x-text="m.body"></div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <div x-show="messages.length === 0" x-cloak class="grid h-full min-h-[12rem] place-items-center text-center">
                                    <div>
                                        <svg class="mx-auto h-8 w-8 text-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                        <p class="mt-2 text-xs text-subtle">{{ __('No messages yet.') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="border-t border-line p-4">
                                <div class="flex gap-2">
                                    <input x-model="messageBody" @keydown.enter="sendMessage()" placeholder="{{ __('Message…') }}" class="flex-1 rounded-pill border-line bg-elevated px-4 text-sm text-ink shadow-sm focus:border-accent focus:ring-accent">
                                    <x-ui.button variant="accent" x-on:click="sendMessage()" x-bind:disabled="sending || !messageBody.trim()">{{ __('Send') }}</x-ui.button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Side panel: current-step action (top) then the phases stepper (below) --}}
                    <div class="space-y-6">
                        {{-- Action panel --}}
                        <div class="rounded-xl border border-line bg-surface p-5 shadow-e1">
                            <template x-if="!currentStep">
                                <div class="text-center">
                                    <div class="mx-auto grid h-10 w-10 place-items-center rounded-pill bg-success-weak text-success">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg>
                                    </div>
                                    <p class="mt-2 text-sm font-medium text-ink">{{ __('This deal is finished.') }}</p>
                                </div>
                            </template>

                            <template x-if="currentStep && !canAct">
                                <div>
                                    <div class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Current step') }}</div>
                                    <div class="mt-1 font-display text-lg text-ink" x-text="currentStep.name"></div>
                                    <p class="mt-1 text-sm text-muted">{{ __('Waiting on the') }} <span x-text="currentStep.actor"></span>.</p>
                                </div>
                            </template>

                            <template x-if="currentStep && canAct">
                                <div class="space-y-4">
                                    <div>
                                        <div class="font-mono text-[10px] uppercase tracking-wider text-accent-ink">{{ __('Your turn') }}</div>
                                        <div class="mt-1 font-display text-lg text-ink" x-text="currentStep.name"></div>
                                        <p class="mt-1 text-sm text-muted" x-text="currentStep.instructions"></p>
                                        <p x-show="errors._" x-cloak class="mt-1 text-xs text-danger" x-text="errors._?.[0]"></p>
                                    </div>

                                    {{-- form (brief) --}}
                                    <template x-if="currentStep.step_type === 'form'">
                                        <div class="space-y-2">
                                            <template x-for="field in (currentStep.fields || [])" :key="field">
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium capitalize text-ink" x-text="field.replaceAll('_', ' ')"></label>
                                                    <input x-model="form.fields[field]" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                                                    <p x-show="errors['fields.' + field]" x-cloak class="mt-1 text-xs text-danger" x-text="errors['fields.' + field]?.[0]"></p>
                                                </div>
                                            </template>
                                            <x-ui.button variant="accent" class="w-full" x-on:click="submitForm()" x-bind:disabled="acting">{{ __('Submit') }}</x-ui.button>
                                        </div>
                                    </template>

                                    {{-- approval (review/accept quote) --}}
                                    <template x-if="currentStep.step_type === 'approval'">
                                        <div class="space-y-2">
                                            <input x-model="form.note" placeholder="{{ __('Optional note') }}" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                                            <div class="flex gap-2">
                                                <x-ui.button variant="accent" class="flex-1" x-on:click="approve()" x-bind:disabled="acting">{{ __('Accept') }}</x-ui.button>
                                                <x-ui.button variant="outline" class="flex-1" x-on:click="reject()" x-bind:disabled="acting">{{ __('Send back') }}</x-ui.button>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- upload --}}
                                    <template x-if="currentStep.step_type === 'upload'">
                                        <div class="space-y-2">
                                            <textarea x-model="form.attachments" rows="3" placeholder="{{ __('One link per line') }}" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                                            <p x-show="errors.attachments" x-cloak class="text-xs text-danger" x-text="errors.attachments?.[0]"></p>
                                            <x-ui.button variant="accent" class="w-full" x-on:click="deliver()" x-bind:disabled="acting">{{ __('Upload') }}</x-ui.button>
                                        </div>
                                    </template>

                                    {{-- payment --}}
                                    <template x-if="currentStep.step_type === 'payment'">
                                        <x-ui.button variant="accent" class="w-full" x-on:click="pay()" x-bind:disabled="acting">{{ __('Pay now') }}</x-ui.button>
                                    </template>

                                    {{-- contract --}}
                                    <template x-if="currentStep.step_type === 'contract'">
                                        <div class="space-y-2">
                                            <input x-model="form.signatory" placeholder="{{ __('Full name') }}" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                                            <x-ui.button variant="accent" class="w-full" x-on:click="sign()" x-bind:disabled="acting">{{ __('Sign') }}</x-ui.button>
                                        </div>
                                    </template>

                                    {{-- schedule --}}
                                    <template x-if="currentStep.step_type === 'schedule'">
                                        <div class="space-y-2">
                                            <div class="flex flex-wrap items-end gap-2">
                                                <input type="date" x-model="form.start_date" class="rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                                                <input type="date" x-model="form.end_date" class="rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                                            </div>
                                            <x-ui.button variant="accent" class="w-full" x-on:click="schedule()" x-bind:disabled="acting">{{ __('Set dates') }}</x-ui.button>
                                        </div>
                                    </template>

                                    {{-- message --}}
                                    <template x-if="currentStep.step_type === 'message'">
                                        <div class="space-y-2">
                                            <textarea x-model="form.body" rows="2" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent"></textarea>
                                            <x-ui.button variant="accent" class="w-full" x-on:click="sendStepMessage()" x-bind:disabled="acting">{{ __('Send') }}</x-ui.button>
                                        </div>
                                    </template>

                                    {{-- info --}}
                                    <template x-if="currentStep.step_type === 'info'">
                                        <x-ui.button variant="accent" class="w-full" x-on:click="acknowledge()" x-bind:disabled="acting">{{ __('Acknowledge') }}</x-ui.button>
                                    </template>

                                    <template x-if="currentStep.is_skippable">
                                        <button @click="skip()" class="text-xs text-subtle underline hover:text-ink">{{ __('Skip this step') }}</button>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- Phases stepper --}}
                        <div class="rounded-xl border border-line bg-surface p-4 shadow-e1">
                            <div class="mb-2 px-2 font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Phases') }}</div>
                            <ol>
                                <template x-for="step in steps" :key="step.id">
                                    <li class="flex items-center gap-3 rounded-md px-2 py-2" :class="step.is_current ? 'bg-accent-weak' : ''">
                                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-pill text-[11px] font-medium"
                                              :class="{
                                                'bg-success text-white': step.status === 'completed',
                                                'bg-accent text-on-accent': step.is_current,
                                                'border border-line-strong bg-surface text-subtle': step.status === 'pending',
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
                </div>
            </div>
        </template>
    </div>
</x-brand-layout>
