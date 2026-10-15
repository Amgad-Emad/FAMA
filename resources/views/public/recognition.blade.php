{{--
    Recognise a peer (fama-ui `recognise.html`) — one talent vouching for another
    (ADR-Z). Deliberately NOT a review form: no stars, because a peer is not
    scoring a booking. The two fields that matter are HOW YOU KNOW THEM and WHAT
    YOU'D SAY; a live preview shows it the way it will read on the profile.

    Guests never reach this view (the controller sends them to the talent login
    with this form as the intended URL) and neither does the talent themself
    (redirected to their profile). Submissions land pending for moderation.
--}}
@php
    $author = auth('talent')->user();
    $initials = collect(preg_split('/\s+/', trim((string) $talent->display_name)))
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
<x-fama.layout :title="__('Recognise a peer')">
    <x-slot:styles>
        <link rel="stylesheet" href="{{ asset('fama-ui/css/pages/recognise.css') }}">
        <style>.target .av img{width:100%;height:100%;border-radius:inherit;object-fit:cover;display:block}</style>
    </x-slot:styles>

    <main class="wrap main"><div class="col" id="page"
         x-data="famaRecognise({
            url: @js(route('talent.recognition.store', ['slug' => $talent->slug])),
            relationship: @js($existing?->relationship ?? ''),
            body: @js($existing?->body ?? ''),
            author: @js($author?->display_name ?? ''),
            strings: @js([
                'howCount' => __('8–160 characters · :n so far'),
                'soFar' => __('40–1,500 characters · :n so far'),
                'eHow' => __('Tell us how you know them, in 8 to 160 characters.'),
                'eSay' => __('Write at least 40 characters.'),
            ]),
         })">
        <a class="back" href="{{ url($talent->slug) }}"><svg class="icon bk"><use href="#i-arrow"/></svg><span>{{ __('Back to :name’s profile', ['name' => $talent->display_name]) }}</span></a>
        <div class="target">
            <span class="av" aria-hidden="true" style="width:72px;height:72px;font-size:24px">@if ($talent->avatar_url)<img src="{{ $talent->avatar_url }}" alt="">@else{{ $initials }}@endif</span>
            <div><h1 class="h1">{{ __('Recognise :name', ['name' => $talent->display_name]) }}</h1><p class="meta">{{ __('Vouch for a peer. No stars, just your words.') }}</p></div>
        </div>

        <div id="body">
            <section class="card" style="margin-top:var(--s6)" id="formCard" x-show="!done">
                @if ($existing)
                    <p class="note" style="margin-top:0">{{ __('You’ve already recognised :name. Sending this replaces it, and it goes back for approval.', ['name' => $talent->display_name]) }}</p>
                @endif
                <form id="form" novalidate style="margin-top:{{ $existing ? 'var(--s5)' : '0' }}" @submit.prevent="submit()">
                    <label class="f" :class="{ bad: errors.relationship }"><span>{{ __('How do you know them?') }}</span>
                        <input class="in" id="how" maxlength="160" x-model="form.relationship" x-ref="how" placeholder="{{ __('e.g. Shot three campaigns together') }}">
                        <p class="help" :class="{ err: errors.relationship }" id="howHelp" x-text="errors.relationship || fmt(strings.howCount, { n: form.relationship.length })"></p></label>
                    <label class="f" :class="{ bad: errors.body }"><span>{{ __('What would you tell someone about working with them?') }}</span>
                        <textarea class="ta" id="say" maxlength="1500" x-model="form.body" x-ref="say" placeholder="{{ __('Something only someone who worked with them would know…') }}" style="min-height:160px"></textarea>
                        <p class="help" :class="{ err: errors.body }" id="sayHelp" x-text="errors.body || fmt(strings.soFar, { n: form.body.length })"></p></label>
                    <div class="prev" aria-live="polite"><span class="label">{{ __('How it looks on the profile') }}</span>
                        <q id="pq" dir="auto" x-text="form.body.trim() ? '“' + form.body.trim() + '”' : '“…”'"></q>
                        <p class="who" id="pw" x-text="'— ' + author + (form.relationship.trim() ? ' · ' + form.relationship.trim() : '')"></p></div>
                    <p class="help err" x-show="errors._" x-cloak x-text="errors._" role="alert"></p>
                    <div class="acts"><a class="btn btn-2" href="{{ url($talent->slug) }}">{{ __('Cancel') }}</a><button class="btn" :disabled="saving"><svg class="icon" aria-hidden="true"><use href="#a-send"/></svg>{{ $existing ? __('Send updated recognition') : __('Send recognition') }}</button></div>
                </form>
            </section>
            <section class="card done" id="done" x-show="done" x-cloak role="status" style="margin-top:var(--s6)">
                <span class="ic"><svg class="icon" style="width:32px;height:32px"><use href="#i-check"/></svg></span>
                <h2 class="h2">{{ __('Recognition sent') }}</h2>
                <p>{{ __('It shows on :name’s profile once our team approves it.', ['name' => $talent->display_name]) }}</p>
                <a class="btn" href="{{ url($talent->slug) }}" style="margin-top:var(--s5)">{{ __('Back to :name’s profile', ['name' => $talent->display_name]) }}</a>
            </section>
        </div>
    </div></main>
</x-fama.layout>
