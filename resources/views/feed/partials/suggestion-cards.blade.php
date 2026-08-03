{{-- A set of suggestion cards (5AA), rendered server-side for the rails AND
     the small-screen inline strips. $type = talent|brand, $items = payloads
     from SuggestionService. Talent cards are PORTRAIT, brand cards SQUARE —
     the shape carries the side of the marketplace without colour or labels.
     Each card is a listitem with its own Alpine state (follow/dismiss). --}}
@foreach ($items as $item)
    <li class="rail-card frame-shadow"
        x-data="suggestionCard(@js($item))"
        x-show="!removed"
        :class="{ 'is-leaving': leaving }">
        <div class="frame {{ $type === 'brand' ? 'rail-card--square' : 'rail-card--portrait' }}">
            <a :href="profileUrl" class="block">
                @if ($type === 'brand')
                    <span class="flex aspect-square w-full items-center justify-center bg-surface-sunk">
                        <img x-show="card.avatar_url" :src="card.avatar_url" alt="" loading="lazy" class="h-full w-full object-cover">
                        <span x-show="!card.avatar_url" class="avatar-fallback t-display" :style="fallbackTint()" x-text="(card.display_name ?? '?').slice(0, 1)"></span>
                    </span>
                @else
                    <span class="block aspect-[4/5] w-full overflow-hidden">
                        <img x-show="card.avatar_url" :src="card.avatar_url" alt="" loading="lazy" class="h-full w-full object-cover">
                        <span x-show="!card.avatar_url" class="avatar-fallback flex h-full w-full items-center justify-center" :style="fallbackTint()">
                            <span class="t-display" x-text="(card.display_name ?? '?').slice(0, 1)"></span>
                        </span>
                    </span>
                @endif
            </a>

            <div class="p-3">
                <a :href="profileUrl" class="block">
                    <span class="block truncate" style="font-family: var(--dl-font-display); font-size: 15px;" x-text="card.display_name"></span>
                </a>
                <p class="rail-handle t-meta truncate">
                    <bdi x-text="'@' + card.username"></bdi>
                    @if ($type === 'brand')
                        <span class="comment-badge ms-1">{{ __('BRAND') }}</span>
                    @endif
                </p>

                @if ($type === 'brand')
                    <p class="t-meta mt-1 truncate">
                        {{-- sector is a raw enum key (e.g. food_beverage) — humanize for display --}}
                        <span x-show="card.sector" x-text="(card.sector ?? '').replaceAll('_', ' ')"></span><span x-show="card.sector && card.city"> · </span><span x-show="card.city" x-text="card.city"></span>
                    </p>
                @else
                    <p class="mt-1 flex flex-wrap gap-1">
                        <template x-for="skill in (card.skills ?? []).slice(0, 2)" :key="skill.slug">
                            <span class="rounded-sm border border-seam px-1 t-meta" x-text="skillName(skill)"></span>
                        </template>
                    </p>
                @endif

                <p class="t-meta mt-1 tabular-nums"
                   x-text="followers > 0 ? followers + ' {{ __('FOLLOWERS') }}' : '{{ __('NEW') }}'"></p>

                {{-- Two-line clamp, not truncate: a reason ending in a handle
                     must show the whole handle (round 3). --}}
                <p x-show="card.reason" x-cloak class="rail-reason t-meta mt-1" x-text="card.reason"></p>

                {{-- Inline guest gate (ADR-F8) — same treatment as like/comment --}}
                <div x-show="gated" x-cloak class="mt-2 rounded-md border border-seam bg-veil/40 p-2">
                    <p class="t-meta rail-reason">{{ __('Sign in to follow') }}</p>
                    <div class="mt-1 flex gap-1">
                        <a :href="window.fama.localizeUrl('/login?role=talent')" class="dl-chip">{{ __("Talent") }}</a>
                        <a :href="window.fama.localizeUrl('/login?role=brand')" class="dl-chip">{{ __("Brand") }}</a>
                    </div>
                </div>

                <div class="mt-2 flex items-center justify-between">
                    @if ($type === 'brand')
                        {{-- Brands: an icon-only bell — subscribe to be notified when
                             this brand posts an opportunity to apply to. --}}
                        <button type="button" class="notify-bell grid place-items-center rounded-full" :class="{ 'is-active': following }"
                                :aria-pressed="following ? 'true' : 'false'"
                                :aria-label="(following ? '{{ __('Turn off opportunity alerts for :name', ['name' => '']) }}' : '{{ __('Notify me of opportunities from :name', ['name' => '']) }}') + ' ' + card.display_name"
                                :title="(following ? '{{ __('Turn off opportunity alerts for :name', ['name' => '']) }}' : '{{ __('Notify me of opportunities from :name', ['name' => '']) }}') + ' ' + card.display_name"
                                @click="follow()">
                            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" :fill="following ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>
                            </svg>
                        </button>
                    @else
                        <button type="button" class="dl-chip" :class="{ 'is-active': following }"
                                :aria-pressed="following ? 'true' : 'false'"
                                :aria-label="(following ? '{{ __('Unfollow') }}' : '{{ __('Follow') }}') + ' ' + card.display_name"
                                @click="follow()"
                                x-text="following ? '{{ __('FOLLOWING') }}' : '{{ __('FOLLOW') }}'"></button>
                    @endif
                    <button type="button" class="t-meta" @click="dismiss()"
                            :aria-label="'{{ __('Dismiss suggestion') }}: ' + card.display_name">✕</button>
                </div>
            </div>
        </div>
    </li>
@endforeach
