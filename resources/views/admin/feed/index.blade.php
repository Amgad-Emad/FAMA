<x-admin-layout :title="__('Feed moderation')">
    {{-- Feed moderation console (Prompt 5Q): reports / posts / comments queues.
         Inline Alpine on the shared fetch wrapper — matches the admin queue
         pattern (server-validated ?queue= deep link, replaceState sync). --}}
    <div x-data="{
            tab: @js($queue),
            rows: [],
            pagination: null,
            loading: false,
            page: 1,
            status: '',
            state: '',
            q: '',

            init() { this.load(); },

            openTab(tab) {
                this.tab = tab;
                this.page = 1;
                this.status = ''; this.state = ''; this.q = '';
                window.history.replaceState({}, '', '{{ route('admin.moderation.feed') }}' + (tab === 'reports' ? '' : '?queue=' + tab));
                this.load();
            },

            endpoint() {
                const params = new URLSearchParams({ page: this.page });
                if (this.tab === 'reports' && this.status) params.set('status', this.status);
                if (this.tab !== 'reports' && this.state) params.set('state', this.state);
                if (this.q) params.set('q', this.q);
                return `{{ route('admin.moderation.feed') }}/${this.tab}?` + params.toString();
            },

            async load() {
                this.loading = true;
                try {
                    const { data, meta } = await window.fama.get(this.endpoint());
                    this.rows = data;
                    this.pagination = meta?.pagination ?? null;
                } catch (e) { console.warn(e); }
                this.loading = false;
            },

            async act(kind, id, action) {
                let reason = '';
                if (action === 'remove') {
                    reason = window.prompt(@js(__('Reason sent to the author:')), '') ?? '';
                    if (reason === '') return;
                }
                const ok = await window.Alpine.store('confirm').ask({
                    title: @js(__('Apply this action?')),
                    message: action,
                    confirmLabel: @js(__('Apply')),
                    tone: action === 'restore' ? 'accent' : 'danger',
                });
                if (!ok) return;
                try {
                    await window.fama.patch(`{{ route('admin.moderation.feed') }}/${kind}/${id}/${action}`, { reason });
                    this.load();
                } catch (e) { console.warn(e); }
            },
        }"
        class="space-y-5">

        <p class="text-sm text-muted">{{ __('Reports, posts and comments from the public feed — act through the state machines; removals notify the author.') }}</p>

        {{-- Tabs --}}
        <div class="flex flex-wrap items-center gap-2">
            @foreach (['reports' => __('Reports'), 'posts' => __('Posts'), 'comments' => __('Comments')] as $key => $label)
                <button @click="openTab('{{ $key }}')"
                        :class="tab === '{{ $key }}' ? 'bg-accent text-on-primary' : 'border border-line text-muted hover:text-ink'"
                        class="rounded-pill px-3 py-1.5 text-xs">{{ $label }}</button>
            @endforeach
        </div>

        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-2">
            <input type="search" x-show="tab !== 'reports'" x-cloak x-model="q" @input.debounce.400ms="page = 1; load()"
                   class="rounded-pill border-line bg-bg py-1.5 px-3 text-xs" placeholder="{{ __('Search body…') }}">
            <select x-show="tab === 'reports'" x-cloak x-model="status" @change="page = 1; load()"
                    class="rounded-pill border-line bg-bg py-1.5 text-xs text-muted">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (['open', 'reviewing', 'actioned', 'dismissed'] as $status)
                    <option value="{{ $status }}">{{ __($status) }}</option>
                @endforeach
            </select>
            <select x-show="tab !== 'reports'" x-cloak x-model="state" @change="page = 1; load()"
                    class="rounded-pill border-line bg-bg py-1.5 text-xs text-muted">
                <option value="">{{ __('All states') }}</option>
                @foreach (['published', 'hidden', 'removed'] as $state)
                    <option value="{{ $state }}">{{ __($state) }}</option>
                @endforeach
            </select>
        </div>

        {{-- Rows --}}
        <div class="overflow-hidden rounded-lg border border-line bg-surface">
            <div x-show="loading" class="p-6"><x-admin.skeleton :rows="4" /></div>

            <template x-if="!loading && rows.length === 0">
                <p class="p-6 text-sm text-muted">{{ __('All clear.') }}</p>
            </template>

            <ul x-show="!loading" class="divide-y divide-line">
                <template x-for="row in rows" :key="row.id">
                    <li class="flex flex-wrap items-center gap-3 px-4 py-3 text-sm">
                        {{-- Reports --}}
                        <template x-if="tab === 'reports'">
                            <div class="flex w-full flex-wrap items-center gap-3">
                                <span class="rounded-pill border border-line px-2 py-0.5 text-xs" x-text="row.target_type"></span>
                                <span class="rounded-pill px-2 py-0.5 text-xs" :class="$pill(row.status)" x-text="$statusLabel(row.status)"></span>
                                <span x-show="row.auto_hidden" class="rounded-pill bg-warn-weak px-2 py-0.5 text-xs text-warn">{{ __('auto-hidden') }}</span>
                                <span class="text-xs text-muted" x-text="row.reason"></span>
                                <span class="min-w-0 flex-1 truncate text-ink" x-text="row.content ?? '—'"></span>
                                <span class="text-xs text-muted" x-text="row.reporter"></span>
                                <a x-show="row.permalink" :href="row.permalink" target="_blank" class="text-xs text-accent hover:underline">{{ __('View') }}</a>
                                <template x-if="row.status === 'open' || row.status === 'reviewing'">
                                    <span class="flex gap-2">
                                        <button @click="act(row.target_type + 's', row.target_id, 'remove')" class="text-xs text-danger hover:underline">{{ __('Remove content') }}</button>
                                        <button @click="act(row.target_type + 's', row.target_id, 'hide')" class="text-xs text-warn hover:underline">{{ __('Hide') }}</button>
                                        <button @click="window.fama.patch(`{{ route('admin.moderation.feed') }}/reports/${row.id}/actioned`).then(() => load())" class="text-xs hover:underline">{{ __('Mark actioned') }}</button>
                                        <button @click="window.fama.patch(`{{ route('admin.moderation.feed') }}/reports/${row.id}/dismissed`).then(() => load())" class="text-xs text-muted hover:underline">{{ __('Dismiss') }}</button>
                                    </span>
                                </template>
                            </div>
                        </template>

                        {{-- Posts / comments --}}
                        <template x-if="tab !== 'reports'">
                            <div class="flex w-full flex-wrap items-center gap-3">
                                <span class="rounded-pill px-2 py-0.5 text-xs" :class="$pill(row.state)" x-text="$statusLabel(row.state)"></span>
                                <span class="text-xs text-muted" x-text="row.author"></span>
                                <span class="min-w-0 flex-1 truncate text-ink" x-text="row.excerpt || '—'"></span>
                                <a :href="row.permalink" target="_blank" class="text-xs text-accent hover:underline">{{ __('View') }}</a>
                                <span class="flex gap-2">
                                    <button x-show="row.state === 'published'" @click="act(tab, row.id, 'hide')" class="text-xs text-warn hover:underline">{{ __('Hide') }}</button>
                                    <button x-show="row.state === 'hidden'" @click="act(tab, row.id, 'restore')" class="text-xs hover:underline">{{ __('Restore') }}</button>
                                    <button x-show="row.state !== 'removed'" @click="act(tab, row.id, 'remove')" class="text-xs text-danger hover:underline">{{ __('Remove') }}</button>
                                </span>
                            </div>
                        </template>
                    </li>
                </template>
            </ul>
        </div>

        {{-- Pagination --}}
        <div x-show="pagination && pagination.last_page > 1" x-cloak class="flex items-center gap-3 text-xs text-muted">
            <button @click="page = Math.max(1, page - 1); load()" :disabled="page <= 1" class="disabled:opacity-40 hover:text-ink">‹ {{ __('Prev') }}</button>
            <span x-text="page + ' / ' + (pagination?.last_page ?? 1)"></span>
            <button @click="page = Math.min(pagination.last_page, page + 1); load()" :disabled="pagination && page >= pagination.last_page" class="disabled:opacity-40 hover:text-ink">{{ __('Next') }} ›</button>
        </div>
    </div>
</x-admin-layout>
