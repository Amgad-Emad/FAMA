{{--
    A quiet, bordered card for quoted/secondary content (a removed review, a
    details summary). Props: label (optional mono kicker above the card).
--}}
@props(['label' => null])
@php
    use App\Mail\EmailTheme as T;
@endphp
@if ($label)
    <p class="fm-subtle" style="margin:28px 0 8px;font-family:{{ T::font('mono') }};font-size:10px;font-weight:500;letter-spacing:0.14em;text-transform:uppercase;color:{{ T::color('subtle') }};">{{ $label }}</p>
@endif
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:{{ $label ? '0' : '20px' }} 0 20px;border-collapse:separate;">
    <tr>
        <td class="fm-panel" style="background-color:{{ T::color('surface') }};border:1px solid {{ T::color('line') }};border-radius:14px;padding:18px 20px;font-family:{{ T::font('body') }};font-size:14px;line-height:1.65;color:{{ T::color('ink') }};text-align:{{ T::start() }};">
            {{ $slot }}
        </td>
    </tr>
</table>
