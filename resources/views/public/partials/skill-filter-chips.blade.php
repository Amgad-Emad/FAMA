{{--
    Skills filter chips (talent-spec, discovery). The primary Skills selector:
    an "All" reset sitting BESIDE the scope groups (Modeling / Crew / Creative),
    each group a divider-separated column of multi-select chips. Bound to the shared
    `talentSearch` Alpine scope.

    $nowrap (default false): keep everything on ONE horizontal line (scrolls if it
    overflows) — used in the wide sticky bar. When false the groups wrap onto new
    lines — used in the narrow modal.

    $staged (default false): bind to the modal's DRAFT state (`draft.type` /
    `toggleDraftType` / …) so nothing applies until "Apply filters". When false the
    chips bind to the LIVE `filters.type` and apply immediately (the sticky bar).
--}}
@php
    $nowrap = $nowrap ?? false;
    $staged = $staged ?? false;
    $typesExpr = $staged ? 'draft.type' : 'filters.type';
    $countExpr = $staged ? 'draftSelectedCount' : 'selectedSkillCount';
    $toggleFn = $staged ? 'toggleDraftType' : 'toggleType';
    $clearFn = $staged ? 'clearDraftSkills' : 'clearSkills';
@endphp

<div @class([
        'flex items-start',
        // One line + horizontal scroll (hidden scrollbar), chips never compress.
        'gap-x-4 overflow-x-auto [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden' => $nowrap,
        // Wrap onto new lines (narrow contexts).
        'flex-wrap gap-x-6 gap-y-4' => ! $nowrap,
    ])>
    {{-- "All" reset — a neutral action (NOT a default selection): it never shows a
         filled/selected state, it just clears any chosen skills. Disabled when none
         are selected. --}}
    <div class="shrink-0">
        <div class="mb-2 font-mono text-[10px] uppercase tracking-wider invisible" aria-hidden="true">&nbsp;</div>
        <button type="button" @click="{{ $clearFn }}()" :disabled="{{ $countExpr }} === 0"
                class="inline-flex items-center gap-1.5 rounded-pill border border-line-strong px-3 py-1.5 text-sm font-medium text-muted transition hover:border-accent hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-surface disabled:cursor-default disabled:opacity-45 disabled:hover:border-line-strong disabled:hover:text-muted">
            {{ __('All') }}
        </button>
    </div>

    {{-- Scope groups, side by side, each preceded by a divider. --}}
    <template x-for="group in skillGroups" :key="group.category">
        <div role="group" :aria-label="group.label" class="shrink-0 border-s border-line ps-4">
            {{-- Keep the label's space even when it just duplicates its single chip
                 (e.g. Modeling), so every group's chips stay aligned. --}}
            <div class="mb-2 font-mono text-[10px] uppercase tracking-wider text-subtle"
                 :class="{ 'invisible': group.types.length === 1 && t(group.types[0].name) === group.label }"
                 x-text="group.label"></div>
            <div class="flex flex-wrap gap-2" :class="{ 'flex-nowrap': {{ $nowrap ? 'true' : 'false' }} }">
                <template x-for="type in group.types" :key="type.id">
                    <button type="button" @click="{{ $toggleFn }}(type.slug)" :aria-pressed="{{ $typesExpr }}.includes(type.slug)"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-pill border px-3 py-1.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-surface"
                            :class="{{ $typesExpr }}.includes(type.slug)
                                ? 'border-transparent bg-accent text-on-accent shadow-e1'
                                : 'border-line-strong text-muted hover:border-accent hover:text-ink'">
                        <span class="h-4 w-4 shrink-0" x-html="iconFor(type)"></span>
                        <span x-text="t(type.name)"></span>
                        <svg x-show="{{ $typesExpr }}.includes(type.slug)" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </button>
                </template>
            </div>
        </div>
    </template>
</div>
