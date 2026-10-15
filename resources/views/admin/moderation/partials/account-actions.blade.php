@php
    // $obj = the JS object holding the row ('row' or 'detail'); $kind = 'talents'|'brands'.
    // One clear PRIMARY action (filled): Verify while unverified → Publish once
    // verified (hidden while suspended) → Reinstate while suspended; Suspend sits
    // beside it (outlined). Unpublish + Delete live in the "⋯" menu.
    // Verify + Suspend open the email composer (openNotice: preview + editable
    // copy) instead of a bare confirm; the rest confirm via $confirm.
    $o = $obj;
    $primary = 'inline-flex h-9 items-center gap-1.5 rounded-pill px-4 text-[13px] font-semibold transition';
    $item = 'flex w-full items-center gap-2.5 rounded-[10px] px-3 py-2 text-start text-sm font-medium transition hover:bg-elevated';
@endphp

{{-- Primary: Verify (one-way go-live sign-off) --}}
<button x-show="!{{ $o }}.is_verified && !{{ $o }}.trashed && {{ $o }}.status !== 'suspended'" type="button"
        @click="openNotice({{ $o }}, '{{ $kind }}', 'verify')"
        class="{{ $primary }} bg-primary text-on-primary hover:opacity-90">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/></svg>
    {{ __('Verify') }}
</button>

{{-- Suspend — beside the primary action (outlined, warn tone); opens the email composer --}}
<button x-show="!{{ $o }}.trashed && {{ $o }}.status !== 'suspended'" type="button"
        @click="openNotice({{ $o }}, '{{ $kind }}', 'suspend')"
        class="{{ $primary }} border border-line bg-surface text-warn hover:border-warn hover:bg-warn-weak">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="m5.6 5.6 12.8 12.8"/></svg>
    {{ __('Suspend') }}
</button>

{{-- Primary: Publish (only once verified, not suspended) --}}
<button x-show="!{{ $o }}.trashed && !{{ $o }}.is_published && {{ $o }}.status !== 'suspended' && {{ $o }}.is_verified" type="button"
        @click="$confirm({ title: '{{ __('Publish this account?') }}', message: '{{ __('The account becomes visible to the public.') }}', confirmLabel: '{{ __('Publish') }}', tone: 'accent' }).then(ok => ok && action({{ $o }},'publish'))"
        class="{{ $primary }} bg-success text-white hover:opacity-90">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-9-9h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>
    {{ __('Publish') }}
</button>

{{-- Primary: Reinstate (while suspended) --}}
<button x-show="!{{ $o }}.trashed && {{ $o }}.status === 'suspended'" type="button"
        @click="$confirm({ title: '{{ __('Reinstate this account?') }}', message: '{{ __('Lifts the suspension; the account returns to a hidden (unpublished) state.') }}', confirmLabel: '{{ __('Reinstate') }}', tone: 'accent' }).then(ok => ok && action({{ $o }},'unsuspend'))"
        class="{{ $primary }} bg-primary text-on-primary hover:opacity-90">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 9V6a4 4 0 0 0-8 0M5 11h14v10H5z"/></svg>
    {{ __('Reinstate') }}
</button>

{{-- Primary: Restore (deleted) --}}
<button x-show="{{ $o }}.trashed" type="button"
        @click="$confirm({ title: '{{ __('Restore this account?') }}', message: '{{ __('The account is restored from trash.') }}', confirmLabel: '{{ __('Restore') }}', tone: 'accent' }).then(ok => ok && action({{ $o }},'restore'))"
        class="{{ $primary }} bg-primary text-on-primary hover:opacity-90">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 3-6.7L3 8m0-5v5h5"/></svg>
    {{ __('Restore') }}
</button>

{{-- More: the less frequent / destructive actions --}}
<div class="relative" x-data="{ more: false }" x-show="!{{ $o }}.trashed" @click.outside="more = false" @keydown.escape.stop="more = false">
    <button type="button" @click="more = !more" :aria-expanded="more.toString()" aria-haspopup="menu" aria-label="{{ __('More actions') }}"
            class="grid h-9 w-9 place-items-center rounded-pill border border-line bg-surface text-ink transition hover:border-ink" :class="{ 'border-ink': more }">
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>
    </button>
    <div x-show="more" x-cloak x-transition.opacity.duration.120ms role="menu"
         class="absolute end-0 top-[calc(100%+6px)] z-30 w-56 rounded-[14px] border border-line bg-surface p-1.5 shadow-e3">
        <button type="button" role="menuitem" x-show="{{ $o }}.is_published" class="{{ $item }} text-ink"
                @click="more = false; $confirm({ title: '{{ __('Unpublish this account?') }}', message: '{{ __('The account is hidden from the public but stays editable.') }}', confirmLabel: '{{ __('Unpublish') }}', tone: 'accent' }).then(ok => ok && action({{ $o }},'unpublish'))">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.6 5.1A9.7 9.7 0 0 1 12 5c6 0 9.5 7 9.5 7a17 17 0 0 1-2.6 3.4M6.6 6.6C3.9 8.4 2.5 12 2.5 12s3.5 7 9.5 7a9.6 9.6 0 0 0 5.4-1.6"/></svg>{{ __('Unpublish') }}</button>
        <div class="my-1 h-px bg-line" x-show="{{ $o }}.is_published"></div>
        <button type="button" role="menuitem" class="{{ $item }} text-danger hover:bg-danger-weak"
                @click="more = false; $confirm({ title: '{{ __('Delete this account?') }}', message: ({{ $o }}.display_name || {{ $o }}.name || '') + ' — {{ __('this can be restored later.') }}', confirmLabel: '{{ __('Delete') }}' }).then(ok => ok && action({{ $o }},'delete'))">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>{{ __('Delete') }}</button>
    </div>
</div>
