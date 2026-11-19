{{--
    One picture of a project's story (ADR-AN) — the prototype's `fig()`. A video
    item plays in place (and stays out of the full-screen viewer); a picture
    opens the viewer. Picture numbers show only in the viewer (12 Nov 2026).
    Params: $fig (ProjectStory figure), $cls (extra classes), $title (project title).
--}}
@php $cls = trim(($cls ?? '').' '.($fig['cls'] ?? '')); @endphp
@if ($fig['video'])
    <figure class="{{ $cls }} is-video" data-nolb @if ($fig['ar']) style="--ar:{{ $fig['ar'] }}" @endif>
        <video class="ph-img" controls playsinline preload="metadata" aria-label="{{ $fig['cap'] ?: __(':title, picture :i', ['title' => $title, 'i' => $fig['no']]) }}"><source src="{{ $fig['url'] }}" type="{{ $fig['mime'] }}"></video>
        @if ($fig['cap'] !== '')<figcaption>{{ $fig['cap'] }}</figcaption>@endif
    </figure>
@else
    <figure class="{{ $cls }}" @if ($fig['ar']) style="--ar:{{ $fig['ar'] }}" @endif>
        <div class="ph-img" role="img" style="background-image:url('{{ $fig['url'] }}')" aria-label="{{ $fig['cap'] ?: __(':title, picture :i', ['title' => $title, 'i' => $fig['no']]) }}"></div>
        @if ($fig['cap'] !== '')<figcaption>{{ $fig['cap'] }}</figcaption>@endif
    </figure>
@endif
