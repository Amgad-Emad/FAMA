<x-admin-layout :title="__('Admin dashboard')">
    <div class="space-y-10">
        <p class="text-sm text-muted">{{ __('Platform governance overview.') }}</p>

        {{-- Moderation queues --}}
        @can('moderate-content')
            <section>
                <h2 class="mb-3 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Moderation queues') }}</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ([
                        ['label' => __('Pending talent profiles'), 'value' => $stats['moderation']['pending_talents'], 'queue' => 'talents'],
                        ['label' => __('Pending talent reviews'), 'value' => $stats['moderation']['pending_reviews'], 'queue' => 'reviews'],
                        ['label' => __('Pending brand reviews'), 'value' => $stats['moderation']['pending_brand_reviews'], 'queue' => 'brand-reviews'],
                        ['label' => __('Brands awaiting verification'), 'value' => $stats['moderation']['unverified_brands'], 'queue' => 'brands'],
                    ] as $card)
                        <a href="{{ route('admin.moderation.index', ['queue' => $card['queue']]) }}" class="rounded-xl border border-line bg-surface p-4 transition hover:border-line-strong">
                            <div class="font-display text-3xl leading-none {{ $card['value'] ? 'text-ink' : 'text-subtle' }}">{{ $card['value'] }}</div>
                            <div class="mt-1 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ $card['label'] }}</div>
                            @if (! $card['value'])
                                <div class="mt-1 text-xs text-muted">{{ __('All clear.') }}</div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endcan

        {{-- Contracts + Projects --}}
        <div class="grid gap-4 lg:grid-cols-2">
            @can('intervene-contracts')
                <section>
                    <h2 class="mb-3 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Contracts') }}</h2>
                    <a href="{{ route('admin.contracts') }}" class="block rounded-xl border border-line bg-surface p-4 transition hover:border-line-strong">
                        <div class="flex items-baseline gap-3">
                            <span class="font-display text-3xl leading-none text-ink">{{ $stats['contracts']['active'] }}</span>
                            <span class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Active contracts') }}</span>
                        </div>
                        @if ($stats['contracts']['active'])
                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-muted">
                                <span class="rounded-pill bg-elevated px-2 py-0.5">{{ __('Awaiting brand') }}: {{ $stats['contracts']['awaiting_brand'] }}</span>
                                <span class="rounded-pill bg-elevated px-2 py-0.5">{{ __('Awaiting talent') }}: {{ $stats['contracts']['awaiting_talent'] }}</span>
                                <span class="rounded-pill bg-elevated px-2 py-0.5">{{ __('Awaiting admin') }}: {{ $stats['contracts']['awaiting_admin'] }}</span>
                            </div>
                        @else
                            <div class="mt-3 text-xs text-muted">{{ __('No contracts in flight.') }}</div>
                        @endif
                    </a>
                </section>
            @endcan

            @can('moderate-content')
                <section>
                    <h2 class="mb-3 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Projects') }}</h2>
                    <a href="{{ route('admin.moderation.index', ['queue' => 'projects']) }}" class="block rounded-xl border border-line bg-surface p-4 transition hover:border-line-strong">
                        <div class="grid grid-cols-3 gap-3">
                            @foreach (['open' => __('Open'), 'in_progress' => __('In progress'), 'completed' => __('Completed')] as $key => $label)
                                <div>
                                    <div class="font-display text-3xl leading-none {{ $stats['projects'][$key] ? 'text-ink' : 'text-subtle' }}">{{ $stats['projects'][$key] }}</div>
                                    <div class="mt-1 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ $label }}</div>
                                </div>
                            @endforeach
                        </div>
                        @if (! array_sum($stats['projects']))
                            <div class="mt-3 text-xs text-muted">{{ __('No projects yet.') }}</div>
                        @endif
                    </a>
                </section>
            @endcan
        </div>

        {{-- Governance quick links --}}
        @php
            $governance = array_filter([
                ['route' => 'admin.flows', 'label' => __('Contract flows'), 'hint' => __('Author the step sequences contracts run on.'), 'can' => 'manage-flows'],
                ['route' => 'admin.skills', 'label' => __('Skills templates'), 'hint' => __('Per-skill preselected blocks and their order.'), 'can' => 'manage-flows'],
                ['route' => 'admin.blocks', 'label' => __('Block catalog'), 'hint' => __('Which blocks exist and who may use them.'), 'can' => 'manage-blocks'],
                ['route' => 'admin.settings', 'label' => __('Settings'), 'hint' => __('Platform globals and feature flags.'), 'can' => 'manage-settings'],
                ['route' => 'admin.users', 'label' => __('Accounts'), 'hint' => __('Create admins, brands, talents; manage staff roles.'), 'can' => 'manage-users'],
            ], fn ($link) => auth('admin')->user()?->can($link['can']));
        @endphp
        @if ($governance)
            <section>
                <h2 class="mb-3 font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Governance') }}</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ($governance as $link)
                        <a href="{{ route($link['route']) }}" class="rounded-xl border border-line bg-surface p-4 transition hover:border-line-strong">
                            <div class="text-sm font-medium text-ink">{{ $link['label'] }}</div>
                            <div class="mt-1 text-xs text-muted">{{ $link['hint'] }}</div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Recent activity --}}
        @can('manage-settings')
            <section>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-mono text-[11px] uppercase tracking-wider text-subtle">{{ __('Recent activity') }}</h2>
                    <a href="{{ route('admin.activity') }}" class="text-xs text-accent-ink hover:underline">{{ __('View activity log') }}</a>
                </div>
                <div class="space-y-2">
                    @forelse ($stats['recent_activity'] as $entry)
                        <div class="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-surface px-4 py-2.5 text-sm">
                            <span class="rounded-pill bg-elevated px-2 py-0.5 font-mono text-[10px] text-muted">{{ $entry->log_name }}</span>
                            <span class="text-ink">{{ $entry->description }}</span>
                            @if ($entry->causer?->name)
                                <span class="text-xs text-muted">{{ __('by :name', ['name' => $entry->causer->name]) }}</span>
                            @endif
                            <span class="ms-auto text-xs text-subtle">{{ $entry->created_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-line py-8 text-center text-sm text-muted">{{ __('No activity yet.') }}</p>
                    @endforelse
                </div>
            </section>
        @endcan
    </div>
</x-admin-layout>
