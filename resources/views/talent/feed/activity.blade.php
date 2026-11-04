{{-- /talent/feed/activity — "Activity" (port of following.html): the talent's
     private feed collections. Saved and Liked show full post cards (the same
     canonical card as the feed); Following / Followers are rows with "Follows
     you" and "Posted N days ago" / "Quiet since…", their three latest pieces of
     work (project lead images), Unfollow with a 5-second
     Undo, Follow back where it isn't mutual. Tabs switch in place; with no
     ?view= the page opens on Saved.
     Behaviour = famaActivity (ui/feed-pages.js). --}}
@php
    // The feed partials (lang bridge, composer, cards) read the acting talent.
    $currentActor = auth('talent')->user();
    $cfg = [
        'view' => $view,
        'who' => $tab,
        'counts' => $counts,
        'collectionUrl' => $collectionUrl,
        'followsUrl' => $followsUrl,
        'skills' => ['following' => $followingSkills, 'followers' => $followerSkills],
    ];
@endphp

<x-fama.layout :title="__('Activity')" :css="['posts', 'pages/activity', 'pages/feed-common']">
    <x-slot:head>
        @include('feed.partials.lang')
    </x-slot:head>
    <x-slot:hero>
        <x-fama.hero :label="__('Only you see this')" :title="__('Activity')" :lead="__('What you saved and liked, who you follow, and who follows you.')" photo="activity" />
    </x-slot:hero>

    @include('feed.partials.glyphs')

    <main class="wrap main" x-data="famaActivity(@js($cfg))">
        <div class="ptabs" role="tablist" id="views">
            @foreach (['saved' => __('Saved'), 'liked' => __('Liked'), 'following' => __('Following')] as $key => $label)
                <button type="button" role="tab" data-v="{{ $key }}" :aria-selected="(view === '{{ $key }}').toString()" aria-selected="{{ $view === $key ? 'true' : 'false' }}" @click="setView('{{ $key }}')">{{ $label }}<span class="n" x-text="counts.{{ $key }}.toLocaleString()">{{ number_format($counts[$key]) }}</span></button>
            @endforeach
        </div>

        <div id="panel" role="tabpanel">
            {{-- Following / Followers --}}
            <div x-show="view === 'following'" @if ($view !== 'following') x-cloak @endif>
                <div class="utabs" role="tablist" style="margin-top:var(--s5)">
                    <button type="button" role="tab" data-who="following" :aria-selected="(who === 'following').toString()" @click="setWho('following')">{{ __('Following') }}<span class="n" x-text="counts.following.toLocaleString()">{{ number_format($counts['following']) }}</span></button>
                    <button type="button" role="tab" data-who="followers" :aria-selected="(who === 'followers').toString()" @click="setWho('followers')">{{ __('Followers') }}<span class="n" x-text="counts.followers.toLocaleString()">{{ number_format($counts['followers']) }}</span></button>
                </div>
                <div class="tools">
                    <label class="vh" for="q">{{ __('Search name or username') }}</label>
                    <input class="in" id="q" type="search" placeholder="{{ __('Search name or username') }}" x-model="q" @input.debounce.300ms="reloadPeople()">
                    <div class="skf" @click.outside="skillOpen = false">
                        <input class="in" type="text" role="combobox" aria-autocomplete="list" :aria-expanded="skillOpen.toString()" aria-controls="skfActivity" autocomplete="off" spellcheck="false"
                               aria-label="{{ __('All skills') }}" placeholder="{{ __('Search skills') }}" x-model="skillQuery"
                               @focus="openSkills(true)" @input="openSkills(false)" @keydown="skillKey($event)" @blur="skillBlur()">
                        <svg class="icon" aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <ul class="skf-list" role="listbox" id="skfActivity" x-show="skillOpen" x-cloak>
                            <template x-for="(o, i) in skillOptions()" :key="o.slug">
                                <li role="option" :class="{ on: i === skillActive }" :aria-selected="(o.slug === skill).toString()" @mousedown.prevent="pickSkill(o)" x-text="o.name"></li>
                            </template>
                            <li class="none" x-show="!skillOptions().length">{{ __('No skill matches that') }}</li>
                        </ul>
                    </div>
                    <label class="vh" for="so">{{ __('Sort') }}</label>
                    <select class="sel" id="so" x-model="sort" @change="reloadPeople()">
                        <option value="recent">{{ __('Recently followed') }}</option>
                        <option value="alpha">{{ __('Alphabetical') }}</option>
                        <option value="least_active">{{ __('Least active') }}</option>
                    </select>
                </div>
                <div id="grid">
                    <ul class="people" x-show="rows.length" x-cloak>
                        <template x-for="p in rows" :key="p.type + '-' + p.id">
                            <li class="pc">
                                <a class="av" :href="profileUrl(p)" aria-hidden="true" tabindex="-1"><img x-show="p.avatar_url" :src="p.avatar_url" alt=""><span x-show="!p.avatar_url" x-text="initials(p.display_name)"></span></a>
                                <div class="who">
                                    <a :href="profileUrl(p)"><b dir="auto" x-text="p.display_name"></b></a>
                                    <span class="meta"><span dir="ltr" x-text="'@' + p.username"></span><span x-show="skillOf(p)" x-text="' · ' + skillOf(p)"></span></span>
                                    <p class="status" :class="{ q: !!p.quiet_since }" x-text="status(p)"></p>
                                </div>
                                <div class="foot">
                                    <template x-if="who === 'followers' && p.can_follow_back">
                                        <button type="button" class="btn" @click="followBack(p)" :disabled="p.busy" :aria-label="@js(__('Follow :name back')).replace(':name', p.display_name)">{{ __('Follow back') }}</button>
                                    </template>
                                    <template x-if="who === 'followers' && !p.can_follow_back">
                                        <button type="button" class="btn btn-2" disabled>{{ __('Following') }}</button>
                                    </template>
                                    <template x-if="who === 'following'">
                                        <button type="button" class="btn btn-2" @click="unfollow(p)" :aria-label="@js(__('Unfollow :name')).replace(':name', p.display_name)">{{ __('Following') }}</button>
                                    </template>
                                </div>
                                {{-- Their three latest pieces of work; no strip when there is nothing to show. --}}
                                <template x-if="(p.work ?? []).length">
                                    <div class="work" aria-hidden="true">
                                        <template x-for="(src, i) in p.work" :key="i"><span :style="{ backgroundImage: `url('${src}')` }"></span></template>
                                    </div>
                                </template>
                            </li>
                        </template>
                    </ul>
                    <div class="card empty" style="margin-top:var(--s5)" x-show="peopleLoaded && !rows.length" x-cloak>
                        <h2 class="h2">{{ __('No one matches') }}</h2>
                        <p>{{ __('Try another name or skill.') }}</p>
                    </div>
                    <button type="button" class="btn btn-2 posts-more" x-show="peoplePage < peopleLast" x-cloak @click="loadPeople(true)">{{ __('Show more') }}</button>
                </div>
            </div>

            {{-- Saved / Liked: full post cards --}}
            @foreach (['saved' => [__('Nothing saved yet'), __('Save posts from the feed to keep them here. Only you can see this list.')], 'liked' => [__('No likes yet'), __('Posts you like show here.')]] as $key => [$emptyT, $emptyP])
                <div x-show="view === '{{ $key }}'" @if ($view !== $key) x-cloak @endif>
                    <div class="posts-list" x-ref="{{ $key }}List" x-show="lists.{{ $key }}.count > 0"></div>
                    <div class="card empty" style="margin-top:var(--s5)" x-show="lists.{{ $key }}.loaded && lists.{{ $key }}.count === 0" x-cloak>
                        <h2 class="h2">{{ $emptyT }}</h2>
                        <p>{{ $emptyP }}</p>
                        <a class="btn" href="{{ route('feed') }}">{{ __('Go to the feed') }}</a>
                    </div>
                    <button type="button" class="btn btn-2 posts-more" x-show="lists.{{ $key }}.page < lists.{{ $key }}.last" x-cloak @click="loadPosts('{{ $key }}', true)">{{ __('Show more') }}</button>
                </div>
            @endforeach
        </div>
    </main>

    @if ($composer !== null)
        @include('feed.partials.composer', ['composer' => $composer])
    @endif
    @include('feed.partials.dialogs')
</x-fama.layout>
