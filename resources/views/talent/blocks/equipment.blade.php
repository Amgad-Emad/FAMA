{{-- Equipment (fama-ui chips). --}}
@php $equipment = $talent->equipment; @endphp
<x-profile.block :block="$block" :empty="$equipment->isEmpty()" :is-owner="$isOwner ?? false">
    <ul class="chips" style="margin-top:var(--s4);list-style:none;padding:0">
        @foreach ($equipment as $item)
            <li class="chip" title="{{ __(ucfirst((string) $item->category)) }}">{{ trim($item->brand.' '.$item->model) ?: $item->name }}@if (trim($item->brand.' '.$item->model) && $item->name) <span class="c">· {{ $item->name }}</span>@endif</li>
        @endforeach
    </ul>
</x-profile.block>
