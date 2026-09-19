{{--
    Credits (ADR-AA) — the CONFIRMATION side of collaborator credits.

    Someone credited you on their project; until you answer, that credit appears
    nowhere. This page is where you answer, and where you can withdraw a credit
    you accepted earlier — being publicly associated with a piece of work has to
    stay your call.
--}}
<x-talent-layout :title="__('Credits')">
    <div x-data="talentCredits({ strings: @js([
            'declineTitle' => __('Decline this credit?'),
            'declineMessage' => __('It will not appear on your profile or on their project.'),
            'declineConfirm' => __('Decline'),
            'withdrawTitle' => __('Remove this credit from your profile?'),
            'withdrawMessage' => __('The project stays theirs; it just stops appearing on your profile.'),
            'withdrawConfirm' => __('Remove'),
        ]) })" class="space-y-8">

        <div class="max-w-2xl">
            <x-ui.eyebrow>{{ __('Work you were credited on') }}</x-ui.eyebrow>
            <h2 class="mt-1.5 font-display text-3xl leading-tight text-ink">{{ __('Credits') }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-muted">
                {{ __('When someone credits you on their project, it needs your confirmation before it appears anywhere — on their project or on your profile. Accepted credits show in the tab of the skill you were credited in.') }}
            </p>
        </div>

        <template x-if="loading">
            <div><x-ui.card><div class="h-20 animate-pulse rounded bg-elevated"></div></x-ui.card></div>
        </template>

        {{-- Waiting on you --}}
        <section x-show="!loading && pending.length > 0" x-cloak>
            <h3 class="mb-3 font-mono text-[11px] uppercase tracking-[0.14em] text-subtle">
                {{ __('Waiting on you') }} <span class="text-accent" x-text="`(${pending.length})`"></span>
            </h3>
            <ul class="space-y-3">
                <template x-for="credit in pending" :key="credit.id">
                    <li>
                        <x-ui.card class="flex flex-wrap items-center gap-4">
                            <template x-if="credit.project?.cover_image_url">
                                <img :src="credit.project.cover_image_url" alt="" class="h-16 w-24 shrink-0 rounded object-cover">
                            </template>
                            <div class="min-w-0 flex-1">
                                <div class="text-ink" x-text="credit.project?.title"></div>
                                <div class="mt-0.5 font-mono text-[11px] uppercase tracking-wider text-subtle">
                                    <span x-text="credit.role"></span>
                                    <span aria-hidden="true"> · </span>
                                    <span x-text="credit.skill?.name"></span>
                                </div>
                                <div class="mt-1 text-xs text-muted">
                                    {{ __('Credited by') }} <span class="text-ink" x-text="credit.project?.owner?.display_name"></span>
                                </div>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <x-ui.button size="sm" variant="accent" x-on:click="respond(credit, 'accept')">{{ __('Confirm') }}</x-ui.button>
                                <x-ui.button size="sm" variant="outline" x-on:click="confirmDecline(credit)">{{ __('Decline') }}</x-ui.button>
                            </div>
                        </x-ui.card>
                    </li>
                </template>
            </ul>
        </section>

        {{-- On your profile --}}
        <section x-show="!loading && accepted.length > 0" x-cloak>
            <h3 class="mb-3 font-mono text-[11px] uppercase tracking-[0.14em] text-subtle">{{ __('On your profile') }}</h3>
            <ul class="space-y-2">
                <template x-for="credit in accepted" :key="credit.id">
                    <li>
                        <x-ui.card class="flex flex-wrap items-center gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="text-ink" x-text="credit.project?.title"></div>
                                <div class="mt-0.5 font-mono text-[11px] uppercase tracking-wider text-subtle">
                                    <span x-text="credit.role"></span>
                                    <span aria-hidden="true"> · </span>
                                    <span x-text="credit.skill?.name"></span>
                                </div>
                            </div>
                            <button type="button" x-on:click="confirmWithdraw(credit)"
                                    class="shrink-0 rounded-md px-2 py-1 text-xs text-subtle transition hover:text-danger focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                                {{ __('Remove') }}
                            </button>
                        </x-ui.card>
                    </li>
                </template>
            </ul>
        </section>

        <p x-show="!loading && pending.length === 0 && accepted.length === 0" x-cloak
           class="rounded-lg border border-dashed border-line p-8 text-center text-sm text-subtle">
            {{ __('Nobody has credited you on a project yet.') }}
        </p>
    </div>
</x-talent-layout>
