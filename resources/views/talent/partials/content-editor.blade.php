@php
    // The blank form for this content type, derived from its field registry.
    $blank = ['position' => null];
    foreach ($config['fields'] as $f) {
        $blank[$f['name']] = $f['kind'] === 'translatable' ? ['en' => '', 'ar' => ''] : '';
    }
    // Skill-scoped content (projects, ADR-Q) carries a talent_type_id.
    if ($config['skill_scoped'] ?? false) {
        $blank['talent_type_id'] = '';
    }
@endphp

{{-- One content block's editor: quick upload (when the type takes media), the
     registry-driven add form, and the reorderable items grid. Rendered once per
     block on the profile-content page, so a section stacks all of its editors. --}}
<div x-data="crudList({ endpoint: '/talent/content/{{ $type }}', blank: @js($blank), fields: @js($config['fields']), media: @js($config['media']) })" class="space-y-6">

    {{-- Media quick-add --}}
    <template x-if="media">
        <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-line-strong bg-surface p-8 text-center transition hover:border-accent">
            <span class="font-medium text-ink">{{ __('Drop files or click to upload') }}</span>
            <span class="font-mono text-[11px] uppercase tracking-wider text-subtle" x-text="saving ? '{{ __('Uploading…') }}' : '{{ __('Images & video') }}'"></span>
            <input type="file" multiple accept="image/*,video/*" class="hidden" @change="createAndUpload($event.target.files); $event.target.value = ''">
        </label>
    </template>

    {{-- Add form (fields from the type registry) --}}
    <div class="rounded-lg border border-line bg-elevated p-4">
        <h4 class="mb-3 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Add an item') }}</h4>
        <div class="grid gap-4 sm:grid-cols-2">
            <template x-for="field in fields" :key="field.name">
                <div>
                    <label class="mb-1 block text-sm font-medium capitalize text-ink" x-text="field.name.replaceAll('_', ' ')"></label>
                    {{-- Plain translatable (English-only for now) --}}
                    <template x-if="field.kind === 'translatable' && !field.rich">
                        <div>
                            <input x-model="form[field.name].en" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                        </div>
                    </template>

                    {{-- Rich translatable (summary / body) — a lightweight WYSIWYG.
                         Toolbar keeps focus (@mousedown.prevent); the editor
                         two-way binds to form[field.name].en (input pushes to
                         the model, x-effect restores it after a form reset). --}}
                    <template x-if="field.kind === 'translatable' && field.rich">
                        <div x-data="richField()" class="overflow-hidden rounded-md border border-line bg-elevated focus-within:border-accent focus-within:ring-1 focus-within:ring-accent">
                            <div class="flex items-center gap-0.5 border-b border-line px-1.5 py-1">
                                <button type="button" @mousedown.prevent="exec('bold')" title="{{ __('Bold') }}" class="grid h-7 w-7 place-items-center rounded font-bold text-muted hover:bg-surface hover:text-ink">B</button>
                                <button type="button" @mousedown.prevent="exec('italic')" title="{{ __('Italic') }}" class="grid h-7 w-7 place-items-center rounded italic text-muted hover:bg-surface hover:text-ink">I</button>
                                <button type="button" @mousedown.prevent="exec('insertUnorderedList')" title="{{ __('Bullet list') }}" class="grid h-7 w-7 place-items-center rounded text-muted hover:bg-surface hover:text-ink">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                                </button>
                                <button type="button" @mousedown.prevent="addLink()" title="{{ __('Link') }}" class="grid h-7 w-7 place-items-center rounded text-muted hover:bg-surface hover:text-ink">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
                                </button>
                            </div>
                            <div x-ref="ed" contenteditable="true" role="textbox" aria-multiline="true"
                                 x-init="$refs.ed.innerHTML = (form[field.name] && form[field.name].en) || ''"
                                 @input="form[field.name].en = $refs.ed.innerHTML"
                                 x-effect="(function(v){ if (document.activeElement !== $refs.ed && v !== $refs.ed.innerHTML) $refs.ed.innerHTML = v; })((form[field.name] && form[field.name].en) || '')"
                                 class="min-h-[92px] w-full px-3 py-2 text-sm leading-relaxed text-ink focus:outline-none [&_a]:text-accent [&_a]:underline [&_ul]:list-disc [&_ul]:ps-5"></div>
                        </div>
                    </template>
                    <template x-if="field.kind === 'select'">
                        <select x-model="form[field.name]" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                            <option value="">—</option>
                            <template x-for="opt in field.options" :key="opt"><option :value="opt" x-text="opt"></option></template>
                        </select>
                    </template>
                    <template x-if="['text', 'url', 'number', 'date'].includes(field.kind)">
                        <input :type="field.kind === 'number' ? 'number' : (field.kind === 'date' ? 'date' : (field.kind === 'url' ? 'url' : 'text'))"
                               x-model="form[field.name]" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    </template>
                    <p x-show="errors[field.name] || errors[field.name + '.en']" x-cloak class="mt-1 text-xs text-danger" x-text="(errors[field.name] || errors[field.name + '.en'])?.[0]"></p>
                </div>
            </template>

            {{-- Skill selector for skill-scoped content (projects) — defaults to the primary skill. --}}
            @if (($config['skill_scoped'] ?? false) && count($config['skills']))
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Skill') }}</label>
                    <select x-model="form.talent_type_id" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                        <option value="">{{ __('Primary skill') }}</option>
                        @foreach ($config['skills'] as $skill)
                            <option value="{{ $skill['id'] }}">{{ $skill['name'][app()->getLocale()] ?? $skill['name']['en'] ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="sm:col-span-2">
                <x-ui.button variant="accent" x-on:click="create()" x-bind:disabled="saving">{{ __('Add item') }}</x-ui.button>
            </div>
        </div>
    </div>

    {{-- Items grid (drag to reorder) --}}
    <div>
        <h4 class="mb-3 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Items') }} · {{ __('Drag to reorder') }}</h4>
        <div x-show="loading" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="aspect-[4/5] animate-pulse rounded-md bg-surface"></div>
            <div class="aspect-[4/5] animate-pulse rounded-md bg-surface"></div>
            <div class="aspect-[4/5] animate-pulse rounded-md bg-surface"></div>
            <div class="aspect-[4/5] animate-pulse rounded-md bg-surface"></div>
        </div>
        <div x-show="!loading" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <template x-for="item in items" :key="item.id">
                <div draggable="true" @dragstart="dragId = item.id" @dragover.prevent @drop.prevent="onDrop(item)"
                     class="group overflow-hidden rounded-md border border-line bg-surface">
                    <div class="aspect-[4/5]">
                        <template x-if="item.thumb"><img :src="item.thumb" class="h-full w-full object-cover" alt=""></template>
                        <template x-if="!item.thumb">
                            <div class="flex h-full w-full items-center justify-center p-2 text-center text-xs text-muted"
                                 style="background:linear-gradient(135deg, var(--accent-weak), var(--gold-weak));" x-text="item.label || '—'"></div>
                        </template>
                    </div>
                    <div class="flex items-center justify-between gap-1 p-2">
                        <span class="truncate text-xs text-ink" x-text="item.label"></span>
                        <div class="flex shrink-0 items-center gap-1">
                            <template x-if="media">
                                <label class="cursor-pointer text-subtle hover:text-ink" title="{{ __('Replace media') }}">↻<input type="file" class="hidden" accept="image/*,video/*" @change="uploadMedia(item.id, $event.target.files[0]); $event.target.value = ''"></label>
                            </template>
                            <button @click="$confirm({ title: '{{ __('Remove this item?') }}', message: '{{ __('This item will be permanently removed.') }}', confirmLabel: '{{ __('Remove') }}' }).then(ok => ok && remove(item.id))" class="text-subtle hover:text-danger" title="{{ __('Remove') }}">✕</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <p x-show="!loading && items.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-6 text-center text-sm text-subtle">{{ __('No items yet.') }}</p>
    </div>
</div>
