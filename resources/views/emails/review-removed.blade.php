{{--
    Sent by ReviewRemovedNotice when an admin removes a review. The admin
    composer's live preview (admin/moderation/index.blade.php) mirrors this
    template — keep the two in step.
--}}
@php
    use App\Mail\EmailTheme as T;

    $rating = (int) ($review['rating'] ?? 0);
@endphp
<x-email.layout
    :title="__('A review was removed')"
    :eyebrow="__('Moderation notice')"
    :preheader="__('Our team removed a review from your Fama profile — here is why.')"
    :footnote="__('You are receiving this because a review on your Fama profile was moderated.')">

    <p class="fm-muted" style="margin:0;color:{{ T::color('muted') }};">
        {{ __('Our team removed a review on Fama. Here is why:') }}
    </p>

    <x-email.callout :label="__('Reason')" preserve>{{ $reason }}</x-email.callout>

    @if (!empty($review['body']) || !empty($review['reviewer_name']))
        <x-email.panel :label="__('The removed review')">
            @if ($rating > 0)
                <div class="fm-star" style="margin:0 0 8px;font-size:15px;letter-spacing:3px;color:{{ T::color('gold') }};" aria-label="{{ $rating }}/5">{{ str_repeat('★', $rating) }}<span class="fm-star-off" style="color:{{ T::color('line_strong') }};">{{ str_repeat('★', max(0, 5 - $rating)) }}</span></div>
            @endif
            @if (!empty($review['body']))
                <p class="fm-ink" dir="auto" style="margin:0;font-size:15px;line-height:1.65;color:{{ T::color('ink') }};">“{{ $review['body'] }}”</p>
            @endif
            @if (!empty($review['reviewer_name']))
                <p class="fm-subtle" style="margin:10px 0 0;font-size:12px;color:{{ T::color('subtle') }};">— {{ $review['reviewer_name'] }}@if (!empty($review['talent'])) · {{ __('on') }} {{ $review['talent'] }}@endif</p>
            @endif
        </x-email.panel>
    @endif

    <p class="fm-muted" style="margin:24px 0 0;color:{{ T::color('muted') }};">
        {{ __('If you believe this was a mistake, reply to this email and our team will take another look.') }}
    </p>
</x-email.layout>
