{{--
    Envelope-driven pagination. Expects the enclosing Alpine component to hold
    `meta` (the JSON envelope's meta, pagination under meta.pagination) and a
    `load(page)` method. Hidden while there is a single page.
--}}
<div x-show="meta?.pagination && meta.pagination.last_page > 1" x-cloak
     class="flex items-center justify-between pt-1 text-xs text-muted">
    <button type="button" @click="load(meta.pagination.current_page - 1)"
            :disabled="meta?.pagination?.current_page <= 1"
            class="rounded-pill border border-line px-3 py-1.5 transition hover:text-ink disabled:opacity-40">
        {{ __('Previous') }}
    </button>
    <span x-text="meta?.pagination ? `${meta.pagination.from ?? 0}–${meta.pagination.to ?? 0} · ${meta.pagination.total}` : ''"></span>
    <button type="button" @click="load(meta.pagination.current_page + 1)"
            :disabled="meta?.pagination?.current_page >= meta?.pagination?.last_page"
            class="rounded-pill border border-line px-3 py-1.5 transition hover:text-ink disabled:opacity-40">
        {{ __('Next') }}
    </button>
</div>
