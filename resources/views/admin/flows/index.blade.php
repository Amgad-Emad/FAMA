<x-admin-layout :title="__('Deal flows')">
    <div x-data="adminFlows()" class="space-y-6">
        <div class="flex items-center justify-between">
            <p class="text-sm text-muted">{{ __('Author reusable deal-flow templates. Edits affect future deals only.') }}</p>
        </div>

        {{-- Create --}}
        <x-ui.card class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
            <div>
                <x-input-label :value="__('New flow name')" />
                <x-text-input class="mt-1 block w-full" x-model="form.name" />
                <template x-if="errors.name"><p class="mt-1 text-xs text-danger" x-text="errors.name[0]"></p></template>
            </div>
            <div>
                <x-input-label :value="__('Applies to')" />
                <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="form.applies_to">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach (['model','crew','creative'] as $c)<option value="{{ $c }}">{{ __(ucfirst($c)) }}</option>@endforeach
                </select>
            </div>
            <x-ui.button variant="accent" x-on:click="create()" x-bind:disabled="creating">{{ __('Create flow') }}</x-ui.button>
        </x-ui.card>

        {{-- List --}}
        <div x-show="loading" class="py-10 text-center text-sm text-muted">{{ __('Loading…') }}</div>
        <div x-show="!loading" class="grid gap-3 sm:grid-cols-2">
            <template x-for="flow in flows" :key="flow.id">
                <a :href="`/admin/flows/${flow.id}`" class="block rounded-xl border border-line bg-surface p-5 transition hover:border-line-strong">
                    <div class="flex items-center justify-between">
                        <h3 class="font-display text-lg" x-text="flow.name"></h3>
                        <span class="rounded-pill bg-elevated px-2 py-0.5 text-xs" :class="flow.is_active ? 'text-ok' : 'text-muted'" x-text="flow.status"></span>
                    </div>
                    <p class="mt-1 text-xs text-muted">
                        <span x-text="flow.steps_count"></span> {{ __('steps') }} ·
                        <span x-text="flow.applies_to || '{{ __('all') }}'"></span>
                        <template x-if="flow.is_default"><span class="ms-1 rounded-pill bg-accent-weak px-2 py-0.5 text-accent-ink">{{ __('default') }}</span></template>
                    </p>
                </a>
            </template>
            <template x-if="!flows.length"><p class="col-span-full rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('No flows yet.') }}</p></template>
        </div>
    </div>
</x-admin-layout>
