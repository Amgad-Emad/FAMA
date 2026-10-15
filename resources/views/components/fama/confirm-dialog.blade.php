{{--
    fama-ui confirm dialog: a native <dialog class="dlg"> (the prototype's
    FAMA.confirm) bound to the SAME `confirm` Alpine store as the Tailwind
    <x-confirm-dialog>, so `$confirm({...})` works unchanged on fama-ui pages.
--}}
<div x-data="{ get s() { return $store.confirm } }"
     x-effect="s.open ? ($refs.d.open || $refs.d.showModal()) : ($refs.d.open && $refs.d.close())">
    <dialog class="dlg" x-ref="d" @cancel.prevent="s.cancel()" aria-labelledby="famaConfirmTitle">
        <form method="dialog" @submit.prevent>
            <h2 id="famaConfirmTitle" x-text="s.title || @js(__('Are you sure?'))"></h2>
            <p class="d" x-text="s.message || @js(__('This action cannot be undone.'))"></p>
            <div class="acts">
                <button type="button" class="btn btn-2" @click="s.cancel()" x-text="s.cancelLabel || @js(__('Cancel'))"></button>
                <button type="button" class="btn" :class="{ 'btn-danger': s.tone === 'danger' }" @click="s.agree()" x-text="s.confirmLabel || @js(__('Confirm'))"></button>
            </div>
        </form>
    </dialog>
</div>
