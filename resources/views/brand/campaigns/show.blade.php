@php
    $initial = ['types' => $talentTypes->map(fn ($t) => ['id' => $t->id, 'name' => $t->getTranslation('name', app()->getLocale())])->values()];
    $brandUser = auth('brand')->user();
@endphp

<x-brand-layout :title="$campaign->title">
    <div x-data="brandCampaign({{ $campaign->id }}, @js($initial))" class="space-y-6">
        <a href="{{ route('brand.campaigns') }}" class="inline-flex items-center gap-1.5 text-xs text-muted transition hover:text-ink">
            <span class="rtl:rotate-180">←</span> {{ __('All campaigns') }}
        </a>

        {{-- Loading skeleton --}}
        <div x-show="loading" class="space-y-4">
            <div class="h-44 animate-pulse rounded-2xl bg-surface"></div>
            <div class="h-24 animate-pulse rounded-2xl bg-surface"></div>
        </div>

        <template x-if="!loading && campaign">
            <div class="space-y-6">
                {{-- ===================== HERO ===================== --}}
                <section class="overflow-hidden rounded-2xl border border-line bg-surface shadow-e1">
                    <div class="flex flex-col gap-6 p-6 sm:p-7">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="mb-2 flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-[10px] uppercase tracking-[0.16em] text-subtle" x-text="campaign.type === 'shoot' ? '{{ __('Shoot') }}' : '{{ __('Campaign') }}'"></span>
                                    <span class="inline-flex items-center gap-1.5 rounded-pill px-2.5 py-1 text-[11px] font-semibold capitalize"
                                          :class="{
                                              'bg-success-weak text-success': campaign.status === 'completed',
                                              'bg-accent-weak text-accent-ink': campaign.status === 'open' || campaign.status === 'in_progress',
                                              'bg-danger-weak text-danger': campaign.status === 'cancelled',
                                              'bg-elevated text-muted': campaign.status === 'draft',
                                          }">
                                        <span class="h-1.5 w-1.5 rounded-pill"
                                              :class="{
                                                  'bg-success': campaign.status === 'completed',
                                                  'bg-accent': campaign.status === 'open' || campaign.status === 'in_progress',
                                                  'bg-danger': campaign.status === 'cancelled',
                                                  'bg-muted': campaign.status === 'draft',
                                              }"></span>
                                        <span x-text="campaign.status.replaceAll('_', ' ')"></span>
                                    </span>
                                </div>
                                <h1 class="font-display text-3xl tracking-tight text-ink sm:text-4xl" x-text="campaign.title"></h1>
                                <p class="mt-1.5 flex items-center gap-2 text-sm text-muted">
                                    <span><span x-text="campaign.deals_count || 0"></span> {{ __('deals') }}</span>
                                    <span class="h-1 w-1 rounded-pill bg-line-strong"></span>
                                    <span x-text="campaign.is_public ? '{{ __('Public') }}' : '{{ __('Private') }}'"></span>
                                </p>
                            </div>

                            {{-- Action cluster --}}
                            <div class="flex flex-wrap items-center gap-2" x-show="!editing">
                                <button x-show="editable" @click="startEdit()" class="inline-flex items-center gap-1.5 rounded-pill border border-line-strong px-3.5 py-2 text-xs font-medium text-muted transition hover:border-accent hover:text-ink">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
                                    {{ __('Edit details') }}
                                </button>
                                <button x-show="campaign.status === 'draft'" @click="transition('open')" :disabled="acting" class="rounded-pill bg-accent px-4 py-2 text-xs font-semibold text-on-accent transition hover:opacity-90 disabled:opacity-50">{{ __('Open') }}</button>
                                <button x-show="campaign.status === 'open'" @click="transition('start')" :disabled="acting" class="rounded-pill bg-accent px-4 py-2 text-xs font-semibold text-on-accent transition hover:opacity-90 disabled:opacity-50">{{ __('Start') }}</button>
                                <button x-show="campaign.status === 'in_progress'" @click="transition('complete')" :disabled="acting" class="rounded-pill bg-success px-4 py-2 text-xs font-semibold text-white transition hover:opacity-90 disabled:opacity-50">{{ __('Complete') }}</button>
                                <button x-show="['draft','open','in_progress'].includes(campaign.status)" @click="transition('cancel')" :disabled="acting" class="rounded-pill border border-line px-3.5 py-2 text-xs font-medium text-muted transition hover:border-danger hover:text-danger">{{ __('Cancel') }}</button>
                            </div>
                        </div>

                        {{-- Lifecycle stepper --}}
                        <div x-show="!editing && campaign.status !== 'cancelled'" class="flex items-center gap-2">
                            <template x-for="(stage, i) in [{k:'draft',l:'{{ __('Draft') }}'},{k:'open',l:'{{ __('Open') }}'},{k:'in_progress',l:'{{ __('In progress') }}'},{k:'completed',l:'{{ __('Completed') }}'}]" :key="stage.k">
                                <div class="flex flex-1 items-center gap-2">
                                    <div class="flex shrink-0 items-center gap-2">
                                        <span class="grid h-6 w-6 place-items-center rounded-pill text-[10px] font-bold transition"
                                              :class="i <= statusIndex ? 'bg-accent text-on-accent' : 'bg-elevated text-subtle'">
                                            <span x-show="i < statusIndex">✓</span>
                                            <span x-show="i >= statusIndex" x-text="i + 1"></span>
                                        </span>
                                        <span class="hidden text-xs font-medium transition sm:inline" :class="i <= statusIndex ? 'text-ink' : 'text-subtle'" x-text="stage.l"></span>
                                    </div>
                                    <div x-show="i < 3" class="h-px flex-1 transition" :class="i < statusIndex ? 'bg-accent' : 'bg-line'"></div>
                                </div>
                            </template>
                        </div>
                        {{-- Cancelled banner --}}
                        <div x-show="!editing && campaign.status === 'cancelled'" x-cloak class="flex items-center gap-2 rounded-lg border border-danger/30 bg-danger-weak px-4 py-2.5 text-sm text-danger">
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
                            {{ __('This campaign was cancelled.') }}
                        </div>
                    </div>

                    {{-- Edit form (revealed by "Edit details") --}}
                    <div x-show="editing" x-cloak class="border-t border-line bg-elevated/40 p-6 sm:p-7">
                        <div class="mb-6 flex items-start gap-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-accent-weak text-accent-ink">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
                            </span>
                            <div>
                                <h3 class="font-display text-lg text-ink">{{ __('Edit campaign') }}</h3>
                                <p class="text-xs text-muted">{{ __('Update the campaign details, budget, and roles.') }}</p>
                            </div>
                        </div>

                        <div class="space-y-5">
                            {{-- Basics --}}
                            <div class="rounded-xl border border-line bg-surface p-5">
                                <div class="mb-4 font-mono text-[10px] uppercase tracking-[0.16em] text-subtle">{{ __('Basics') }}</div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <x-input-label :value="__('Title')" /><x-text-input class="mt-1 block w-full" x-model="form.title" />
                                        <template x-if="errors.title"><p class="mt-1 text-xs text-danger" x-text="errors.title[0]"></p></template>
                                    </div>
                                    <div>
                                        <x-input-label :value="__('Type')" />
                                        <select class="mt-1 block w-full rounded-md border-line bg-bg" x-model="form.type"><option value="campaign">{{ __('Campaign') }}</option><option value="shoot">{{ __('Shoot') }}</option></select>
                                    </div>
                                    <label class="flex cursor-pointer items-center justify-between gap-3 self-end rounded-lg border border-line bg-bg px-3.5 py-2.5 transition hover:border-line-strong">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-ink">{{ __('List publicly') }}</span>
                                            <span class="block text-xs text-muted">{{ __('Show this campaign on your public profile.') }}</span>
                                        </span>
                                        <input type="checkbox" x-model="form.is_public" class="h-4 w-4 shrink-0 rounded border-line text-accent focus:ring-accent">
                                    </label>
                                </div>
                            </div>

                            {{-- Budget --}}
                            <div class="rounded-xl border border-line bg-surface p-5">
                                <div class="mb-4 font-mono text-[10px] uppercase tracking-[0.16em] text-subtle">{{ __('Budget') }}</div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div><x-input-label :value="__('Budget min')" /><x-text-input type="number" min="0" class="mt-1 block w-full" x-model.number="form.budget_min" /></div>
                                    <div>
                                        <x-input-label :value="__('Budget max')" /><x-text-input type="number" min="0" class="mt-1 block w-full" x-model.number="form.budget_max" />
                                        <template x-if="errors.budget_max"><p class="mt-1 text-xs text-danger" x-text="errors.budget_max[0]"></p></template>
                                    </div>
                                </div>
                            </div>

                            {{-- Location & dates --}}
                            <div class="rounded-xl border border-line bg-surface p-5">
                                <div class="mb-4 font-mono text-[10px] uppercase tracking-[0.16em] text-subtle">{{ __('Location & dates') }}</div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div><x-input-label :value="__('City')" /><x-text-input class="mt-1 block w-full" x-model="form.location_city" /></div>
                                    <div><x-input-label :value="__('Country')" /><x-text-input class="mt-1 block w-full" x-model="form.location_country" /></div>
                                    <div><x-input-label :value="__('Start date')" /><x-text-input type="date" class="mt-1 block w-full" x-model="form.start_date" /></div>
                                    <div>
                                        <x-input-label :value="__('End date')" /><x-text-input type="date" class="mt-1 block w-full" x-model="form.end_date" />
                                        <template x-if="errors.end_date"><p class="mt-1 text-xs text-danger" x-text="errors.end_date[0]"></p></template>
                                    </div>
                                </div>
                            </div>

                            {{-- Roles --}}
                            <div class="rounded-xl border border-line bg-surface p-5">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="font-mono text-[10px] uppercase tracking-[0.16em] text-subtle">{{ __('Roles') }}</span>
                                    <button @click="addRole()" class="inline-flex items-center gap-1 rounded-pill border border-line-strong px-2.5 py-1 text-xs font-medium text-accent-ink transition hover:border-accent">+ {{ __('Add role') }}</button>
                                </div>
                                <div class="space-y-2">
                                    <template x-for="(role, i) in form.roles" :key="i">
                                        <div class="flex items-center gap-2 rounded-lg border border-line bg-bg p-2">
                                            <select class="min-w-0 flex-1 rounded-md border-line bg-surface text-sm" x-model.number="role.talent_type_id">
                                                <template x-for="type in types" :key="type.id"><option :value="type.id" x-text="type.name"></option></template>
                                            </select>
                                            <x-text-input type="number" min="1" class="w-20 text-sm" x-model.number="role.quantity" />
                                            <button @click="removeRole(i)" class="grid h-9 w-9 shrink-0 place-items-center rounded-md text-danger transition hover:bg-danger-weak" aria-label="{{ __('Remove') }}">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="!form.roles.length"><p class="py-2 text-xs text-muted">{{ __('No roles defined.') }}</p></template>
                                </div>
                            </div>
                        </div>

                        {{-- Footer actions --}}
                        <div class="mt-6 flex items-center gap-2">
                            <button @click="save()" :disabled="saving" class="rounded-pill bg-accent px-5 py-2 text-sm font-semibold text-on-accent transition hover:opacity-90 disabled:opacity-50">{{ __('Save changes') }}</button>
                            <button @click="cancelEdit()" class="rounded-pill border border-line px-4 py-2 text-sm text-muted transition hover:text-ink">{{ __('Cancel') }}</button>
                            <span x-show="saved" x-cloak class="text-xs text-ok">{{ __('Saved') }}</span>
                        </div>
                    </div>
                </section>

                {{-- ===================== READ-ONLY BODY ===================== --}}
                <template x-if="!editing">
                    <div class="space-y-6">
                        {{-- KPI strip --}}
                        <div class="grid grid-cols-2 overflow-hidden rounded-2xl border border-line sm:grid-cols-4" style="gap:1px;background:var(--line)">
                            <div class="bg-surface px-5 py-4">
                                <div class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Budget') }}</div>
                                <div class="mt-1 font-display text-lg text-ink">
                                    <template x-if="campaign.budget_min != null || campaign.budget_max != null">
                                        <span><span x-text="[campaign.budget_min, campaign.budget_max].filter((v) => v != null).map((v) => Number(v).toLocaleString()).join(' – ')"></span> <span class="text-sm text-muted" x-text="campaign.currency"></span></span>
                                    </template>
                                    <template x-if="campaign.budget_min == null && campaign.budget_max == null"><span class="text-muted">—</span></template>
                                </div>
                            </div>
                            <div class="bg-surface px-5 py-4">
                                <div class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Deals') }}</div>
                                <div class="mt-1 font-display text-lg text-ink" x-text="campaign.deals_count || 0"></div>
                            </div>
                            <div class="bg-surface px-5 py-4">
                                <div class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Positions') }}</div>
                                <div class="mt-1 font-display text-lg text-ink" x-text="totalPositions || '—'"></div>
                            </div>
                            <div class="bg-surface px-5 py-4">
                                <div class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ __('Location') }}</div>
                                <div class="mt-1 truncate font-display text-lg text-ink" x-text="[campaign.location_city, campaign.location_country].filter(Boolean).join(', ') || '—'"></div>
                            </div>
                        </div>

                        <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
                            {{-- MAIN COLUMN --}}
                            <div class="flex min-w-0 flex-col gap-6">
                                {{-- Roles --}}
                                <section class="rounded-2xl border border-line bg-surface p-6">
                                    <h3 class="mb-4 font-display text-lg text-ink">{{ __('Roles sought') }}</h3>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <template x-for="role in (campaign.roles || [])" :key="role.talent_type_id">
                                            <div class="flex items-center justify-between gap-3 rounded-xl border border-line bg-elevated px-4 py-3">
                                                <span class="min-w-0 truncate font-medium text-ink" x-text="role.name"></span>
                                                <span class="shrink-0 rounded-pill bg-accent-weak px-2.5 py-1 font-mono text-[11px] text-accent-ink">× <span x-text="role.quantity"></span></span>
                                            </div>
                                        </template>
                                        <template x-if="!(campaign.roles || []).length"><p class="text-sm text-muted">{{ __('No roles defined.') }}</p></template>
                                    </div>
                                </section>

                                {{-- Gallery --}}
                                <section class="rounded-2xl border border-line bg-surface p-6">
                                    <div class="mb-4 flex items-center justify-between">
                                        <h3 class="font-display text-lg text-ink">{{ __('Gallery') }}</h3>
                                        <label class="cursor-pointer rounded-pill border border-line-strong px-3 py-1.5 text-xs font-medium text-muted transition hover:border-accent hover:text-ink">
                                            {{ __('Add media') }}<input type="file" accept="image/*" class="hidden" @change="uploadMedia($event)">
                                        </label>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                        <template x-for="item in (campaign.gallery || [])" :key="item.id">
                                            <div class="group relative aspect-square overflow-hidden rounded-xl border border-line">
                                                <img :src="item.thumbnail_url || item.media_url" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.04]" alt="">
                                                <button @click="removeMedia(item.id)"
                                                        class="absolute end-1.5 top-1.5 grid h-7 w-7 place-items-center rounded-full bg-black/55 text-white opacity-0 backdrop-blur transition hover:bg-danger group-hover:opacity-100 focus:opacity-100"
                                                        aria-label="{{ __('Remove') }}">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="!(campaign.gallery || []).length">
                                            <label class="col-span-full flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-line py-12 text-center text-sm text-muted transition hover:border-accent hover:text-ink">
                                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                                {{ __('Add your first image') }}
                                                <input type="file" accept="image/*" class="hidden" @change="uploadMedia($event)">
                                            </label>
                                        </template>
                                    </div>
                                </section>

                                {{-- Deals under this campaign --}}
                                <section class="rounded-2xl border border-line bg-surface p-6">
                                    <h3 class="mb-4 font-display text-lg text-ink">{{ __('Deals under this campaign') }}</h3>
                                    <div class="space-y-2">
                                        <template x-for="deal in deals" :key="deal.id">
                                            <a :href="`/brand/deals/${deal.id}`" class="flex items-center justify-between gap-3 rounded-xl border border-line px-4 py-3 transition hover:border-line-strong">
                                                <div class="min-w-0">
                                                    <div class="truncate text-sm font-medium text-ink" x-text="deal.title"></div>
                                                    <div class="text-xs text-muted" x-text="deal.talent?.display_name || deal.reference"></div>
                                                </div>
                                                <span class="shrink-0 rounded-pill bg-elevated px-2.5 py-1 text-[11px] capitalize text-muted" x-text="deal.status.replaceAll('_', ' ')"></span>
                                            </a>
                                        </template>
                                        <template x-if="!deals.length">
                                            <p class="rounded-xl border border-dashed border-line py-8 text-center text-sm text-muted">{{ __('No deals under this campaign yet.') }}</p>
                                        </template>
                                    </div>
                                </section>
                            </div>

                            {{-- SIDEBAR --}}
                            <aside class="flex flex-col gap-4 lg:sticky lg:top-20 lg:self-start">
                                <div class="rounded-2xl border border-line bg-surface p-5">
                                    <div class="font-mono text-[10px] uppercase tracking-[0.16em] text-subtle">{{ __('Summary') }}</div>
                                    <dl class="mt-4 flex flex-col gap-3 text-sm">
                                        <div class="flex justify-between gap-3"><dt class="text-muted">{{ __('Type') }}</dt><dd class="font-medium capitalize text-ink" x-text="campaign.type"></dd></div>
                                        <div class="flex justify-between gap-3" x-show="campaign.start_date || campaign.end_date">
                                            <dt class="text-muted">{{ __('Dates') }}</dt>
                                            <dd class="text-end font-medium text-ink"><span x-text="campaign.start_date || '—'"></span> → <span x-text="campaign.end_date || '—'"></span></dd>
                                        </div>
                                        <div class="flex justify-between gap-3"><dt class="text-muted">{{ __('Currency') }}</dt><dd class="font-medium text-ink" x-text="campaign.currency"></dd></div>
                                    </dl>

                                    <div class="mt-4 flex items-center justify-between gap-3 border-t border-line pt-4">
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-ink" x-text="campaign.is_public ? '{{ __('Public') }}' : '{{ __('Private') }}'"></div>
                                            <div class="text-xs text-muted">{{ __('Listing visibility') }}</div>
                                        </div>
                                        <button @click="togglePublic()" class="shrink-0 rounded-pill border border-line-strong px-3 py-1.5 text-xs font-medium text-muted transition hover:border-accent hover:text-ink" x-text="campaign.is_public ? '{{ __('Make private') }}' : '{{ __('List publicly') }}'"></button>
                                    </div>

                                    @if ($brandUser?->is_published && $brandUser?->slug)
                                        <a x-show="campaign.is_public" x-cloak :href="`/brands/{{ $brandUser->slug }}/campaigns/${campaign.slug}`" target="_blank" rel="noopener"
                                           class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium text-accent-ink transition hover:opacity-80">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            {{ __('View public listing') }}
                                        </a>
                                    @endif
                                </div>

                                {{-- Danger zone --}}
                                <div class="rounded-2xl border border-danger/25 bg-surface p-5">
                                    <div class="font-mono text-[10px] uppercase tracking-[0.16em] text-danger/80">{{ __('Danger zone') }}</div>
                                    <p class="mt-2 text-xs text-muted">{{ __('Deleting a campaign removes it from your workspace. Deals under it are kept.') }}</p>
                                    <div class="mt-3" x-show="!confirmingDelete">
                                        <button @click="confirmingDelete = true" class="rounded-pill border border-danger/40 px-3.5 py-2 text-xs font-medium text-danger transition hover:bg-danger-weak">{{ __('Delete campaign') }}</button>
                                    </div>
                                    <div class="mt-3 flex items-center gap-2" x-show="confirmingDelete" x-cloak>
                                        <button @click="destroy()" :disabled="deleting" class="rounded-pill bg-danger px-3.5 py-2 text-xs font-semibold text-white transition hover:opacity-90 disabled:opacity-50">{{ __('Confirm delete') }}</button>
                                        <button @click="confirmingDelete = false" class="rounded-pill border border-line px-3.5 py-2 text-xs text-muted">{{ __('Cancel') }}</button>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</x-brand-layout>
