<x-brand-layout :title="$campaign->title">
    <div x-data="brandCampaign({{ $campaign->id }})" class="space-y-6">
        <a href="{{ route('brand.campaigns') }}" class="text-xs text-muted hover:text-ink">← {{ __('All campaigns') }}</a>

        <div x-show="loading" class="py-10 text-center text-sm text-muted">{{ __('Loading…') }}</div>

        <template x-if="!loading && campaign">
            <div class="space-y-6">
                {{-- Header + lifecycle --}}
                <div class="rounded-xl border border-line bg-surface p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="font-display text-2xl" x-text="campaign.title"></h2>
                            <p class="mt-1 text-xs text-muted"><span x-text="campaign.status"></span> · <span x-text="campaign.type"></span></p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button x-show="campaign.status === 'draft'" @click="transition('open')" :disabled="acting" class="rounded-pill bg-primary px-3 py-1.5 text-xs text-on-primary">{{ __('Open') }}</button>
                            <button x-show="campaign.status === 'open'" @click="transition('start')" :disabled="acting" class="rounded-pill bg-primary px-3 py-1.5 text-xs text-on-primary">{{ __('Start') }}</button>
                            <button x-show="campaign.status === 'in_progress'" @click="transition('complete')" :disabled="acting" class="rounded-pill bg-accent px-3 py-1.5 text-xs text-on-primary">{{ __('Complete') }}</button>
                            <button x-show="['draft','open','in_progress'].includes(campaign.status)" @click="transition('cancel')" :disabled="acting" class="rounded-pill border border-line px-3 py-1.5 text-xs text-muted">{{ __('Cancel') }}</button>
                            <button @click="togglePublic()" class="rounded-pill border border-line px-3 py-1.5 text-xs text-muted" x-text="campaign.is_public ? '{{ __('Make private') }}' : '{{ __('List publicly') }}'"></button>
                        </div>
                    </div>
                </div>

                {{-- Roles --}}
                <section class="rounded-xl border border-line bg-surface p-6">
                    <h3 class="mb-3 font-display text-lg">{{ __('Roles') }}</h3>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="role in (campaign.roles || [])" :key="role.talent_type_id">
                            <span class="rounded-pill bg-elevated px-3 py-1 text-xs"><span x-text="role.name"></span> × <span x-text="role.quantity"></span></span>
                        </template>
                        <template x-if="!(campaign.roles || []).length"><span class="text-sm text-muted">{{ __('No roles defined.') }}</span></template>
                    </div>
                </section>

                {{-- Gallery --}}
                <section class="rounded-xl border border-line bg-surface p-6">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="font-display text-lg">{{ __('Gallery') }}</h3>
                        <label class="cursor-pointer rounded-pill border border-line-strong px-3 py-1.5 text-xs text-muted hover:text-ink">{{ __('Add media') }}<input type="file" accept="image/*" class="hidden" @change="uploadMedia($event)"></label>
                    </div>
                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
                        <template x-for="item in (campaign.gallery || [])" :key="item.id">
                            <div class="aspect-square overflow-hidden rounded-lg border border-line"><img :src="item.thumbnail_url || item.media_url" class="h-full w-full object-cover" alt=""></div>
                        </template>
                    </div>
                </section>

                {{-- Deals under this campaign --}}
                <section class="rounded-xl border border-line bg-surface p-6">
                    <h3 class="mb-3 font-display text-lg">{{ __('Deals') }}</h3>
                    <template x-for="deal in deals" :key="deal.id">
                        <a :href="`/brand/deals/${deal.id}`" class="mb-2 flex items-center justify-between rounded-lg border border-line px-4 py-3 hover:border-line-strong">
                            <span class="text-sm" x-text="deal.title"></span>
                            <span class="rounded-pill bg-elevated px-2 py-0.5 text-xs text-muted" x-text="deal.status"></span>
                        </a>
                    </template>
                    <template x-if="!deals.length"><p class="py-4 text-center text-sm text-muted">{{ __('No deals under this campaign yet.') }}</p></template>
                </section>
            </div>
        </template>
    </div>
</x-brand-layout>
