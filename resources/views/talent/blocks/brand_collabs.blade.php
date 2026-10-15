{{--
    Brands worked with — rendered as the fama-ui Experience card (exp.css .xp):
    logo (or initials), brand, project, year. The app keeps brand collabs, not
    LinkedIn-style roles (see the report).
--}}
@php $collabs = $talent->brandCollabs->sortBy('position')->values(); @endphp
<x-profile.block :block="$block" :empty="$collabs->isEmpty()" :is-owner="$isOwner ?? false">
    <div class="card" style="margin-top:var(--s5)">
        <ol class="xp">
            @foreach ($collabs as $collab)
                @php $ini = collect(preg_split('/\s+/', trim((string) $collab->brand_name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''); @endphp
                <li class="xp-g one">
                    @if ($collab->brand_logo_url)
                        <span class="xp-logo" aria-hidden="true" style="background:url('{{ $collab->brand_logo_url }}') center/cover;color:transparent">{{ $ini }}</span>
                    @else
                        <span class="xp-logo" aria-hidden="true">{{ $ini }}</span>
                    @endif
                    <div class="xp-b"><div class="xp-top"><div>
                        <h3 class="xp-t">@if ($collab->url)<a href="{{ $collab->url }}" target="_blank" rel="noopener nofollow" style="color:inherit;text-decoration:none">{{ $collab->brand_name }}</a>@else{{ $collab->brand_name }}@endif</h3>
                        @if ($collab->project_title)<p class="xp-m">{{ $collab->project_title }}</p>@endif
                        @if ($collab->year)<p class="xp-d">{{ $collab->year }}</p>@endif
                    </div></div></div>
                </li>
            @endforeach
        </ol>
    </div>
</x-profile.block>
