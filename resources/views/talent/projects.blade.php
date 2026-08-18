<x-talent-layout :title="__('Projects')">
    @php
        // Runtime strings for the Alpine component — this page uses design-head,
        // so window.famaLang does not exist here (a dashboard rule).
        $strings = [
            'needTitle' => __('Give the project a title to save it.'),
            'needSkill' => __('Pick at least one skill to save it.'),
            'discardTitle' => __('Discard this project?'),
            'discardMessage' => __('Your unsaved changes will be lost.'),
            'discardConfirm' => __('Discard'),
            'deleteTitle' => __('Remove this project?'),
            'deleteMessage' => __('":title" and its cover will be permanently removed from your profile.'),
            'deleteConfirm' => __('Remove'),
        ];
    @endphp

    <div x-data="talentProjects({ skills: @js($skills), total: @js($total), strings: @js($strings) })">

        {{-- Page header — the promise of the page, then the one primary action. --}}
        <div class="mb-8 flex flex-wrap items-end justify-between gap-5">
            <div class="min-w-0">
                <x-ui.eyebrow>{{ __('Selected work') }}</x-ui.eyebrow>
                <h2 class="mt-1.5 font-display text-3xl leading-tight text-ink">{{ __('Projects') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
                    {{ __('Every project carries at least one skill — that is what decides which tab it appears in on your public profile. Drag the cards to set the order visitors see.') }}
                </p>
            </div>

            @if (count($skills))
                <x-ui.button variant="accent" size="lg" x-on:click="openComposer()" class="shrink-0">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('New project') }}
                </x-ui.button>
            @endif
        </div>

        @if (! count($skills))
            {{-- The skill rule made concrete: no skills, no projects. --}}
            <x-ui.card class="text-center">
                <div class="mx-auto max-w-md">
                    <div class="mx-auto grid h-12 w-12 place-items-center rounded-pill bg-accent-weak text-accent-ink">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2M3 7h18v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7zm0 5h18"/></svg>
                    </div>
                    <h3 class="mt-4 font-display text-xl text-ink">{{ __('Add a skill first') }}</h3>
                    <p class="mt-2 text-sm text-muted">{{ __('A project has to say which discipline it belongs to, so you need at least one skill on your profile before you can add work.') }}</p>
                    <x-ui.button variant="accent" :href="route('talent.profile.edit')" class="mt-5">{{ __('Manage your skills') }}</x-ui.button>
                </div>
            </x-ui.card>
        @else

            {{-- Skill filter bar — the same order as the public tab bar. --}}
            <nav style="z-index: var(--z-sticky);" class="sticky top-16 -mx-4 mb-6 border-b border-line bg-bg/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6"
                 aria-label="{{ __('Filter projects by skill') }}">
                <div class="flex items-center gap-3 overflow-x-auto pb-0.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <span class="shrink-0 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Skill') }}</span>

                    <button type="button" x-on:click="setFilter('')" :aria-pressed="filter === '' ? 'true' : 'false'"
                            class="inline-flex shrink-0 items-center gap-2 rounded-pill px-4 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-bg"
                            :class="filter === '' ? 'bg-accent text-on-accent shadow-sm' : 'border border-line bg-elevated text-ink hover:border-line-strong'">
                        <span>{{ __('All work') }}</span>
                        <span class="grid h-5 min-w-5 place-items-center rounded-pill px-1.5 font-mono text-[10px]"
                              :class="filter === '' ? 'bg-on-accent/20 text-on-accent' : 'bg-surface text-muted'"
                              x-text="countFor('')">{{ $total }}</span>
                    </button>

                    @foreach ($skills as $skill)
                        <button type="button" x-on:click="setFilter('{{ $skill['id'] }}')"
                                :aria-pressed="filter === '{{ $skill['id'] }}' ? 'true' : 'false'"
                                class="inline-flex shrink-0 items-center gap-2 rounded-pill px-4 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-bg"
                                :class="filter === '{{ $skill['id'] }}' ? 'bg-accent text-on-accent shadow-sm' : 'border border-line bg-elevated text-ink hover:border-line-strong'">
                            <x-skill-icon :icon="$skill['icon']" class="h-4 w-4 shrink-0" />
                            <span>{{ $skill['name'] }}</span>
                            @if ($skill['is_primary'])
                                <span class="rounded-pill px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-wider"
                                      :class="filter === '{{ $skill['id'] }}' ? 'bg-on-accent/20 text-on-accent' : 'bg-accent-weak text-accent-ink'">{{ __('Primary') }}</span>
                            @endif
                            <span class="grid h-5 min-w-5 place-items-center rounded-pill px-1.5 font-mono text-[10px]"
                                  :class="filter === '{{ $skill['id'] }}' ? 'bg-on-accent/20 text-on-accent' : 'bg-surface text-muted'"
                                  x-text="countFor('{{ $skill['id'] }}')">0</span>
                        </button>
                    @endforeach

                    {{-- Only offered when work is actually parked there (a removed skill leaves projects behind). --}}
                    <button type="button" x-show="countFor('none') > 0" x-cloak x-on:click="setFilter('none')"
                            :aria-pressed="filter === 'none' ? 'true' : 'false'"
                            class="inline-flex shrink-0 items-center gap-2 rounded-pill px-4 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-bg"
                            :class="filter === 'none' ? 'bg-accent text-on-accent shadow-sm' : 'border border-dashed border-line-strong bg-elevated text-muted hover:text-ink'">
                        <span>{{ __('No skill yet') }}</span>
                        <span class="grid h-5 min-w-5 place-items-center rounded-pill bg-surface px-1.5 font-mono text-[10px] text-muted" x-text="countFor('none')"></span>
                    </button>
                </div>
            </nav>

            {{-- Loading skeletons --}}
            <div x-show="loading" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @for ($i = 0; $i < 3; $i++)
                    <div class="overflow-hidden rounded-lg border border-line bg-surface">
                        <div class="aspect-video animate-pulse bg-elevated"></div>
                        <div class="space-y-3 p-5">
                            <div class="h-4 w-2/3 animate-pulse rounded bg-elevated"></div>
                            <div class="h-3 w-1/3 animate-pulse rounded bg-elevated"></div>
                            <div class="h-6 w-1/2 animate-pulse rounded-pill bg-elevated"></div>
                        </div>
                    </div>
                @endfor
            </div>

            {{-- The grid --}}
            <div x-show="!loading" x-cloak class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <template x-for="project in items" :key="project.id">
                    <article :draggable="canReorder"
                             x-on:dragstart="onDragStart($event, project)"
                             x-on:dragover.prevent="dragOverId = project.id"
                             x-on:dragleave="dragOverId === project.id && (dragOverId = null)"
                             x-on:drop.prevent="onDrop(project)"
                             x-on:dragend="dragId = null; dragOverId = null"
                             class="group relative flex flex-col overflow-hidden rounded-lg border bg-surface shadow-e1 transition-all duration-150"
                             :class="[
                                 dragOverId === project.id ? 'border-accent ring-2 ring-accent' : 'border-line',
                                 dragId === project.id ? 'scale-[0.98] opacity-50' : 'hover:shadow-e2',
                                 canReorder ? 'cursor-grab active:cursor-grabbing' : '',
                             ]">

                        {{-- Cover --}}
                        <button type="button" x-on:click="openComposer(project)"
                                class="relative block aspect-video w-full overflow-hidden text-start focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-inset"
                                :aria-label="'{{ __('Edit') }} ' + (project.label || '')">
                            <template x-if="project.cover_image_url">
                                <img :src="project.cover_image_url" :alt="project.label"
                                     class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            </template>
                            <template x-if="!project.cover_image_url">
                                <span class="flex h-full w-full items-center justify-center" :style="`background:${tint(project)}`">
                                    <svg class="h-8 w-8 text-ink/25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 6h16a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
                                </span>
                            </template>
                            <span x-show="project.year" x-cloak
                                  class="absolute bottom-2 end-2 rounded-pill bg-ink/70 px-2 py-0.5 font-mono text-[10px] tracking-wider text-bg backdrop-blur"
                                  x-text="project.year"></span>
                        </button>

                        {{-- Body --}}
                        <div class="flex flex-1 flex-col gap-3 p-5">
                            <div>
                                <h3 class="font-display text-lg leading-snug text-ink" x-text="project.label || '{{ __('Untitled project') }}'"></h3>
                                <p class="mt-1 font-mono text-[11px] uppercase tracking-wider text-subtle"
                                   x-text="[project.client_name, t(project.role)].filter(Boolean).join(' · ') || '—'"></p>
                            </div>

                            {{-- Skill tags: the first is the home tab on the public profile. --}}
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="(skill, index) in project.skills" :key="skill.id">
                                    <span class="inline-flex items-center gap-1.5 rounded-pill border px-2.5 py-1 text-[11px] font-medium"
                                          :class="index === 0 ? 'border-transparent bg-accent-weak text-accent-ink' : 'border-line bg-surface text-muted'"
                                          :title="index === 0 ? '{{ __('Home tab on your public profile') }}' : ''">
                                        <span class="h-3.5 w-3.5" x-html="iconFor(skill)"></span>
                                        <span x-text="skill.name"></span>
                                    </span>
                                </template>
                                <span x-show="!project.skills.length" x-cloak
                                      class="inline-flex items-center rounded-pill border border-dashed border-warn px-2.5 py-1 text-[11px] font-medium text-warn">
                                    {{ __('No skill — pick one') }}
                                </span>
                            </div>

                            {{-- Actions --}}
                            <div class="mt-auto flex items-center gap-2 border-t border-line pt-3">
                                <button type="button" x-on:click="openComposer(project)"
                                        class="rounded-pill border border-line-strong px-3 py-1.5 text-xs font-medium text-ink transition hover:bg-elevated focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                                    {{ __('Edit') }}
                                </button>
                                <a x-show="project.url" x-cloak :href="project.url" target="_blank" rel="noopener noreferrer"
                                   class="rounded-pill border border-line px-3 py-1.5 text-xs font-medium text-muted transition hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                                    {{ __('Visit') }} ↗
                                </a>
                                <button type="button" x-on:click="remove(project)"
                                        class="ms-auto rounded-pill px-3 py-1.5 text-xs font-medium text-subtle transition hover:text-danger focus:outline-none focus-visible:ring-2 focus-visible:ring-danger">
                                    {{ __('Remove') }}
                                </button>
                            </div>
                        </div>
                    </article>
                </template>
            </div>

            {{-- Empty states --}}
            <div x-show="!loading && items.length === 0" x-cloak>
                <x-ui.card class="text-center">
                    <div class="mx-auto max-w-md">
                        <h3 class="font-display text-xl text-ink" x-text="filter === '' ? '{{ __('No projects yet') }}' : '{{ __('Nothing under this filter') }}'"></h3>
                        <p class="mt-2 text-sm text-muted" x-text="filter === '' ? '{{ __('Add your first piece of work — a cover, who it was for, and the skills behind it.') }}' : '{{ __('This skill has no work on it yet.') }}'"></p>
                        <div class="mt-5 flex items-center justify-center gap-3">
                            <x-ui.button variant="accent" x-on:click="openComposer()">{{ __('New project') }}</x-ui.button>
                            <button type="button" x-show="filter !== ''" x-cloak x-on:click="setFilter('')"
                                    class="text-sm text-accent-ink underline">{{ __('Show all work') }}</button>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            {{-- Pagination --}}
            <div x-show="!loading && pages > 1" x-cloak class="mt-8 flex items-center justify-center gap-2">
                <button type="button" x-on:click="load(page - 1)" :disabled="page <= 1"
                        class="rounded-pill border border-line px-4 py-2 text-sm text-ink transition hover:bg-elevated disabled:opacity-40">{{ __('Previous') }}</button>
                <span class="font-mono text-xs text-subtle" x-text="`${page} / ${pages}`"></span>
                <button type="button" x-on:click="load(page + 1)" :disabled="page >= pages"
                        class="rounded-pill border border-line px-4 py-2 text-sm text-ink transition hover:bg-elevated disabled:opacity-40">{{ __('Next') }}</button>
            </div>

            <p x-show="!loading && items.length > 1 && !canReorder" x-cloak class="mt-6 text-center text-xs text-subtle">
                {{ __('Show all work on one page to drag the order.') }}
            </p>

            @include('talent.partials.project-composer')
        @endif
    </div>
</x-talent-layout>
