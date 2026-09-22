{{-- Markdown `<x-mail::button>` → the shared Fama pill. Colour variants collapse to the brand accent. --}}
@props(['url', 'color' => 'primary', 'align' => 'center'])
<x-email.button :href="$url" :align="$align === 'center' ? 'start' : $align">{!! $slot !!}</x-email.button>
