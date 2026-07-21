@php $initial = ['roles' => $roles->values()]; @endphp

<x-admin-layout :title="__('Accounts')">
    <div x-data="adminUsers(@js($initial))" class="space-y-6">
        <p class="text-sm text-muted">{{ __('Create accounts and manage staff. Admins are listed below; talent and brand accounts appear in the moderation queues.') }}</p>
        {{-- Create --}}
        <x-ui.card>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-display text-lg">{{ __('Add account') }}</h3>
                <span x-show="flash" x-cloak class="rounded-pill bg-success-weak px-2.5 py-0.5 text-xs font-medium text-success" x-text="flash"></span>
            </div>

            {{-- Account type: admin / brand / talent --}}
            <div class="mb-4">
                <x-input-label :value="__('Account type')" />
                <div class="mt-1.5 grid max-w-md grid-cols-3 gap-1 rounded-pill border border-line bg-elevated p-1">
                    @foreach (['admin' => __('Admin'), 'brand' => __('Brand'), 'talent' => __('Talent')] as $value => $label)
                        <button type="button" @click="form.account_type = '{{ $value }}'"
                                :class="form.account_type === '{{ $value }}' ? 'bg-surface text-ink shadow-e1' : 'text-muted hover:text-ink'"
                                class="rounded-pill px-3 py-2 text-sm font-medium transition">{{ $label }}</button>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-muted">
                    <span x-show="form.account_type === 'admin'">{{ __('A staff member with console access (assign roles below).') }}</span>
                    <span x-show="form.account_type === 'brand'" x-cloak>{{ __('A brand account — starts unpublished, pending onboarding.') }}</span>
                    <span x-show="form.account_type === 'talent'" x-cloak>{{ __('A talent account — starts as a draft profile.') }}</span>
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <x-input-label x-text="form.account_type === 'brand' ? '{{ __('Brand name') }}' : '{{ __('Name') }}'" />
                    <x-text-input class="mt-1 block w-full" x-model="form.name" />
                    <template x-if="errors.name"><p class="mt-1 text-xs text-danger" x-text="errors.name[0]"></p></template>
                </div>
                <div><x-input-label :value="__('Email')" /><x-text-input type="email" class="mt-1 block w-full" x-model="form.email" /><template x-if="errors.email"><p class="mt-1 text-xs text-danger" x-text="errors.email[0]"></p></template></div>
                <div><x-input-label :value="__('Password')" /><x-text-input type="password" class="mt-1 block w-full" x-model="form.password" /><template x-if="errors.password"><p class="mt-1 text-xs text-danger" x-text="errors.password[0]"></p></template></div>
                {{-- Roles: admins only --}}
                <div x-show="form.account_type === 'admin'" x-cloak>
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
            <x-ui.button variant="accent" class="mt-4" x-on:click="create()" x-bind:disabled="creating"
                         x-text="form.account_type === 'brand' ? '{{ __('Create brand') }}' : (form.account_type === 'talent' ? '{{ __('Create talent') }}' : '{{ __('Create admin') }}')"></x-ui.button>
        </x-ui.card>

        {{-- List --}}
        <template x-if="loading"><div><x-admin.skeleton :rows="3" /></div></template>
        <div x-show="!loading" class="space-y-2">
            <template x-for="user in users" :key="user.id">
                <div class="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-surface px-4 py-3 text-sm">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-pill bg-elevated font-display text-sm text-muted" x-text="(user.name || '?').charAt(0)"></span>
                    <div>
                        <div class="font-medium text-ink" x-text="user.name"></div>
                        <div class="text-xs text-muted" x-text="user.email"></div>
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <template x-for="r in (user.roles || [])" :key="r"><span class="rounded-pill bg-accent-weak px-2 py-0.5 text-[10px] text-accent-ink" x-text="r"></span></template>
                    </div>
                    <span class="ms-auto" :class="$pill(user.is_active ? 'active' : 'suspended')" x-text="user.is_active ? '{{ __('active') }}' : '{{ __('inactive') }}'"></span>
                    <button @click="$confirm({ title: '{{ __('Remove this admin?') }}', message: user.name + ' · ' + user.email, confirmLabel: '{{ __('Remove') }}' }).then(ok => ok && remove(user))" class="rounded-pill border border-line px-2.5 py-1 text-xs text-danger transition hover:border-danger">{{ __('Remove') }}</button>
                </div>
            </template>
        </div>
    </div>
</x-admin-layout>
