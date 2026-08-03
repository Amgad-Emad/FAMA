{{-- The shared report dialog (5AF): ONE focused modal per page, opened via
     the window 'open-report' event with {type, id, quote}. Config-driven
     reasons; "Something else" requires a note; success (or a duplicate
     report) swaps the form for an acknowledgement panel in place. --}}
@php
    $reasonLabels = [
        'spam' => __('Spam or misleading'),
        'nudity' => __('Nudity or sexual content'),
        'harassment' => __('Harassment or bullying'),
        'hate' => __('Hate speech'),
        'impersonation' => __('Impersonation'),
        'ip' => __('Intellectual property'),
        'other' => __('Something else'),
    ];
@endphp

<template x-teleport="body">
    <div x-data="reportDialog()" x-show="open" x-cloak
         class="fixed inset-0" style="z-index: var(--z-modal-backdrop);"
         @open-report.window="show($event.detail)"
         @keydown.escape.window="open && close()"
         role="dialog" aria-modal="true" aria-label="{{ __('Report content') }}">
        <div class="absolute inset-0" style="background: color-mix(in srgb, var(--dl-ink, #1A1712) 40%, transparent); backdrop-filter: blur(8px);" @click="close()"></div>

        <div data-surface="daylight" style="z-index: var(--z-modal); background: var(--dl-surface); box-shadow: var(--dl-shadow-lifted);"
             class="absolute inset-x-0 bottom-0 rounded-t-2xl p-6 sm:inset-auto sm:start-1/2 sm:top-1/2 sm:w-full sm:max-w-lg sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-2xl sm:p-8"
             @click.stop>

            {{-- Acknowledgement panel (also shown on a duplicate report) --}}
            <div x-show="confirmed" x-cloak>
                <h2 class="t-display">{{ __('Report received') }}</h2>
                <p class="t-body mt-3 text-muted">{{ __('Thank you. Our team will review this content — you won’t see updates, but every report is read.') }}</p>
                <div class="mt-5 flex justify-end">
                    <button type="button" class="dl-btn is-ready" @click="close()">{{ __('CLOSE') }}</button>
                </div>
            </div>

            {{-- The form --}}
            <div x-show="!confirmed">
                <h2 class="t-display">{{ __('Report this') }}</h2>

                <blockquote x-show="quote" x-cloak class="thread-tombstone t-body mt-3" x-text="quote"></blockquote>

                <div class="mt-4 space-y-1" role="radiogroup" aria-label="{{ __('Reason') }}">
                    @foreach (config('feed.report_reasons') as $reasonKey)
                        <label class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-2 hover:bg-ground-sunk">
                            <input type="radio" name="report-reason" value="{{ $reasonKey }}" x-model="reason"
                                   class="border-seam text-daylight focus:ring-daylight/40">
                            <span class="t-body">{{ $reasonLabels[$reasonKey] }}</span>
                        </label>
                    @endforeach
                </div>

                <div x-show="reason === 'other' || note !== ''" x-cloak class="mt-3">
                    <textarea x-model="note" rows="2" maxlength="500" class="well well--grow t-body w-full p-3"
                              :placeholder="reason === 'other' ? '{{ __('Tell us what’s wrong (required)') }}' : '{{ __('Anything we should know? (optional)') }}'"></textarea>
                </div>

                <p class="t-body mt-2 text-danger" x-show="error" x-cloak x-text="error"></p>

                <div class="mt-5 flex items-center justify-end gap-3">
                    <button type="button" class="dl-btn" @click="close()">{{ __('CANCEL') }}</button>
                    <button type="button" class="dl-btn" :class="{ 'is-ready': canSubmit(), 'is-busy': busy }"
                            :disabled="!canSubmit() || busy" @click="submit()">
                        <span x-show="busy" class="dl-spinner" aria-hidden="true"></span>
                        {{ __('SEND REPORT') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
