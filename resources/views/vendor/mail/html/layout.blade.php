{{--
    Framework markdown mail (password reset, …) rendered inside THE Fama email
    shell (components/email/layout) so every email shares one design. The
    $header / $footer slots from mail::message are ignored on purpose — the
    shell owns the logo header and the footer. Markdown body gets the
    `fm-md` scope that themes/fama.css styles (inlined at send time).
--}}
<x-email.layout>
<div class="fm-md">
{!! Illuminate\Mail\Markdown::parse($slot) !!}
</div>

{!! $subcopy ?? '' !!}
</x-email.layout>
