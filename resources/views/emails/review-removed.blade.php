<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('A review was removed') }}</title>
</head>
<body style="margin:0;background:#EDEFF3;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1A1A1C;">
    <div style="max-width:560px;margin:0 auto;padding:32px 20px;">
        <div style="font-size:20px;font-weight:700;letter-spacing:-.01em;">Fama<span style="color:#0E8A83;">.</span></div>

        <div style="margin-top:20px;background:#F8FAFC;border:1px solid #E2E6EC;border-radius:14px;padding:28px;">
            <h1 style="margin:0 0 12px;font-size:20px;line-height:1.3;">{{ __('A review was removed') }}</h1>

            <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#5E5E63;">
                {{ __('Our team removed a review on Fama. Here is why:') }}
            </p>

            <div style="margin:0 0 20px;padding:14px 16px;border-inline-start:3px solid #0E8A83;background:#D0EBE9;border-radius:8px;font-size:14px;line-height:1.6;color:#0A5F5A;white-space:pre-wrap;">{{ $reason }}</div>

            @if (!empty($review['body']) || !empty($review['reviewer_name']))
                <p style="margin:20px 0 8px;font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#93939A;">{{ __('The removed review') }}</p>
                <div style="border:1px solid #E2E6EC;border-radius:10px;padding:14px 16px;font-size:14px;line-height:1.6;">
                    @if (!empty($review['rating']))
                        <div style="color:#B0862F;letter-spacing:2px;font-size:13px;">{!! str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) !!}</div>
                    @endif
                    @if (!empty($review['body']))
                        <p style="margin:6px 0;color:#1A1A1C;">"{{ $review['body'] }}"</p>
                    @endif
                    @if (!empty($review['reviewer_name']))
                        <p style="margin:6px 0 0;font-size:12px;color:#93939A;">— {{ $review['reviewer_name'] }}@if(!empty($review['talent'])) · {{ __('on') }} {{ $review['talent'] }}@endif</p>
                    @endif
                </div>
            @endif

            <p style="margin:24px 0 0;font-size:13px;line-height:1.7;color:#5E5E63;">
                {{ __('If you believe this was a mistake, reply to this email and our team will take another look.') }}
            </p>
        </div>

        <p style="margin:18px 4px 0;font-size:11px;color:#93939A;">{{ __('Fama · Creative talent · Egypt-first · MENA-wide') }}</p>
    </div>
</body>
</html>
