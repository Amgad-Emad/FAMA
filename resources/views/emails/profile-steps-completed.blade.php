{{--
    Sent by ProfileStepsCompleted the first time a talent's Go Live steps are
    done and the profile goes Live (ADR-AC: no review step).
--}}
@php
    use App\Mail\EmailTheme as T;

    $steps = [__('Display name'), __('A skill and a speciality'), __('Your city')];
@endphp
<x-email.layout
    :title="__('You’re Live')"
    :eyebrow="__('Go Live: 3 of 3 done')"
    :preheader="__('Your link works now. Next, add something to show so brands find you in the Creatives Index.')"
    :footnote="__('You are receiving this because your Fama profile went Live.')">

    <p class="fm-ink" style="margin:0;color:{{ T::color('ink') }};">{{ __('Hi :name,', ['name' => $name]) }}</p>
    <p class="fm-muted" style="margin:12px 0 0;color:{{ T::color('muted') }};">
        {{ __('Your profile is Live: anyone with your link can open it, and you can share it.') }}
    </p>

    <x-email.panel :label="__('Done')">
        @foreach ($steps as $step)
            <p class="fm-ink" style="margin:{{ $loop->first ? '0' : '6px 0 0' }};font-size:15px;color:{{ T::color('ink') }};"><span style="color:{{ T::color('accent') }};font-weight:700;">✓</span>&nbsp; {{ $step }}</p>
        @endforeach
    </x-email.panel>

    <p class="fm-muted" style="margin:0 0 20px;color:{{ T::color('muted') }};">
        {{ __('Next, stand out: add something to show — a project, three photos or a showreel — and brands find you in the Creatives Index.') }}
    </p>

    <x-email.button :href="$profileUrl">{{ __('Open your profile') }}</x-email.button>
</x-email.layout>
