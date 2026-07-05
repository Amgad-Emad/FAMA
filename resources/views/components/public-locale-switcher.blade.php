@php use Mcamara\LaravelLocalization\Facades\LaravelLocalization; @endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center rounded-pill border border-line-strong bg-surface p-0.5 text-xs font-medium']) }}>
    @foreach (LaravelLocalization::getSupportedLocales() as $code => $properties)
        @php
            $active = $code === app()->getLocale();
            $label = $code === 'ar' ? 'ع' : strtoupper($code);
        @endphp
        <a
            href="{{ LaravelLocalization::getLocalizedURL($code) }}"
            hreflang="{{ $code }}"
            class="rounded-pill px-3 py-1 transition {{ $active ? 'bg-primary text-on-primary' : 'text-muted hover:text-ink' }}"
        >{{ $label }}</a>
    @endforeach
</div>
