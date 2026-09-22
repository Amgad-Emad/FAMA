{{--
    THE Fama email shell — every outgoing email (Blade mailables AND the
    framework's markdown notifications, via vendor/mail/html/layout) renders
    inside it, so the brand lives in one place.

    Email-client rules this follows on purpose: table layout (Outlook), inline
    styles (Gmail strips most <style>), physical left/right from EmailTheme
    (no logical properties in mail), a PNG logo (no SVG support in Gmail/
    Outlook) with a styled alt so it still reads when images are blocked, and
    a hidden preheader for the inbox preview line. The <style> block only
    carries progressive extras: mobile padding and dark mode (Apple Mail/iOS).

    Props: title (<title> + H1), preheader (inbox preview), eyebrow (small
    kicker above the H1), footnote (why the recipient got this email).
--}}
@props(['title' => null, 'preheader' => null, 'eyebrow' => null, 'footnote' => null])
@php
    use App\Mail\EmailTheme as T;

    $start = T::start();
    $support = T::supportAddress();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ T::dir() }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ $title ?? config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Sora:wght@400;600&family=IBM+Plex+Mono:wght@500&family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table { border-collapse: collapse; mso-table-lspace: 0; mso-table-rspace: 0; }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        a { color: {{ T::color('accent_ink') }}; }
        .fm-md a { color: {{ T::color('accent_ink') }}; text-decoration: underline; }

        @media only screen and (max-width: 620px) {
            .fm-container { width: 100% !important; }
            .fm-pad { padding-left: 24px !important; padding-right: 24px !important; }
            .fm-card-body { padding: 28px 24px 32px !important; }
            .fm-h1 { font-size: 24px !important; }
            table.fm-btn td a { display: block !important; }
        }

        @media (prefers-color-scheme: dark) {
            .fm-bg { background-color: #171019 !important; }
            .fm-card { background-color: #201826 !important; border-color: #382C40 !important; }
            .fm-ink, .fm-md h1, .fm-md h2, .fm-md p, .fm-md li { color: #F1EAF3 !important; }
            .fm-muted { color: #ADA1B3 !important; }
            .fm-subtle { color: #7C7083 !important; }
            .fm-line { border-color: #382C40 !important; }
            .fm-eyebrow { color: #E4B4F5 !important; }
            .fm-callout, .fm-callout-label { background-color: #3D1C4E !important; color: #E4B4F5 !important; }
            .fm-star { color: #EFD874 !important; }
            .fm-star-off { color: #4C3D57 !important; }
            .fm-panel { background-color: #2A1F31 !important; border-color: #382C40 !important; }
            a, .fm-md a { color: #E4B4F5 !important; }
            /* After the generic link rule and more specific — the button label must stay dark on the light orchid. */
            table.fm-btn td { background-color: #C87BE7 !important; }
            table.fm-btn td a { color: #171019 !important; }
        }
    </style>
    <!--[if mso]><style>* { font-family: Arial, sans-serif !important; }</style><![endif]-->
    {{ $head ?? '' }}
</head>
<body class="fm-bg" style="margin:0;padding:0;background-color:{{ T::color('bg') }};">
    @if ($preheader)
        {{-- Inbox preview line; the filler stops clients pulling body text in after it. --}}
        <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
            {{ $preheader }}&#8199;&#65279;&#847;&#8199;&#65279;&#847;&#8199;&#65279;&#847;&#8199;&#65279;&#847;&#8199;&#65279;&#847;&#8199;&#65279;&#847;&#8199;&#65279;&#847;&#8199;&#65279;&#847;
        </div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="fm-bg" style="background-color:{{ T::color('bg') }};">
        <tr>
            <td align="center" style="padding:40px 16px 48px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" class="fm-container" style="width:600px;max-width:600px;">

                    {{-- Header: the official logo, linking home. --}}
                    <tr>
                        <td class="fm-pad" align="{{ $start }}" style="padding:0 8px 24px;">
                            <a href="{{ config('app.url') }}" target="_blank" rel="noopener" style="display:inline-block;text-decoration:none;">
                                <img src="{{ asset('images/email/fama-logo.png') }}" width="112" height="48" alt="{{ config('app.name') }}"
                                     style="display:block;width:112px;height:48px;border:0;font-family:{{ T::font('display') }};font-size:28px;font-weight:700;line-height:48px;color:{{ T::color('accent') }};">
                            </a>
                        </td>
                    </tr>

                    {{-- Card — its own `separate` table: collapsed borders ignore border-radius. --}}
                    <tr>
                        <td>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="fm-card" style="border-collapse:separate;background-color:{{ T::color('card') }};border:1px solid {{ T::color('line') }};border-radius:20px;overflow:hidden;">
                        <tr>
                        <td style="border-radius:20px;overflow:hidden;">
                            {{-- Brand band: the logo's yellow star meeting the orchid. --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="72" height="6" style="width:72px;height:6px;line-height:6px;font-size:0;background-color:{{ T::color('star') }};border-top-{{ $start }}-radius:20px;">&nbsp;</td>
                                    <td height="6" style="height:6px;line-height:6px;font-size:0;background-color:{{ T::color('accent') }};border-top-{{ T::end() }}-radius:20px;">&nbsp;</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td class="fm-card-body" align="{{ $start }}" dir="{{ T::dir() }}" style="padding:40px 44px 44px;text-align:{{ $start }};font-family:{{ T::font('body') }};font-size:15px;line-height:1.7;color:{{ T::color('ink') }};">
                                        @if ($eyebrow)
                                            <p class="fm-eyebrow" style="margin:0 0 12px;font-family:{{ T::font('mono') }};font-size:11px;font-weight:500;letter-spacing:0.14em;text-transform:uppercase;color:{{ T::color('accent_ink') }};">{{ $eyebrow }}</p>
                                        @endif
                                        @if ($title)
                                            <h1 class="fm-h1 fm-ink" style="margin:0 0 16px;font-family:{{ T::font('display') }};font-size:28px;font-weight:700;line-height:1.2;letter-spacing:-0.02em;color:{{ T::color('ink') }};">{{ $title }}</h1>
                                        @endif

                                        {{ $slot }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                        </tr>
                        </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td class="fm-pad" align="{{ $start }}" style="padding:28px 8px 0;text-align:{{ $start }};font-family:{{ T::font('body') }};font-size:12px;line-height:1.7;color:{{ T::color('muted') }};">
                            @if ($support)
                                <p class="fm-muted" style="margin:0 0 10px;color:{{ T::color('muted') }};">
                                    {{ __('Questions? Just reply, or write to') }}
                                    <a href="mailto:{{ $support }}" style="color:{{ T::color('accent_ink') }};text-decoration:underline;">{{ $support }}</a>
                                </p>
                            @endif
                            @if ($footnote)
                                <p class="fm-subtle" style="margin:0 0 10px;color:{{ T::color('subtle') }};">{{ $footnote }}</p>
                            @endif
                            <p class="fm-subtle fm-line" style="margin:0;padding-top:14px;border-top:1px solid {{ T::color('line') }};color:{{ T::color('subtle') }};">
                                <a href="{{ config('app.url') }}" target="_blank" rel="noopener" style="color:{{ T::color('ink') }};font-weight:600;text-decoration:none;" class="fm-ink">{{ config('app.name') }}</a>
                                &nbsp;·&nbsp; {{ __('Creative talent · Egypt-first · MENA-wide') }}
                                <br>© {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
