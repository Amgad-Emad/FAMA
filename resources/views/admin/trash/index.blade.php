{{--
    Trash — every soft-deleted item, by type: restore it, or delete it forever
    (single + bulk). Items left here are removed for good automatically after the
    restore window (media:purge-trashed). Behaviour: adminTrash (resources/js/admin.js).
--}}
@php
    $labels = ['talents' => __('Talents'), 'posts' => __('Posts'), 'comments' => __('Comments'), 'reviews' => __('Reviews'), 'recognitions' => __('Recognitions'), 'brands' => __('Brands')];
@endphp
<x-admin-layout :title="__('Trash')">
    <div x-data="adminTrash({ type: @js($type), counts: @js($counts), canPurge: @js($canPurge) })" class="space-y-5"
         data-purge-one="{{ __('Delete this forever?') }}" data-purge-many="{{ __('Delete :n items forever?') }}"
         data-purge-body="{{ __('This can’t be undone. Files and everything attached to it are removed for good.') }}"
         data-talent-body="{{ __('This can’t be undone. Their posts, comments, projects (unless co-owned — those pass to a co-owner), profile content, files, likes and follows are removed for good.') }}">

        <div class="flex flex-wrap items-center gap-3 rounded-[16px] border border-gold-weak bg-gold-weak/40 px-5 py-3 text-sm text-ink">
            <svg class="h-5 w-5 shrink-0 text-gold" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5m0 3h.01M10.3 3.9 2.6 17.5A2 2 0 0 0 4.3 20.5h15.4a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
            <span>{{ __('Deleted items wait here for :n days — restore them any time before that. After it they, and their files, are removed for good automatically.', ['n' => $retention]) }}</span>
        </div>

        {{-- Types + search --}}
        <div class="rounded-[20px] border border-line bg-surface p-2.5">
            <div class="flex gap-1 overflow-x-auto" role="tablist">
                @foreach (array_keys($counts) as $key)
                    <button type="button" role="tab" @click="openType('{{ $key }}')" :aria-selected="(type === '{{ $key }}').toString()"
                            :class="type === '{{ $key }}' ? 'bg-primary text-on-primary' : 'text-muted hover:bg-elevated hover:text-ink'"
                            class="inline-flex shrink-0 items-center gap-2 rounded-pill px-4 py-2.5 text-sm font-semibold transition">
                        {{ $labels[$key] ?? $key }}
                        <span class="min-w-6 rounded-pill px-1.5 py-0.5 text-center text-[11px] font-bold leading-none"
                              :class="type === '{{ $key }}' ? 'bg-white/20 text-on-primary' : 'bg-elevated text-muted'" x-text="counts['{{ $key }}'] ?? 0"></span>
                    </button>
                @endforeach
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-2 border-t border-line px-1 pt-3 sm:px-2">
                <div class="relative min-w-[220px] flex-1">
                    <svg class="pointer-events-none absolute inset-y-0 start-4 my-auto h-[18px] w-[18px] text-subtle" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                    <input type="search" x-model="q" @input.debounce.400ms="load()" placeholder="{{ __('Search the trash…') }}" class="h-11 w-full rounded-pill border-line bg-bg ps-11 pe-4 text-[15px] text-ink" aria-label="{{ __('Search') }}">
                </div>
                <span class="ms-auto text-sm text-muted" x-show="meta?.pagination" x-cloak x-text="(meta?.pagination?.total ?? 0) + ' {{ __('in the trash') }}'"></span>
            </div>
        </div>

        {{-- Bulk bar --}}
        <div x-show="!loading && rows.length" x-cloak class="sticky top-24 z-10 flex flex-wrap items-center gap-2 rounded-[16px] border px-4 py-2.5 transition"
             :class="selected.length ? 'border-ink bg-primary text-on-primary' : 'border-line bg-surface text-ink'">
            <label class="inline-flex cursor-pointer items-center gap-2.5 pe-2 text-sm font-semibold">
                <input type="checkbox" :checked="allSelected" x-effect="$el.indeterminate = selected.length > 0 && !allSelected" @change="toggleAll()" class="h-[18px] w-[18px] rounded">
                <span x-text="selected.length ? selected.length + ' {{ __('selected') }}' : '{{ __('Select all on this page') }}'"></span>
            </label>
            <template x-if="selected.length">
                <span class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="bulk('restore')" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-white px-4 text-[13px] font-semibold text-ink hover:opacity-90">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 3-6.7L3 8m0-5v5h5"/></svg>{{ __('Restore') }}</button>
                    <button type="button" x-show="canPurge" @click="bulk('purge')" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-danger px-4 text-[13px] font-semibold text-white hover:opacity-90">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>{{ __('Delete forever') }}</button>
                    <button type="button" @click="selected = []" class="h-9 px-2 text-[13px] font-semibold hover:underline">{{ __('Clear selection') }}</button>
                </span>
            </template>
            <span x-show="notice" x-cloak class="ms-auto text-sm font-semibold" x-text="notice"></span>
        </div>

        <template x-if="loading"><div><x-admin.skeleton :rows="5" /></div></template>

        <div x-show="!loading" class="space-y-2">
            <template x-for="row in rows" :key="type + row.id">
                <article class="flex flex-wrap items-center gap-3.5 rounded-[16px] border border-line bg-surface px-4 py-3.5 sm:px-5">
                    <input type="checkbox" class="h-[18px] w-[18px] shrink-0 rounded" :checked="selected.includes(row.id)" @change="toggle(row.id)" aria-label="{{ __('Select') }}">
                    <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-pill bg-elevated text-sm font-semibold text-muted grayscale">
                        <template x-if="row.avatar"><img :src="row.avatar" alt="" class="h-full w-full object-cover"></template>
                        <template x-if="!row.avatar"><span x-text="initials(row.title)"></span></template>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[15px] font-semibold text-ink" dir="auto" x-text="row.title || '—'"></p>
                        <p class="truncate text-[13px] text-muted" x-text="row.subtitle"></p>
                        <p class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                            <span class="text-subtle" x-text="'{{ __('Deleted') }} ' + row.deleted_ago"></span>
                            <span class="rounded-pill px-2 py-0.5 font-semibold" :class="row.purge_in_days <= 3 ? 'bg-danger-weak text-danger' : 'bg-elevated text-muted'"
                                  x-text="row.purge_in_days === 0 ? '{{ __('Removed for good tonight') }}' : '{{ __('Gone for good in') }} ' + row.purge_in_days + ' {{ __('days') }}'"></span>
                            <template x-if="row.blocked"><span class="font-semibold text-warn" x-text="row.blocked"></span></template>
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <button type="button" @click="restore(row)" :disabled="!!row.blocked" class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-primary px-4 text-[13px] font-semibold text-on-primary hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 3-6.7L3 8m0-5v5h5"/></svg>{{ __('Restore') }}</button>
                        <button type="button" x-show="canPurge" @click="purge(row)" class="inline-flex h-9 items-center gap-1.5 rounded-pill border border-line px-4 text-[13px] font-semibold text-danger hover:border-danger hover:bg-danger-weak">{{ __('Delete forever') }}</button>
                    </div>
                </article>
            </template>
            <template x-if="!rows.length">
                <div class="rounded-[20px] border border-dashed border-line py-14 text-center">
                    <p class="font-display text-xl text-ink">{{ __('Nothing in the trash') }}</p>
                    <p class="mt-1 text-sm text-muted">{{ __('Deleted items of this type show up here.') }}</p>
                </div>
            </template>
        </div>
        <x-admin.pagination />
    </div>
</x-admin-layout>
