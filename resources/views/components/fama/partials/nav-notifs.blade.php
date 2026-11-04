{{-- The latest six notifications (top-bar bell popover and the side bar's hover popover).
     Reads the shared `famaNotifs` Alpine store; opening one marks it read. --}}
<div class="fx-nh"><b>{{ __('Notifications') }}</b><button type="button" class="fx-nall" @click.stop="$store.famaNotifs.readAll()">{{ __('Mark all as read') }}</button></div>
<ul class="fx-nlist">
    <template x-if="$store.famaNotifs.loaded && !$store.famaNotifs.items.length">
        <li class="fx-n"><span class="fx-nb"><span class="fx-nex">{{ __('No notifications yet.') }}</span></span></li>
    </template>
    <template x-for="n in $store.famaNotifs.items.slice(0, 6)" :key="n.id">
        <li>
            <a class="fx-n" :class="{ unread: !n.read_at }" :href="n.url || '#'" @click="$store.famaNotifs.markRead(n)">
                <span class="fx-nic" aria-hidden="true"><svg class="fx-icon"><use :href="'#' + window.famaNotifIcon(n)"/></svg></span>
                <span class="fx-nb">
                    <span><b x-text="window.famaNotifWho(n)"></b><span x-text="window.famaNotifRest(n)"></span></span>
                    <span class="fx-nago"><span x-text="window.famaAgo(n.created_at)"></span><span class="fx-vh" x-show="!n.read_at"> · {{ __('unread') }}</span></span>
                </span>
                <span class="fx-ndot" aria-hidden="true" x-show="!n.read_at"></span>
            </a>
        </li>
    </template>
</ul>
<a class="fx-nmore" href="{{ route('notifications.page') }}">{{ __('See all notifications') }}</a>
