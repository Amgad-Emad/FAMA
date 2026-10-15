{{--
    Credits & recognitions (fama-ui `credits.html`) — the collaborator's side of
    collaborator credits (ADR-AA), plus the peer recognitions received (ADR-Z).

    Nothing appears on anyone's profile without their confirmation: a credit
    naming you waits here until you Confirm (it then shows on your profile, in
    the tab of the skill you were credited for) or Decline. Leaving a confirmed
    project is the same transition as declining — being publicly associated with
    work stays your call at any point.

    Recognitions are read-only (an admin moderates them); the switch shows or
    hides the whole Recognitions section of your profile.

    Behaviour: `famaCredits` + `famaRecognitionsSwitch` (resources/js/ui/projects.js).
--}}
@php
    $strings = [
        'from' => __('from :who'),
        'confirmedOn' => __('Confirmed :d'),
        'whereTab' => __('Shows in your :s tab.'),
        'noskill' => __(':s isn’t one of your skills yet. Add it, and the project shows in its tab.'),
        'addSkill' => __('Add :s'),
        'see' => __('See it on your profile'),
        'confirmA' => __('Confirm credit on :p'),
        'declineA' => __('Decline credit on :p'),
        'withdrawA' => __('Leave :p'),
        'tConfirmed' => __('You co-own :p now'),
        'tDeclined' => __('Declined :p'),
        'tWithdrawn' => __('You left :p'),
        'declineTitle' => __('Decline this credit?'),
        'declineBody' => __('It won’t appear on your profile or on their project. They can credit you again.'),
        'declineOk' => __('Decline'),
        'wTitle' => __('Leave :p?'),
        'wBody' => __('You stop being an owner, and it comes off your profile and your name comes off the project. The project stays for its other owners. They can credit you again.'),
        'wOk' => __('Leave project'),
        'failed' => __('Something went wrong. Try again.'),
    ];
    $shownRecognitions = $recognitions->filter(fn ($r) => (string) $r->status === 'approved')->count();
@endphp
<x-fama.layout :title="__('Credits & recognitions')">
    <x-slot:styles>
        <link rel="stylesheet" href="{{ asset('fama-ui/css/pages/credits.css') }}">
        <style>
            /* App additions: the real cover inside the prototype's grey frame. */
            .cr .ph{background-size:cover;background-position:center}
        </style>
    </x-slot:styles>

    <x-slot:hero>
        <x-fama.hero :label="__('What other people say about your work')" :title="__('Credits & recognitions')"
                     :lead="__('Projects other people credited you on, and what peers say about you. Confirm a credit and the project is yours too.')"
                     photo="credits" />
    </x-slot:hero>

    <main class="wrap main"
          x-data="famaCredits({ held: @js($heldSkillIds), slug: @js($talent->slug), skillsUrl: @js(route('talent.profile.edit')), strings: @js($strings) })">

        <section class="card">
            <h2 class="h2 sm"><span>{{ __('Waiting for you') }}</span><span class="count" x-show="pending.length" x-cloak x-text="pending.length"></span></h2>
            <p class="meta" style="margin-top:var(--s2);max-width:70ch">{{ __('Confirming makes you a co-owner: it shows on your profile, you can edit it, and you can leave it any time. Nobody can delete a project that has other owners.') }}</p>
            <div id="waiting" style="margin-top:var(--s5)">
                <template x-for="c in pending" :key="c.id">
                    <article class="cr">
                        <a class="pt" :href="projectUrl(c)" aria-hidden="true" tabindex="-1"><div class="ph" :style="c.project?.cover_image_url ? { backgroundImage: 'url(' + c.project.cover_image_url + ')' } : {}"></div></a>
                        <div><a class="pt" :href="projectUrl(c)"><b x-text="c.project?.title"></b></a>
                            <p class="meta by" x-text="fmt(strings.from, { who: c.project?.owner?.display_name || '' })"></p>
                            <div class="role"><span class="tag" x-text="c.skill?.name"></span><span class="tag" x-text="c.role"></span></div>
                            <p class="note"><span x-text="noteText(c)"></span></p></div>
                        <div class="acts"><button class="btn" type="button" @click="accept(c)" :aria-label="fmt(strings.confirmA, { p: c.project?.title })"><svg class="icon" aria-hidden="true"><use href="#a-check"/></svg>{{ __('Confirm') }}</button><button class="btn btn-2" type="button" @click="decline(c)" :aria-label="fmt(strings.declineA, { p: c.project?.title })"><svg class="icon" aria-hidden="true"><use href="#c-x"/></svg>{{ __('Decline') }}</button></div>
                    </article>
                </template>
                <div class="empty" x-show="loaded && !pending.length" x-cloak><h3 class="h2">{{ __('Nothing waiting') }}</h3><p>{{ __('When someone credits you on a project, it shows up here for you to confirm.') }}</p></div>
            </div>
        </section>

        <section class="card" style="margin-top:var(--s5)">
            <h2 class="h2 sm">{{ __('Projects you co-own') }}</h2>
            <div id="onprofile" style="margin-top:var(--s5)">
                <template x-for="c in accepted" :key="c.id">
                    <article class="cr">
                        <a class="pt" :href="projectUrl(c)" aria-hidden="true" tabindex="-1"><div class="ph" :style="c.project?.cover_image_url ? { backgroundImage: 'url(' + c.project.cover_image_url + ')' } : {}"></div></a>
                        <div><a class="pt" :href="projectUrl(c)"><b x-text="c.project?.title"></b></a>
                            <p class="meta by" x-text="fmt(strings.from, { who: c.project?.owner?.display_name || '' }) + (c.responded_at ? ' · ' + fmt(strings.confirmedOn, { d: date(c.responded_at) }) : '')"></p>
                            <div class="role"><span class="tag" x-text="c.skill?.name"></span><span class="tag" x-text="c.role"></span></div>
                            <p class="note"><span x-text="noteText(c)"></span><template x-if="!holds(c)"><span> <a class="link" :href="skillsUrl" x-text="fmt(strings.addSkill, { s: c.skill?.name })"></a></span></template><template x-if="holds(c)"><span> <a class="link" :href="profileUrl(c)" x-text="strings.see"></a></span></template></p></div>
                        <div class="acts"><button class="btn btn-2" type="button" @click="withdraw(c)" :aria-label="fmt(strings.withdrawA, { p: c.project?.title })"><svg class="icon" aria-hidden="true"><use href="#c-trash"/></svg>{{ __('Leave project') }}</button></div>
                    </article>
                </template>
                <div class="empty" x-show="loaded && !accepted.length" x-cloak><h3 class="h2">{{ __('No shared projects yet') }}</h3><p>{{ __('Projects you confirm show on your profile, in the tab of the skill you were credited for.') }}</p></div>
            </div>
        </section>

        {{-- Recognitions (moved here from Profile content) --}}
        <section class="card" style="margin-top:var(--s5)" id="recognitions"
                 x-data="famaRecognitionsSwitch({ url: @js($recognitionsBlock ? route('talent.profile.blocks.visibility', $recognitionsBlock) : null), on: @js((bool) ($recognitionsBlock?->is_visible ?? true)), strings: @js(['off' => __('Recognitions are hidden from your profile. They keep their content.'), 'on' => __('Recognitions are back on your profile.'), 'failed' => __('Something went wrong. Try again.')]) })">
            <div class="rhead">
                <h2 class="h2 sm"><span>{{ __('Recognitions') }}</span>@if ($shownRecognitions)<span class="count">{{ $shownRecognitions }}</span>@endif<span class="pill p-off" x-show="!on" x-cloak>{{ __('Hidden from your profile') }}</span></h2>
                @if ($recognitionsBlock)
                    <label class="showsw"><span>{{ __('Show on profile') }}</span><button type="button" class="switch" role="switch" id="rShow" :aria-checked="on.toString()" aria-checked="{{ $recognitionsBlock->is_visible ? 'true' : 'false' }}" @click="toggle()" :disabled="busy"><i></i></button></label>
                @endif
            </div>
            <p class="meta" style="margin-top:var(--s2)">{{ __('Peers vouching for you. Approved ones show on your profile once our team has checked them.') }}</p>
            <div id="recs" style="margin-top:var(--s5)" :class="{ 'is-off': !on }">
                @forelse ($recognitions as $recognition)
                    <article class="rc{{ (string) $recognition->status === 'approved' ? '' : ' is-hidden' }}">
                        <div>
                            <q dir="auto">{{ $recognition->body }}</q>
                            <p class="meta by">{{ $recognition->author?->display_name ?? __('A Fama talent') }} · {{ $recognition->relationship }} · {{ ($recognition->recognized_at ?? $recognition->created_at)?->format('j M Y') }}</p>
                            @if ((string) $recognition->status !== 'approved')
                                <p class="note"><span class="pill p-off">{{ __('Pending') }}</span> {{ __('Waiting for our team to approve it.') }}</p>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="empty"><h3 class="h2">{{ __('No recognitions yet') }}</h3><p>{{ __('When a peer recognises your work, it shows up here.') }}</p></div>
                @endforelse
            </div>
        </section>
    </main>
</x-fama.layout>
