{{-- /notifications/all — everything from the bell (port of notifications.html),
     grouped by when it happened: All / Unread / Mentions, Mark all as read.
     Rows come from GET /notifications (?filter=unread|mention), localized at
     read time. Behaviour = famaNotifications (ui/feed-pages.js). --}}
@php
    $cfg = [
        'listUrl' => route('notifications'),
        'readUrl' => route('notifications.read'),
        'unreadUrl' => route('notifications.unread'),
        'groups' => [__('Today'), __('This week'), __('Earlier')],
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
        <section class="card">
            <div class="nbar">
                <div class="seg" role="group" aria-label="{{ __('Show') }}" id="show">
                    <button type="button" data-v="all" :aria-pressed="(show === 'all').toString()" aria-pressed="true" @click="setShow('all')">{{ __('All') }}</button>
                    <button type="button" data-v="unread" :aria-pressed="(show === 'unread').toString()" aria-pressed="false" @click="setShow('unread')">{{ __('Unread') }}</button>
                    <button type="button" data-v="mention" :aria-pressed="(show === 'mention').toString()" aria-pressed="false" @click="setShow('mention')">{{ __('Mentions') }}</button>
                </div>
                <button type="button" class="btn btn-2" id="all" :disabled="unread === 0" @click="readAll()"><svg class="icon" aria-hidden="true"><use href="#a-check"/></svg>{{ __('Mark all as read') }}</button>
            </div>
            <div id="list" style="margin-top:var(--s5)">
                <template x-for="g in grouped()" :key="g.label">
                    <section class="ngrp">
                        <h2 x-text="g.label"></h2>
                        <ul>
                            <template x-for="n in g.items" :key="n.id">
                                <li><a class="fx-n" :class="{ unread: !n.read_at }" :href="n.url || '#'" @click="markRead(n)">
                                    <span class="fx-nic" aria-hidden="true"><svg class="fx-icon"><use :href="'#' + icon(n)"/></svg></span>
                                    <span class="fx-nb"><span><b x-text="who(n)"></b><span x-text="rest(n)"></span></span><span class="fx-nago"><span x-text="ago(n.created_at)"></span><span class="fx-vh" x-show="!n.read_at"> · {{ __('unread') }}</span></span></span>
                                    <span class="fx-ndot" aria-hidden="true" x-show="!n.read_at"></span>
                                </a></li>
                            </template>
                        </ul>
                    </section>
                </template>
                <div class="empty" x-show="loaded && !items.length" x-cloak>
                    <h3 class="h2" x-text="show === 'unread' ? @js(__("You're all caught up")) : @js(__('Nothing here yet'))"></h3>
                    <p x-text="show === 'mention' ? @js(__('When someone mentions you in a post or comment, it shows up here.')) : @js(__('New notifications show up here.'))"></p>
                </div>
                <button type="button" class="btn btn-2" style="display:block;margin:var(--s5) auto 0" x-show="page < lastPage" x-cloak @click="load(true)">{{ __('Show older') }}</button>
            </div>
        </section>
    </main>
</x-fama.layout>
