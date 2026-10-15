{{--
    A searchable drop list (fama-ui). Behaviour: famaCombo (resources/js/ui/combo.js).
    Usage: <x-fama.combo id="fCountry" model="core.base_country" items="countryOptions"
                         :flags="true" :placeholder="__('Choose a country')" … />
    `model` and `items` are Alpine expressions in the parent scope.
--}}
@props([
    'id',
    'model',
    'items',
    'label' => '',
    'placeholder' => '',
    'searchPlaceholder' => __('Search'),
    'emptyText' => __('No matches'),
    'customLabel' => __('Use “:text”'),
    'flags' => false,
    'allowCustom' => false,
    'disabled' => null,
    'icon' => null, // 'globe' | 'pin' — shown when there is no flag to show
])
<div {{ $attributes->merge(['class' => 'combo']) }}
     x-data="famaCombo({ get: () => {{ $model }}, set: (v) => { {{ $model }} = v }, items: () => {{ $items }}, flags: @js((bool) $flags), allowCustom: @js((bool) $allowCustom), flagBase: @js(asset('fama-ui/img/flags').'/') })"
     @click.outside="close()" :class="{ open }">
    <button type="button" class="in combo-btn" id="{{ $id }}" x-ref="btn"
            aria-haspopup="listbox" :aria-expanded="open.toString()" aria-controls="{{ $id }}-list"
            @if ($label) aria-label="{{ $label }}" @endif
            @if ($disabled) :disabled="{{ $disabled }}" @endif
            @click="toggle()" @keydown.arrow-down.prevent="show()">
        <template x-if="flags && current && current.code"><img class="flag" :src="flagSrc(current.code)" alt="" width="20" height="15"></template>
        @if ($icon)
            <svg class="combo-ic" viewBox="0 0 24 24" aria-hidden="true" x-show="!(flags && current && current.code)">
                @if ($icon === 'globe')<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>
                @else<path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/>@endif
            </svg>
        @endif
        <span class="combo-val" :class="{ 'is-empty': !value }" x-text="current ? current.label : (value || @js($placeholder))">{{ $placeholder }}</span>
        <svg class="combo-chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </button>
    <div class="combo-pop" x-show="open" x-cloak x-transition.opacity.duration.120ms>
        <input class="combo-q" type="search" x-ref="q" x-model="q" @input="active = 0" @keydown="onKey($event)"
               placeholder="{{ $searchPlaceholder }}" autocomplete="off" spellcheck="false" aria-label="{{ $searchPlaceholder }}"
               role="combobox" aria-autocomplete="list" aria-controls="{{ $id }}-list" :aria-activedescendant="'{{ $id }}-o' + active">
        <ul class="combo-list" id="{{ $id }}-list" role="listbox" x-ref="list">
            <template x-for="(o, i) in filtered" :key="o.value">
                <li role="option" :id="'{{ $id }}-o' + i" :aria-selected="(o.value === value).toString()" :data-active="(i === active).toString()"
                    @mousemove="active = i" @click="pick(o)">
                    <template x-if="flags && o.code"><img class="flag" :src="flagSrc(o.code)" alt="" width="20" height="15" loading="lazy"></template>
                    <span x-text="o.label"></span>
                    <svg class="combo-tick" viewBox="0 0 24 24" aria-hidden="true" x-show="o.value === value"><path d="M20 6 9 17l-5-5"/></svg>
                </li>
            </template>
            <li role="option" class="combo-custom" x-show="customText" :id="'{{ $id }}-o' + filtered.length"
                :data-active="(active === filtered.length).toString()" @mousemove="active = filtered.length" @click="pickCustom()"
                x-text="@js($customLabel).replace(':text', customText)"></li>
            <li class="combo-empty" x-show="!rowCount">{{ $emptyText }}</li>
        </ul>
    </div>
</div>
