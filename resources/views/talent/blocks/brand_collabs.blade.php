{{--
    Experience — LinkedIn-style roles (ADR-AK), drawn as the prototype's FAMA_EXP
    list (exp.css .xp): newest first, consecutive roles at one company grouped
    under it with the total time; each role shows its dates + duration, place,
    highlights and the skills it used. Rules live in App\Support\Experience (the
    editor's resources/js/ui/experience.js draws the same list).
--}}
@php
    $groups = \App\Support\Experience::groups($talent->brandCollabs);
    $place = fn (array $r) => collect([$r['loc'], $r['lt']])->filter()->implode(' · ');
@endphp
<x-profile.block :block="$block" :empty="$groups === []" :is-owner="$isOwner ?? false">
    <div class="card" style="margin-top:var(--s5)">
        <ol class="xp">
            @foreach ($groups as $group)
                @php $one = count($group['roles']) === 1; $last = $group['roles'][0]; @endphp
                <li @class(['xp-g', 'one' => $one])>
                    <span class="xp-logo" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/></svg></span>
                    <div class="xp-b">
                        @if ($one)
                            <div class="xp-top"><div>
                                <h3 class="xp-t">{{ $last['t'] ?: $group['n'] }}</h3>
                                <p class="xp-m">{{ collect([$group['n'], $last['et']])->filter()->implode(' · ') }}</p>
                                @if ($last['sy'])<p class="xp-d">{{ \App\Support\Experience::range($last) }}</p>@endif
                                @if ($place($last) !== '')<p class="xp-d">{{ $place($last) }}</p>@endif
                            </div></div>
                            @include('talent.blocks.partials.xp-role-extra', ['r' => $last])
                        @else
                            @php $et = collect($group['roles'])->every(fn ($x) => $x['et'] === $last['et']) ? $last['et'] : ''; @endphp
                            <h3 class="xp-org">{{ $group['n'] }}</h3>
                            <p class="xp-m">{{ collect([$et, \App\Support\Experience::duration(\App\Support\Experience::groupMonths($group['roles']))])->filter()->implode(' · ') }}</p>
                            @if ($place($last) !== '')<p class="xp-d">{{ $place($last) }}</p>@endif
                            <ol class="xp-roles">
                                @foreach ($group['roles'] as $r)
                                    <li class="xp-r"><div class="xp-top"><div><h4 class="xp-t">{{ $r['t'] }}</h4>@if ($r['sy'])<p class="xp-d">{{ \App\Support\Experience::range($r) }}</p>@endif</div></div>
                                        @include('talent.blocks.partials.xp-role-extra', ['r' => $r])</li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</x-profile.block>
