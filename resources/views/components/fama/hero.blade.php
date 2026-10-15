{{--
    The fama-ui page banner (port of the prototype's data-fama-hero). The soft
    lavender tone + a photo clipped to the brand star on the right is the talent
    page default. `photo` is a banner key under public/fama-ui/img/banners
    (feed|activity|notifications|projects|credits|profile-structure|profile|…);
    the star is drawn by public/fama-ui/js/hero.js. Slot = the action buttons.
--}}
@props(['label' => null, 'title', 'lead' => null, 'photo' => null, 'tone' => 'soft'])

<div class="fx-hero-wrap">
    <section {{ $attributes->class(['fx-hero', 'fx-soft' => $tone === 'soft', 'fx-photo' => (bool) $photo]) }}
             @if ($photo) data-fx-star="{{ asset('fama-ui/img/banners/'.$photo.'.jpg') }}" data-fx-focus="{{ $photo }}" @endif>
        <div class="fx-hb">
            <div>
                @if ($label)<span class="fx-hl">{{ $label }}</span>@endif
                <h1>{{ $title }}</h1>
                @if ($lead)<p class="fx-lead">{{ $lead }}</p>@endif
            </div>
            <div class="fx-ha">{{ $slot }}</div>
        </div>
        @unless ($photo)<div class="fx-band" role="presentation"></div>@endunless
    </section>
</div>
