{{-- Loading skeletons (Prompt 5M): notched frames with the locale-aware light
     sweep, matching the spine's variable widths. --}}
@props(['count' => 3])

@for ($i = 0; $i < $count; $i++)
    <div class="w-full {{ ['max-w-[520px]', 'max-w-[640px]', 'max-w-[780px]'][$i % 3] }} frame-shadow" aria-hidden="true">
        <div class="frame">
            <div class="flex items-center gap-3 p-5">
                <span class="skeleton block h-10 w-10 rounded-full"></span>
                <span class="skeleton block h-3 w-32 rounded"></span>
            </div>
            <div class="skeleton {{ $i % 3 === 0 ? 'h-28' : 'aspect-[4/3]' }} w-full"></div>
            <div class="p-5">
                <span class="skeleton block h-3 w-48 rounded"></span>
            </div>
        </div>
    </div>
@endfor
