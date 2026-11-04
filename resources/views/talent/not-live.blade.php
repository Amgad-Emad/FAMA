{{--
    The not-live page (fama-ui preview.html, ?state=private as a visitor): a
    profile that exists but is not Live yet. Served with a 404 status by
    TalentProfileController@show and deliberately says nothing about the talent
    — no name, no picture — so a private profile stays private.
--}}
<x-fama.layout :title="__('Profile')">
    <x-slot:styles>
        <link rel="stylesheet" href="{{ \App\Support\FamaAsset::url('fama-ui/css/pages/preview.css') }}">
    </x-slot:styles>

    <main class="wrap" id="page">
        <section class="notlive">
            <h1 class="h1">{{ __('This profile is still being set up.') }}</h1>
            <p>{{ __('Check back soon.') }}</p>
            <a class="btn" style="margin-top:var(--s5)" href="{{ route('feed') }}">{{ __('Browse the feed') }}</a>
        </section>
    </main>
</x-fama.layout>
