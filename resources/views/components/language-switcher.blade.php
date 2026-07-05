@php use Mcamara\LaravelLocalization\Facades\LaravelLocalization; @endphp

{{-- Locale switcher: links to the same page in each supported locale. --}}
<div {{ $attributes->merge(['class' => 'flex items-center gap-x-3 text-sm']) }}>
    @foreach (LaravelLocalization::getSupportedLocales() as $code => $properties)
        @if ($code === app()->getLocale())
            <span class="font-semibold text-gray-900 dark:text-gray-100" aria-current="true">
                {{ $properties['native'] }}
            </span>
        @else
            <a
                rel="alternate"
                hreflang="{{ $code }}"
                href="{{ LaravelLocalization::getLocalizedURL($code) }}"
                class="text-gray-500 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-100"
            >
                {{ $properties['native'] }}
            </a>
        @endif
    @endforeach
</div>
