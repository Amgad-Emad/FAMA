@php $card = $talent->compCard; @endphp

@if ($card)
    <x-ui.section :title="$block->title ?: $block->blockType->name" :eyebrow="__('Measurements')">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([
                __('Height') => $card->height_cm ? $card->height_cm.' cm' : null,
                __('Bust') => $card->bust_cm ? $card->bust_cm.' cm' : null,
                __('Waist') => $card->waist_cm ? $card->waist_cm.' cm' : null,
                __('Hips') => $card->hips_cm ? $card->hips_cm.' cm' : null,
                __('Hair') => $card->hair_color,
                __('Eyes') => $card->eye_color,
                __('Shoe') => $card->shoe_size,
                __('Dress') => $card->dress_size,
            ] as $label => $value)
                @if ($value)
                    <x-ui.stat :label="$label" :value="$value" />
                @endif
            @endforeach
        </div>
    </x-ui.section>
@endif
