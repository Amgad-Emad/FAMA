{{--
    A highlighted note (a reason, a key fact): brand wash with a start-edge
    rule. Props: tone (accent|gold|success|danger), label (optional kicker),
    preserve (keep the author's line breaks — for admin-written text).
--}}
@props(['tone' => 'accent', 'label' => null, 'preserve' => false])
@php
    use App\Mail\EmailTheme as T;

    [$bg, $fg, $rule] = match ($tone) {
        'gold' => [T::color('gold_weak'), T::color('gold'), T::color('star')],
        'success' => [T::color('success_weak'), T::color('success'), T::color('success')],
        'danger' => [T::color('danger_weak'), T::color('danger'), T::color('danger')],
        default => [T::color('accent_weak'), T::color('accent_ink'), T::color('accent')],
    };
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0;border-collapse:separate;">
    <tr>
        <td class="fm-callout" style="background-color:{{ $bg }};border-{{ T::start() }}:4px solid {{ $rule }};border-radius:12px;padding:16px 20px;font-family:{{ T::font('body') }};font-size:15px;line-height:1.65;color:{{ $fg }};text-align:{{ T::start() }};">
            @if ($label)
                <p class="fm-callout-label" style="margin:0 0 6px;font-family:{{ T::font('mono') }};font-size:10px;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;color:{{ $fg }};">{{ $label }}</p>
            @endif
            <div dir="auto" style="{{ $preserve ? 'white-space:pre-wrap;' : '' }}">{{ $slot }}</div>
        </td>
    </tr>
</table>
