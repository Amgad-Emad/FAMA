<x-admin-layout :title="__('Moderation')">
    <div x-data="adminModeration()" class="space-y-5">
        {{-- Tabs --}}
        <div class="flex flex-wrap gap-2">
            @foreach (['talents' => __('Talents'), 'reviews' => __('Reviews'), 'brands' => __('Brands'), 'brand-reviews' => __('Brand reviews'), 'campaigns' => __('Campaigns')] as $key => $label)
                <button @click="openTab('{{ $key }}')" :class="tab === '{{ $key }}' ? 'bg-accent text-on-primary' : 'border border-line text-muted hover:text-ink'" class="rounded-pill px-3 py-1.5 text-xs">{{ $label }}</button>
            @endforeach
        </div>

        <div x-show="loading" class="py-10 text-center text-sm text-muted">{{ __('Loading…') }}</div>

        {{-- Batch bar (reviews) --}}
        <div x-show="tab === 'reviews' && selected.length" x-cloak class="flex items-center gap-3 rounded-md border border-line bg-surface px-4 py-2 text-sm">
            <span><span x-text="selected.length"></span> {{ __('selected') }}</span>
            <button @click="batch('approve')" class="rounded-pill bg-ok px-3 py-1 text-xs text-white">{{ __('Approve') }}</button>
            <button @click="batch('reject')" class="rounded-pill bg-danger px-3 py-1 text-xs text-white">{{ __('Reject') }}</button>
        </div>

        {{-- Rows --}}
        <div x-show="!loading" class="space-y-2">
            <template x-for="row in rows" :key="row.id">
                <div class="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-surface px-4 py-3 text-sm">
                    <template x-if="tab === 'reviews'"><input type="checkbox" @change="toggle(row.id)" class="rounded border-line"></template>
                    <span class="font-medium text-ink" x-text="row.display_name || row.name || row.title || row.talent || ('#'+row.id)"></span>
                    <template x-if="row.status"><span class="rounded-pill bg-elevated px-2 py-0.5 text-xs text-muted" x-text="row.status"></span></template>
                    <template x-if="row.is_verified"><span class="rounded-pill bg-accent-weak px-2 py-0.5 text-xs text-accent-ink">{{ __('verified') }}</span></template>

                    <div class="ms-auto flex flex-wrap gap-1.5">
                        {{-- Talents --}}
                        <template x-if="tab === 'talents'">
                            <span class="flex gap-1.5">
                                <button x-show="!row.trashed" @click="action(row,'suspend')" class="rounded-pill border border-line px-2.5 py-1 text-xs text-muted">{{ __('Suspend') }}</button>
                                <button x-show="!row.trashed" @click="action(row,'unpublish')" class="rounded-pill border border-line px-2.5 py-1 text-xs text-muted">{{ __('Unpublish') }}</button>
                                <button x-show="!row.trashed" @click="action(row,'delete')" class="rounded-pill border border-line px-2.5 py-1 text-xs text-danger">{{ __('Delete') }}</button>
                                <button x-show="row.trashed" @click="action(row,'restore')" class="rounded-pill border border-line px-2.5 py-1 text-xs text-accent-ink">{{ __('Restore') }}</button>
                            </span>
                        </template>
                        {{-- Reviews / brand reviews --}}
                        <template x-if="tab === 'reviews' || tab === 'brand-reviews'">
                            <span class="flex gap-1.5">
                                <button @click="action(row,'approve')" class="rounded-pill border border-line px-2.5 py-1 text-xs text-ok">{{ __('Approve') }}</button>
                                <button @click="action(row,'reject')" class="rounded-pill border border-line px-2.5 py-1 text-xs text-danger">{{ __('Reject') }}</button>
                            </span>
                        </template>
                        {{-- Brands --}}
                        <template x-if="tab === 'brands'">
                            <span class="flex gap-1.5">
                                <button x-show="!row.is_verified" @click="action(row,'verify')" class="rounded-pill border border-line px-2.5 py-1 text-xs text-accent-ink">{{ __('Verify') }}</button>
                                <button @click="action(row,'suspend')" class="rounded-pill border border-line px-2.5 py-1 text-xs text-muted">{{ __('Suspend') }}</button>
                                <button @click="action(row,'delete')" class="rounded-pill border border-line px-2.5 py-1 text-xs text-danger">{{ __('Delete') }}</button>
                            </span>
                        </template>
                        {{-- Campaigns --}}
                        <template x-if="tab === 'campaigns'">
                            <span class="flex gap-1.5">
                                <button @click="action(row,'private')" class="rounded-pill border border-line px-2.5 py-1 text-xs text-muted">{{ __('Make private') }}</button>
                                <button @click="action(row,'cancel')" class="rounded-pill border border-line px-2.5 py-1 text-xs text-danger">{{ __('Cancel') }}</button>
                            </span>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="!rows.length"><p class="rounded-lg border border-dashed border-line py-10 text-center text-sm text-muted">{{ __('Nothing to moderate here.') }}</p></template>
        </div>
    </div>
</x-admin-layout>
