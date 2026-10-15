{{--
    One frame of the project story (fama-ui project.html `.st-img`): an image as
    the prototype's background-image `.m`, or a video in the same box. With no
    frame at all, the empty `.m` keeps the prototype's warm placeholder.
    Params: $frame (['url','video','mime'] or null), $index, $count, $title.
--}}
<figure class="st-img">
    @if ($frame && $frame['video'])
        <video class="m" controls preload="metadata" aria-label="{{ $title }}, {{ __('picture :i of :n', ['i' => $index + 1, 'n' => $count]) }}">
            <source src="{{ $frame['url'] }}" type="{{ $frame['mime'] }}">
        </video>
    @else
        <div class="m" role="img" aria-label="{{ $title }}, {{ __('picture :i of :n', ['i' => $index + 1, 'n' => $count]) }}"
             @if ($frame) style="background-image:url('{{ $frame['url'] }}')" @endif></div>
    @endif
</figure>
