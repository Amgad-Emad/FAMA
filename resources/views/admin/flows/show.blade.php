<x-admin-layout :title="$flow->name">
    <div x-data="adminFlow({{ $flow->id }})" x-cloak class="space-y-6">
        <a href="{{ route('admin.flows') }}" class="text-xs text-muted hover:text-ink">← {{ __('All flows') }}</a>

        <template x-if="loading"><div><x-admin.skeleton :rows="4" /></div></template>

        <template x-if="!loading && flow">
            <div class="space-y-6">
                {{-- Header + lifecycle --}}
                <x-ui.card class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-display text-2xl" x-text="$flowLabel(flow)"></h2>
                        <p class="mt-1 text-xs text-muted">
                            <span :class="$pill(flow.status)" x-text="$statusLabel(flow.status)"></span> <span class="text-subtle">·</span> <span x-text="flow.applies_to ? $categoryLabel(flow.applies_to) : '{{ __('all categories') }}'"></span>
                            <template x-if="flow.is_default"><span class="ms-1 rounded-pill bg-accent-weak px-2 py-0.5 text-accent-ink">{{ __('default') }}</span></template>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button size="sm" variant="outline" x-show="flow.status !== 'active'" x-on:click="lifecycle('activate')" x-bind:disabled="acting">{{ __('Activate') }}</x-ui.button>
                        <x-ui.button size="sm" variant="outline" x-show="flow.status === 'active' && !flow.is_default" x-on:click="lifecycle('default')" x-bind:disabled="acting">{{ __('Set default') }}</x-ui.button>
                        <x-ui.button size="sm" variant="outline" x-show="flow.status !== 'archived'" x-on:click="lifecycle('archive')" x-bind:disabled="acting">{{ __('Archive') }}</x-ui.button>
                    </div>
                </x-ui.card>

                {{-- Steps (drag to reorder) --}}
                <section>
                    <h3 class="mb-3 font-display text-lg">{{ __('Steps') }}</h3>
                    <div class="space-y-2">
                        <template x-for="step in flow.steps" :key="step.id">
                            <div draggable="true" @dragstart="dragId = step.id" @dragover.prevent @drop.prevent="drop(step)"
                                 class="flex flex-wrap items-center gap-3 rounded-md border border-line bg-surface px-4 py-3">
                                <span class="cursor-grab font-mono text-xs text-subtle">⋮⋮</span>
                                <span class="font-display text-base text-ink" x-text="step.name"></span>
                                <span class="rounded-pill bg-elevated px-2 py-0.5 font-mono text-[10px] text-muted" x-text="step.actor"></span>
                                <span class="rounded-pill bg-elevated px-2 py-0.5 font-mono text-[10px] text-muted" x-text="step.step_type"></span>
                                <template x-if="step.is_required"><span class="text-[10px] text-muted">{{ __('required') }}</span></template>
                                <template x-if="step.is_skippable"><span class="text-[10px] text-muted">{{ __('skippable') }}</span></template>
                                <button @click="$confirm({ title: '{{ __('Remove this step?') }}', message: step.name, confirmLabel: '{{ __('Remove') }}' }).then(ok => ok && removeStep(step))" class="ms-auto text-xs text-danger">{{ __('Remove') }}</button>
                            </div>
                        </template>
                        <template x-if="!flow.steps.length"><p class="rounded-lg border border-dashed border-line py-6 text-center text-sm text-muted">{{ __('No steps yet.') }}</p></template>
                    </div>
                </section>

                {{-- Add step --}}
                <x-ui.card>
                    <h3 class="mb-3 font-display text-lg">{{ __('Add step') }}</h3>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div><x-input-label :value="__('Key')" /><x-text-input class="mt-1 block w-full" x-model="newStep.key" placeholder="brief" /></div>
                        <div><x-input-label :value="__('Name')" /><x-text-input class="mt-1 block w-full" x-model="newStep.name" /></div>
                        <div>
                            <x-input-label :value="__('Actor')" />
                            <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="newStep.actor">
                                @foreach (['brand','talent','admin','system','both'] as $a)<option value="{{ $a }}">{{ __(ucfirst($a)) }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label :value="__('Type')" />
                            <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="newStep.step_type">
                                @foreach (['form','approval','upload','payment','contract','message','schedule','info'] as $t)<option value="{{ $t }}">{{ __(ucfirst($t)) }}</option>@endforeach
                            </select>
                        </div>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="newStep.is_required" class="rounded border-line">{{ __('Required') }}</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="newStep.is_skippable" class="rounded border-line">{{ __('Skippable') }}</label>
                    </div>
                    <template x-if="errors.key"><p class="mt-1 text-xs text-danger" x-text="errors.key[0]"></p></template>
                    <x-ui.button variant="accent" class="mt-4" x-on:click="addStep()">{{ __('Add step') }}</x-ui.button>
                </x-ui.card>
            </div>
        </template>
    </div>
</x-admin-layout>
