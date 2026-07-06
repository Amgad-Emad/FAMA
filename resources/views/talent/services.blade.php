<x-talent-layout :title="__('Rate card')">
    <div x-data="crudList({ endpoint: '/talent/services', blank: { name: { en: '', ar: '' }, description: { en: '', ar: '' }, price: '', currency: 'EGP', price_unit: 'project', duration_minutes: '' } })" class="space-y-8">

        <x-ui.section :title="__('Add a service')">
            <x-ui.card class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Name (EN)') }}</label>
                    <input x-model="form.name.en" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                    <p x-show="errors['name.en']" x-cloak class="mt-1 text-xs text-danger" x-text="errors['name.en']?.[0]"></p>
                </div>
                <div dir="rtl">
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Name (AR)') }}</label>
                    <input x-model="form.name.ar" type="text" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Price') }}</label>
                    <input x-model="form.price" type="number" min="0" step="0.01" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Unit') }}</label>
                    <select x-model="form.price_unit" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                        <option value="hour">{{ __('Per hour') }}</option>
                        <option value="day">{{ __('Per day') }}</option>
                        <option value="project">{{ __('Per project') }}</option>
                        <option value="fixed">{{ __('Fixed') }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Currency') }}</label>
                    <input x-model="form.currency" type="text" maxlength="3" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Duration (min)') }}</label>
                    <input x-model="form.duration_minutes" type="number" min="0" class="w-full rounded-md border-line bg-elevated text-ink shadow-sm focus:border-accent focus:ring-accent">
                </div>
                <div class="sm:col-span-2">
                    <x-ui.button variant="accent" x-on:click="create()" x-bind:disabled="saving">
                        <span x-show="!saving">{{ __('Add service') }}</span><span x-show="saving" x-cloak>{{ __('Saving…') }}</span>
                    </x-ui.button>
                </div>
            </x-ui.card>
        </x-ui.section>

        <x-ui.section :title="__('Your services')">
            <div x-show="loading" class="space-y-2">
                <div class="h-16 animate-pulse rounded-lg bg-surface"></div>
                <div class="h-16 animate-pulse rounded-lg bg-surface"></div>
            </div>
            <div x-show="!loading" class="space-y-2">
                <template x-for="s in items" :key="s.id">
                    <x-ui.card class="flex flex-wrap items-center justify-between gap-4" :pad="false">
                        <div class="flex flex-wrap items-center justify-between gap-4 p-4" style="width:100%">
                            <div class="min-w-0">
                                <div class="font-medium text-ink" x-text="t(s.name)"></div>
                                <div class="text-sm text-muted" x-text="t(s.description)"></div>
                            </div>
                            <div class="text-end">
                                <div class="font-display text-lg text-ink" x-show="s.price" x-text="s.price + ' ' + s.currency"></div>
                                <div class="font-mono text-[10px] uppercase tracking-wider text-subtle" x-text="'/ ' + s.price_unit"></div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="act(s.id, 'toggle')" class="rounded-pill px-3 py-1 text-xs font-medium"
                                        :class="s.is_active ? 'bg-success-weak text-success' : 'border border-line text-muted'"
                                        x-text="s.is_active ? '{{ __('Active') }}' : '{{ __('Paused') }}'"></button>
                                <button @click="remove(s.id)" class="text-subtle hover:text-danger">✕</button>
                            </div>
                        </div>
                    </x-ui.card>
                </template>
                <p x-show="items.length === 0" x-cloak class="rounded-lg border border-line bg-surface p-6 text-center text-sm text-subtle">{{ __('No services yet.') }}</p>
            </div>
        </x-ui.section>
    </div>
</x-talent-layout>
