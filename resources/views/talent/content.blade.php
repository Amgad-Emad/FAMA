<x-talent-layout :title="__('Profile content')">
    {{-- Section switcher — the profile's own sections (ADR-Q/ADR-R): the
         profile-wide scope plus one per skill. Picking one opens ALL of that
         section's content blocks, stacked down the page in block order. --}}
    <nav class="mb-8" aria-label="{{ __('Profile sections') }}">
        <p class="mb-3 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Sections') }} · {{ __('Pick one to edit its blocks') }}</p>
        <div class="flex flex-wrap gap-2">
            @foreach ($sections as $section)
                @php $isActive = $section['key'] === $active; @endphp
                <a href="{{ route('talent.content', ['section' => $section['key']]) }}"
                   @if ($isActive) aria-current="page" @endif
                   @class([
                       'group inline-flex items-center gap-2.5 rounded-pill px-5 py-2.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-bg',
                       'bg-accent text-on-accent shadow-sm' => $isActive,
                       'border border-line bg-elevated text-ink hover:border-line-strong hover:shadow-sm' => ! $isActive,
                   ])>
                    @if ($section['icon'])
                        <x-skill-icon :icon="$section['icon']" class="h-4 w-4 shrink-0" />
                    @else
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 2.5 15.3 0 18-2.5-2.7-2.5-15.3 0-18z"/></svg>
                    @endif
                    <span>{{ $section['label'] }}</span>
                    @if ($section['is_primary'])
                        <span @class([
                            'rounded-pill px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-wider',
                            'bg-on-accent/20 text-on-accent' => $isActive,
                            'bg-accent-weak text-accent-ink' => ! $isActive,
                        ])>{{ __('Primary') }}</span>
                    @endif
                    <span @class([
                        'grid h-5 min-w-5 place-items-center rounded-pill px-1.5 font-mono text-[10px]',
                        'bg-on-accent/20 text-on-accent' => $isActive,
                        'bg-surface text-muted' => ! $isActive,
                    ])>{{ $section['count'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>

    {{-- Every block of the active section, stacked. --}}
    <div class="space-y-8">
        @forelse ($panels as $panel)
            <section id="block-{{ $panel['type'] }}" class="rounded-xl border border-line bg-surface p-5 sm:p-6">
                <header class="mb-5 flex flex-wrap items-center gap-3 border-b border-line pb-4">
                    <div class="min-w-0 flex-1">
                        <div class="font-mono text-[10px] uppercase tracking-wider text-subtle">{{ $activeLabel }}</div>
                        <h3 class="truncate text-lg font-semibold text-ink">{{ $panel['title'] }}</h3>
                    </div>
                    @unless ($panel['is_visible'])
                        <span class="rounded-pill border border-line px-2.5 py-1 font-mono text-[10px] uppercase tracking-wider text-muted">{{ __('Hidden on your profile') }}</span>
                    @endunless
                </header>

                @if ($panel['kind'] === 'reviews')
                    <p class="mb-4 text-sm text-muted">{{ __('Reviews are written by clients and moderated by Fama — you can read and filter them here.') }}</p>
                    @include('talent.partials.reviews-list')
                @elseif ($panel['kind'] === 'link')
                    {{-- A block authored on its own page (projects — ADR-X): a signpost, not a second editor. --}}
                    <div class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-line bg-elevated p-5">
                        <p class="max-w-xl text-sm text-muted">{{ $panel['blurb'] }}</p>
                        <x-ui.button variant="accent" :href="$panel['url']">
                            {{ $panel['cta'] }}
                            <svg class="h-4 w-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                        </x-ui.button>
                    </div>
                @else
                    @include('talent.partials.content-editor', ['type' => $panel['type'], 'config' => $panel['config']])
                @endif
            </section>
        @empty
            <p class="rounded-xl border border-line bg-surface p-8 text-center text-sm text-subtle">
                {{ __('This section has no content blocks yet.') }}
                <a href="{{ route('talent.profile.edit') }}" class="text-accent hover:underline">{{ __('Manage your blocks') }}</a>
            </p>
        @endforelse
    </div>
</x-talent-layout>
