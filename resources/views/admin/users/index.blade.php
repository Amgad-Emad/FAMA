@php $initial = ['roles' => $roles->values()]; @endphp

<x-admin-layout :title="__('Admins')">
    <div x-data="adminUsers(@js($initial))" class="space-y-6">
        {{-- Create --}}
        <x-ui.card>
            <h3 class="mb-3 font-display text-lg">{{ __('Add admin') }}</h3>
            <div class="grid gap-3 sm:grid-cols-2">
                <div><x-input-label :value="__('Name')" /><x-text-input class="mt-1 block w-full" x-model="form.name" /></div>
                <div><x-input-label :value="__('Email')" /><x-text-input type="email" class="mt-1 block w-full" x-model="form.email" /><template x-if="errors.email"><p class="mt-1 text-xs text-danger" x-text="errors.email[0]"></p></template></div>
                <div><x-input-label :value="__('Password')" /><x-text-input type="password" class="mt-1 block w-full" x-model="form.password" /></div>
                <div>
                    <x-input-label :value="__('Roles')" />
                    <div class="mt-1 flex flex-wrap gap-2">
                        <template x-for="role in roles" :key="role">
                            <label class="flex items-center gap-1.5 rounded-pill border border-line px-3 py-1.5 text-xs">
                                <input type="checkbox" :value="role" x-model="form.roles" class="rounded border-line"><span x-text="role"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
            <x-ui.button variant="accent" class="mt-4" x-on:click="create()" x-bind:disabled="creating">{{ __('Create admin') }}</x-ui.button>
        </x-ui.card>

        {{-- List --}}
        <div x-show="loading" class="py-10 text-center text-sm text-muted">{{ __('Loading…') }}</div>
        <div x-show="!loading" class="space-y-2">
            <template x-for="user in users" :key="user.id">
                <div class="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-surface px-4 py-3 text-sm">
                    <div>
                        <div class="font-medium text-ink" x-text="user.name"></div>
                        <div class="text-xs text-muted" x-text="user.email"></div>
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <template x-for="r in (user.roles || [])" :key="r"><span class="rounded-pill bg-accent-weak px-2 py-0.5 text-[10px] text-accent-ink" x-text="r"></span></template>
                    </div>
                    <span class="ms-auto text-xs" :class="user.is_active ? 'text-ok' : 'text-muted'" x-text="user.is_active ? '{{ __('active') }}' : '{{ __('inactive') }}'"></span>
                    <button @click="remove(user)" class="text-xs text-danger">{{ __('Remove') }}</button>
                </div>
            </template>
        </div>
    </div>
</x-admin-layout>
