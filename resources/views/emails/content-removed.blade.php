{{--
    Sent by ContentRemovedNotice when staff remove a post or comment. The Posts
    module's remove dialog previews this exact view.
--}}
@php
    use App\Mail\EmailTheme as T;

    $paragraphs = preg_split('/\R{2,}/', trim($body)) ?: [];
@endphp
<x-email.layout
    :title="$kind === 'comment' ? __('Your comment was removed') : __('Your post was removed')"
    :eyebrow="__('Community guidelines')"
    :preheader="__('Our team removed something you shared on Fama — here is why.')"
    :footnote="__('You are receiving this because content you shared on Fama was moderated.')">

    @foreach ($paragraphs as $p)
        <p class="fm-muted" dir="auto" style="margin:{{ $loop->first ? '0' : '14px 0 0' }};color:{{ T::color('muted') }};">{!! nl2br(e($p)) !!}</p>
    @endforeach

    @if (filled($reason))
        <x-email.callout :label="__('Reason')" preserve>{{ $reason }}</x-email.callout>
    @endif

    @if (filled($excerpt))
        <x-email.panel :label="$kind === 'comment' ? __('The removed comment') : __('The removed post')">
            <p class="fm-ink" dir="auto" style="margin:0;font-size:15px;line-height:1.65;color:{{ T::color('ink') }};">“{{ $excerpt }}”</p>
        </x-email.panel>
    @endif

    <p class="fm-muted" style="margin:24px 0 0;color:{{ T::color('muted') }};">
        {{ __('If you believe this was a mistake, reply to this email and our team will take another look.') }}
    </p>
</x-email.layout>
