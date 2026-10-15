{{-- Software (fama-ui chips), proficiency beside each tool. --}}
@php $stack = $talent->softwareStack; @endphp
<x-profile.block :block="$block" :empty="$stack->isEmpty()" :is-owner="$isOwner ?? false">
    <ul class="chips" style="margin-top:var(--s4);list-style:none;padding:0">
        @foreach ($stack as $tool)
            <li class="chip">{{ $tool->software_name }}@if ($tool->proficiency) <span class="c">· {{ __(ucfirst((string) $tool->proficiency)) }}</span>@endif</li>
        @endforeach
    </ul>
</x-profile.block>
