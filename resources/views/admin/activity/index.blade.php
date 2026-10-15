{{--
    Activity log — every audited admin action as a timeline grouped by day:
    who (initials), what (a readable sentence from the event key), on what
    (subject name), when (relative, exact on hover), and an expandable
    "What changed" (old → new attributes + properties such as the reason).
    Behaviour: adminActivity (resources/js/admin.js).
--}}
@php
    $logs = [
        'moderation' => __('Moderation'), 'feed_moderation' => __('Feed moderation'), 'catalog' => __('Catalog'),
        'settings' => __('Settings'), 'admin_users' => __('Accounts'), 'media' => __('Media'),
        'contract_flow' => __('Contract flows'), 'contract_intervention' => __('Contracts'),
    ];
    $verbs = [
        'talent.verified' => __('verified a talent'), 'talent.suspended' => __('suspended a talent'), 'talent.unsuspended' => __('reinstated a talent'),
        'talent.published' => __('published a talent'), 'talent.unpublished' => __('unpublished a talent'), 'talent.soft_deleted' => __('deleted a talent'), 'talent.restored' => __('restored a talent'),
        'brand.verified' => __('verified a brand'), 'brand.suspended' => __('suspended a brand'), 'brand.unsuspended' => __('reinstated a brand'),
        'brand.published' => __('published a brand'), 'brand.unpublished' => __('unpublished a brand'), 'brand.soft_deleted' => __('deleted a brand'),
        'review.approved' => __('approved a review'), 'review.rejected' => __('rejected a review'), 'review.deleted' => __('deleted a review'),
        'recognition.approved' => __('approved a recognition'), 'recognition.rejected' => __('rejected a recognition'), 'recognition.deleted' => __('deleted a recognition'),
        'created' => __('created'), 'updated' => __('updated'), 'deleted' => __('deleted'), 'activated' => __('activated'), 'archived' => __('archived'),
    ];
@endphp
<x-admin-layout :title="__('Activity log')">
    <div x-data="adminActivity({ logs: @js($logs), verbs: @js($verbs), today: @js(__('Today')), yesterday: @js(__('Yesterday')) })" class="space-y-5">

        {{-- Filters --}}
        <div class="rounded-[20px] border border-line bg-surface p-2.5">
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-[220px] flex-1">
                    <svg class="pointer-events-none absolute inset-y-0 start-4 my-auto h-[18px] w-[18px] text-subtle" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                    <input type="search" x-model.debounce.400ms="q" @input="load()" placeholder="{{ __('Search actions, e.g. verified, suspended…') }}"
                           class="h-11 w-full rounded-pill border-line bg-bg ps-11 pe-4 text-[15px] text-ink placeholder:text-subtle" aria-label="{{ __('Search') }}">
                </div>
                <select class="h-11 rounded-pill border-line bg-bg ps-4 text-[15px] text-ink" x-model="log" @change="load()" aria-label="{{ __('Category') }}">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach ($logs as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
                <div class="inline-flex rounded-pill border border-line bg-bg p-1" role="group" aria-label="{{ __('Period') }}">
                    @foreach (['24h' => __('24h'), '7d' => __('7 days'), '30d' => __('30 days'), '' => __('All')] as $r => $label)
                        <button type="button" @click="range = '{{ $r }}'; load()" :class="range === '{{ $r }}' ? 'bg-primary text-on-primary' : 'text-muted hover:text-ink'"
                                class="rounded-pill px-3.5 py-1.5 text-sm font-semibold transition">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="flex items-center justify-between px-1 text-sm text-muted">
            <span>{{ __('Every audited admin action — who did it, to what, and what changed.') }}</span>
            <span x-show="meta?.pagination" x-cloak x-text="(meta?.pagination?.total ?? 0) + ' {{ __('entries') }}'"></span>
        </div>

        <template x-if="loading"><div><x-admin.skeleton :rows="6" /></div></template>

        {{-- Timeline, grouped by day --}}
        <div x-show="!loading" class="space-y-6">
            <template x-for="group in groups" :key="group.key">
                <section>
                    <h2 class="mb-2 px-1 text-[12px] font-semibold uppercase tracking-[0.14em] text-subtle" x-text="group.label"></h2>
                    <ol class="overflow-hidden rounded-[20px] border border-line bg-surface">
                        <template x-for="(row, i) in group.rows" :key="row.id">
                            <li :class="{ 'border-t border-line': i > 0 }" x-data="{ open: false }">
                                <div class="flex items-start gap-3.5 px-5 py-4">
                                    <span class="mt-0.5 grid h-10 w-10 shrink-0 place-items-center rounded-pill text-xs font-semibold"
                                          :class="tone(row.log_name).avatar" x-text="initials(row.causer?.name || 'System')"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[15px] leading-snug text-ink">
                                            <b class="font-semibold" x-text="row.causer?.name || '{{ __('System') }}'"></b>
                                            <span x-text="sentence(row)"></span>
                                            <template x-if="row.subject_name || row.subject_id">
                                                <span> · <span class="font-semibold" x-text="row.subject_name || (row.subject_type + ' #' + row.subject_id)"></span></span>
                                            </template>
                                        </p>
                                        <p class="mt-1 flex flex-wrap items-center gap-2 text-[13px] text-muted">
                                            <span class="rounded-pill px-2 py-0.5 text-[11px] font-semibold" :class="tone(row.log_name).chip" x-text="logs[row.log_name] || row.log_name.replace(/_/g, ' ')"></span>
                                            <time :datetime="row.created_at" :title="new Date(row.created_at).toLocaleString()" x-text="ago(row.created_at)"></time>
                                            <template x-if="reason(row)"><span class="truncate">“<span x-text="reason(row)"></span>”</span></template>
                                        </p>
                                    </div>
                                    <button type="button" x-show="hasChanges(row)" @click="open = !open" :aria-expanded="open.toString()"
                                            class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-pill border border-line px-3.5 text-[13px] font-semibold text-ink transition hover:border-ink">
                                        {{ __('What changed') }}
                                        <svg class="h-4 w-4 transition" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                </div>
                                <div x-show="open" x-cloak class="border-t border-line bg-bg px-5 py-4 sm:ps-[4.6rem]">
                                    <dl class="grid gap-2 text-sm">
                                        <template x-for="c in changeRows(row)" :key="c.key">
                                            <div class="grid grid-cols-[minmax(0,10rem)_1fr] items-baseline gap-3">
                                                <dt class="truncate font-semibold text-muted" x-text="c.key.replace(/_/g, ' ')"></dt>
                                                <dd class="min-w-0 break-words text-ink">
                                                    <template x-if="c.old !== undefined"><span><span class="text-danger line-through decoration-danger/50" x-text="show(c.old)"></span> <span class="text-subtle">→</span> </span></template>
                                                    <span class="font-medium" :class="{ 'text-success': c.old !== undefined }" x-text="show(c.new)"></span>
                                                </dd>
                                            </div>
                                        </template>
                                    </dl>
                                </div>
                            </li>
                        </template>
                    </ol>
                </section>
            </template>
            <template x-if="!rows.length">
                <div class="rounded-[20px] border border-dashed border-line py-14 text-center">
                    <p class="font-display text-xl text-ink">{{ __('Nothing here yet') }}</p>
                    <p class="mt-1 text-sm text-muted">{{ __('No admin actions match these filters.') }}</p>
                </div>
            </template>
        </div>
        <x-admin.pagination />
    </div>
</x-admin-layout>
