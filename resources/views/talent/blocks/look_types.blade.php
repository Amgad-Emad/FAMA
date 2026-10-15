{{-- Looks. The app stores a look as a name only (no picture set yet), so it renders as chips. --}}
@php $looks = $talent->lookTypes; @endphp
<x-profile.block :block="$block" :empty="$looks->isEmpty()" :is-owner="$isOwner ?? false">
    <ul class="chips" style="margin-top:var(--s4);list-style:none;padding:0">
        @foreach ($looks as $look)<li class="chip">{{ $look->name }}</li>@endforeach
    </ul>
</x-profile.block>
