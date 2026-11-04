{{-- /notifications/all — everything from the bell (port of notifications.html,
     1 Nov 2026 redesign): pill filters with counts (All / Unread / Mentions),
     Mark all as read, then the rows grouped Today / This week / Earlier — each
     its own card with one clear action for what it is (Reply, Review credit,
     View project, Follow back, View post, Open); the whole card opens it too.
     Rows + counts come from GET /notifications (?filter=unread|mention),
     localized at read time. Behaviour = famaNotifications (ui/feed-pages.js). --}}
@php
    $cfg = [
        'listUrl' => route('notifications'),
        'readUrl' => route('notifications.read'),
        'groups' => [__('Today'), __('This week'), __('Earlier')],
        // The one action that fits each kind (prototype ACT map): [label, primary?].
        'actions' => [
            'mention' => [__('Reply'), false],
            'comment' => [__('Reply'), false],
            'credit' => [__('Review credit'), true],
            'credit-ok' => [__('View project'), false],
            'follow' => [__('Follow back'), true],
            'like' => [__('View post'), false],
            'system' => [__('Open'), false],
        ],
    ];
@endphp

<x-fama.layout :title="__('Notifications')" :css="['pages/notifications']">
    <x-slot:head>
        @include('feed.partials.lang')
    </x-slot:head>
    <x-slot:hero>
        <x-fama.hero :label="__('Your activity')" :title="__('Notifications')" :lead="__('Mentions, credits, comments and follows, newest first.')" photo="notifications" />
    </x-slot:hero>

    <main class="wrap main" x-data="famaNotifications(@js($cfg))">
        <section>
            <div class="nbar">
                <div class="ptabs" role="tablist" aria-label="{{ __('Show') }}" id="show" @keydown.arrow-right.prevent="stepShow(1)" @keydown.arrow-left.prevent="stepShow(-1)">
                    @foreach (['all' => __('All'), 'unread' => __('Unread'), 'mention' => __('Mentions')] as $key => $label)
                        <button type="button" role="tab" data-v="{{ $key }}" :aria-selected="(show === '{{ $key }}').toString()" aria-selected="{{ $key === 'all' ? 'true' : 'false' }}" :tabindex="show === '{{ $key }}' ? 0 : -1" @click="setShow('{{ $key }}')">{{ $label }}<span class="n" x-text="counts.{{ $key }}.toLocaleString()">0</span></button>
                    @endforeach
                </div>
                <button type="button" class="btn btn-2" id="all" :disabled="counts.unread === 0" @click="readAll()"><svg class="icon" aria-hidden="true"><use href="#a-check"/></svg>{{ __('Mark all as read') }}</button>
            </div>
            <div id="list" style="margin-top:var(--s6)" role="tabpanel">
                <template x-for="g in grouped()" :key="g.label">
                    <section class="ngrp">
                        <h2 x-text="g.label"></h2>
                        <ul>
                            <template x-for="n in g.items" :key="n.id">
                                <li class="nc" :class="{ unread: !n.read_at }">
                                    <span class="ic" aria-hidden="true"><svg class="fx-icon"><use :href="'#' + icon(n)"/></svg></span>
                                    <div class="tx">
                                        <a :href="n.url || '#'" @click="markRead(n)"><b x-text="who(n)"></b><span x-text="rest(n)"></span></a>
                                        <span class="ex" dir="auto" x-show="n.excerpt" x-text="n.excerpt"></span>
                                        <span class="ago"><i aria-hidden="true" x-show="!n.read_at"></i><span x-text="ago(n.created_at)"></span><span class="fx-vh" x-show="!n.read_at"> · {{ __('unread') }}</span></span>
                                    </div>
                                    <template x-if="n.kind === 'follow' && n.actor?.type && n.actor?.id">
                                        <button type="button" class="act btn" :class="{ 'btn-2': n.followed_back }" :aria-pressed="(!!n.followed_back).toString()" :disabled="n.busy" @click="toggleFollow(n)" x-text="n.followed_back ? followingLabel() : action(n)[0]"></button>
                                    </template>
                                    <template x-if="!(n.kind === 'follow' && n.actor?.type && n.actor?.id)">
                                        <a class="act btn" :class="{ 'btn-2': !action(n)[1] }" :href="n.url || '#'" @click="markRead(n)" x-text="action(n)[0]"></a>
                                    </template>
                                </li>
                            </template>
                        </ul>
                    </section>
                </template>
                <div class="empty" x-show="loaded && !items.length" x-cloak>
                    <h3 class="h2" x-text="show === 'unread' ? @js(__("You're all caught up")) : @js(__('Nothing here yet'))"></h3>
                    <p x-text="show === 'mention' ? @js(__('When someone mentions you in a post or comment, it shows up here.')) : @js(__('New notifications show up here.'))"></p>
                </div>
                <button type="button" class="btn btn-2" style="display:block;margin:var(--s6) auto 0" x-show="page < lastPage" x-cloak @click="load(true)">{{ __('Show older') }}</button>
            </div>
        </section>
    </main>
</x-fama.layout>
