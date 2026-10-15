{{--
    Reviews (brand → talent). Hidden on the public profile while
    features.reviews is off (the fama-ui design replaces them with
    Recognitions) — profile.blade.php filters the block out; kept for the flag.
--}}
@php
    $reviews = $talent->reviews;
    $avg = $reviews->isNotEmpty() ? round((float) $reviews->avg('rating'), 1) : null;
@endphp
<x-profile.block :block="$block" :empty="$reviews->isEmpty()" :is-owner="$isOwner ?? false">
    @if ($avg !== null)
        <x-slot:sum><p class="revsum"><b>{{ number_format($avg, 1) }}</b><span class="stars" aria-hidden="true">★★★★★</span><span class="meta">{{ __('from :n reviews', ['n' => $reviews->count()]) }}</span></p></x-slot:sum>
    @endif
    <div class="revs">
        @foreach ($reviews as $review)
            <article class="card tight rev">
                <span class="stars" aria-label="{{ __(':n out of 5', ['n' => $review->rating]) }}">{{ str_repeat('★', (int) $review->rating) }}<span>{{ str_repeat('★', 5 - (int) $review->rating) }}</span></span>
                <q>{{ $review->body }}</q>
                <p class="who">{{ collect([$review->reviewer_name, $review->reviewer_role, $review->reviewer_company])->filter()->implode(' · ') }}</p>
            </article>
        @endforeach
    </div>
</x-profile.block>
