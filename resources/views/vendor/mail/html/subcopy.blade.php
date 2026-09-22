{{-- Markdown subcopy (the "trouble clicking the button?" fallback link), set small and quiet under a hairline. --}}
@php use App\Mail\EmailTheme as T; @endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:28px;">
<tr>
<td class="fm-subcopy fm-line" style="padding-top:18px;border-top:1px solid {{ T::color('line') }};font-family:{{ T::font('body') }};font-size:12px;line-height:1.6;color:{{ T::color('subtle') }};text-align:{{ T::start() }};word-break:break-all;">
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
