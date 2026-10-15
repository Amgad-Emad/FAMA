{{-- One text/email field in the prototype's markup (<label class="f"> wrapping
     label · input · help). The error replaces the help line (prototype
     fieldError): rendered server-side from $errors, then kept live by the
     parent famaAuthForm's `errors` map.
     Vars: name, label, type='text', value=null, help='', autocomplete=null,
           ltr=false, readonly=false, autofocus=false, maxlength=null, labelBind=null (Alpine x-text). --}}
@php
    $type = $type ?? 'text';
    $help = $help ?? '';
    $err = $errors->first($name);
    $key = \Illuminate\Support\Js::from($name);
@endphp
<label class="f{{ $err ? ' bad' : '' }}" :class="{ bad: !!errors[{{ $key }}] }">
    <span @if (! empty($labelBind)) x-text="{{ $labelBind }}" @endif>{{ $label }}</span>
    <input class="in" id="{{ $id ?? $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value ?? '' }}"
           @if (! empty($autocomplete)) autocomplete="{{ $autocomplete }}" @endif
           @if (! empty($ltr)) dir="ltr" @endif
           @if (! empty($maxlength)) maxlength="{{ $maxlength }}" @endif
           @if (! empty($readonly)) readonly @else required @endif
           @if (! empty($autofocus)) autofocus @endif
           aria-invalid="{{ $err ? 'true' : 'false' }}" :aria-invalid="errors[{{ $key }}] ? 'true' : 'false'">
    <p class="help{{ $err ? ' err' : '' }}" :class="{ err: !!errors[{{ $key }}] }"
       x-text="errors[{{ $key }}] || {{ \Illuminate\Support\Js::from($help) }}"
       @if ($err) role="alert" @endif :role="errors[{{ $key }}] ? 'alert' : false">{{ $err ?: $help }}</p>
</label>
