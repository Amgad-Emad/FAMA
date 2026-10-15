{{--
    Sent by ProfileStepsCompleted the first time a talent finishes the six
    profile steps, while Fama's check is still pending.
--}}
@php
    use App\Mail\EmailTheme as T;

    $steps = [__('Profile photo'), __('Display name'), __('City and country'), __('A short bio'), __('A skill and a speciality'), __('One project')];
@endphp
<x-email.layout
    :title="__('You’ve finished your profile')"
    :eyebrow="__('All 6 steps done')"
    :preheader="__('Nice work. Our team checks every new profile once, then you can go Live.')"
    :footnote="__('You are receiving this because you completed your Fama profile.')">

    <p class="fm-ink" style="margin:0;color:{{ T::color('ink') }};">{{ __('Hi :name,', ['name' => $name]) }}</p>
    <p class="fm-muted" style="margin:12px 0 0;color:{{ T::color('muted') }};">
        {{ __('You’ve completed all six steps on your profile. The last one is ours: our team checks every new profile once before it can go Live.') }}
    </p>

    <x-email.panel :label="__('Done')">
        @foreach ($steps as $step)
            <p class="fm-ink" style="margin:{{ $loop->first ? '0' : '6px 0 0' }};font-size:15px;color:{{ T::color('ink') }};"><span style="color:{{ T::color('accent') }};font-weight:700;">✓</span>&nbsp; {{ $step }}</p>
        @endforeach
        <p class="fm-subtle" style="margin:12px 0 0;font-size:13px;color:{{ T::color('subtle') }};">⏳&nbsp; {{ __('Checked by Fama — in progress') }}</p>
    </x-email.panel>

    <p class="fm-muted" style="margin:0 0 20px;color:{{ T::color('muted') }};">
        {{ __('You can keep editing meanwhile. Once the check is done, Go Live unlocks on your Profile structure page, and you choose when.') }}
    </p>

    <x-email.button :href="$profileUrl">{{ __('Open your profile') }}</x-email.button>
</x-email.layout>
