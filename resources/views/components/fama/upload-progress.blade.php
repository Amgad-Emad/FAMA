{{--
    The upload modal (2026-11-23): one branded, blocking "Uploading…" dialog for
    every media upload on fama-ui pages. Driven by $store.famaUpload through
    window.famaUploading(opts, fn) (resources/js/ui/shell.js) — a page never
    builds its own spinner. Not dismissable while it runs (the file is on its
    way); it closes itself. Styles: .fx-up in app-bridge.css.
--}}
@php
    $upStrings = [
        'media1' => __('Uploading 1 file'),
        'mediaN' => __('Uploading :n files'),
        'cover' => __('Uploading your cover'),
        'avatar' => __('Uploading your photo'),
        'upload' => __('Keep this page open. It only takes a moment.'),
        'process' => __('Finishing up. Getting it ready for your profile.'),
        'done' => __('All done.'),
    ];
@endphp
<div class="fx-up" x-data="{ s: @js($upStrings),
        get st() { return $store.famaUpload },
        get title() { return this.st.kind === 'cover' ? this.s.cover : this.st.kind === 'avatar' ? this.s.avatar : (this.st.count === 1 ? this.s.media1 : this.s.mediaN.replace(':n', this.st.count)) } }"
     x-show="st.open" x-cloak
     x-transition:enter="fx-up-in" x-transition:enter-start="fx-up-from" x-transition:leave="fx-up-out" x-transition:leave-end="fx-up-from"
     role="dialog" aria-modal="true" aria-labelledby="fxUpTitle" aria-describedby="fxUpDetail">
    <div class="fx-up-card" :class="'is-' + st.phase">
        <div class="fx-up-badge" aria-hidden="true">
            <svg class="fx-up-ring" viewBox="0 0 64 64"><circle cx="32" cy="32" r="28" pathLength="100"/><circle class="fill" cx="32" cy="32" r="28" pathLength="100" :style="'stroke-dashoffset:' + (100 - st.pct)"/></svg>
            <svg class="fx-up-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-show="st.phase !== 'done'"><path d="M12 16V5"/><path d="m7 10 5-5 5 5"/><path d="M5 19h14"/></svg>
            <svg class="fx-up-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" x-show="st.phase === 'done'" x-cloak><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
        </div>
        <h2 id="fxUpTitle" class="fx-up-title" x-text="title"></h2>
        <p id="fxUpDetail" class="fx-up-detail" aria-live="polite" x-text="s[st.phase]"></p>
        <div class="fx-up-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="st.pct" :aria-label="title"
             :class="{ 'is-indeterminate': st.phase === 'process' }">
            <i :style="'width:' + st.pct + '%'"></i>
        </div>
        <p class="fx-up-pct"><span x-text="st.pct + '%'"></span></p>
    </div>
</div>
