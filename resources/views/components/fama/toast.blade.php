{{--
    fama-ui toast (the prototype's FAMA.toast): one pill at the bottom, optional
    Undo, auto-hides after 5s. Drive it with $store.famaToast.show(msg, undoFn)
    or window.famaToast(msg, undoFn).
--}}
<div class="toast" role="status" x-data x-show="$store.famaToast.visible" x-cloak>
    <span x-text="$store.famaToast.message"></span>
    <button type="button" x-show="!!$store.famaToast.undo" @click="$store.famaToast.runUndo()">{{ __('Undo') }}</button>
</div>
