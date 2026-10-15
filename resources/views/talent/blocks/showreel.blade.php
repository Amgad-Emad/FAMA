{{-- Showreel — no prototype design of its own, so it uses the project tile (.projs/.proj). --}}
@php $reels = $talent->showreels; @endphp
<x-profile.block :block="$block" :empty="$reels->isEmpty()" :is-owner="$isOwner ?? false">
    <div class="projs">
        @foreach ($reels as $reel)
            <a class="proj" href="{{ $reel->video_url }}" target="_blank" rel="noopener">
                @if ($reel->thumbnail_url)
                    <div class="ph cover" style="background-image:url('{{ $reel->thumbnail_url }}')" aria-hidden="true"></div>
                @else
                    <div class="ph" aria-hidden="true"><svg class="icon"><use href="#i-play"/></svg></div>
                @endif
                <b>{{ $reel->title ?: __('Showreel') }}</b><span class="meta">{{ ucfirst(str_replace('_', ' ', (string) $reel->platform)) }}</span>
            </a>
        @endforeach
    </div>
</x-profile.block>
