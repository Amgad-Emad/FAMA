{{--
    The official Fama logo (branding artwork) — inlined so it needs no request
    and can be sized with a class. The source file's `<style>` block (.st0/.st1)
    was converted to fill attributes on purpose: those class names are generic
    and would leak onto anything else on the page carrying them.

    Colours are the brand's own (orchid + yellow), not tokens — the mark stays
    itself in light and dark. Size with a height class; width follows.
--}}
<svg {{ $attributes->merge(['class' => 'h-8 w-auto']) }} viewBox="0 0 998.4 426.8"
     xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ __('Fama') }}">
    <polygon fill="#FFE72F" points="189.9 349.5 0 421 127.8 284.2 44.8 180.4 246.4 171.3 436.5 0 395 171.2 998.4 171.9 355.6 284.8 258.5 426.8 189.9 349.5"/>
    <path fill="#B247D4" d="M766.2,171.2h-67.8l-96.4,158.7-32.8-158.7h-44.7l-31.9,128.4-2.8-128.4h-242.2l-57.8,178.2,46.1,52.2,24.2-73.6,64.8.6-66.5,98.1,54.8-.6,48.1-74.7h88.3l-14,74.7h83.8l24.6-171.8,11.7,170.9,24.4,1,111.9-184.6-50,184.6h80.7l7-71.8h73.7l41.8,71.8h72.6l-151.6-254.9ZM354.8,284.7l-83.2.3,21.3-63.6,103.7,1.6-41.9,61.8ZM368.2,310.5l65.7-97.4,11,98.2-76.7-.8ZM724.5,312.5l18.3-85.7,49.3,84.9-67.6.8Z"/>
</svg>
