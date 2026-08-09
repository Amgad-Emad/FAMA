{{-- A set of suggestion cards (5AA), rendered server-side for the rails AND
     the small-screen inline strips. $type = talent|brand, $items = payloads
     from SuggestionService. TALENTS render as premium compact rows (a small
     portrait thumb + name/handle/skill + a ghost Follow pill — a curator's
     shortlist that lets several show at once); BRANDS keep the square frame.
     Each card is a listitem with its own Alpine state (follow/dismiss). --}}
@foreach ($items as $item)
    @if ($type === 'brand')
        <li class="rail-card frame-shadow"
            x-data="suggestionCard(@js($item))"
            x-show="!removed"
            :class="{ 'is-leaving': leaving }">
            <div class="frame rail-card--square">
                <a :href="profileUrl" class="block">
                    <span class="flex aspect-square w-full items-center justify-center bg-surface-sunk">
                        <img x-show="card.avatar_url" :src="card.avatar_url" alt="" loading="lazy" class="h-full w-full object-cover">
                        <span x-show="!card.avatar_url" class="avatar-fallback t-display" :style="fallbackTint()" x-text="(card.display_name ?? '?').slice(0, 1)"></span>
                    </span>
                </a>

                <div class="p-3">
                    <a :href="profileUrl" class="block">
                        <span class="block truncate" style="font-family: var(--dl-font-display); font-size: 15px;" x-text="card.display_name"></span>
                    </a>
                    <p class="rail-handle t-meta truncate">
                        <bdi x-text="'@' + card.username"></bdi>
                        <span class="comment-badge ms-1">{{ __('BRAND') }}</span>
                    </p>
                    <p class="t-meta mt-1 truncate">
                        <span x-show="card.sector" x-text="(card.sector ?? '').replaceAll('_', ' ')"></span><span x-show="card.sector && card.city"> · </span><span x-show="card.city" x-text="card.city"></span>
                    </p>
                    <p class="t-meta mt-1 tabular-nums" x-text="followers > 0 ? followers + ' {{ __('FOLLOWERS') }}' : '{{ __('NEW') }}'"></p>
                    <p x-show="card.reason" x-cloak class="rail-reason t-meta mt-1" x-text="card.reason"></p>

                    <div x-show="gated" x-cloak class="mt-2 rounded-md border border-seam bg-veil/40 p-2">
                        <p class="t-meta rail-reason">{{ __('Sign in to follow') }}</p>
                        <div class="mt-1 flex gap-1">
                            <a :href="window.fama.localizeUrl('/login?role=talent')" class="dl-chip">{{ __('Talent') }}</a>
                            @feature('brands')
                                <a :href="window.fama.localizeUrl('/login?role=brand')" class="dl-chip">{{ __('Brand') }}</a>
                            @endfeature
                        </div>
                    </div>

                    <div class="mt-2 flex items-center justify-between">
                        <button type="button" class="notify-bell grid place-items-center rounded-full" :class="{ 'is-active': following }"
                                :aria-pressed="following ? 'true' : 'false'"
                                :aria-label="(following ? '{{ __('Turn off opportunity alerts for :name', ['name' => '']) }}' : '{{ __('Notify me of opportunities from :name', ['name' => '']) }}') + ' ' + card.display_name"
                                :title="(following ? '{{ __('Turn off opportunity alerts for :name', ['name' => '']) }}' : '{{ __('Notify me of opportunities from :name', ['name' => '']) }}') + ' ' + card.display_name"
                                @click="follow()">
                            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" :fill="following ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>
                            </svg>
                        </button>
                        <button type="button" class="t-meta" @click="dismiss()"
                                :aria-label="'{{ __('Dismiss suggestion') }}: ' + card.display_name">✕</button>
                    </div>
                </div>
            </div>
        </li>
    @else
        {{-- Premium compact talent row --}}
        <li class="rail-card suggest-row"
            x-data="suggestionCard(@js($item))"
            x-show="!removed"
            :class="{ 'is-leaving': leaving }">
            <a :href="profileUrl" class="suggest-thumb" tabindex="-1" aria-hidden="true">
                <img x-show="card.avatar_url" :src="card.avatar_url" alt="" loading="lazy">
                <span x-show="!card.avatar_url" class="avatar-fallback suggest-thumb__fallback" :style="fallbackTint()" x-text="(card.display_name ?? '?').slice(0, 1)"></span>
            </a>

            <div class="suggest-body">
                <a :href="profileUrl" class="suggest-name" x-text="card.display_name"></a>
                <p class="suggest-handle"><bdi x-text="'@' + card.username"></bdi></p>
                <p class="suggest-line">
                    <template x-if="(card.skills ?? []).length">
                        <span x-text="skillName(card.skills[0])"></span>
                    </template>
                    <template x-if="(card.skills ?? []).length && followers > 0"><span aria-hidden="true"> · </span></template>
                    <span x-text="followers > 0 ? followers + ' {{ __('followers') }}' : '{{ __('New') }}'"></span>
                </p>

                <div x-show="gated" x-cloak class="suggest-gate rounded-md border border-seam bg-veil/40 p-2">
                    <p class="t-meta rail-reason">{{ __('Sign in to follow') }}</p>
                    <div class="mt-1 flex gap-1">
                        <a :href="window.fama.localizeUrl('/login?role=talent')" class="dl-chip">{{ __('Talent') }}</a>
                        @feature('brands')
                            <a :href="window.fama.localizeUrl('/login?role=brand')" class="dl-chip">{{ __('Brand') }}</a>
                        @endfeature
                    </div>
                </div>
            </div>

            <div class="suggest-actions">
                <button type="button" class="dl-chip suggest-follow" :class="{ 'is-active': following }"
                        :aria-pressed="following ? 'true' : 'false'"
                        :aria-label="(following ? '{{ __('Unfollow') }}' : '{{ __('Follow') }}') + ' ' + card.display_name"
                        @click="follow()"
                        x-text="following ? '{{ __('Following') }}' : '{{ __('Follow') }}'"></button>
                <button type="button" class="suggest-dismiss" @click="dismiss()"
                        :aria-label="'{{ __('Dismiss suggestion') }}: ' + card.display_name">✕</button>
            </div>
        </li>
    @endif
@endforeach
