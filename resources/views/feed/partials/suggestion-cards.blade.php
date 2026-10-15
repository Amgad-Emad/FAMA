{{-- Follow suggestions as server-rendered rows (the ?format=html contract of
     /feed/suggestions/*). fama-ui's "Talent to follow" list markup (.rows li).
     The live feed page renders the same rows client-side from the JSON
     payload ($store.famaPeople); this stays for HTML consumers. --}}
@foreach ($items as $item)
    @php
        $name = $item['display_name'] ?? '';
        $init = collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
        $skill = $item['skills'][0]['name'] ?? null;
        $skill = is_array($skill) ? ($skill[app()->getLocale()] ?? $skill['en'] ?? reset($skill)) : $skill;
        $url = ($item['type'] ?? 'talent') === 'brand' ? url('brands/'.$item['username']) : url($item['username']);
    @endphp
    <li data-u="{{ $item['username'] }}" data-type="{{ $type }}" data-id="{{ $item['id'] }}">
        <span class="av">@if (! empty($item['avatar_url']))<img src="{{ $item['avatar_url'] }}" alt="" loading="lazy">@else{{ $init }}@endif</span>
        <span class="grow"><a href="{{ $url }}"><b>{{ $name }}</b></a><span class="why">{{ $item['reason'] ?? $skill }}</span></span>
    </li>
@endforeach
