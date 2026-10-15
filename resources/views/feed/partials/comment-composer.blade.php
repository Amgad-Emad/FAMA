{{-- The post-page comment box (post.html composer): one rounded box with the
     send button inside; a reply says who it answers, with a cross to drop it.
     $parent = JS expression of the top-level comment being answered, or null
     for a new comment. @ credits someone (famaMention). --}}
@php
    $parent = $parent ?? null;
    $me = $currentActor ?? null;
    $meName = $me?->feedDisplayName() ?? '';
    $meInitials = collect(preg_split('/\s+/', trim($meName)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $draft = $parent ? 'replyDraft' : 'rootDraft';
    $error = $parent ? 'replyError' : 'rootError';
    $fieldId = $parent ? "'ta' + ".$parent.'.id' : "'taroot'";
    $phExpr = $parent ? 'replyPlaceholder()' : \Illuminate\Support\Js::from(__('Add a comment…'))->toHtml();
@endphp
<li class="add">
    <span class="av" aria-hidden="true">@if ($me?->feedAvatarUrl())<img src="{{ $me->feedAvatarUrl() }}" alt="">@else{{ $meInitials }}@endif</span>
    <form novalidate @submit.prevent="send({{ $parent ?? 'null' }})">
        @if ($parent)
            <p class="rto">{{ __('Replying to') }} <b dir="auto" x-text="replyName"></b><button type="button" class="iconbtn plain" @click="cancelReply()" aria-label="{{ __('Cancel') }}"><svg class="icon sm" aria-hidden="true"><use href="#i-x"/></svg></button></p>
        @endif
        <label class="vh" :for="{{ $fieldId }}" x-text="{{ $phExpr }}"></label>
        <div class="cbox">
            <span class="mwrap" x-data="famaMention({ grow: 160 })" x-modelable="value" x-model="{{ $draft }}">
                <textarea class="ta" rows="1" :id="{{ $fieldId }}" x-ref="field" maxlength="2400" x-model="value"
                          :placeholder="{{ $phExpr }}"
                          @input="onInput($event)" @keydown="onKeydown($event)" @blur="onBlur()"
                          role="combobox" aria-autocomplete="list" :aria-expanded="open.toString()" :aria-controls="listId"></textarea>
                <x-feed.mention-field />
            </span>
            <button class="csend" aria-label="{{ __('Post') }}" :disabled="!{{ $draft }}.trim() || sending"><svg class="icon" aria-hidden="true"><use href="#i-send-up"/></svg></button>
        </div>
        <span class="meta" data-count x-text="{{ $draft }}.length ? counter({{ $draft }}.length) : ''"></span>
        <p class="err" role="alert" x-text="{{ $error }}"></p>
    </form>
</li>
