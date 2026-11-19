{{--
    Recognitions (ADR-Z) — peer vouches, talent → talent. Quote-led cards, the
    relationship first, no stars (they must not read like reviews). Four show;
    the rest open with "Show all N recognitions".
--}}
@php $recognitions = $talent->recognitions->filter->isShown()->values(); @endphp
<x-profile.block :block="$block" :empty="$recognitions->isEmpty()" :is-owner="$isOwner ?? false">
    <div x-data="{ all: false }">
    <ul class="recs" id="recList">
        @foreach ($recognitions as $recognition)
            @php
                $author = $recognition->author;
                $who = $author?->display_name ?: __('A Fama talent');
                $ini = collect(preg_split('/\s+/', trim($who)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
            @endphp
            <li @class(['rec', 'more' => $loop->index >= 4]) @if ($loop->index >= 4) x-show="all" x-cloak @endif>
                <q dir="auto">{{ $recognition->body }}</q>
                <span class="by">
                    @if ($author?->avatar_url)
                        <span class="av" style="background:url('{{ $author->avatar_url }}') center/cover;color:transparent" aria-hidden="true">{{ $ini }}</span>
                    @else
                        <span class="av" aria-hidden="true">{{ $ini }}</span>
                    @endif
                    <span>@if ($author)<a class="crl" href="{{ url($author->slug) }}"><b>{{ $who }}</b></a>@else<b>{{ $who }}</b>@endif<span class="meta">{{ $recognition->relationship }}</span></span>
                </span>
            </li>
        @endforeach
    </ul>
    @if ($recognitions->count() > 4)
        <button type="button" class="btn btn-2 recmore" @click="all = !all" aria-controls="recList" :aria-expanded="all.toString()"
                x-text="all ? @js(__('Show fewer')) : @js(__('Show all :n recognitions', ['n' => $recognitions->count()]))">{{ __('Show all :n recognitions', ['n' => $recognitions->count()]) }}</button>
    @endif
    </div>
</x-profile.block>
