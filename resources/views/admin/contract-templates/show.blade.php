<x-admin-layout :title="$template->name">
    <div x-data="adminContractTemplate({{ $template->id }})" x-cloak class="space-y-6">
        <a href="{{ route('admin.contract-templates') }}" class="text-xs text-muted hover:text-ink">← {{ __('All templates') }}</a>

        <template x-if="loading"><div><x-admin.skeleton :rows="4" /></div></template>

        <div x-show="!loading" class="space-y-6">
            {{-- Header + lifecycle --}}
            <x-ui.card class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-mono text-[10px] uppercase tracking-[0.15em] text-subtle">{{ __('Fama — contract schema') }}</div>
                    <h2 class="font-display text-2xl text-ink" x-text="form.name || '{{ __('Untitled template') }}'"></h2>
                    <p class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-muted">
                        <span :class="$pill(isActive ? 'active' : 'archived')" x-text="isActive ? '{{ __('active') }}' : '{{ __('inactive') }}'"></span>
                        <template x-if="isDefault"><span class="rounded-pill bg-accent-weak px-2 py-0.5 text-accent-ink">{{ __('default') }}</span></template>
                        <span x-show="flowsCount" class="text-subtle">· {{ __('used by') }} <span x-text="flowsCount"></span> {{ __('flows') }}</span>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button size="sm" variant="accent" x-on:click="save()" x-bind:disabled="saving">
                        <span x-show="!saved" x-text="saving ? '{{ __('Saving…') }}' : '{{ __('Save template') }}'"></span>
                        <span x-show="saved" x-cloak>{{ __('Saved ✓') }}</span>
                    </x-ui.button>
                    <x-ui.button size="sm" variant="outline" x-show="!isDefault" x-on:click="makeDefault()">{{ __('Set default') }}</x-ui.button>
                    <x-ui.button size="sm" variant="outline" x-on:click="toggleActive()" x-text="isActive ? '{{ __('Deactivate') }}' : '{{ __('Activate') }}'"></x-ui.button>
                    <x-ui.button size="sm" variant="outline" x-show="!isDefault"
                        x-on:click="$confirm({ title: '{{ __('Delete this template?') }}', message: form.name, confirmLabel: '{{ __('Delete') }}', tone: 'danger' }).then(ok => ok && remove())">{{ __('Delete') }}</x-ui.button>
                </div>
            </x-ui.card>

            <template x-if="errors._"><p class="rounded-md border border-danger/40 bg-danger-weak px-3 py-2 text-sm text-danger" x-text="errors._[0]"></p></template>

            {{-- ── Two columns: author (left) · live document (right) ─────────── --}}
            <div class="grid gap-6 lg:grid-cols-2 lg:items-start">

                {{-- LEFT: the schema author --}}
                <div class="space-y-5">
                    <div class="font-mono text-[10px] uppercase tracking-[0.15em] text-accent-ink">● {{ __('Schema') }}</div>

                    {{-- Identity --}}
                    <x-ui.card class="space-y-4">
                        <div>
                            <x-input-label :value="__('Template name')" />
                            <x-text-input class="mt-1 block w-full" x-model="form.name" />
                            <template x-if="errors.name"><p class="mt-1 text-xs text-danger" x-text="errors.name[0]"></p></template>
                        </div>
                        <div>
                            <x-input-label :value="__('Default currency')" />
                            <x-text-input class="mt-1 block w-full uppercase sm:max-w-[8rem]" x-model="form.preview_currency" maxlength="3" @input="schedulePreview()" />
                        </div>
                        <div>
                            <x-input-label :value="__('Intro line')" />
                            <x-text-input class="mt-1 block w-full" x-model="form.intro" @input="schedulePreview()" placeholder="Between {brand} and {talent}" />
                            <p class="mt-1 text-xs text-subtle">{{ __('Tokens:') }} <code class="font-mono">{brand}</code> <code class="font-mono">{talent}</code></p>
                        </div>
                    </x-ui.card>

                    {{-- Standard-terms parameters --}}
                    <x-ui.card class="space-y-4">
                        <h3 class="font-display text-lg text-ink">{{ __('Payment & terms') }}</h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label :value="__('Down payment %')" />
                                <x-text-input type="number" min="0" max="100" class="mt-1 block w-full" x-model.number="form.deposit_pct" @input="schedulePreview()" />
                            </div>
                            <div>
                                <x-input-label :value="__('Deposit held by')" />
                                <x-text-input class="mt-1 block w-full" x-model="form.deposit_holder" @input="schedulePreview()" />
                            </div>
                            <div>
                                <x-input-label :value="__('Cancellation forfeit %')" />
                                <x-text-input type="number" min="0" max="100" class="mt-1 block w-full" x-model.number="form.cancellation_forfeit_pct" @input="schedulePreview()" />
                            </div>
                            <div>
                                <x-input-label :value="__('Revision rounds')" />
                                <x-text-input type="number" min="0" max="50" class="mt-1 block w-full" x-model.number="form.revision_rounds" @input="schedulePreview()" />
                            </div>
                        </div>
                    </x-ui.card>

                    {{-- Clauses (add / remove / reorder) --}}
                    <x-ui.card class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="font-display text-lg text-ink">{{ __('Standard terms (clauses)') }}</h3>
                            <span class="text-xs text-subtle" x-text="form.clauses.length + ' {{ __('clauses') }}'"></span>
                        </div>
                        <p class="text-xs text-subtle">{{ __('Drag to reorder. Body tokens:') }}
                            <code class="font-mono">{deposit_pct}</code> <code class="font-mono">{rest_pct}</code>
                            <code class="font-mono">{deposit_holder}</code> <code class="font-mono">{forfeit_pct}</code> <code class="font-mono">{revisions}</code>
                        </p>
                        <div class="space-y-2">
                            <template x-for="(clause, i) in form.clauses" :key="i">
                                <div draggable="true" @dragstart="dragIndex = i" @dragover.prevent @drop.prevent="dropClause(i)"
                                     class="rounded-lg border border-line bg-bg p-3">
                                    <div class="flex items-start gap-2">
                                        <span class="mt-2 cursor-grab select-none font-mono text-xs text-subtle">⋮⋮</span>
                                        <div class="flex-1 space-y-2">
                                            <x-text-input class="block w-full text-sm font-medium" x-model="clause.label" @input="schedulePreview()" placeholder="{{ __('Clause label (e.g. Payment)') }}" />
                                            <textarea rows="2" class="block w-full rounded-md border-line bg-surface text-sm" x-model="clause.body" @input="schedulePreview()" placeholder="{{ __('Clause text — supports the tokens above') }}"></textarea>
                                        </div>
                                        <button @click="removeClause(i)" class="mt-1 shrink-0 text-xs text-danger hover:underline" aria-label="{{ __('Remove clause') }}">✕</button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!form.clauses.length"><p class="rounded-lg border border-dashed border-line py-6 text-center text-sm text-muted">{{ __('No clauses yet.') }}</p></template>
                        </div>
                        <x-ui.button size="sm" variant="outline" x-on:click="addClause()">+ {{ __('Add clause') }}</x-ui.button>
                    </x-ui.card>

                    {{-- Sample inputs that drive the preview (never saved) --}}
                    <x-ui.card class="space-y-3">
                        <h3 class="font-display text-lg text-ink">{{ __('Preview inputs') }}</h3>
                        <p class="text-xs text-subtle">{{ __('Fictional values so you can see how a real contract will read. These are never saved.') }}</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div><x-input-label :value="__('Talent')" /><x-text-input class="mt-1 block w-full" x-model="sample.talent" @input="schedulePreview()" /></div>
                            <div><x-input-label :value="__('Brand')" /><x-text-input class="mt-1 block w-full" x-model="sample.brand" @input="schedulePreview()" /></div>
                            <div class="sm:col-span-2"><x-input-label :value="__('Scope of work')" /><x-text-input class="mt-1 block w-full" x-model="sample.scope" @input="schedulePreview()" /></div>
                            <div><x-input-label :value="__('Timeline')" /><x-text-input class="mt-1 block w-full" x-model="sample.timeline" @input="schedulePreview()" /></div>
                            <div><x-input-label :value="__('Total fee')" /><x-text-input type="number" min="0" class="mt-1 block w-full" x-model.number="sample.total" @input="schedulePreview()" /></div>
                            <div class="sm:col-span-2"><x-input-label :value="__('Exception (optional)')" /><x-text-input class="mt-1 block w-full" x-model="sample.exception" @input="schedulePreview()" placeholder="{{ __('e.g. Usable on Instagram only, 6 months') }}" /></div>
                        </div>
                    </x-ui.card>
                </div>

                {{-- RIGHT: the live contract document --}}
                <div class="lg:sticky lg:top-20">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="font-mono text-[10px] uppercase tracking-[0.15em] text-accent-ink">● {{ __('Contract, live') }}</div>
                        <span x-show="previewing" x-cloak class="text-[10px] uppercase tracking-wider text-subtle">{{ __('rendering…') }}</span>
                    </div>
                    <div x-html="previewHtml"></div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
