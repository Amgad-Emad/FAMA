{{-- One password field in the prototype's markup (.f · .lbl · .pw with the
     Show/Hide text toggle · optional strength .meter · help). The static
     type="password" is the no-JS default; famaPw's :type takes over after init.
     Vars: name, label, autocomplete='current-password', help='', meter=false, id=name. --}}
@php
    $id = $id ?? $name;
    $help = $help ?? '';
    $meter = $meter ?? false;
    $err = $errors->first($name);
    $key = \Illuminate\Support\Js::from($name);
@endphp
<div class="f{{ $err ? ' bad' : '' }}" :class="{ bad: !!errors[{{ $key }}] }" x-data="famaPw()">
    <label class="lbl" for="{{ $id }}">{{ $label }}</label>
    <div class="pw">
        <input class="in" id="{{ $id }}" name="{{ $name }}" type="password" x-bind:type="show ? 'text' : 'password'"
               autocomplete="{{ $autocomplete ?? 'current-password' }}" dir="ltr" required
               @if ($meter) @input="measure($event)" @endif
               aria-invalid="{{ $err ? 'true' : 'false' }}" :aria-invalid="errors[{{ $key }}] ? 'true' : 'false'">
        <button type="button" @click="toggle()" aria-pressed="false" :aria-pressed="show.toString()"
                aria-label="{{ __('Show password') }}" :aria-label="show ? @js(__('Hide password')) : @js(__('Show password'))"
                x-text="show ? @js(__('Hide')) : @js(__('Show'))">{{ __('Show') }}</button>
    </div>
    @if ($meter)
        <div class="meter" :data-s="strength" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
    @endif
    <p class="help{{ $err ? ' err' : '' }}" :class="{ err: !!errors[{{ $key }}] }"
       x-text="errors[{{ $key }}] || {{ \Illuminate\Support\Js::from($help) }}"
       @if ($err) role="alert" @endif :role="errors[{{ $key }}] ? 'alert' : false">{{ $err ?: $help }}</p>
</div>
