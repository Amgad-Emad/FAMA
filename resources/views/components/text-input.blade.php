@props(['disabled' => false, 'error' => false])

{{-- Token-bound text input (Fama design system). `error` marks the field
     invalid: danger border + aria-invalid for assistive tech. --}}
<input @disabled($disabled)
       @if ($error) aria-invalid="true" @endif
       {{ $attributes->merge(['class' => 'rounded-md border bg-bg text-ink placeholder:text-subtle shadow-none transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/40 '.($error ? 'border-danger' : 'border-line-strong')]) }}>
