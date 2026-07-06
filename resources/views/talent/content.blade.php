@php
    $blank = ['position' => null];
    foreach ($config['fields'] as $f) {
        $blank[$f['name']] = $f['kind'] === 'translatable' ? ['en' => '', 'ar' => ''] : '';
    }
@endphp

<x-talent-layout :title="__($config['label'])">
    {{-- Content type switcher --}}
    <div class="mb-6 flex flex-wrap gap-2">
        @foreach ($types as $t)
            <a href="{{ route('talent.content', ['type' => $t['type']]) }}"
               class="rounded-pill px-3 py-1.5 text-xs font-medium {{ $t['type'] === $type ? 'bg-primary text-on-primary' : 'border border-line text-muted hover:text-ink' }}">
                {{ __($t['label']) }}
            </a>
        @endforeach
    </div>

    <div x-data="crudList({ endpoint: '/talent/content/{{ $type }}', blank: @js($blank), fields: @js($config['fields']), media: @js($config['media']) })" class="space-y-8">

        {{-- Media quick-add (skeleton/loading state) --}}
        <template x-if="media">
            <x-ui.section :title="__('Upload')">
                <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-line-strong bg-surface p-10 text-center transition hover:border-accent">
                    <span class="font-medium text-ink">{{ __('Drop files or click to upload') }}</span>
                    <span class="font-mono text-[11px] uppercase tracking-wider text-subtle" x-text="saving ? '{{ __('Uploading…') }}' : '{{ __('Images & video') }}'"></span>
                    <input type="file" multiple accept="image/*,video/*" class="hidden" @change="createAndUpload($event.target.files); $event.target.value = ''">
                </label>
            </x-ui.section>
        </template>

        {{-- Add form (fields from the type registry) --}}
        <x-ui.section :title="__('Add an item')">
            <x-ui.card class="grid gap-4 sm:grid-cols-2">
                <template x-for="field in fields" :key="field.name">
                    <div>
                        <label class="mb-1 block text-sm font-medium capitalize text-ink" x-text="field.name.replaceAll('_', ' ')"></label>
                        <template x-if="field.kind === 'translatable'">
                            <div class="grid grid-cols-2 gap-2">
                                <input x-model="form[field.name].en" placeholder="EN" class="rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                                <input x-model="form[field.name].ar" placeholder="AR" dir="rtl" class="rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
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
                <div class="sm:col-span-2">
                    <x-ui.button variant="accent" x-on:click="create()" x-bind:disabled="saving">{{ __('Add item') }}</x-ui.button>
                </div>
            </x-ui.card>
        </x-ui.section>

        {{-- Items grid (drag to reorder) --}}
        <x-ui.section :title="__('Items')" :eyebrow="__('Drag to reorder')">
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
                                <button @click="remove(item.id)" class="text-subtle hover:text-danger" title="{{ __('Remove') }}">✕</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <p x-show="!loading && items.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-6 text-center text-sm text-subtle">{{ __('No items yet.') }}</p>
        </x-ui.section>
    </div>
</x-talent-layout>
