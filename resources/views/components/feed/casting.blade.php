{{-- "Talent to discover" (fama-ui: the tinted .strip band of swipeable
     .pp cards between posts, every Nth slot — feed.casting_every). Talents
     come from DiscoverTalentsService with the viewer's follow-state stamped
     on. Follow is castingCard (ui/feed.js); a guest gets the sign-in prompt. --}}
@props(['talents'])

@if ($talents->isNotEmpty())
    <section class="strip" aria-label="{{ __('Talent to discover') }}">
        <div class="strip-h"><div><h2 class="h3">{{ __('Talent to discover') }}</h2><p class="why">{{ __('New faces in your skills') }}</p></div></div>
        <div class="people">
            @foreach ($talents as $talent)
                @php
                    $name = $talent->display_name ?: $talent->slug;
                    $init = collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                @endphp
                <div class="pp" x-data="castingCard({ id: {{ $talent->id }}, following: {{ ($talent->followed_by_viewer ?? false) ? 'true' : 'false' }} })">
                    <a class="av md" href="{{ route('talent.public', ['slug' => $talent->slug]) }}" aria-hidden="true" tabindex="-1">@if ($talent->avatar_url)<img src="{{ $talent->avatar_url }}" alt="" loading="lazy">@else{{ $init }}@endif</a>
                    <a href="{{ route('talent.public', ['slug' => $talent->slug]) }}"><b>{{ $name }}</b></a>
                    <span class="role">{{ $talent->talentTypes->first()?->name }}</span>
                    <button type="button" class="btn btn-2" @click="toggle()" :aria-pressed="following.toString()" aria-pressed="{{ ($talent->followed_by_viewer ?? false) ? 'true' : 'false' }}">
                        <svg class="icon" aria-hidden="true" x-show="!following"><use href="#a-user-plus"/></svg><span x-text="following ? @js(__('Following')) : @js(__('Follow'))">{{ ($talent->followed_by_viewer ?? false) ? __('Following') : __('Follow') }}</span>
                    </button>
                </div>
            @endforeach
        </div>
    </section>
@endif
