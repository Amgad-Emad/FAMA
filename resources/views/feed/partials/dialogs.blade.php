{{-- The feed's shared dialogs (fama-ui), one each per page:
     1. the guest sign-in prompt (posts.css dialog.signdlg) — the action was
        already captured server-side by the 401 and replays after login;
     2. the report sheet (posts.js FAMA.report) on config('feed.report_reasons');
     3. the media viewer (no prototype design — a plain dark dialog). --}}
@php
    $reasonLabels = [
        'spam' => __('Spam'),
        'nudity' => __('Nudity'),
        'harassment' => __('Harassment'),
        'hate' => __('Hate'),
        'impersonation' => __('Impersonation'),
        'ip' => __('Intellectual property'),
        'other' => __('Something else'),
    ];
@endphp

<div x-data="famaSignInDialog()">
    <dialog class="signdlg" aria-labelledby="signT" x-ref="d" @close="closed()" @click="if ($event.target === $refs.d) $refs.d.close()">
        <button type="button" class="iconbtn plain sx" @click="$refs.d.close()" aria-label="{{ __('Close') }}"><svg class="icon" aria-hidden="true"><use href="#i-x"/></svg></button>
        <svg class="sstar" viewBox="10 80 760 690" aria-hidden="true"><polygon points="314.63 635.59 16.74 747.68 217.29 533.15 86.97 370.29 403.26 356.02 701.63 87.21 636.51 355.76 760 356.91 574.68 534.04 422.33 756.79 314.63 635.59"/></svg>
        <h2 id="signT" x-text="title()"></h2>
        <p x-text="body()"></p>
        <div class="row">
            <a class="btn" href="{{ route('login') }}" x-ref="first">{{ __('Log in') }}</a>
            <a class="btn btn-2" href="{{ route('register') }}">{{ __('Join as talent') }}</a>
        </div>
    </dialog>
</div>

<div x-data="famaReportDialog()" @open-report.window="show($event.detail)">
    <dialog class="dlg" x-ref="d" @close="closed()">
        <form method="dialog" novalidate @submit.prevent="submit()" x-show="!sent">
            <div class="dh"><h2 x-text="type === 'comment' ? @js(__('Report this comment')) : @js(__('Report this post'))"></h2><button type="button" class="iconbtn plain" @click="$refs.d.close()" aria-label="{{ __('Cancel') }}"><svg class="icon" aria-hidden="true"><use href="#i-x"/></svg></button></div>
            <fieldset style="border:0;padding:0;margin:var(--s5) 0 0">
                <legend class="lbl" style="padding:0">{{ __('Why are you reporting it?') }}</legend>
                <div class="chips" role="radiogroup">
                    @foreach (config('feed.report_reasons') as $reasonKey)
                        <button type="button" class="chip" role="radio" :aria-checked="(reason === '{{ $reasonKey }}').toString()" :aria-pressed="(reason === '{{ $reasonKey }}').toString()" aria-pressed="false" @click="reason = '{{ $reasonKey }}'; error = ''">{{ $reasonLabels[$reasonKey] ?? \Illuminate\Support\Str::headline($reasonKey) }}</button>
                    @endforeach
                </div>
            </fieldset>
            <label class="f" style="margin-top:var(--s5)"><span>{{ __('Tell us more') }}<small x-text="reason === 'other' ? @js(__('Required for “Something else”')) : ''"></small></span><textarea class="ta" maxlength="500" style="min-height:96px" x-model="note" x-ref="note"></textarea><p class="help err" x-text="error"></p></label>
            <div class="acts"><button type="button" class="btn btn-2" @click="$refs.d.close()">{{ __('Cancel') }}</button><button class="btn" :aria-disabled="busy ? 'true' : null">{{ __('Send report') }}</button></div>
        </form>
        <form method="dialog" x-show="sent" x-cloak>
            <div class="done-r" role="status"><h2>{{ __("Thanks. We'll take a look.") }}</h2><div class="acts"><button type="button" class="btn" x-ref="ok" @click="$refs.d.close()">{{ __('OK') }}</button></div></div>
        </form>
    </dialog>
</div>

<div x-data="famaViewerDialog()" @open-viewer.window="show($event.detail)">
    <dialog class="viewer" x-ref="d" @close="closed()" @click="if ($event.target === $refs.d) $refs.d.close()" @keydown.arrow-right="next()" @keydown.arrow-left="prev()" :aria-label="items[index]?.alt">
        <div class="vbar">
            <span class="meta" style="color:var(--line)" x-text="(index + 1) + ' / ' + items.length"></span>
            <span style="display:flex;gap:var(--s2)">
                <button type="button" class="iconbtn" x-show="items.length > 1" @click="prev()" aria-label="{{ __('Previous image') }}"><svg class="icon" style="transform:scaleX(-1)" aria-hidden="true"><use href="#i-arrow"/></svg></button>
                <button type="button" class="iconbtn" x-show="items.length > 1" @click="next()" aria-label="{{ __('Next image') }}"><svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg></button>
                <button type="button" class="iconbtn" @click="$refs.d.close()" aria-label="{{ __('Close') }}"><svg class="icon" aria-hidden="true"><use href="#i-x"/></svg></button>
            </span>
        </div>
        <figure @touchstart.passive="touchX = $event.touches[0].clientX" @touchend="swipe($event.changedTouches[0].clientX)">
            <img :src="items[index]?.full_url" :alt="items[index]?.alt">
            <figcaption x-show="items[index]?.alt" x-text="items[index]?.alt"></figcaption>
        </figure>
    </dialog>
</div>

{{-- Share to your feed (ADR-AM, prototype repostDialog): your words (optional) + a preview of
     what you're sharing. One dialog for every card on the page. --}}
<div x-data="famaRepostDialog()" @open-repost.window="show($event.detail)">
    <dialog class="dlg" x-ref="d" @close="closed()">
        <form method="dialog" novalidate @submit.prevent="submit()">
            <div class="dh"><h2>{{ __('Share to your feed') }}</h2><button type="button" class="iconbtn plain" @click="$refs.d.close()" aria-label="{{ __('Close') }}"><svg class="icon" aria-hidden="true"><use href="#i-x"/></svg></button></div>
            <label class="fx-vh" for="rpText">{{ __('Add your thoughts (optional). Type @ to credit someone.') }}</label>
            <textarea class="ta" id="rpText" x-ref="text" maxlength="2200" style="margin-top:var(--s4);min-height:88px" x-model="body" placeholder="{{ __('Add your thoughts (optional). Type @ to credit someone.') }}"></textarea>
            <div class="rpprev"><div class="rp">
                <span class="rp-h"><b x-text="preview.name"></b> <span class="meta" dir="ltr" x-text="'@' + (preview.handle || '')"></span></span>
                <span class="rp-t" dir="auto" x-show="preview.text" x-text="preview.text"></span>
                <span class="meta rp-m" x-show="preview.media"><svg class="icon" aria-hidden="true"><use href="#i-image"/></svg> <span x-text="preview.media"></span></span>
            </div></div>
            <p class="help err" x-show="error" x-cloak x-text="error"></p>
            <div class="acts"><button type="button" class="btn btn-2" @click="$refs.d.close()">{{ __('Cancel') }}</button><button class="btn" :aria-disabled="busy ? 'true' : null"><svg class="icon" aria-hidden="true"><use href="#i-repost"/></svg>{{ __('Share') }}</button></div>
        </form>
    </dialog>
</div>

