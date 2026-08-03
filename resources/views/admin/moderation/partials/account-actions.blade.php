@php
    // $obj = the JS object holding the row ('row' or 'detail'); $kind = 'talents'|'brands'.
    // State-aware toggles: Suspend⇄Reinstate and Publish⇄Unpublish (Publish is
    // hidden while suspended — you must reinstate first). Every action confirms.
    $o = $obj;
    $btn = 'rounded-pill border border-line px-2.5 py-1 text-xs transition';
@endphp

@if ($kind === 'brands')
    <button x-show="!{{ $o }}.is_verified && !{{ $o }}.trashed"
            @click="$confirm({ title: '{{ __('Verify this brand?') }}', message: '{{ __('Verification is a one-way trust badge and cannot be removed.') }}', confirmLabel: '{{ __('Verify') }}', tone: 'accent' }).then(ok => ok && action({{ $o }},'verify'))"
            class="{{ $btn }} text-accent-ink hover:border-accent">{{ __('Verify') }}</button>
@endif

{{-- Suspend ⇄ Reinstate --}}
<button x-show="!{{ $o }}.trashed && {{ $o }}.status !== 'suspended'"
        @click="$confirm({ title: '{{ __('Suspend this account?') }}', message: '{{ __('The account is hidden from the public until it is reinstated.') }}', confirmLabel: '{{ __('Suspend') }}', tone: 'accent' }).then(ok => ok && action({{ $o }},'suspend'))"
        class="{{ $btn }} text-warn hover:border-warn">{{ __('Suspend') }}</button>
<button x-show="!{{ $o }}.trashed && {{ $o }}.status === 'suspended'"
        @click="$confirm({ title: '{{ __('Reinstate this account?') }}', message: '{{ __('Lifts the suspension; the account returns to a hidden (unpublished) state.') }}', confirmLabel: '{{ __('Reinstate') }}', tone: 'accent' }).then(ok => ok && action({{ $o }},'unsuspend'))"
        class="{{ $btn }} text-accent-ink hover:border-accent">{{ __('Reinstate') }}</button>

{{-- Publish ⇄ Unpublish (hidden while suspended) --}}
<button x-show="!{{ $o }}.trashed && {{ $o }}.is_published"
        @click="$confirm({ title: '{{ __('Unpublish this account?') }}', message: '{{ __('The account is hidden from the public but stays editable.') }}', confirmLabel: '{{ __('Unpublish') }}', tone: 'accent' }).then(ok => ok && action({{ $o }},'unpublish'))"
        class="{{ $btn }} text-muted hover:text-ink">{{ __('Unpublish') }}</button>
<button x-show="!{{ $o }}.trashed && !{{ $o }}.is_published && {{ $o }}.status !== 'suspended'"
        @click="$confirm({ title: '{{ __('Publish this account?') }}', message: '{{ __('The account becomes visible to the public.') }}', confirmLabel: '{{ __('Publish') }}', tone: 'accent' }).then(ok => ok && action({{ $o }},'publish'))"
        class="{{ $btn }} text-success hover:border-success">{{ __('Publish') }}</button>

{{-- Delete ⇄ Restore --}}
<button x-show="!{{ $o }}.trashed"
        @click="$confirm({ title: '{{ __('Delete this account?') }}', message: ({{ $o }}.display_name || {{ $o }}.name || '') + ' — {{ __('this can be restored later.') }}', confirmLabel: '{{ __('Delete') }}' }).then(ok => ok && action({{ $o }},'delete'))"
        class="{{ $btn }} text-danger hover:border-danger">{{ __('Delete') }}</button>
<button x-show="{{ $o }}.trashed"
        @click="$confirm({ title: '{{ __('Restore this account?') }}', message: '{{ __('The account is restored from trash.') }}', confirmLabel: '{{ __('Restore') }}', tone: 'accent' }).then(ok => ok && action({{ $o }},'restore'))"
        class="{{ $btn }} text-accent-ink hover:border-accent">{{ __('Restore') }}</button>
