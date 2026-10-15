{{--
    Sent by AccountStatusNotice with an admin's Verify / Suspend decision. The
    admin moderation modal previews this exact view (AccountNoticeService::render).
--}}
@php
    use App\Mail\EmailTheme as T;

    $paragraphs = preg_split('/\R{2,}/', trim($body)) ?: [];
@endphp
<x-email.layout
    :title="$heading"
    :eyebrow="$type === 'verify' ? __('Profile checked') : __('Account notice')"
    :preheader="\Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $body), 120)"
    :footnote="__('You are receiving this because a Fama team member reviewed your account.')">

    @foreach ($paragraphs as $p)
        <p class="fm-muted" dir="auto" style="margin:{{ $loop->first ? '0' : '14px 0 0' }};color:{{ T::color('muted') }};">{!! nl2br(e($p)) !!}</p>
    @endforeach

    @if ($ctaLabel && $ctaUrl)
        <div style="margin-top:24px;"><x-email.button :href="$ctaUrl">{{ $ctaLabel }}</x-email.button></div>
    @endif
</x-email.layout>
