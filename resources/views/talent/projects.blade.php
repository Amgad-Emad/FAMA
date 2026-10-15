{{--
    Projects (fama-ui `projects.html`) — the talent's selected work.

    Every project carries at least one of the talent's own skills (ADR-X); those
    tags decide the profile tabs it appears in. Filter by skill, drag to reorder
    (only on the unfiltered single-page list — a partial list cannot express an
    order), delete with confirmation. New / Edit open the editor PAGE
    (talent.projects.new / talent.projects.edit) — no pop-up.

    Projects other people credited you on, and you confirmed (ADR-AA), are listed
    after your own with a Leave action (withdrawing = declining the credit).

    Behaviour: `famaProjects` (resources/js/ui/projects.js) on the JSON endpoints.
--}}
@php
    $initials = collect(preg_split('/\s+/', trim((string) ($talent->display_name ?: $talent->slug))))
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $strings = [
        'you' => __('You'),
        'and' => __('and'),
        'ownMany' => __('You and :n others'),
        'coowned' => __('Co-owned with :names'),
        'waiting' => __(':n awaiting confirmation'),
        'editA' => __('Edit :p'),
        'delA' => __('Delete :p'),
        'leaveA' => __('Leave :p'),
        'dragA' => __('Drag to reorder :p'),
        'delTitle' => __('Delete :p?'),
        'delBody' => __('You’re its only owner, so it comes off Fama for everyone, with its photos and credits. This can’t be undone.'),
        'delOk' => __('Delete project'),
        'deleted' => __(':p deleted'),
        'leaveTitle' => __('Leave :p?'),
        'leaveBody' => __('You stop being an owner, and it comes off your profile. The project stays for :n. Nobody can delete a project that still has other owners.'),
        'leaveOk' => __('Leave project'),
        'left' => __('You left :p'),
        'untitled' => __('Untitled project'),
        'featureA' => __('Feature :p on your profile'),
        'featuredA' => __('Featured on your profile. Feature another project to change it.'),
        'featuredOnly' => __('Your only project is always featured.'),
        'featuredT' => __(':p is now your featured project'),
        'failed' => __('Something went wrong. Try again.'),
    ];
@endphp
<x-fama.layout :title="__('Projects')">
    <x-slot:styles>
        <link rel="stylesheet" href="{{ \App\Support\FamaAsset::url('fama-ui/css/pages/projects.css') }}">
        <style>
            /* App additions: real cover images on the prototype's grey card cover. */
            .pc .cover.has{background-size:cover;background-position:center}
            /* Featured project (the profile cover) */
            .pc .cover{position:relative}
            .feat-badge{position:absolute;top:var(--s3);inset-inline-end:var(--s3);display:inline-flex;align-items:center;gap:6px;padding:5px var(--s3) 5px 8px;border-radius:var(--radius-pill);background:rgba(255,255,255,.92);backdrop-filter:blur(6px);font-size:12px;font-weight:600;color:var(--ink);box-shadow:0 2px 10px rgba(20,17,15,.12)}
            .feat-badge svg{width:15px;height:15px;fill:var(--yellow);stroke:var(--ink);stroke-width:40;stroke-linejoin:round}
            .featbtn svg{width:19px;height:19px;fill:none;stroke:currentColor;stroke-width:44;stroke-linejoin:round;transition:fill .2s var(--ease),transform .25s var(--ease)}
            .featbtn:hover svg{transform:rotate(-8deg) scale(1.08)}
            .featbtn.on{color:var(--ink);cursor:default}
            .featbtn.on svg{fill:var(--yellow);transform:none}
            .pc .foot>span:last-child{display:flex;flex:none;gap:2px}
            .pc .foot .waiting{min-width:0}
            .pager{display:flex;justify-content:center;align-items:center;gap:var(--s3);margin-top:var(--s6)}
        </style>
    </x-slot:styles>

    <x-slot:hero>
        <x-fama.hero :label="__('Selected work')" :title="__('Projects')"
                     :lead="__('Each project carries at least one of your skills, which decides the profile tab it appears in.')"
                     photo="projects">
            @if (count($skills))
                <a class="fx-hbtn" href="{{ route('talent.projects.new') }}"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg><span>{{ __('New project') }}</span></a>
            @endif
        </x-fama.hero>
    </x-slot:hero>

    <main class="wrap main"
          x-data="famaProjects({ skills: @js($skills), total: @js($total), slug: @js($talent->slug), live: @js((bool) $talent->is_published), me: @js($initials), strings: @js($strings) })">

        @if (! count($skills))
            {{-- The skill rule made concrete: no skills, no projects (ADR-X). --}}
            <section class="card">
                <div class="empty">
                    <h2 class="h2">{{ __('Add a skill first') }}</h2>
                    <p>{{ __('A project has to say which of your skills it belongs to, so you need at least one skill on your profile before you can add work.') }}</p>
                    <a class="btn" href="{{ route('talent.profile.edit') }}">{{ __('Manage your skills') }}</a>
                </div>
            </section>
        @else
            <div class="bar">
                <div class="chips" id="filters" role="group" aria-label="{{ __('Filter projects by skill') }}">
                    <button class="chip" type="button" @click="setFilter('')" :aria-pressed="(filter === '').toString()" aria-pressed="true">{{ __('All work') }}<span class="c" x-text="countFor('')">{{ $total }}</span></button>
                    @foreach ($skills as $skill)
                        <button class="chip" type="button" @click="setFilter('{{ $skill['id'] }}')" :aria-pressed="(filter === '{{ $skill['id'] }}').toString()" aria-pressed="false">{{ $skill['name'] }}<span class="c" x-text="countFor('{{ $skill['id'] }}')">0</span></button>
                    @endforeach
                    {{-- Only offered while work is parked there (removing a skill can leave projects behind). --}}
                    <button class="chip" type="button" x-show="countFor('none') > 0" x-cloak @click="setFilter('none')" :aria-pressed="(filter === 'none').toString()">{{ __('No skill yet') }}<span class="c" x-text="countFor('none')"></span></button>
                </div>
            </div>
            <p class="meta hint" x-text="canReorder ? @js(__('Drag cards to set the order visitors see.')) : @js(__('Clear the filter to reorder.'))">{{ __('Drag cards to set the order visitors see.') }}</p>

            <div class="grid" id="grid" :aria-busy="loading.toString()">
                {{-- Your own projects --}}
                <template x-for="p in items" :key="'p' + p.id">
                    <article class="pc" :class="{ dragging: dragId === p.id }" :draggable="canReorder.toString()"
                             @dragstart="onDragStart($event, p)" @dragover="canReorder && dragId !== null && $event.preventDefault()"
                             @drop.prevent="onDrop(p)" @dragend="dragId = null">
                        <div class="cover" :class="{ has: !!p.cover_image_url }" :style="p.cover_image_url ? { backgroundImage: 'url(' + p.cover_image_url + ')' } : {}">
                            <span class="grip" x-show="canReorder" :title="fmt(strings.dragA, { p: titleOf(p) })" aria-hidden="true"><svg class="icon sm"><use href="#i-grip"/></svg></span>
                            <svg class="icon" x-show="!p.cover_image_url"><use href="#i-image"/></svg>
                            <span class="feat-badge" x-show="p.is_featured" x-cloak x-transition.opacity><svg viewBox="10 80 760 690" aria-hidden="true"><polygon points="314.63 635.59 16.74 747.68 217.29 533.15 86.97 370.29 403.26 356.02 701.63 87.21 636.51 355.76 760 356.91 574.68 534.04 422.33 756.79 314.63 635.59"/></svg>{{ __('Featured') }}</span>
                        </div>
                        <div class="body">
                            <a :href="viewUrl(p)" style="text-decoration:none"><b x-text="titleOf(p)"></b></a>
                            <span class="meta" x-text="[p.client_name, p.role?.en].filter(Boolean).join(' · ')"></span>
                            <template x-if="others(p).length">
                                <span class="owners" :title="fmt(strings.coowned, { names: names(others(p)) })">
                                    <span class="stack" aria-hidden="true">
                                        <template x-for="(f, i) in faces(others(p))" :key="i"><i x-text="f"></i></template>
                                        <template x-if="others(p).length > 2"><i class="more" x-text="'+' + (others(p).length - 2)"></i></template>
                                    </span>
                                    <span x-text="ownersLine(others(p))"></span>
                                </span>
                            </template>
                            <div class="chips">
                                <template x-for="(s, i) in p.skills" :key="s.id"><span class="tag" :class="{ 'tag-ink': i === 0 }" x-text="s.name"></span></template>
                            </div>
                            <div class="foot">
                                <span class="waiting" x-text="waitingCount(p) ? fmt(strings.waiting, { n: waitingCount(p) }) : ''"></span>
                                <span>
                                    {{-- Featured = the profile cover. Never switched off: feature another to change it. --}}
                                    <template x-if="p.can_feature"><button class="iconbtn plain featbtn" type="button" :class="{ on: p.is_featured }" @click="feature(p)"
                                        :aria-pressed="(!!p.is_featured).toString()" :aria-disabled="(!!p.is_featured).toString()" :title="featureTitle(p)" :aria-label="featureTitle(p)"><svg viewBox="10 80 760 690" aria-hidden="true"><polygon points="314.63 635.59 16.74 747.68 217.29 533.15 86.97 370.29 403.26 356.02 701.63 87.21 636.51 355.76 760 356.91 574.68 534.04 422.33 756.79 314.63 635.59"/></svg></button></template>
                                    <a class="iconbtn plain" :href="editUrl(p)" :aria-label="fmt(strings.editA, { p: titleOf(p) })"><svg class="icon"><use href="#i-edit"/></svg></a>
                                    {{-- Only a sole owner may delete; with other owners, you leave instead. --}}
                                    <template x-if="p.has_co_owners"><button class="iconbtn plain del" type="button" @click="leaveOwn(p)" :aria-label="fmt(strings.leaveA, { p: titleOf(p) })" :title="fmt(strings.leaveA, { p: titleOf(p) })"><svg class="icon"><use href="#i-leave"/></svg></button></template>
                                    <template x-if="!p.has_co_owners"><button class="iconbtn plain del" type="button" @click="remove(p)" :aria-label="fmt(strings.delA, { p: titleOf(p) })"><svg class="icon"><use href="#i-trash"/></svg></button></template>
                                </span>
                            </div>
                        </div>
                    </article>
                </template>

                {{-- Work other people credited you on, that you confirmed (ADR-AA) --}}
                <template x-for="c in creditedShown" :key="'c' + c.id">
                    <article class="pc">
                        <div class="cover" :class="{ has: !!c.project?.cover_image_url }" :style="c.project?.cover_image_url ? { backgroundImage: 'url(' + c.project.cover_image_url + ')' } : {}">
                            <svg class="icon" x-show="!c.project?.cover_image_url"><use href="#i-image"/></svg>
                        </div>
                        <div class="body">
                            <a :href="creditUrl(c)" style="text-decoration:none"><b x-text="c.project?.title"></b></a>
                            <span class="meta" x-text="c.role"></span>
                            <span class="owners" :title="fmt(strings.coowned, { names: c.project?.owner?.display_name || '' })">
                                <span class="stack" aria-hidden="true"><i x-text="me"></i><i x-text="initials(c.project?.owner?.display_name || '')"></i></span>
                                <span x-text="ownersLine([c.project?.owner?.display_name || ''])"></span>
                            </span>
                            <div class="chips"><span class="tag tag-ink" x-text="c.skill?.name"></span></div>
                            <div class="foot">
                                <span class="waiting"></span>
                                <span>
                                    <a class="iconbtn plain" :href="editUrl(c.project)" :aria-label="fmt(strings.editA, { p: c.project?.title || '' })"><svg class="icon"><use href="#i-edit"/></svg></a>
                                    <button class="iconbtn plain del" type="button" @click="leaveCredited(c)" :aria-label="fmt(strings.leaveA, { p: c.project?.title || '' })" :title="fmt(strings.leaveA, { p: c.project?.title || '' })"><svg class="icon"><use href="#i-leave"/></svg></button>
                                </span>
                            </div>
                        </div>
                    </article>
                </template>

                <a class="newcard" href="{{ route('talent.projects.new') }}" style="text-decoration:none"><svg class="icon"><use href="#i-plus"/></svg>{{ __('Add a project') }}<small>{{ __('Title, skills and your best frames') }}</small></a>
            </div>

            <div class="pager" x-show="pages > 1" x-cloak>
                <button type="button" class="btn btn-2" @click="load(page - 1)" :disabled="page <= 1">{{ __('Previous') }}</button>
                <span class="meta" x-text="page + ' / ' + pages"></span>
                <button type="button" class="btn btn-2" @click="load(page + 1)" :disabled="page >= pages">{{ __('Next') }}</button>
            </div>
        @endif
    </main>
</x-fama.layout>
