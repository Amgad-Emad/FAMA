<x-admin-layout :title="__('Activity log')">
    <div x-data="adminActivity()" class="space-y-5">
        <div class="flex flex-wrap gap-2">
            <x-text-input class="flex-1" x-model.debounce.400ms="q" @input="load()" :placeholder="__('Search descriptions…')" />
            <select class="rounded-md border-line bg-bg text-sm" x-model="log" @change="load()">
                <option value="">{{ __('All logs') }}</option>
                @foreach (['deal_flow','moderation','catalog','media','deal_intervention','settings','admin_users'] as $l)<option value="{{ $l }}">{{ $l }}</option>@endforeach
            </select>
        </div>

        <div x-show="loading" class="py-10 text-center text-sm text-muted">{{ __('Loading…') }}</div>
        <div x-show="!loading" class="overflow-x-auto rounded-lg border border-line">
            <table class="w-full text-sm">
                <thead class="bg-surface text-start font-mono text-[10px] uppercase tracking-wider text-subtle">
                    <tr>
                        <th class="px-4 py-2 text-start">{{ __('When') }}</th>
                        <th class="px-4 py-2 text-start">{{ __('Log') }}</th>
                        <th class="px-4 py-2 text-start">{{ __('Action') }}</th>
                        <th class="px-4 py-2 text-start">{{ __('Subject') }}</th>
                        <th class="px-4 py-2 text-start">{{ __('Causer') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in rows" :key="row.id">
                        <tr class="border-t border-line">
                            <td class="whitespace-nowrap px-4 py-2 font-mono text-[11px] text-muted" x-text="new Date(row.created_at).toLocaleString()"></td>
                            <td class="px-4 py-2"><span class="rounded-pill bg-elevated px-2 py-0.5 text-[10px] text-muted" x-text="row.log_name"></span></td>
                            <td class="px-4 py-2 text-ink" x-text="row.description"></td>
                            <td class="px-4 py-2 text-muted"><span x-text="row.subject_type"></span> <span x-text="row.subject_id ? '#'+row.subject_id : ''"></span></td>
                            <td class="px-4 py-2 text-muted" x-text="row.causer ? (row.causer.name || (row.causer.type + ' #' + row.causer.id)) : '—'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <template x-if="!rows.length"><p class="py-10 text-center text-sm text-muted">{{ __('No activity found.') }}</p></template>
        </div>
    </div>
</x-admin-layout>
