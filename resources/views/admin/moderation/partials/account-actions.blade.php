@php
    // $obj = the JS object holding the row ('row' or 'detail'); $kind = 'talents'|'brands'.
    // State-aware toggles: Suspend⇄Reinstate and Publish⇄Unpublish (Publish is
    // hidden while suspended — you must reinstate first). Every action confirms.
    $o = $obj;
    $btn = 'rounded-pill border border-line px-2.5 py-1 text-xs transition';
@endphp

{{-- Verify (one-way) — the admin sign-off that unlocks publishing. Until it is
     set, neither the account owner nor an admin can take the profile live. --}}
<button x-show="!{{ $o }}.is_verified && !{{ $o }}.trashed"
        @click="$confirm({ title: '{{ __('Verify this account?') }}', message: '{{ __('Verification approves the account to go public. It is one-way and cannot be removed — hide the account with Suspend or Unpublish instead.') }}', confirmLabel: '{{ __('Verify') }}', tone: 'accent' }).then(ok => ok && action({{ $o }},'verify'))"
        class="{{ $btn }} text-accent-ink hover:border-accent">{{ __('Verify') }}</button>

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
{{-- Publish only after verification — the go-live gate. --}}
<button x-show="!{{ $o }}.trashed && !{{ $o }}.is_published && {{ $o }}.status !== 'suspended' && {{ $o }}.is_verified"
        @click="$confirm({ title: '{{ __('Publish this account?') }}', message: '{{ __('The account becomes visible to the public.') }}', confirmLabel: '{{ __('Publish') }}', tone: 'accent' }).then(ok => ok && action({{ $o }},'publish'))"
        class="{{ $btn }} text-success hover:border-success">{{ __('Publish') }}</button>
<span x-show="!{{ $o }}.trashed && !{{ $o }}.is_published && {{ $o }}.status !== 'suspended' && !{{ $o }}.is_verified"
      class="text-xs text-muted">{{ __('Verify to enable publishing') }}</span>

{{-- Delete ⇄ Restore --}}
<button x-show="!{{ $o }}.trashed"
        @click="$confirm({ title: '{{ __('Delete this account?') }}', message: ({{ $o }}.display_name || {{ $o }}.name || '') + ' — {{ __('this can be restored later.') }}', confirmLabel: '{{ __('Delete') }}' }).then(ok => ok && action({{ $o }},'delete'))"
        class="{{ $btn }} text-danger hover:border-danger">{{ __('Delete') }}</button>
<button x-show="{{ $o }}.trashed"
        @click="$confirm({ title: '{{ __('Restore this account?') }}', message: '{{ __('The account is restored from trash.') }}', confirmLabel: '{{ __('Restore') }}', tone: 'accent' }).then(ok => ok && action({{ $o }},'restore'))"
        class="{{ $btn }} text-accent-ink hover:border-accent">{{ __('Restore') }}</button>
