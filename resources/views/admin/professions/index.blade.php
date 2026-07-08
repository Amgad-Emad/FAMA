@php $initial = ['blockTypes' => $blockTypes->map(fn ($b) => ['key' => $b->key, 'name' => $b->getTranslation('name', app()->getLocale())])->values()]; @endphp

<x-admin-layout :title="__('Professions')">
    <div x-data="adminProfessions(@js($initial))" class="space-y-6">
        <p class="text-sm text-muted">{{ __('Edit the default block layout each profession seeds for NEW talents. Existing profiles are untouched.') }}</p>

        <div x-show="loading" class="py-10 text-center text-sm text-muted">{{ __('Loading…') }}</div>

        {{-- Types --}}
        <div x-show="!loading" class="space-y-4">
            <template x-for="type in types" :key="type.id">
                <x-ui.card>
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-display text-lg" x-text="type.name"></h3>
                        <span class="rounded-pill bg-elevated px-2 py-0.5 font-mono text-[10px] text-muted" x-text="type.category"></span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="block in blockTypes" :key="block.key">
                            <button type="button" @click="toggleBlock(type, block.key)"
                                    :class="type.default_blocks.includes(block.key) ? 'border-accent bg-accent-weak text-accent-ink' : 'border-line text-muted'"
                                    class="rounded-pill border px-3 py-1.5 text-xs" x-text="block.name"></button>
                        </template>
                    </div>
                    <x-ui.button size="sm" variant="primary" class="mt-4" x-on:click="saveBlocks(type)">{{ __('Save blocks') }}</x-ui.button>
                </x-ui.card>
            </template>
        </div>

        {{-- Add profession --}}
        <x-ui.card>
            <h3 class="mb-3 font-display text-lg">{{ __('Add profession') }}</h3>
            <div class="grid gap-3 sm:grid-cols-3">
                <div><x-input-label :value="__('Name (EN)')" /><x-text-input class="mt-1 block w-full" x-model="newType.name.en" /></div>
                <div><x-input-label :value="__('Name (AR)')" /><x-text-input class="mt-1 block w-full" dir="rtl" x-model="newType.name.ar" /></div>
                <div>
                    <x-input-label :value="__('Category')" />
                    <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="newType.category">
                        @foreach (['model','crew','creative'] as $c)<option value="{{ $c }}">{{ __(ucfirst($c)) }}</option>@endforeach
                    </select>
                </div>
            </div>
            <template x-if="errors['name.en']"><p class="mt-1 text-xs text-danger" x-text="errors['name.en'][0]"></p></template>
            <x-ui.button variant="accent" class="mt-4" x-on:click="addType()">{{ __('Add profession') }}</x-ui.button>
        </x-ui.card>
    </div>
</x-admin-layout>
