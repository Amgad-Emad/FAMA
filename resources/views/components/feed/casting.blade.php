{{-- The casting strip (Prompt 5M/5P) — the brand-side discovery rail injected
     every 6th feed slot: horizontally scrollable notched talent cards
     (portrait crop, Fraunces name, mono skills), keyboard navigable,
     RTL-mirrored by logical flow. Talents come from DiscoverTalentsService. --}}
@props(['talents'])

@if ($talents->isNotEmpty())
    <section class="w-full max-w-[780px]" aria-label="{{ __('Casting — talents to discover') }}">
        <p class="t-meta mb-3">{{ __('CASTING') }}</p>
        <div class="flex snap-x gap-4 overflow-x-auto pb-3" role="list">
            @foreach ($talents as $talent)
                <div role="listitem" class="w-44 flex-none snap-start frame-shadow"
                     x-data="castingCard({ id: {{ $talent->id }}, following: {{ ($talent->followed_by_viewer ?? false) ? 'true' : 'false' }} })">
                    <div class="frame">
                        <a href="{{ route('talent.public', ['slug' => $talent->slug]) }}" class="block">
                            @if ($talent->avatar_url)
                                <img src="{{ $talent->avatar_url }}" alt="{{ $talent->display_name }}"
                                     loading="lazy" class="block aspect-[3/4] w-full object-cover">
                            @else
                                <div class="flex aspect-[3/4] w-full items-center justify-center bg-ground-sunk">
                                    <span class="t-display opacity-30">{{ str($talent->display_name)->substr(0, 1) }}</span>
                                </div>
                            @endif
                        </a>
                        <div class="p-3">
                            <a href="{{ route('talent.public', ['slug' => $talent->slug]) }}" class="block">
                                <span class="block truncate" style="font-family: var(--dl-font-display); font-size: 17px;">{{ $talent->display_name }}</span>
                            </a>
                            <p class="t-meta mt-1 truncate">
                                {{ $talent->talentTypes->pluck('name')->implode(' · ') }}
                            </p>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="t-meta tabular-nums">{{ $talent->followers_count }}</span>
                                <button type="button" class="dl-chip"
                                        :class="{ 'is-active': following }" :aria-pressed="following ? 'true' : 'false'"
                                        @click="toggle()"
                                        x-text="following ? '{{ __('FOLLOWING') }}' : '{{ __('FOLLOW') }}'">
                                    {{ __('FOLLOW') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
