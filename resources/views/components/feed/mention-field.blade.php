{{-- The suggestion list for a famaMention field (fama-ui: the composer's
     ul.sugg — "@ to credit someone" people, "#" skill tags when the field
     enables them). Place it inside an element carrying x-data="famaMention(…)".
     People who the viewer follows say "· you follow". --}}
<ul class="sugg" :class="{ tagmenu: mode === 'tag' }" x-show="open" x-cloak role="listbox" :id="listId" aria-label="{{ __('Suggestions') }}">
    <template x-for="(t, i) in (mode === 'tag' ? tags : [])" :key="'t-' + t.tag">
        <li><button type="button" role="option" :class="{ on: i === active }" :aria-selected="(i === active).toString()" @mousedown.prevent="pickTag(t)"><span class="hash" aria-hidden="true">#</span><span class="nm" x-text="t.name"></span><span class="sub" x-show="t.sub" x-text="t.sub"></span></button></li>
    </template>
    <template x-for="(p, i) in (mode === 'at' ? people : [])" :key="'p-' + p.type + p.id">
        <li><button type="button" role="option" :class="{ on: i === active }" :aria-selected="(i === active).toString()" @mousedown.prevent="pick(p)">
            <span class="av"><img x-show="p.avatar_url" :src="p.avatar_url" alt=""><span x-show="!p.avatar_url" x-text="initials(p.display_name)"></span></span>
            <span><b x-text="p.display_name"></b> <span class="meta" dir="ltr" x-text="'@' + p.username"></span><span class="meta" x-show="p.followed"> · {{ __('you follow') }}</span></span>
        </button></li>
    </template>
</ul>
