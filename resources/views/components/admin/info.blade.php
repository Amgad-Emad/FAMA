{{--
    An "i" button with a popover that explains how a figure is calculated.
    Static text: <x-admin.info :text="__('…')" />   Alpine text: <x-admin.info bind="S.info[m]" />
    Opens on hover, keyboard focus and tap; Escape / click-away closes. Clicks
    never reach a clickable parent (e.g. a KPI tile).
--}}
@props(['text' => null, 'bind' => null, 'align' => 'end', 'label' => __('How is this calculated?')])
<span {{ $attributes->merge(['class' => 'relative inline-flex']) }} x-data="{ tip: false }"
      @mouseenter="tip = true" @mouseleave="tip = false" @click.outside="tip = false" @keydown.escape.stop="tip = false">
    <button type="button" @click.stop.prevent="tip = !tip" @focus="tip = true" @blur="tip = false" :aria-expanded="tip.toString()" aria-label="{{ $label }}"
            class="grid h-6 w-6 place-items-center rounded-pill border border-line bg-surface text-subtle transition hover:border-ink hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
            :class="{ 'border-ink text-ink': tip }">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 11v6"/><circle cx="12" cy="7.5" r=".6" fill="currentColor"/></svg>
    </button>
    <span x-show="tip" x-cloak x-transition.opacity.duration.120ms role="tooltip"
          class="absolute top-[calc(100%+8px)] z-40 w-72 max-w-[80vw] rounded-[14px] bg-primary px-4 py-3 text-start text-[13px] font-normal normal-case leading-relaxed tracking-normal text-on-primary shadow-e3 {{ $align === 'start' ? 'start-0' : 'end-0' }}"
          @if ($bind) x-text="{{ $bind }}" @endif>{{ $text }}</span>
</span>
