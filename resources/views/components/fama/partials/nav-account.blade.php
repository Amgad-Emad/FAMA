{{-- One account block, the same in the side-bar menu, the avatar menu and the phone menu (24 Oct 2026).
     Expects $talent, $initials, $displayName, $handle from components/fama/nav. --}}
<span class="fx-acct"><span class="fx-av fx-av-s" aria-hidden="true">@if ($talent->avatar_url)<img src="{{ $talent->avatar_url }}" alt="" style="width:100%;height:100%;border-radius:999px;object-fit:cover">@else{{ $initials }}@endif</span><span class="fx-who"><b>{{ $displayName }}</b>@if ($handle)<span>{{ $handle }}</span>@endif</span></span>
