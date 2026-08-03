{{-- The skill tags on a post (revised). The author and collaborators now live
     in the post HEADER (Facebook-style "with X and N others"), so this block is
     just the professional Skill tags — clean pills that deep-link into Discover.
     No skills → no block. --}}
@props(['post'])

@if ($post->skills->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'feed-tags flex flex-wrap gap-2']) }}>
        @foreach ($post->skills as $skill)
            <a href="{{ route('discover', ['filter' => ['type' => $skill->slug]]) }}" class="feed-tag">{{ $skill->name }}</a>
        @endforeach
    </div>
@endif
