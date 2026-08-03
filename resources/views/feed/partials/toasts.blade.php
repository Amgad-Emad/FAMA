{{-- The toast rack (5AF): bottom inline-end, stacked (cap 3), hover pauses
     the timer, click dismisses, polite live region announces. --}}
<div x-data class="fixed flex flex-col gap-2"
     style="z-index: var(--z-toast); inset-inline-end: 24px; inset-block-start: calc(var(--topbar-h, 4rem) + 16px);"
     aria-live="polite" data-surface="daylight">
    <template x-for="toast in $store.feedToasts.items" :key="toast.id">
        <div class="frame-shadow cursor-pointer" @click="$store.feedToasts.dismiss(toast.id)"
             @mouseenter="$store.feedToasts.pause(toast.id)" @mouseleave="$store.feedToasts.resume(toast.id)">
            <div class="frame flex items-center gap-3 px-4 py-3">
                <span class="t-body" x-text="toast.message"></span>
                <button type="button" x-show="toast.undo" class="t-meta underline"
                        @click.stop="toast.undo(); $store.feedToasts.dismiss(toast.id)">{{ __('Undo') }}</button>
                <a x-show="toast.href" :href="toast.href" class="t-meta text-daylight hover:underline"
                   :target="toast.hrefBlank ? '_blank' : null" rel="noopener"
                   x-text="toast.hrefLabel" @click.stop></a>
            </div>
        </div>
    </template>
</div>
