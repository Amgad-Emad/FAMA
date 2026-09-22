{{--
    Bulletproof email button: the colour lives on the <td> (bgcolor) so it
    survives Outlook, and the padded <a> keeps the whole pill clickable.
    Background is accent-ink, not accent — white on the lighter orchid misses
    WCAG AA at body size. Props: href, align (start|center).
--}}
@props(['href', 'align' => 'start'])
@php
    use App\Mail\EmailTheme as T;

    $side = $align === 'center' ? 'center' : T::start();
@endphp
{{-- Full-width wrapper + aligned cell: an `align` on the button table itself floats it and text wraps around. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:28px 0 24px;">
    <tr>
        <td align="{{ $side }}">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="fm-btn" style="border-collapse:separate;">
                <tr>
                    <td bgcolor="{{ T::color('accent_ink') }}" style="background-color:{{ T::color('accent_ink') }};border-radius:999px;mso-padding-alt:14px 28px;">
                        <a href="{{ $href }}" target="_blank" rel="noopener"
                           style="display:inline-block;padding:14px 28px;border-radius:999px;font-family:{{ T::font('body') }};font-size:15px;font-weight:600;line-height:20px;color:{{ T::color('on_accent') }};text-decoration:none;text-align:center;">
                            {{ $slot }}&nbsp;&nbsp;{{ T::isRtl() ? '←' : '→' }}
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
